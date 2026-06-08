<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\WorkloadPlannerBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkloadPlannerVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_workload_hides_seed_placeholders_and_legacy_duplicates(): void
    {
        $organization = Organization::create([
            'slug' => 'rla',
            'name' => 'RL Associates',
            'plan' => Organization::PLAN_PROFESSIONAL,
            'seat_limit' => 25,
            'is_active' => true,
        ]);

        User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'associate',
            'name' => 'Firm Associate',
            'email' => 'associate@rlassociates.in',
        ]);

        User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'article',
            'name' => 'Articles',
            'email' => 'article@rlassociates.in',
        ]);

        User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'article',
            'name' => 'Article Clerk',
            'email' => 'article2@rlassociates.in',
        ]);

        User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'associate',
            'name' => 'Firm Associate',
            'email' => 'associate2@rlassociates.in',
        ]);

        User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'associate',
            'name' => 'Nilesh Bhai',
            'email' => 'nilesh@rlassociates.in',
        ]);

        $partner = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'partner',
            'name' => 'Rajat Lakhani',
            'email' => 'rajat@rlassociates.in',
        ]);

        $data = app(WorkloadPlannerBuilder::class)->build($partner, null);
        $names = collect($data['members'])->map(fn ($row) => $row->user->name)->all();

        $this->assertSame([], $names);
    }

    public function test_workload_hides_seed_placeholders_even_with_legacy_emails(): void
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
            'role' => 'article',
            'name' => 'Articles',
            'email' => 'article@rla.local',
        ]);

        User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'associate',
            'name' => 'Firm Associate',
            'email' => 'associate@rla.local',
        ]);

        $data = app(WorkloadPlannerBuilder::class)->build($partner, null);

        $this->assertSame([], collect($data['members'])->map(fn ($row) => $row->user->name)->all());
    }

    public function test_workload_shows_staff_added_with_real_accounts(): void
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
            'role' => 'staff',
            'name' => 'Priya Sharma',
            'email' => 'priya@rlassociates.in',
        ]);

        User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'associate',
            'name' => 'Firm Associate',
            'email' => 'associate@rlassociates.in',
        ]);

        $data = app(WorkloadPlannerBuilder::class)->build($partner, null);
        $names = collect($data['members'])->map(fn ($row) => $row->user->name)->all();

        $this->assertSame(['Priya Sharma'], $names);
    }

    public function test_workload_scopes_members_to_actor_organization(): void
    {
        $orgA = Organization::create([
            'slug' => 'rla',
            'name' => 'RL Associates',
            'plan' => Organization::PLAN_PROFESSIONAL,
            'seat_limit' => 25,
            'is_active' => true,
        ]);

        $orgB = Organization::create([
            'slug' => 'other',
            'name' => 'Other Firm',
            'plan' => Organization::PLAN_PROFESSIONAL,
            'seat_limit' => 25,
            'is_active' => true,
        ]);

        User::factory()->create([
            'organization_id' => $orgB->id,
            'role' => 'staff',
            'name' => 'Other Firm Staff',
            'email' => 'other@example.com',
        ]);

        $partner = User::factory()->create([
            'organization_id' => $orgA->id,
            'role' => 'partner',
            'name' => 'Rajat Lakhani',
            'email' => 'rajat@rlassociates.in',
        ]);

        User::factory()->create([
            'organization_id' => $orgA->id,
            'role' => 'staff',
            'name' => 'Added Staff',
            'email' => 'staff@rlassociates.in',
        ]);

        $data = app(WorkloadPlannerBuilder::class)->build($partner, null);
        $names = collect($data['members'])->map(fn ($row) => $row->user->name)->all();

        $this->assertSame(['Added Staff'], $names);
    }
}
