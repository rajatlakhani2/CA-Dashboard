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
