<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\FirmAlert;
use App\Models\User;
use App\Services\Intelligence\AiAssistantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntelligencePhaseOneTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_scan_and_dismiss_firm_alert(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $alert = FirmAlert::create([
            'type' => FirmAlert::TYPE_DUPLICATE_PAN,
            'severity' => FirmAlert::SEVERITY_CRITICAL,
            'title' => 'Test',
            'message' => 'Test message',
            'fingerprint' => 'test:1',
        ]);

        $this->actingAs($partner)
            ->post(route('firm-alerts.scan'))
            ->assertRedirect();

        $this->actingAs($partner)
            ->post(route('firm-alerts.dismiss', $alert))
            ->assertRedirect();

        $this->assertNotNull($alert->fresh()->dismissed_at);
    }

    public function test_staff_cannot_use_client_ai(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $client = Client::factory()->create(['manager_id' => $staff->id]);

        $this->actingAs($staff)
            ->postJson(route('clients.ai.summarize', $client))
            ->assertForbidden();
    }

    public function test_partner_ai_summarize_when_enabled(): void
    {
        config([
            'ai.enabled' => true,
            'ai.openai.api_key' => 'test-key',
            'ai.openai.model' => 'gpt-4o-mini',
            'ai.openai.base_url' => 'https://api.openai.com/v1',
        ]);

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => '- Client is active\n- 2 open dues']],
                ],
            ], 200),
        ]);

        $partner = User::factory()->create(['role' => 'partner']);
        $client = Client::factory()->create();

        $this->actingAs($partner)
            ->postJson(route('clients.ai.summarize', $client))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('text', '- Client is active\n- 2 open dues');

        $this->assertTrue(app(AiAssistantService::class)->isEnabled());
    }

    public function test_partner_dashboard_shows_firm_alerts_section(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        FirmAlert::create([
            'type' => FirmAlert::TYPE_COMPLIANCE_STACK,
            'severity' => FirmAlert::SEVERITY_WARNING,
            'title' => 'Backlog alert',
            'message' => 'Client has many dues',
            'fingerprint' => 'stack:99',
        ]);

        $this->actingAs($partner)
            ->get(route('partner.dashboard'))
            ->assertRedirect(route('dashboard', ['tab' => 'firm']));

        $this->actingAs($partner)
            ->get(route('dashboard', ['tab' => 'firm']))
            ->assertOk()
            ->assertSee('Firm alerts', false)
            ->assertSee('Backlog alert', false);
    }
}
