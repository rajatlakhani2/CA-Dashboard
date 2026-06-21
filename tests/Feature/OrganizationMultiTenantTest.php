<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationMultiTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_for_organization_partner(): void
    {
        $org = Organization::create([
            'name' => 'RL Associates',
            'slug' => 'rl-associates',
            'plan' => 'professional',
            'seat_limit' => 10,
        ]);

        $user = User::factory()->create([
            'organization_id' => $org->id,
            'role' => 'partner',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Executive Summary', false);
        $response->assertSee('data-dashboard-widget="exec-kpis"', false);
    }

    public function test_middleware_sets_organization_context_for_authenticated_requests(): void
    {
        $org = Organization::create([
            'name' => 'Scoped Firm',
            'slug' => 'scoped-firm',
            'plan' => 'starter',
            'seat_limit' => 5,
        ]);

        $user = User::factory()->create([
            'organization_id' => $org->id,
            'role' => 'partner',
        ]);

        $this->actingAs($user)->get(route('dashboard'));

        $this->assertSame($org->id, OrganizationContext::id());
    }
}
