<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\DocumentIngestion;
use App\Models\Invoice;
use App\Models\User;
use App\Support\InvoicePaymentLinkBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IntelligencePhaseThreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_payment_link_builds_from_upi_setting(): void
    {
        \App\Models\Setting::set('bank_upi', 'firm@upi');
        \App\Models\Setting::set('company_name', 'RLA Test');

        $client = Client::factory()->create();
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-PAY1',
            'date' => now(),
            'due_date' => now()->addDays(15),
            'status' => Invoice::STATUS_OVERDUE,
            'subtotal' => 1000,
            'total_amount' => 1000,
        ]);

        $url = app(InvoicePaymentLinkBuilder::class)->build($invoice);

        $this->assertNotNull($url);
        $this->assertStringContainsString('upi://pay', $url);
        $this->assertStringContainsString('firm%40upi', $url);
    }

    public function test_partner_can_issue_portal_link_and_client_can_upload(): void
    {
        Storage::fake('local');
        $partner = User::factory()->create(['role' => 'partner']);
        $client = Client::factory()->create();

        $this->actingAs($partner)
            ->post(route('clients.portal-link', $client))
            ->assertRedirect();

        $portalUrl = session('portal_url');
        $this->assertNotEmpty($portalUrl);
        preg_match('#/portal/([A-Za-z0-9]+)#', $portalUrl, $m);
        $token = $m[1];
        $this->assertNotEmpty($token);

        $this->get(route('portal.home', ['token' => $token]))->assertOk()->assertSee($client->name, false);

        $file = UploadedFile::fake()->create('gstr3b_notice.pdf', 100, 'application/pdf');

        $this->post(route('portal.upload', ['token' => $token]), [
            'document' => $file,
            'notes' => 'Please process',
        ])->assertRedirect();

        $this->assertDatabaseHas('document_ingestions', [
            'client_id' => $client->id,
            'source' => 'portal',
            'status' => DocumentIngestion::STATUS_PENDING,
        ]);
    }

    public function test_expired_or_unknown_portal_token_shows_friendly_page(): void
    {
        $this->get(route('portal.home', ['token' => str_repeat('x', 48)]))
            ->assertForbidden()
            ->assertSee('invalid or has expired', false);
    }

    public function test_document_review_confirm_creates_task(): void
    {
        Storage::fake('local');
        $partner = User::factory()->create(['role' => 'partner']);
        $client = Client::factory()->create();

        $path = 'document-ingestions/'.$client->id.'/test.pdf';
        Storage::disk('local')->put($path, 'pdf');

        $ingestion = DocumentIngestion::create([
            'client_id' => $client->id,
            'uploaded_by' => $partner->id,
            'source' => DocumentIngestion::SOURCE_FIRM,
            'original_filename' => 'itr_notice.pdf',
            'stored_path' => $path,
            'status' => DocumentIngestion::STATUS_PENDING,
            'extracted_fields' => ['document_type' => 'ITR'],
        ]);

        $this->actingAs($partner)
            ->post(route('document-ingestions.confirm', $ingestion), [
                'document_type' => 'ITR',
                'create_task' => '1',
                'task_title' => 'Process ITR notice',
            ])
            ->assertRedirect(route('document-ingestions.index'));

        $ingestion->refresh();
        $this->assertSame(DocumentIngestion::STATUS_CONFIRMED, $ingestion->status);
        $this->assertNotNull($ingestion->created_task_id);
    }
}
