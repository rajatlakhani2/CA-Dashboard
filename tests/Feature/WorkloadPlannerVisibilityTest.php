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

    public function test_workload_hides_nilesh_and_duplicate_article_users(): void
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
            'name' => 'Nilesh Bhai',
            'email' => 'nilesh@rlassociates.in',
        ]);

        $partner = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => 'partner',
            'name' => 'Rajat Lakhani',
            'email' => 'rajat@rlassociates.in',
        ]);

        $builder = app(WorkloadPlannerBuilder::class);
        $data = $builder->build($partner, null);

        $names = collect($data['members'])->map(fn ($row) => $row->user->name)->all();

        $this->assertContains('Firm Associate', $names);
        $this->assertContains('Articles', $names);
        $this->assertNotContains('Nilesh Bhai', $names);
        $this->assertNotContains('Article Clerk', $names);
        $this->assertSame(2, count($names));
    }
}
