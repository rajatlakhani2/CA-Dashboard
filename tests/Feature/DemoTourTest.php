<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\DemoTourService;
use App\Support\DemoWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoTourTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_tour_shows_when_session_pending(): void
    {
        $organization = Organization::create([
            'slug' => DemoWorkspace::SLUG,
            'name' => 'Vouchex Demo Firm',
            'plan' => Organization::PLAN_PROFESSIONAL,
            'seat_limit' => 25,
            'is_active' => true,
            'is_demo' => true,
        ]);

        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'email' => DemoWorkspace::EMAIL,
            'role' => 'partner',
            'demo_tour_completed_at' => now(),
        ]);

        $this->withSession(['demo_tour_pending' => true]);
        $this->assertTrue(app(DemoTourService::class)->shouldShowWelcome($user));
    }

    public function test_demo_tour_shows_for_demo_user_until_completed(): void
    {
        $organization = Organization::create([
            'slug' => DemoWorkspace::SLUG,
            'name' => 'Vouchex Demo Firm',
            'plan' => Organization::PLAN_PROFESSIONAL,
            'seat_limit' => 25,
            'is_active' => true,
            'is_demo' => true,
        ]);

        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'email' => DemoWorkspace::EMAIL,
            'role' => 'partner',
        ]);

        $payload = app(DemoTourService::class)->payloadFor($user);

        $this->assertTrue($payload['show']);
        $this->assertTrue($payload['isDemo']);
        $this->assertNotEmpty($payload['steps']);
        $this->assertSame('workflow-v6-live-20260609', $payload['version']);
        $this->assertSame('modal', $payload['steps'][0]['type']);
        $this->assertArrayNotHasKey('element', $payload['steps'][0]);
        $this->assertSame('📱', $payload['steps'][0]['emoji']);
        $this->assertStringContainsString('mission-control', $payload['steps'][1]['element']);
        $this->assertSame('My Day', $payload['steps'][2]['title']);
        $this->assertSame('my-day-start', $payload['steps'][2]['play']);
        $this->assertSame('Client 360°', $payload['steps'][3]['title']);
        $this->assertSame('Create a task live', $payload['steps'][5]['title']);
        $this->assertSame('task-create-live', $payload['steps'][5]['play']);
        $this->assertTrue(collect($payload['steps'])->pluck('title')->contains('Send invoice by email & WhatsApp'));
        $this->assertTrue(collect($payload['steps'])->contains('title', 'The Pulse'));
        $this->assertSame('Neha Kapoor', $payload['staffName']);
    }

    public function test_demo_tour_hidden_after_completion(): void
    {
        $organization = Organization::create([
            'slug' => DemoWorkspace::SLUG,
            'name' => 'Vouchex Demo Firm',
            'plan' => Organization::PLAN_PROFESSIONAL,
            'seat_limit' => 25,
            'is_active' => true,
            'is_demo' => true,
        ]);

        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'email' => DemoWorkspace::EMAIL,
            'role' => 'partner',
            'demo_tour_completed_at' => now(),
        ]);

        $this->assertFalse(app(DemoTourService::class)->shouldShowWelcome($user));
    }

    public function test_demo_user_can_dismiss_tour(): void
    {
        $organization = Organization::create([
            'slug' => DemoWorkspace::SLUG,
            'name' => 'Vouchex Demo Firm',
            'plan' => Organization::PLAN_PROFESSIONAL,
            'seat_limit' => 25,
            'is_active' => true,
            'is_demo' => true,
        ]);

        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'email' => DemoWorkspace::EMAIL,
            'role' => 'partner',
        ]);

        $this->actingAs($user)
            ->post(route('demo-tour.dismiss'))
            ->assertOk();

        $this->assertNotNull($user->fresh()->demo_tour_completed_at);
    }
}
