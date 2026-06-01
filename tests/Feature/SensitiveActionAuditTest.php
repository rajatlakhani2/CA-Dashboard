<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\SensitiveActionLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class SensitiveActionAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_delete_writes_sensitive_action_log(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $client = Client::factory()->create();
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-AUDIT-1',
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => Invoice::STATUS_DRAFT,
            'subtotal' => 5000,
            'tax' => 0,
            'total_amount' => 5000,
        ]);

        $this->actingAs($partner)
            ->delete(route('invoices.destroy', $invoice))
            ->assertRedirect(route('invoices.index'));

        $activity = Activity::where('log_name', SensitiveActionLogger::LOG_NAME)
            ->where('event', 'invoice_deleted')
            ->where('subject_type', Invoice::class)
            ->first();

        $this->assertNotNull($activity);
        $this->assertStringContainsString('INV-AUDIT-1', $activity->description);
    }

    public function test_payment_delete_writes_sensitive_action_log(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $client = Client::factory()->create();
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-AUDIT-2',
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => Invoice::STATUS_DRAFT,
            'subtotal' => 10000,
            'tax' => 0,
            'total_amount' => 10000,
        ]);
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'receipt_number' => 'REC-AUDIT-99',
            'amount' => 1000,
            'payment_date' => now(),
            'payment_mode' => 'UPI',
        ]);

        $this->actingAs($partner)
            ->delete(route('payments.destroy', $payment))
            ->assertRedirect(route('payments.index'));

        $activity = Activity::where('log_name', SensitiveActionLogger::LOG_NAME)
            ->where('event', 'payment_deleted')
            ->where('subject_type', Payment::class)
            ->first();

        $this->assertNotNull($activity);
        $this->assertStringContainsString('REC-AUDIT-99', $activity->description);
    }

    public function test_user_role_change_is_audited(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $staff = User::factory()->create(['role' => 'staff', 'mobile' => '9999999999']);

        $this->actingAs($partner)
            ->patch(route('users.update-role', $staff), [
                'role' => 'manager',
                'mobile' => '8888888888',
            ])
            ->assertRedirect();

        $this->assertSame('manager', $staff->fresh()->role);

        $activity = Activity::where('log_name', SensitiveActionLogger::LOG_NAME)
            ->where('event', 'user_role_changed')
            ->where('subject_type', User::class)
            ->where('subject_id', $staff->id)
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('staff', $activity->properties['previous_role']);
        $this->assertSame('manager', $activity->properties['new_role']);
    }

    public function test_module_access_change_is_audited(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $staff = User::factory()->create([
            'role' => 'staff',
            'module_access' => ['tasks' => true, 'dashboard' => true],
        ]);

        $this->actingAs($partner)
            ->patch(route('users.update-module-access', $staff), [
                'modules' => [
                    'tasks' => true,
                    'dashboard' => true,
                    'clients' => true,
                ],
            ])
            ->assertRedirect();

        $activity = \Spatie\Activitylog\Models\Activity::where('log_name', SensitiveActionLogger::LOG_NAME)
            ->where('event', 'user_module_access_changed')
            ->where('subject_id', $staff->id)
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('clients', $activity->properties['changes']);
    }

    public function test_bulk_client_delete_writes_summary_audit(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $clients = Client::factory()->count(2)->create();
        $ids = $clients->pluck('id')->all();

        $this->actingAs($partner)
            ->delete(route('clients.bulk-destroy'), ['selected_clients' => $ids])
            ->assertRedirect();

        $activity = Activity::where('log_name', SensitiveActionLogger::LOG_NAME)
            ->where('event', 'clients_bulk_deleted')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame(2, $activity->properties['count']);
    }

    public function test_invoice_update_writes_sensitive_action_log_when_totals_change(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $client = Client::factory()->create();
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-AUDIT-UPD',
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => Invoice::STATUS_DRAFT,
            'subtotal' => 5000,
            'tax' => 0,
            'total_amount' => 5000,
        ]);
        $item = $invoice->items()->create([
            'description' => 'Consulting',
            'quantity' => 1,
            'rate' => 5000,
            'amount' => 5000,
            'gst_rate' => 0,
        ]);

        $this->actingAs($partner)
            ->put(route('invoices.update', $invoice), [
                'client_id' => $client->id,
                'invoice_number' => 'INV-AUDIT-UPD',
                'date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
                'status' => Invoice::STATUS_DRAFT,
                'items' => [
                    [
                        'id' => $item->id,
                        'description' => 'Consulting',
                        'quantity' => 1,
                        'rate' => 7500,
                        'gst_rate' => 0,
                    ],
                ],
            ])
            ->assertRedirect(route('invoices.show', $invoice));

        $activity = Activity::where('log_name', SensitiveActionLogger::LOG_NAME)
            ->where('event', 'invoice_updated')
            ->where('subject_id', $invoice->id)
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('total_amount', $activity->properties['changes']);
        $this->assertEquals(5000, $activity->properties['changes']['total_amount']['from']);
        $this->assertEquals(7500, $activity->properties['changes']['total_amount']['to']);
    }

    public function test_payment_create_writes_sensitive_action_log(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $client = Client::factory()->create();
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-AUDIT-PAY',
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => Invoice::STATUS_DRAFT,
            'subtotal' => 10000,
            'tax' => 0,
            'total_amount' => 10000,
        ]);

        $this->actingAs($partner)
            ->post(route('payments.store'), [
                'invoice_id' => $invoice->id,
                'receipt_number' => 'REC-AUDIT-CREATE',
                'amount' => 2500,
                'payment_date' => now()->toDateString(),
                'payment_mode' => 'UPI',
            ])
            ->assertRedirect(route('payments.index'));

        $payment = Payment::where('receipt_number', 'REC-AUDIT-CREATE')->first();
        $this->assertNotNull($payment);

        $activity = Activity::where('log_name', SensitiveActionLogger::LOG_NAME)
            ->where('event', 'payment_created')
            ->where('subject_id', $payment->id)
            ->first();

        $this->assertNotNull($activity);
        $this->assertStringContainsString('REC-AUDIT-CREATE', $activity->description);
        $this->assertEquals(2500, $activity->properties['amount']);
    }
}
