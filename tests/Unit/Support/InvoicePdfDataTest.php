<?php

namespace Tests\Unit\Support;

use App\Models\Client;
use App\Models\Invoice;
use App\Support\InvoicePdfData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePdfDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_for_builds_pdf_payload_with_amount_in_words(): void
    {
        $client = Client::factory()->create();
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'TEST/001',
            'date' => now(),
            'due_date' => now()->addDays(15),
            'status' => Invoice::STATUS_DRAFT,
            'subtotal' => 1000,
            'tax' => 180,
            'total_amount' => 1180,
        ]);

        $data = InvoicePdfData::for($invoice);

        $this->assertSame($invoice->id, $data['invoice']->id);
        $this->assertArrayHasKey('firm', $data);
        $this->assertArrayHasKey('amountInWords', $data);
        $this->assertStringContainsString('Rupees', $data['amountInWords']);
        $this->assertSame(1000.0, (float) $data['taxableValue']);
    }

    public function test_setting_keys_returns_expected_fields(): void
    {
        $keys = InvoicePdfData::settingKeys();
        $this->assertContains('invoice_number_prefix', $keys);
        $this->assertContains('bank_ifsc', $keys);
    }

    public function test_default_terms_is_non_empty(): void
    {
        $this->assertNotEmpty(InvoicePdfData::defaultTerms());
    }
}
