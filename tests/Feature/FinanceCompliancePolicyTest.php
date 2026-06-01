<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\ClientService;
use App\Models\Dsc;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\ServiceDue;
use App\Models\Subscription;
use App\Models\TdsEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class FinanceCompliancePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_use_finance_compliance_and_report_policies(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->assertFalse(Gate::forUser($staff)->allows('viewAny', Expense::class));
        $this->assertFalse(Gate::forUser($staff)->allows('viewAny', Subscription::class));
        $this->assertFalse(Gate::forUser($staff)->allows('viewAny', Dsc::class));
        $this->assertFalse(Gate::forUser($staff)->allows('viewAny', TdsEntry::class));
        $this->assertFalse(Gate::forUser($staff)->allows('viewReports'));
    }

    public function test_manager_access_for_branch_owned_records_is_limited_by_branch(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);

        $ownClient = $this->clientForBranch($branchA, 'POL-A');
        $otherClient = $this->clientForBranch($branchB, 'POL-B');

        $ownExpense = Expense::create([
            'category' => 'Software',
            'description' => 'Branch A tool',
            'amount' => 100,
            'expense_date' => now(),
            'payment_mode' => 'Cash',
            'user_id' => User::factory()->create(['role' => 'staff', 'branch_id' => $branchA->id])->id,
        ]);
        $otherExpense = Expense::create([
            'category' => 'Software',
            'description' => 'Branch B tool',
            'amount' => 100,
            'expense_date' => now(),
            'payment_mode' => 'Cash',
            'user_id' => User::factory()->create(['role' => 'staff', 'branch_id' => $branchB->id])->id,
        ]);

        $ownSubscription = $this->subscriptionForClient($ownClient, 'Branch A Retainer');
        $otherSubscription = $this->subscriptionForClient($otherClient, 'Branch B Retainer');
        $ownDsc = $this->dscForClient($ownClient, 'Branch A Holder');
        $otherDsc = $this->dscForClient($otherClient, 'Branch B Holder');
        $ownTds = $this->tdsForInvoice($this->invoiceForClient($ownClient, 'TDS-A'));
        $otherTds = $this->tdsForInvoice($this->invoiceForClient($otherClient, 'TDS-B'));

        $this->assertTrue(Gate::forUser($manager)->allows('delete', $ownExpense));
        $this->assertFalse(Gate::forUser($manager)->allows('delete', $otherExpense));
        $this->assertTrue(Gate::forUser($manager)->allows('update', $ownSubscription));
        $this->assertFalse(Gate::forUser($manager)->allows('update', $otherSubscription));
        $this->assertTrue(Gate::forUser($manager)->allows('update', $ownDsc));
        $this->assertFalse(Gate::forUser($manager)->allows('update', $otherDsc));
        $this->assertTrue(Gate::forUser($manager)->allows('delete', $ownTds));
        $this->assertFalse(Gate::forUser($manager)->allows('delete', $otherTds));
    }

    public function test_manager_indexes_are_scoped_to_branch(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);

        $branchAStaff = User::factory()->create(['role' => 'staff', 'branch_id' => $branchA->id]);
        $branchBStaff = User::factory()->create(['role' => 'staff', 'branch_id' => $branchB->id]);
        Expense::create([
            'category' => 'Software',
            'description' => 'Visible Expense',
            'amount' => 100,
            'expense_date' => now(),
            'payment_mode' => 'Cash',
            'user_id' => $branchAStaff->id,
        ]);
        Expense::create([
            'category' => 'Software',
            'description' => 'Hidden Expense',
            'amount' => 100,
            'expense_date' => now(),
            'payment_mode' => 'Cash',
            'user_id' => $branchBStaff->id,
        ]);

        $ownClient = $this->clientForBranch($branchA, 'IDX-A');
        $otherClient = $this->clientForBranch($branchB, 'IDX-B');
        $this->subscriptionForClient($ownClient, 'Visible Retainer');
        $this->subscriptionForClient($otherClient, 'Hidden Retainer');
        $this->dscForClient($ownClient, 'Visible Holder');
        $this->dscForClient($otherClient, 'Hidden Holder');
        $this->tdsForInvoice($this->invoiceForClient($ownClient, 'VISIBLE-TDS'));
        $this->tdsForInvoice($this->invoiceForClient($otherClient, 'HIDDEN-TDS'));

        $this->actingAs($manager)
            ->get(route('expenses.index'))
            ->assertOk()
            ->assertSee('Visible Expense')
            ->assertDontSee('Hidden Expense');

        $this->actingAs($manager)
            ->get(route('subscriptions.index'))
            ->assertOk()
            ->assertSee('Visible Retainer')
            ->assertDontSee('Hidden Retainer');

        $this->actingAs($manager)
            ->get(route('dscs.index'))
            ->assertOk()
            ->assertSee('Visible Holder')
            ->assertDontSee('Hidden Holder');

        $this->actingAs($manager)
            ->get(route('tds.index'))
            ->assertOk()
            ->assertSee('VISIBLE-TDS')
            ->assertDontSee('HIDDEN-TDS');
    }

    public function test_manager_reports_are_scoped_to_branch(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);

        $ownClient = $this->clientForBranch($branchA, 'RPT-A');
        $otherClient = $this->clientForBranch($branchB, 'RPT-B');
        $this->invoiceForClient($ownClient, 'VISIBLE-RPT');
        $this->invoiceForClient($otherClient, 'HIDDEN-RPT');
        $this->dueForClient($ownClient, 'Visible Service');
        $this->dueForClient($otherClient, 'Hidden Service');

        $this->actingAs($manager)
            ->get(route('reports.financial'))
            ->assertOk()
            ->assertSee('Client RPT-A')
            ->assertDontSee('Client RPT-B');

        $this->actingAs($manager)
            ->get(route('reports.compliance'))
            ->assertOk()
            ->assertSee('Visible Service')
            ->assertDontSee('Hidden Service');
    }

    public function test_partner_can_manage_records_across_branches(): void
    {
        [, $branchB] = $this->branches();
        $partner = User::factory()->create(['role' => 'partner']);
        $otherClient = $this->clientForBranch($branchB, 'PARTNER-B');

        $this->assertTrue(Gate::forUser($partner)->allows('update', $this->subscriptionForClient($otherClient, 'Partner Retainer')));
        $this->assertTrue(Gate::forUser($partner)->allows('update', $this->dscForClient($otherClient, 'Partner Holder')));
        $this->assertTrue(Gate::forUser($partner)->allows('delete', $this->tdsForInvoice($this->invoiceForClient($otherClient, 'PARTNER-TDS'))));
    }

    private function branches(): array
    {
        return [
            Branch::create(['name' => 'Branch A', 'code' => 'FPA']),
            Branch::create(['name' => 'Branch B', 'code' => 'FPB']),
        ];
    }

    private function clientForBranch(Branch $branch, string $suffix): Client
    {
        return Client::factory()->create([
            'name' => 'Client ' . $suffix,
            'client_code' => 'CL-' . $suffix,
            'pan' => 'PAN' . substr(str_pad((string) crc32($suffix), 7, '0'), 0, 7),
            'branch_id' => $branch->id,
        ]);
    }

    private function subscriptionForClient(Client $client, string $name): Subscription
    {
        return Subscription::create([
            'client_id' => $client->id,
            'name' => $name,
            'amount' => 1000,
            'frequency' => Subscription::FREQUENCY_MONTHLY,
            'billing_day' => 1,
            'start_date' => now(),
            'next_billing_date' => now()->addMonth(),
        ]);
    }

    private function dscForClient(Client $client, string $holderName): Dsc
    {
        return Dsc::create([
            'client_id' => $client->id,
            'holder_name' => $holderName,
            'class_type' => 'Class 3',
            'issue_date' => now()->subMonth(),
            'expiry_date' => now()->addYear(),
            'status' => Dsc::STATUS_ACTIVE,
        ]);
    }

    private function invoiceForClient(Client $client, string $invoiceNumber): Invoice
    {
        return Invoice::create([
            'client_id' => $client->id,
            'branch_id' => $client->branch_id,
            'invoice_number' => $invoiceNumber,
            'date' => now(),
            'due_date' => now()->addDays(7),
            'status' => Invoice::STATUS_DRAFT,
            'subtotal' => 1000,
            'tax' => 180,
            'total_amount' => 1180,
        ]);
    }

    private function tdsForInvoice(Invoice $invoice): TdsEntry
    {
        return TdsEntry::create([
            'invoice_id' => $invoice->id,
            'tds_rate' => 10,
            'tds_amount' => 118,
        ]);
    }

    private function dueForClient(Client $client, string $serviceName): ServiceDue
    {
        $service = Service::create([
            'name' => $serviceName,
            'code' => strtoupper(substr(md5($serviceName), 0, 8)),
            'frequency' => 'Monthly',
            'due_day' => 15,
            'is_statutory' => true,
        ]);

        $clientService = ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'status' => ClientService::STATUS_ACTIVE,
        ]);

        return ServiceDue::create([
            'client_service_id' => $clientService->id,
            'due_date' => now()->startOfMonth()->addDays(3),
            'status' => ServiceDue::STATUS_PENDING,
        ]);
    }
}
