<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ComplianceRiskScore;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\ServiceDue;
use App\Models\WhatsAppMessageLog;
use App\Services\Intelligence\ComplianceRiskScorer;
use App\Services\Intelligence\WhatsAppInboundBot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntelligencePhaseFourTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'whatsapp.inbound_enabled' => true,
            'whatsapp.webhook_verify_token' => 'test-verify-token',
            'whatsapp.firm_reply_name' => 'Test Firm',
        ]);
    }

    public function test_webhook_verify_accepts_matching_token(): void
    {
        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=test-verify-token&hub.challenge=12345')
            ->assertOk()
            ->assertSee('12345', false);
    }

    public function test_webhook_verify_rejects_bad_token(): void
    {
        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=12345')
            ->assertForbidden();
    }

    public function test_webhook_handle_logs_inbound_and_outbound(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200)]);

        config([
            'services.whatsapp.token' => 'test-token',
            'services.whatsapp.phone_number_id' => '123456',
        ]);

        $client = Client::factory()->create([
            'primary_contact_phone' => '9876543210',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'from' => '919876543210',
                                        'id' => 'msg-1',
                                        'timestamp' => '1710000000',
                                        'type' => 'text',
                                        'text' => ['body' => 'GST status please'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson('/webhooks/whatsapp', $payload)->assertOk()->assertSee('EVENT_RECEIVED', false);

        $this->assertDatabaseHas('whatsapp_message_logs', [
            'client_id' => $client->id,
            'direction' => WhatsAppMessageLog::DIRECTION_IN,
            'intent' => 'compliance_status',
        ]);

        $this->assertDatabaseHas('whatsapp_message_logs', [
            'client_id' => $client->id,
            'direction' => WhatsAppMessageLog::DIRECTION_OUT,
        ]);
    }

    public function test_inbound_bot_detects_invoice_intent(): void
    {
        $bot = app(WhatsAppInboundBot::class);

        $this->assertSame('invoice_status', $bot->detectIntent('What is my invoice balance?'));
        $this->assertSame('compliance_status', $bot->detectIntent('Any GST due?'));
        $this->assertSame('help', $bot->detectIntent('HELP'));
    }

    public function test_inbound_bot_invoice_reply_lists_open_invoices(): void
    {
        $client = Client::factory()->create(['name' => 'Phase Four Client']);

        Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-P4',
            'date' => now(),
            'due_date' => now()->subDays(10),
            'status' => Invoice::STATUS_OVERDUE,
            'subtotal' => 2500,
            'total_amount' => 2500,
        ]);

        $bot = app(WhatsAppInboundBot::class);
        $reply = $bot->buildReply($client, 'invoice_status', 'invoice');

        $this->assertStringContainsString('INV-P4', $reply);
        $this->assertStringContainsString('2,500', $reply);
    }

    public function test_compliance_scorer_v2_sets_predicted_miss_for_category_a(): void
    {
        $client = Client::factory()->create(['category' => 'A']);
        $service = Service::create(['name' => 'GSTR-3B', 'code' => 'G3B', 'frequency' => 'Monthly', 'due_day' => 20]);
        $clientService = \App\Models\ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
        ]);

        for ($i = 0; $i < 3; $i++) {
            ServiceDue::create([
                'client_service_id' => $clientService->id,
                'due_date' => now()->subDays(10 + $i),
                'status' => ServiceDue::STATUS_OVERDUE,
            ]);
        }

        for ($i = 0; $i < 3; $i++) {
            ServiceDue::create([
                'client_service_id' => $clientService->id,
                'due_date' => now()->subMonths($i + 2),
                'status' => ServiceDue::STATUS_COMPLETED,
                'completed_at' => now()->subMonths($i + 2)->addDays(5),
            ]);
        }

        app(ComplianceRiskScorer::class)->score();

        $score = ComplianceRiskScore::where('client_id', $client->id)->first();

        $this->assertNotNull($score);
        $this->assertSame(ComplianceRiskScorer::MODEL_VERSION, $score->model_version);
        $this->assertTrue($score->predicted_miss);
        $this->assertGreaterThanOrEqual(60, $score->score);
    }

    public function test_whatsapp_page_shows_webhook_setup(): void
    {
        $partner = \App\Models\User::factory()->create(['role' => 'partner']);

        $this->actingAs($partner)
            ->get(route('whatsapp.index'))
            ->assertOk()
            ->assertSee('Client auto-reply bot', false)
            ->assertSee('/webhooks/whatsapp', false);
    }
}
