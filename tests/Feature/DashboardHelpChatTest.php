<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\DashboardHelpChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardHelpChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_help_chat_returns_mission_control_guidance(): void
    {
        $organization = Organization::create([
            'slug' => 'test-firm',
            'name' => 'Test Firm',
            'plan' => Organization::PLAN_PROFESSIONAL,
            'seat_limit' => 10,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'partner',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('dashboard.help-chat'), [
                'message' => 'What is Mission Control?',
            ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('source', 'help');

        $this->assertStringContainsString('Mission Control', (string) $response->json('text'));
    }

    public function test_service_matches_invoice_email_topic(): void
    {
        $organization = Organization::create([
            'slug' => 'test-firm-2',
            'name' => 'Test Firm 2',
            'plan' => Organization::PLAN_PROFESSIONAL,
            'seat_limit' => 10,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'partner',
        ]);

        $result = app(DashboardHelpChatService::class)->reply($user, 'How do I email an invoice?');

        $this->assertTrue($result['ok']);
        $this->assertStringContainsString('Email invoice', $result['text']);
        $this->assertNotNull($result['url']);
    }
}
