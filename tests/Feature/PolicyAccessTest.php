<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\ClientCredential;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PolicyAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_invoice_access_is_limited_by_branch(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);

        $ownInvoice = $this->invoiceForBranch($branchA, 'INV-A-001');
        $otherInvoice = $this->invoiceForBranch($branchB, 'INV-B-001');

        $this->assertTrue(Gate::forUser($manager)->allows('view', $ownInvoice));
        $this->assertFalse(Gate::forUser($manager)->allows('view', $otherInvoice));
    }

    public function test_partner_can_access_invoices_across_branches(): void
    {
        [, $branchB] = $this->branches();
        $partner = User::factory()->create(['role' => 'partner']);
        $otherInvoice = $this->invoiceForBranch($branchB, 'INV-B-002');

        $this->assertTrue(Gate::forUser($partner)->allows('view', $otherInvoice));
    }

    public function test_payment_access_follows_invoice_branch(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);

        $ownPayment = $this->paymentForInvoice($this->invoiceForBranch($branchA, 'INV-A-003'), 'REC-A-001');
        $otherPayment = $this->paymentForInvoice($this->invoiceForBranch($branchB, 'INV-B-003'), 'REC-B-001');

        $this->assertTrue(Gate::forUser($manager)->allows('view', $ownPayment));
        $this->assertFalse(Gate::forUser($manager)->allows('view', $otherPayment));
    }

    public function test_credential_access_follows_client_branch(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);

        $ownCredential = $this->credentialForBranch($branchA, 'GST Portal A');
        $otherCredential = $this->credentialForBranch($branchB, 'GST Portal B');

        $this->assertTrue(Gate::forUser($manager)->allows('view', $ownCredential));
        $this->assertFalse(Gate::forUser($manager)->allows('view', $otherCredential));
    }

    public function test_staff_cannot_use_manager_level_model_policies(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->assertFalse(Gate::forUser($staff)->allows('viewAny', Invoice::class));
        $this->assertFalse(Gate::forUser($staff)->allows('viewAny', Payment::class));
        $this->assertFalse(Gate::forUser($staff)->allows('viewAny', ClientCredential::class));
        $this->assertFalse(Gate::forUser($staff)->allows('export', Client::class));
    }

    public function test_invoice_index_is_scoped_to_manager_branch(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);

        $this->invoiceForBranch($branchA, 'INV-A-SCOPE');
        $this->invoiceForBranch($branchB, 'INV-B-SCOPE');

        $this->actingAs($manager)
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertSee('INV-A-SCOPE')
            ->assertDontSee('INV-B-SCOPE');
    }

    public function test_payment_index_is_scoped_to_manager_branch(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);

        $this->paymentForInvoice($this->invoiceForBranch($branchA, 'INV-A-PAY'), 'REC-A-SCOPE');
        $this->paymentForInvoice($this->invoiceForBranch($branchB, 'INV-B-PAY'), 'REC-B-SCOPE');

        $this->actingAs($manager)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('REC-A-SCOPE')
            ->assertDontSee('REC-B-SCOPE');
    }

    public function test_credential_index_is_scoped_to_manager_branch(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);

        $this->credentialForBranch($branchA, 'Branch A Portal');
        $this->credentialForBranch($branchB, 'Branch B Portal');

        $this->actingAs($manager)
            ->get(route('credentials.index'))
            ->assertOk()
            ->assertSee('Branch A Portal')
            ->assertDontSee('Branch B Portal');
    }

    private function branches(): array
    {
        return [
            Branch::create(['name' => 'Branch A', 'code' => 'BRA']),
            Branch::create(['name' => 'Branch B', 'code' => 'BRB']),
        ];
    }

    private function clientForBranch(Branch $branch, string $suffix): Client
    {
        return Client::factory()->create([
            'name' => 'Client ' . $suffix,
            'client_code' => 'CL-' . $suffix,
            'pan' => 'PAN' . str_pad($suffix, 7, '0'),
            'branch_id' => $branch->id,
        ]);
    }

    private function invoiceForBranch(Branch $branch, string $invoiceNumber): Invoice
    {
        return Invoice::create([
            'client_id' => $this->clientForBranch($branch, $invoiceNumber)->id,
            'branch_id' => $branch->id,
            'invoice_number' => $invoiceNumber,
            'date' => now(),
            'due_date' => now()->addDays(7),
            'status' => Invoice::STATUS_DRAFT,
            'subtotal' => 1000,
            'tax' => 180,
            'total_amount' => 1180,
        ]);
    }

    private function paymentForInvoice(Invoice $invoice, string $receiptNumber): Payment
    {
        return Payment::create([
            'invoice_id' => $invoice->id,
            'receipt_number' => $receiptNumber,
            'amount' => 500,
            'payment_date' => now(),
            'payment_mode' => 'Bank Transfer',
        ]);
    }

    private function credentialForBranch(Branch $branch, string $portalName): ClientCredential
    {
        return ClientCredential::create([
            'client_id' => $this->clientForBranch($branch, $portalName)->id,
            'portal_name' => $portalName,
            'username' => 'user@example.com',
            'password' => 'secret',
        ]);
    }
}
