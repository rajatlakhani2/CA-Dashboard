<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\ServiceDue;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Deep POST/action flows from DASHBOARD_FEATURE_TEST.md §16.
 */
class DeepPostFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_billing_process_prefills_invoice_create_session(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $client = Client::create([
            'client_code' => 'BILL-1',
            'name' => 'Billing Flow Client',
            'pan' => 'BILLFLOW1A',
            'status' => Client::STATUS_ACTIVE,
            'category' => 'A',
            'approval_status' => Client::APPROVAL_APPROVED,
        ]);
        $service = Service::create([
            'name' => 'GST Return',
            'code' => 'GST',
            'frequency' => 'Monthly',
            'due_day' => 10,
            'is_statutory' => true,
        ]);
        $clientService = ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'status' => ClientService::STATUS_ACTIVE,
        ]);
        $due = ServiceDue::create([
            'client_service_id' => $clientService->id,
            'due_date' => now()->subDay(),
            'status' => ServiceDue::STATUS_COMPLETED,
            'billing_status' => ServiceDue::BILLING_STATUS_UNBILLED,
            'billing_amount' => 2500,
        ]);

        $response = $this->actingAs($partner)
            ->post(route('billing.process'), ['dues' => [$due->id]]);

        $response->assertRedirect(route('invoices.create', ['client_id' => $client->id]));
        $this->assertEquals(
            [$due->id],
            session('invoice_prefill_dues')
        );
        $this->assertNotEmpty(session('invoice_prefill_items'));
    }

    public function test_partner_can_create_invoice_after_billing_prefill(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $client = Client::create([
            'client_code' => 'INV-POST-1',
            'name' => 'Invoice Post Client',
            'pan' => 'INVPOST01A',
            'status' => Client::STATUS_ACTIVE,
            'category' => 'A',
            'approval_status' => Client::APPROVAL_APPROVED,
        ]);

        $this->actingAs($partner)
            ->post(route('invoices.store'), [
                'client_id' => $client->id,
                'date' => now()->format('Y-m-d'),
                'due_date' => now()->addDays(7)->format('Y-m-d'),
                'invoice_number' => 'INV-DEEP-001',
                'status' => Invoice::STATUS_DRAFT,
                'items' => [
                    [
                        'description' => 'Deep flow service',
                        'quantity' => 1,
                        'rate' => 1000,
                        'amount' => 1000,
                    ],
                ],
                'subtotal' => 1000,
                'tax' => 180,
                'discount' => 0,
                'total_amount' => 1180,
            ])
            ->assertRedirect(route('invoices.index'));

        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'INV-DEEP-001',
            'client_id' => $client->id,
        ]);
    }

    public function test_partner_invoice_whatsapp_send_uses_mocked_service(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $client = Client::create([
            'client_code' => 'WA-1',
            'name' => 'WhatsApp Client',
            'pan' => 'WAPOST001A',
            'status' => Client::STATUS_ACTIVE,
            'category' => 'A',
            'primary_contact_phone' => '919876543210',
            'approval_status' => Client::APPROVAL_APPROVED,
        ]);
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-WA-001',
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => Invoice::STATUS_DRAFT,
            'total_amount' => 5000,
        ]);

        $whatsApp = \Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldReceive('sendMessage')
            ->once()
            ->andReturn(['success' => true, 'message' => 'Message sent successfully']);
        $this->instance(WhatsAppService::class, $whatsApp);

        $this->actingAs($partner)
            ->post(route('invoices.whatsapp', $invoice))
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_partner_service_due_whatsapp_send_uses_mocked_service(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $client = Client::create([
            'client_code' => 'WA-2',
            'name' => 'Due WhatsApp Client',
            'pan' => 'WAPOST002B',
            'status' => Client::STATUS_ACTIVE,
            'category' => 'A',
            'primary_contact_phone' => '919876543211',
            'approval_status' => Client::APPROVAL_APPROVED,
        ]);
        $service = Service::create([
            'name' => 'TDS Filing',
            'code' => 'TDS',
            'frequency' => 'Quarterly',
            'due_day' => 7,
            'is_statutory' => true,
        ]);
        $clientService = ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'status' => ClientService::STATUS_ACTIVE,
        ]);
        $due = ServiceDue::create([
            'client_service_id' => $clientService->id,
            'due_date' => now()->addDays(3),
            'status' => ServiceDue::STATUS_PENDING,
        ]);

        $whatsApp = \Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldReceive('sendMessage')
            ->once()
            ->andReturn(['success' => true, 'message' => 'Message sent successfully']);
        $this->instance(WhatsAppService::class, $whatsApp);

        $this->actingAs($partner)
            ->from(route('service-dues.index'))
            ->post(route('service-dues.whatsapp', $due))
            ->assertRedirect(route('service-dues.index'))
            ->assertSessionHas('success');
    }

    public function test_associate_cannot_post_billing_or_invoice_create(): void
    {
        $associate = User::factory()->create(['role' => 'associate']);

        $this->actingAs($associate)->post(route('billing.process'), ['dues' => []])->assertForbidden();
        $this->actingAs($associate)->get(route('invoices.create'))->assertForbidden();
    }
}
