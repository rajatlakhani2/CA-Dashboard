<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PersonalRenewal;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Support\ModuleAccess;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Regression tests for the bugs found in the November 2026 site audit. */
class AuditFixesTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private User $partner;

    private User $manager;

    private User $staff;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->org = Organization::create(['name' => 'Audit Firm', 'slug' => 'auditfirm', 'plan' => 'professional', 'seat_limit' => 10, 'is_active' => true]);
        OrganizationContext::set($this->org->id);
        $this->partner = $this->member('partner');
        $this->manager = $this->member('manager');
        $this->staff = $this->member('staff');
        $this->client = Client::create([
            'client_code' => 'AUD-1', 'name' => 'Audit Client', 'pan' => 'AUDIT1234A', 'status' => Client::STATUS_ACTIVE,
            'category' => 'A', 'approval_status' => Client::APPROVAL_APPROVED, 'organization_id' => $this->org->id,
        ]);
    }

    private function member(string $role): User
    {
        return User::factory()->create([
            'organization_id' => $this->org->id, 'role' => $role, 'name' => ucfirst($role).' Person',
            'module_access' => ModuleAccess::defaultsForRole($role),
        ]);
    }

    private function invoice(string $number, float $total, string $status, string $due): Invoice
    {
        return Invoice::create([
            'client_id' => $this->client->id, 'invoice_number' => $number, 'date' => now()->subDays(40), 'due_date' => $due,
            'status' => $status, 'subtotal' => $total, 'tax' => 0, 'total_amount' => $total,
        ]);
    }

    private function pay(Invoice $invoice, float $amount, string $receipt): Payment
    {
        return Payment::create(['invoice_id' => $invoice->id, 'receipt_number' => $receipt, 'amount' => $amount, 'payment_date' => now()->toDateString(), 'payment_mode' => 'Bank Transfer', 'received_by' => $this->partner->id]);
    }

    public function test_personal_renewals_are_private_to_their_owner(): void
    {
        $renewal = PersonalRenewal::create(['user_id' => $this->partner->id, 'title' => 'LIC policy', 'category' => 'LIC', 'due_date' => now()->addMonth(), 'amount' => 25000, 'status' => PersonalRenewal::STATUS_PENDING]);

        $this->actingAs($this->staff)->get(route('personal-renewals.edit', $renewal))->assertNotFound();
        $this->actingAs($this->staff)->put(route('personal-renewals.update', $renewal), [
            'title' => 'Changed', 'category' => 'LIC', 'due_date' => now()->addMonth()->toDateString(), 'amount' => 1, 'status' => PersonalRenewal::STATUS_PENDING,
        ])->assertNotFound();
        $this->actingAs($this->staff)->delete(route('personal-renewals.destroy', $renewal))->assertNotFound();
        $this->assertSame('LIC policy', $renewal->fresh()->title);

        $this->actingAs($this->partner)->get(route('personal-renewals.edit', $renewal))->assertOk();
    }

    public function test_only_owner_or_manager_can_delete_a_time_entry(): void
    {
        $task = Task::create(['client_id' => $this->client->id, 'assigned_to' => $this->partner->id, 'created_by' => $this->partner->id, 'title' => 'Audit file', 'status' => Task::STATUS_PENDING, 'priority' => 'Normal', 'due_date' => now()]);
        $entry = TimeEntry::create(['task_id' => $task->id, 'user_id' => $this->partner->id, 'date' => now(), 'hours' => 2, 'is_billable' => true]);

        $this->actingAs($this->staff)->delete(route('time-entries.destroy', $entry))->assertForbidden();
        $this->assertNotNull($entry->fresh());

        $this->actingAs($this->manager)->delete(route('time-entries.destroy', $entry))->assertRedirect();
        $this->assertNull($entry->fresh());
    }

    public function test_payment_page_and_statement_of_account_open(): void
    {
        $inv = $this->invoice('INV-A1', 10000, Invoice::STATUS_PARTIALLY_PAID, now()->addDays(5)->toDateString());
        $payment = $this->pay($inv, 4000, 'RC-1');

        $this->actingAs($this->partner)->get(route('payments.show', $payment))->assertOk()->assertSee('RC-1')->assertSee('4,000.00');
        $res = $this->actingAs($this->partner)->get(route('ledger.soa', $this->client));
        $res->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $res->headers->get('content-type'));
    }

    public function test_ledger_outstanding_ignores_payments_on_already_paid_invoices(): void
    {
        $paid = $this->invoice('INV-P1', 5000, Invoice::STATUS_PAID, now()->subDays(20)->toDateString());
        $this->pay($paid, 5000, 'RC-P1');
        $open = $this->invoice('INV-O1', 8000, Invoice::STATUS_PARTIALLY_PAID, now()->subDays(45)->toDateString());
        $this->pay($open, 3000, 'RC-O1');

        $res = $this->actingAs($this->partner)->get(route('ledger.show', $this->client))->assertOk();
        // Old formula gave 8,000 - (5,000 + 3,000) = 0. Correct outstanding is 8,000 - 3,000 = 5,000, all in 31-60 days.
        $this->assertSame(5000.0, (float) $res->viewData('totalOutstanding'));
        $this->assertSame(5000.0, (float) $res->viewData('aging')['31-60']);
        $this->assertSame(0.0, (float) $res->viewData('aging')['0-30']);
    }

    public function test_invoice_summary_uses_real_balances_and_this_months_payments(): void
    {
        $open = $this->invoice('INV-S1', 10000, Invoice::STATUS_PARTIALLY_PAID, now()->addDays(10)->toDateString());
        $this->pay($open, 2500, 'RC-S1');
        $this->invoice('INV-S2', 7000, Invoice::STATUS_CANCELLED, now()->addDays(10)->toDateString());
        $paid = $this->invoice('INV-S3', 4000, Invoice::STATUS_PAID, now()->addDays(10)->toDateString());
        $this->pay($paid, 4000, 'RC-S3');

        $res = $this->actingAs($this->partner)->get(route('invoices.index'))->assertOk();
        $this->assertSame(7500.0, (float) $res->viewData('unpaidAmount'));       // 10,000 - 2,500; cancelled ignored
        $this->assertSame(6500.0, (float) $res->viewData('collectedThisMonth')); // 2,500 + 4,000 received this month
    }

    public function test_users_page_has_valid_alpine_data(): void
    {
        $html = $this->actingAs($this->partner)->get(route('users.index'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/x-data="\{ tab: [^"]*hints: JSON\.parse\(/', $html);
    }
}
