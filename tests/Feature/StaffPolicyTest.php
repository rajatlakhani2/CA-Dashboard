<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_staff_index_is_scoped_to_own_branch(): void
    {
        [$north, $south] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $north->id, 'name' => 'North Manager']);
        $ownStaff = User::factory()->create(['role' => 'staff', 'branch_id' => $north->id, 'name' => 'North Staff']);
        $otherStaff = User::factory()->create(['role' => 'staff', 'branch_id' => $south->id, 'name' => 'South Staff']);
        $partner = User::factory()->create(['role' => 'partner', 'branch_id' => $north->id, 'name' => 'Firm Partner']);

        $response = $this->actingAs($manager)->get(route('staff.index'));

        $response->assertOk();
        $response->assertSee($manager->name);
        $response->assertSee($ownStaff->name);
        $response->assertDontSee($otherStaff->name);
        $response->assertDontSee($partner->name);
    }

    public function test_manager_cannot_view_or_manage_other_branch_staff(): void
    {
        [$north, $south] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $north->id]);
        $otherStaff = User::factory()->create(['role' => 'staff', 'branch_id' => $south->id]);

        $southTask = $this->createTaskForBranch($south);

        $this->actingAs($manager)
            ->get(route('staff.show', $otherStaff))
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('staff.allot-work', $otherStaff), ['task_id' => $southTask->id])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('staff.send-reminder', $otherStaff), ['type' => 'summary'])
            ->assertForbidden();
    }

    public function test_manager_can_create_staff_only_in_own_branch(): void
    {
        [$north, $south] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $north->id]);

        $this->actingAs($manager)
            ->post(route('staff.store'), $this->staffPayload([
                'role' => 'staff',
                'branch_id' => $north->id,
                'email' => 'own-branch@example.com',
            ]))
            ->assertRedirect(route('staff.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'own-branch@example.com',
            'role' => 'staff',
            'branch_id' => $north->id,
        ]);

        $this->actingAs($manager)
            ->post(route('staff.store'), $this->staffPayload([
                'role' => 'manager',
                'branch_id' => $north->id,
                'email' => 'new-manager@example.com',
            ]))
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('staff.store'), $this->staffPayload([
                'role' => 'staff',
                'branch_id' => $south->id,
                'email' => 'other-branch@example.com',
            ]))
            ->assertForbidden();
    }

    public function test_manager_allotment_is_limited_to_branch_tasks(): void
    {
        [$north, $south] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $north->id]);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $north->id]);
        $northTask = $this->createTaskForBranch($north, ['title' => 'North Task']);
        $southTask = $this->createTaskForBranch($south, ['title' => 'South Task']);

        $this->actingAs($manager)
            ->get(route('staff.show', $staff))
            ->assertOk()
            ->assertSee('North Task')
            ->assertDontSee('South Task');

        $this->actingAs($manager)
            ->post(route('staff.allot-work', $staff), ['task_id' => $northTask->id])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'id' => $northTask->id,
            'assigned_to' => $staff->id,
        ]);

        $this->actingAs($manager)
            ->post(route('staff.allot-work', $staff), ['task_id' => $southTask->id])
            ->assertForbidden();
    }

    public function test_partner_can_manage_staff_across_branches(): void
    {
        [$north, $south] = $this->branches();
        $partner = User::factory()->create(['role' => 'partner', 'branch_id' => $north->id]);
        $otherStaff = User::factory()->create(['role' => 'staff', 'branch_id' => $south->id, 'name' => 'South Staff']);

        $this->actingAs($partner)
            ->get(route('staff.index'))
            ->assertOk()
            ->assertSee($otherStaff->name);

        $this->actingAs($partner)
            ->get(route('staff.show', $otherStaff))
            ->assertOk();
    }

    private function branches(): array
    {
        return [
            Branch::create(['name' => 'North', 'code' => 'NTH']),
            Branch::create(['name' => 'South', 'code' => 'STH']),
        ];
    }

    private function staffPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Staff',
            'email' => 'new-staff@example.com',
            'mobile' => null,
            'role' => 'staff',
            'branch_id' => null,
            'password' => 'password123',
        ], $overrides);
    }

    private function createTaskForBranch(Branch $branch, array $overrides = []): Task
    {
        $client = Client::create([
            'name' => $branch->name . ' Client',
            'client_code' => fake()->unique()->bothify('STF-###'),
            'pan' => fake()->unique()->regexify('[A-Z]{5}[0-9]{4}[A-Z]'),
            'status' => 'Active',
            'category' => 'A',
            'branch_id' => $branch->id,
        ]);

        return Task::create(array_merge([
            'title' => fake()->sentence(3),
            'client_id' => $client->id,
            'priority' => 'Normal',
            'status' => 'Pending',
            'due_date' => now()->addDays(5),
            'assigned_to' => null,
            'created_by' => User::factory()->create(['role' => 'manager', 'branch_id' => $branch->id])->id,
        ], $overrides));
    }
}
