<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Support\InvoicePdfData;
use App\Support\ModuleAccess;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BankAccountsTest extends TestCase
{
    use RefreshDatabase;

    private Organization $firm;

    private User $partner;

    protected function setUp(): void
    {
        parent::setUp();
        OrganizationContext::clear();

        $this->firm = Organization::create(['name' => 'Bank Firm', 'slug' => 'bankfirm', 'plan' => 'professional', 'seat_limit' => 5]);
        $this->partner = User::factory()->create([
            'organization_id' => $this->firm->id,
            'role' => 'partner',
            'module_access' => ModuleAccess::defaultsForRole('partner'),
            'mobile' => '919876543210',
        ]);
    }

    protected function tearDown(): void
    {
        OrganizationContext::clear();
        parent::tearDown();
    }

    private function account(array $attrs = [], ?Organization $org = null): BankAccount
    {
        $account = new BankAccount(array_merge([
            'label' => 'Current',
            'bank_name' => 'HDFC Bank',
            'account_name' => 'Bank Firm',
            'account_number' => '50100012345678',
            'ifsc' => 'HDFC0001234',
        ], $attrs));
        $account->forceFill(['organization_id' => ($org ?? $this->firm)->id])->save();

        return $account;
    }

    private function saveSettings(array $rows, ?string $default = null)
    {
        return $this->actingAs($this->partner)->put(route('settings.update'), [
            'name' => $this->partner->name,
            'email' => $this->partner->email,
            'mobile' => $this->partner->mobile,
            'theme' => 'modern',
            'bank_accounts_submitted' => '1',
            'bank_accounts' => $rows,
            'bank_default' => $default,
        ]);
    }

    private function invoice(array $attrs = []): Invoice
    {
        $client = Client::factory()->create();
        $client->forceFill(['organization_id' => $this->firm->id])->save();

        $invoice = new Invoice(array_merge([
            'client_id' => $client->id,
            'invoice_number' => 'BANK/'.fake()->unique()->numerify('###'),
            'date' => now(),
            'due_date' => now()->addDays(15),
            'status' => Invoice::STATUS_DRAFT,
            'subtotal' => 1000,
            'tax' => 180,
            'total_amount' => 1180,
        ], $attrs));
        $invoice->forceFill(['organization_id' => $this->firm->id])->save();

        return $invoice;
    }

    public function test_settings_page_lists_bank_accounts_with_add_button(): void
    {
        $this->account(['label' => 'Collections account']);

        $this->actingAs($this->partner)->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Bank accounts')
            ->assertSee('Add bank account')
            ->assertSee('Collections account')
            ->assertSee('50100012345678');
    }

    public function test_partner_can_add_several_accounts_and_pick_default(): void
    {
        $this->saveSettings([
            'new-1' => ['label' => 'Current', 'bank_name' => 'HDFC Bank', 'account_name' => 'Bank Firm', 'account_number' => '5010 0012 3456 78', 'ifsc' => 'hdfc0001234', 'upi_id' => 'firm@hdfcbank'],
            'new-2' => ['label' => 'Savings', 'bank_name' => 'SBI', 'account_name' => 'Bank Firm', 'account_number' => '30987654321', 'ifsc' => 'SBIN0004567', 'branch' => 'Navrangpura'],
            'new-3' => ['label' => '', 'bank_name' => '', 'account_number' => ''], // untouched blank row
        ], 'new-2')->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');

        $accounts = BankAccount::withoutGlobalScopes()->where('organization_id', $this->firm->id)->orderBy('sort_order')->get();
        $this->assertCount(2, $accounts);
        $this->assertSame(['Current', 'Savings'], $accounts->pluck('label')->all());
        $this->assertSame('50100012345678', $accounts[0]->account_number, 'spaces stripped');
        $this->assertSame('HDFC0001234', $accounts[0]->ifsc, 'IFSC upper-cased');
        $this->assertSame([false, true], $accounts->pluck('is_default')->all());
    }

    public function test_editing_updates_and_removing_archives(): void
    {
        $keep = $this->account(['label' => 'Old name', 'is_default' => true]);
        $drop = $this->account(['label' => 'Closed account', 'account_number' => '111122223333']);

        $this->saveSettings([
            'id-'.$keep->id => ['id' => $keep->id, 'label' => 'Renamed', 'bank_name' => 'HDFC Bank', 'account_number' => '50100012345678', 'ifsc' => 'HDFC0001234'],
        ], 'id-'.$keep->id)->assertSessionHasNoErrors();

        $this->assertSame('Renamed', $keep->fresh()->label);
        $this->assertTrue($keep->fresh()->is_default);
        $this->assertSoftDeleted('bank_accounts', ['id' => $drop->id]);
    }

    public function test_first_account_becomes_default_when_none_chosen(): void
    {
        $this->saveSettings([
            'new-a' => ['account_number' => '123456789012', 'ifsc' => 'ICIC0000001'],
            'new-b' => ['upi_id' => 'firm@okaxis'],
        ], null)->assertSessionHasNoErrors();

        $defaults = BankAccount::withoutGlobalScopes()->where('organization_id', $this->firm->id)->where('is_default', true)->pluck('account_number');
        $this->assertSame(['123456789012'], $defaults->all());
    }

    public function test_invalid_details_are_rejected_and_nothing_changes(): void
    {
        $existing = $this->account();

        $this->saveSettings([
            'new-1' => ['bank_name' => 'HDFC Bank', 'account_number' => '50100099999999', 'ifsc' => 'HDFC1234'],
            'new-2' => ['bank_name' => 'No number or UPI'],
        ])->assertSessionHasErrors(['bank_accounts.new-1.ifsc', 'bank_accounts.new-2.account_number']);

        $this->assertSame([$existing->id], BankAccount::withoutGlobalScopes()->pluck('id')->all());
    }

    public function test_cannot_edit_or_delete_another_firms_account(): void
    {
        $other = Organization::create(['name' => 'Other', 'slug' => 'other', 'plan' => 'starter', 'seat_limit' => 5]);
        $theirs = $this->account(['label' => 'Their account', 'account_number' => '999988887777'], $other);

        $this->saveSettings([
            'id-'.$theirs->id => ['id' => $theirs->id, 'label' => 'Hijacked', 'account_number' => '000011112222'],
        ])->assertSessionHasNoErrors();

        $fresh = BankAccount::withoutGlobalScopes()->find($theirs->id);
        $this->assertSame('Their account', $fresh->label);
        $this->assertSame('999988887777', $fresh->account_number);
        $this->assertNull($fresh->deleted_at);
        $this->assertSame(0, BankAccount::withoutGlobalScopes()->where('organization_id', $this->firm->id)->count());
    }

    public function test_invoice_form_offers_accounts_and_saves_choice(): void
    {
        $default = $this->account(['label' => 'Main', 'is_default' => true]);
        $second = $this->account(['label' => 'GST collections', 'bank_name' => 'Axis Bank', 'account_number' => '920020012345678', 'ifsc' => 'UTIB0000123']);

        $this->actingAs($this->partner)->get(route('invoices.create'))
            ->assertOk()
            ->assertSee('Bank account on invoice')
            ->assertSee('GST collections — Axis Bank ••5678');

        $client = Client::factory()->create();
        $client->forceFill(['organization_id' => $this->firm->id])->save();

        $this->actingAs($this->partner)->post(route('invoices.store'), [
            'client_id' => $client->id,
            'date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'invoice_number' => 'INV-BANK-1',
            'bank_account_id' => $second->id,
            'items' => [['description' => 'Audit', 'quantity' => 1, 'rate' => 5000]],
        ])->assertRedirect();

        $this->assertSame($second->id, (int) Invoice::withoutGlobalScopes()->where('invoice_number', 'INV-BANK-1')->value('bank_account_id'));
    }

    public function test_invoice_cannot_point_at_another_firms_account(): void
    {
        $other = Organization::create(['name' => 'Other', 'slug' => 'other2', 'plan' => 'starter', 'seat_limit' => 5]);
        $theirs = $this->account([], $other);
        $client = Client::factory()->create();
        $client->forceFill(['organization_id' => $this->firm->id])->save();

        $this->actingAs($this->partner)->post(route('invoices.store'), [
            'client_id' => $client->id,
            'date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'invoice_number' => 'INV-BANK-2',
            'bank_account_id' => $theirs->id,
            'items' => [['description' => 'Audit', 'quantity' => 1, 'rate' => 5000]],
        ])->assertRedirect();

        $this->assertNull(Invoice::withoutGlobalScopes()->where('invoice_number', 'INV-BANK-2')->value('bank_account_id'));
    }

    public function test_pdf_prints_chosen_account_else_default(): void
    {
        $this->account(['label' => 'Main', 'bank_name' => 'HDFC Bank', 'is_default' => true]);
        $second = $this->account(['bank_name' => 'Axis Bank', 'account_number' => '920020012345678', 'ifsc' => 'UTIB0000123', 'branch' => 'CG Road']);

        $chosen = InvoicePdfData::for($this->invoice(['bank_account_id' => $second->id]))['firm'];
        $this->assertSame('Axis Bank', $chosen['bank_name']);
        $this->assertSame('920020012345678', $chosen['bank_account_number']);
        $this->assertSame('CG Road', $chosen['bank_branch']);

        $fallback = InvoicePdfData::for($this->invoice())['firm'];
        $this->assertSame('HDFC Bank', $fallback['bank_name']);
    }

    public function test_removed_account_still_prints_on_invoices_that_used_it(): void
    {
        $this->account(['bank_name' => 'HDFC Bank', 'is_default' => true]);
        $old = $this->account(['bank_name' => 'Closed Bank', 'account_number' => '777766665555']);
        $invoice = $this->invoice(['bank_account_id' => $old->id]);
        $old->delete();

        $firm = InvoicePdfData::for($invoice->fresh())['firm'];
        $this->assertSame('Closed Bank', $firm['bank_name']);
        $this->assertSame('777766665555', $firm['bank_account_number']);
    }

    public function test_existing_single_bank_settings_become_first_account(): void
    {
        $fresh = Organization::create(['name' => 'Legacy', 'slug' => 'legacy', 'plan' => 'starter', 'seat_limit' => 5]);
        BankAccount::withoutGlobalScopes()->forceDelete();
        foreach (['bank_name' => 'Kotak', 'bank_account_number' => '4411223344', 'bank_ifsc' => 'KKBK0000811', 'bank_upi' => 'legacy@kotak'] as $key => $value) {
            DB::table('settings')->insert(['organization_id' => $fresh->id, 'key' => $key, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
        }

        $migration = require database_path('migrations/2026_11_07_120000_create_bank_accounts_table.php');
        (new \ReflectionMethod($migration, 'copyLegacyBankSettings'))->invoke($migration);

        $copied = BankAccount::withoutGlobalScopes()->where('organization_id', $fresh->id)->get();
        $this->assertCount(1, $copied);
        $this->assertTrue($copied[0]->is_default);
        $this->assertSame('Kotak', $copied[0]->bank_name);
        $this->assertSame('4411223344', $copied[0]->account_number);
        $this->assertSame('legacy@kotak', $copied[0]->upi_id);
        // Firms with no bank settings get nothing.
        $this->assertSame(0, BankAccount::withoutGlobalScopes()->where('organization_id', $this->firm->id)->count());
    }
}
