<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationWorkspaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTeamVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_nilesh_user_is_hidden_from_workspace_team(): void
    {
        $organization = Organization::create([
            'slug' => 'rla',
            'name' => 'RL Associates',
            'plan' => Organization::PLAN_PROFESSIONAL,
            'seat_limit' => 25,
            'is_active' => true,
        ]);

        $partner = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'partner',
            'name' => 'Rajat Lakhani',
            'email' => 'rajat@rlassociates.in',
        ]);

        User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'associate',
            'name' => 'Nilesh Bhai',
            'email' => 'nilesh@rlassociates.in',
        ]);

        $workspace = app(OrganizationWorkspaceService::class)->forUser($partner);
        $names = collect($workspace['team'])->pluck('name')->all();

        $this->assertContains('Rajat Lakhani', $names);
        $this->assertNotContains('Nilesh Bhai', $names);
    }
}
