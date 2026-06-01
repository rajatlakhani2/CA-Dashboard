<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\ClientCredential;
use App\Models\ClientService;
use App\Models\Dsc;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServiceDue;
use App\Models\Subscription;
use App\Models\TdsEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class NewerModuleFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_credential_create_and_delete_respect_client_branch(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);
        $ownClient = $this->clientForBranch($branchA, 'CRED-A');
        $otherClient = $this->clientForBranch($branchB, 'CRED-B');

        $this->actingAs($manager)
            ->post(route('credentials.store'), [
                'client_id' => $ownClient->id,
                'portal_name' => 'GST Portal Flow',
                'username' => 'gst@example.test',
                'password' => 'secret',
                'notes' => 'Quarterly filing login',
            ])
            ->assertRedirect();

        $credential = ClientCredential::where('portal_name', 'GST Portal Flow')->first();
        $this->assertNotNull($credential);
        $this->assertSame($ownClient->id, $credential->client_id);

        $this->actingAs($manager)
            ->post(route('credentials.store'), [
                'client_id' => $otherClient->id,
                'portal_name' => 'Blocked Portal Flow',
                'username' => 'blocked@example.test',
            ])
            ->assertForbidden();

        $otherCredential = ClientCredential::create([
            'client_id' => $otherClient->id,
            'portal_name' => 'Other Branch Portal Flow',
            'username' => 'other@example.test',
        ]);

        $this->actingAs($manager)
            ->delete(route('credentials.destroy', $otherCredential))
            ->assertForbidden();

        $this->actingAs($manager)
            ->delete(route('credentials.destroy', $credential))
            ->assertRedirect();

        $this->assertDatabaseMissing('client_credentials', ['id' => $credential->id]);
    }

    public function test_payment_create_overpayment_delete_and_branch_denial_flows(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);
        $ownInvoice = $this->invoiceForClient($this->clientForBranch($branchA, 'PAY-A'), 'FLOW-PAY-A', 1180);
        $otherInvoice = $this->invoiceForClient($this->clientForBranch($branchB, 'PAY-B'), 'FLOW-PAY-B', 1180);

        $this->actingAs($manager)
            ->post(route('payments.store'), [
                'invoice_id' => $ownInvoice->id,
                'receipt_number' => 'REC-FLOW-001',
                'amount' => 500,
                'payment_date' => now()->format('Y-m-d'),
                'payment_mode' => 'Bank Transfer',
                'reference_number' => 'UTR123',
            ])
            ->assertRedirect(route('payments.index'));

        $payment = Payment::where('receipt_number', 'REC-FLOW-001')->first();
        $this->assertNotNull($payment);
        $this->assertSame($manager->id, $payment->received_by);
        $this->assertSame(Invoice::STATUS_PARTIALLY_PAID, $ownInvoice->fresh()->status);

        $this->actingAs($manager)
            ->post(route('payments.store'), [
                'invoice_id' => $ownInvoice->id,
                'receipt_number' => 'REC-FLOW-002',
                'amount' => 1000,
                'payment_date' => now()->format('Y-m-d'),
                'payment_mode' => 'Bank Transfer',
            ])
            ->assertSessionHasErrors('amount');

        $this->actingAs($manager)
            ->post(route('payments.store'), [
                'invoice_id' => $otherInvoice->id,
                'receipt_number' => 'REC-FLOW-003',
                'amount' => 100,
                'payment_date' => now()->format('Y-m-d'),
                'payment_mode' => 'Bank Transfer',
            ])
            ->assertForbidden();

        $otherPayment = Payment::create([
            'invoice_id' => $otherInvoice->id,
            'receipt_number' => 'REC-FLOW-OTHER',
            'amount' => 100,
            'payment_date' => now(),
            'payment_mode' => 'Cash',
        ]);

        $this->actingAs($manager)
            ->delete(route('payments.destroy', $otherPayment))
            ->assertForbidden();

        $this->actingAs($manager)
            ->delete(route('payments.destroy', $payment))
            ->assertRedirect(route('payments.index'));

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        $this->assertSame(Invoice::STATUS_DRAFT, $ownInvoice->fresh()->status);
    }

    public function test_expense_create_update_delete_and_branch_denial_flows(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);
        $otherUser = User::factory()->create(['role' => 'manager', 'branch_id' => $branchB->id]);

        $this->actingAs($manager)
            ->post(route('expenses.store'), [
                'category' => 'Software',
                'description' => 'Audit tool subscription',
                'amount' => 1200,
                'expense_date' => now()->format('Y-m-d'),
                'payment_mode' => 'UPI',
                'vendor' => 'Audit Tools Ltd',
            ])
            ->assertRedirect(route('expenses.index'));

        $expense = Expense::where('description', 'Audit tool subscription')->first();
        $this->assertNotNull($expense);
        $this->assertSame($manager->id, $expense->user_id);

        $this->actingAs($manager)
            ->patch(route('expenses.update', $expense), [
                'category' => 'Professional Fees',
                'description' => 'Audit tool renewal',
                'amount' => 1400,
                'expense_date' => now()->format('Y-m-d'),
                'payment_mode' => 'Bank Transfer',
            ])
            ->assertRedirect(route('expenses.index'));

        $this->assertSame('Professional Fees', $expense->fresh()->category);

        $otherExpense = Expense::create([
            'category' => 'Software',
            'description' => 'Other branch expense',
            'amount' => 100,
            'expense_date' => now(),
            'payment_mode' => 'Cash',
            'user_id' => $otherUser->id,
        ]);

        $this->actingAs($manager)
            ->patch(route('expenses.update', $otherExpense), [
                'category' => 'Travel',
                'description' => 'Blocked edit',
                'amount' => 100,
                'expense_date' => now()->format('Y-m-d'),
                'payment_mode' => 'Cash',
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->delete(route('expenses.destroy', $expense))
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_dsc_create_update_delete_and_branch_denial_flows(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);
        $ownClient = $this->clientForBranch($branchA, 'DSC-A');
        $otherClient = $this->clientForBranch($branchB, 'DSC-B');

        $this->actingAs($manager)
            ->post(route('dscs.store'), [
                'client_id' => $ownClient->id,
                'holder_name' => 'Flow DSC Holder',
                'class_type' => 'Class 3',
                'provider' => 'eMudhra',
                'issue_date' => now()->subMonth()->format('Y-m-d'),
                'expiry_date' => now()->addYear()->format('Y-m-d'),
            ])
            ->assertRedirect(route('dscs.index'));

        $dsc = Dsc::where('holder_name', 'Flow DSC Holder')->first();
        $this->assertNotNull($dsc);

        $this->actingAs($manager)
            ->patch(route('dscs.update', $dsc), [
                'client_id' => $ownClient->id,
                'holder_name' => 'Flow DSC Holder Updated',
                'class_type' => 'Class 3',
                'expiry_date' => now()->addYears(2)->format('Y-m-d'),
            ])
            ->assertRedirect(route('dscs.index'));

        $this->assertSame('Flow DSC Holder Updated', $dsc->fresh()->holder_name);

        $this->actingAs($manager)
            ->post(route('dscs.store'), [
                'client_id' => $otherClient->id,
                'holder_name' => 'Blocked DSC Holder',
                'class_type' => 'Class 3',
                'issue_date' => now()->subMonth()->format('Y-m-d'),
                'expiry_date' => now()->addYear()->format('Y-m-d'),
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->patch(route('dscs.update', $dsc), [
                'client_id' => $otherClient->id,
                'holder_name' => 'Blocked Reassignment',
                'class_type' => 'Class 3',
                'expiry_date' => now()->addYear()->format('Y-m-d'),
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->delete(route('dscs.destroy', $dsc))
            ->assertRedirect(route('dscs.index'));

        $this->assertDatabaseMissing('dscs', ['id' => $dsc->id]);
    }

    public function test_tds_create_update_delete_and_branch_denial_flows(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);
        $ownInvoice = $this->invoiceForClient($this->clientForBranch($branchA, 'TDS-A'), 'FLOW-TDS-A');
        $otherInvoice = $this->invoiceForClient($this->clientForBranch($branchB, 'TDS-B'), 'FLOW-TDS-B');

        $this->actingAs($manager)
            ->post(route('tds.store'), [
                'invoice_id' => $ownInvoice->id,
                'tds_rate' => 10,
                'tds_amount' => 118,
                'certificate_number' => 'CERT-A',
            ])
            ->assertRedirect();

        $tdsEntry = TdsEntry::where('certificate_number', 'CERT-A')->first();
        $this->assertNotNull($tdsEntry);

        $this->actingAs($manager)
            ->patch(route('tds.update', $tdsEntry), [
                'certificate_received' => true,
                'certificate_date' => now()->format('Y-m-d'),
                'certificate_number' => 'CERT-A-UPDATED',
            ])
            ->assertRedirect();

        $this->assertTrue($tdsEntry->fresh()->certificate_received);

        $this->actingAs($manager)
            ->post(route('tds.store'), [
                'invoice_id' => $otherInvoice->id,
                'tds_rate' => 10,
                'tds_amount' => 118,
            ])
            ->assertForbidden();

        $otherTds = TdsEntry::create([
            'invoice_id' => $otherInvoice->id,
            'tds_rate' => 10,
            'tds_amount' => 118,
        ]);

        $this->actingAs($manager)
            ->delete(route('tds.destroy', $otherTds))
            ->assertForbidden();

        $this->actingAs($manager)
            ->delete(route('tds.destroy', $tdsEntry))
            ->assertRedirect();

        $this->assertDatabaseMissing('tds_entries', ['id' => $tdsEntry->id]);
    }

    public function test_subscription_create_toggle_delete_and_branch_denial_flows(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);
        $ownClient = $this->clientForBranch($branchA, 'SUB-A');
        $otherClient = $this->clientForBranch($branchB, 'SUB-B');
        $service = $this->service('SUBFLOW');

        $this->actingAs($manager)
            ->post(route('subscriptions.store'), [
                'client_id' => $ownClient->id,
                'service_id' => $service->id,
                'name' => 'Monthly Flow Retainer',
                'amount' => 2500,
                'frequency' => Subscription::FREQUENCY_MONTHLY,
                'billing_day' => 5,
                'start_date' => now()->format('Y-m-d'),
            ])
            ->assertRedirect(route('subscriptions.index'));

        $subscription = Subscription::where('name', 'Monthly Flow Retainer')->first();
        $this->assertNotNull($subscription);
        $this->assertNotNull($subscription->next_billing_date);

        $this->actingAs($manager)
            ->post(route('subscriptions.toggle', $subscription))
            ->assertRedirect();

        $this->assertSame(Subscription::STATUS_PAUSED, $subscription->fresh()->status);

        $this->actingAs($manager)
            ->post(route('subscriptions.store'), [
                'client_id' => $otherClient->id,
                'service_id' => $service->id,
                'name' => 'Blocked Retainer',
                'amount' => 2500,
                'frequency' => Subscription::FREQUENCY_MONTHLY,
                'billing_day' => 5,
                'start_date' => now()->format('Y-m-d'),
            ])
            ->assertForbidden();

        $otherSubscription = Subscription::create([
            'client_id' => $otherClient->id,
            'service_id' => $service->id,
            'name' => 'Other Branch Retainer',
            'amount' => 500,
            'frequency' => Subscription::FREQUENCY_MONTHLY,
            'billing_day' => 1,
            'start_date' => now(),
            'next_billing_date' => now()->addMonth(),
        ]);

        $this->actingAs($manager)
            ->post(route('subscriptions.toggle', $otherSubscription))
            ->assertForbidden();

        $this->actingAs($manager)
            ->delete(route('subscriptions.destroy', $subscription))
            ->assertRedirect(route('subscriptions.index'));

        $this->assertDatabaseMissing('subscriptions', ['id' => $subscription->id]);
    }

    public function test_report_exports_are_branch_scoped_for_managers(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);
        $ownClient = $this->clientForBranch($branchA, 'EXP-A');
        $otherClient = $this->clientForBranch($branchB, 'EXP-B');
        $this->invoiceForClient($ownClient, 'EXPORT-OWN');
        $this->invoiceForClient($otherClient, 'EXPORT-HIDDEN');
        $this->dueForClient($ownClient, 'Export Visible Service');
        $this->dueForClient($otherClient, 'Export Hidden Service');

        $financialExport = $this->actingAs($manager)
            ->get(route('reports.financial.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $financialCsv = $financialExport->streamedContent();
        $this->assertStringContainsString('EXPORT-OWN', $financialCsv);
        $this->assertStringNotContainsString('EXPORT-HIDDEN', $financialCsv);

        $complianceExport = $this->actingAs($manager)
            ->get(route('reports.compliance.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $complianceCsv = $complianceExport->streamedContent();
        $this->assertStringContainsString('Export Visible Service', $complianceCsv);
        $this->assertStringNotContainsString('Export Hidden Service', $complianceCsv);
    }

    public function test_system_actions_are_partner_only_and_call_artisan(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)
            ->post(route('system.clear-cache'))
            ->assertForbidden();

        $partner = User::factory()->create(['role' => 'partner']);

        Artisan::shouldReceive('call')->once()->with('cache:clear')->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('view:clear')->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('config:clear')->andReturn(0);
        Artisan::shouldReceive('call')->once()->with('route:clear')->andReturn(0);

        $this->actingAs($partner)
            ->post(route('system.clear-cache'))
            ->assertRedirect()
            ->assertSessionHas('success');

        Artisan::shouldReceive('call')->once()->with('optimize')->andReturn(0);

        $this->actingAs($partner)
            ->post(route('system.optimize'))
            ->assertRedirect()
            ->assertSessionHas('success');

        Artisan::shouldReceive('call')->once()->with('migrate', ['--force' => true])->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('Nothing to migrate.');

        $this->actingAs($partner)
            ->post(route('system.migrate'))
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    private function branches(): array
    {
        return [
            Branch::create(['name' => 'Branch A', 'code' => 'NFA']),
            Branch::create(['name' => 'Branch B', 'code' => 'NFB']),
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

    private function invoiceForClient(Client $client, string $invoiceNumber, int $total = 1180): Invoice
    {
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'branch_id' => $client->branch_id,
            'invoice_number' => $invoiceNumber,
            'date' => now(),
            'due_date' => now()->addDays(7),
            'status' => Invoice::STATUS_OVERDUE,
            'subtotal' => $total,
            'tax' => 0,
            'total_amount' => $total,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Professional Service',
            'quantity' => 1,
            'rate' => $total,
            'amount' => $total,
        ]);

        return $invoice;
    }

    private function service(string $code): Service
    {
        return Service::create([
            'name' => 'Service ' . $code,
            'code' => $code,
            'frequency' => 'Monthly',
            'due_day' => 15,
            'is_statutory' => true,
        ]);
    }

    private function dueForClient(Client $client, string $serviceName): ServiceDue
    {
        $service = $this->service(strtoupper(substr(md5($serviceName), 0, 8)));
        $service->update(['name' => $serviceName]);

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
