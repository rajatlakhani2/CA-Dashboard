<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\ServiceDue;
use App\Services\BillingDraftInvoiceBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingDraftInvoiceBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_throws_when_no_unbilled_items(): void
    {
        $client = Client::factory()->create();
        $builder = new BillingDraftInvoiceBuilder;

        $this->expectException(\RuntimeException::class);
        $builder->createDraftForClient($client);
    }

    public function test_creates_draft_and_links_due(): void
    {
        $client = Client::factory()->create();
        $service = Service::create(['name' => 'Audit', 'code' => 'AUD', 'frequency' => 'Annually']);
        $clientService = ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'status' => ClientService::STATUS_ACTIVE,
        ]);
        $due = ServiceDue::create([
            'client_service_id' => $clientService->id,
            'due_date' => now(),
            'status' => ServiceDue::STATUS_COMPLETED,
            'billing_status' => ServiceDue::BILLING_STATUS_UNBILLED,
            'billing_amount' => 3000,
        ]);

        $invoice = (new BillingDraftInvoiceBuilder)->createDraftForClient($client);

        $this->assertSame(Invoice::STATUS_DRAFT, $invoice->status);
        $this->assertSame(ServiceDue::BILLING_STATUS_BILLED, $due->fresh()->billing_status);
        $this->assertSame($invoice->id, $due->fresh()->invoice_id);
        $this->assertGreaterThan(0, $invoice->items()->count());
    }
}
