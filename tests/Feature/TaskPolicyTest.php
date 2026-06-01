<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_only_sees_assigned_or_created_tasks(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'name' => 'Staff One']);
        $otherStaff = User::factory()->create(['role' => 'staff', 'name' => 'Staff Two']);

        $ownTask = $this->createTask([
            'title' => 'Visible Staff Task',
            'assigned_to' => $staff->id,
            'created_by' => $otherStaff->id,
        ]);
        $otherTask = $this->createTask([
            'title' => 'Hidden Staff Task',
            'assigned_to' => $otherStaff->id,
            'created_by' => $otherStaff->id,
        ]);

        $response = $this->actingAs($staff)->get(route('tasks.index'));

        $response->assertOk();
        $response->assertSee($ownTask->title);
        $response->assertDontSee($otherTask->title);
    }

    public function test_staff_cannot_edit_another_staff_task(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $otherStaff = User::factory()->create(['role' => 'staff']);
        $task = $this->createTask([
            'assigned_to' => $otherStaff->id,
            'created_by' => $otherStaff->id,
        ]);

        $this->actingAs($staff)
            ->get(route('tasks.edit', $task))
            ->assertForbidden();
    }

    public function test_staff_can_update_own_task_status(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $task = $this->createTask([
            'assigned_to' => $staff->id,
            'created_by' => $staff->id,
        ]);

        $this->actingAs($staff)
            ->patchJson(route('tasks.update-status', $task), ['status' => 'Completed'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'Completed',
        ]);
    }

    public function test_staff_cannot_update_or_delete_another_staff_task(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $otherStaff = User::factory()->create(['role' => 'staff']);
        $task = $this->createTask([
            'assigned_to' => $otherStaff->id,
            'created_by' => $otherStaff->id,
        ]);

        $this->actingAs($staff)
            ->patchJson(route('tasks.update-status', $task), ['status' => 'Completed'])
            ->assertForbidden();

        $this->actingAs($staff)
            ->delete(route('tasks.destroy', $task))
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'Pending',
            'deleted_at' => null,
        ]);
    }

    public function test_staff_cannot_assign_tasks_to_other_users_or_mark_foc(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $otherStaff = User::factory()->create(['role' => 'staff']);
        $task = $this->createTask([
            'assigned_to' => $staff->id,
            'created_by' => $staff->id,
        ]);

        $this->actingAs($staff)
            ->post(route('tasks.store'), [
                'title' => 'Delegated Staff Task',
                'assigned_to' => $otherStaff->id,
                'priority' => 'Normal',
                'due_date' => now()->addDay()->format('Y-m-d'),
            ])
            ->assertForbidden();

        $this->actingAs($staff)
            ->patch(route('tasks.mark-foc', $task))
            ->assertForbidden();
    }

    public function test_manager_can_see_manage_assign_and_mark_foc_tasks(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $staff = User::factory()->create(['role' => 'staff']);
        $task = $this->createTask([
            'title' => 'Team Wide Task',
            'assigned_to' => $staff->id,
            'created_by' => $staff->id,
        ]);

        $this->actingAs($manager)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee($task->title);

        $this->actingAs($manager)
            ->patch(route('tasks.mark-foc', $task))
            ->assertRedirect();

        $this->actingAs($manager)
            ->post(route('tasks.store'), [
                'title' => 'Manager Assigned Task',
                'assigned_to' => $staff->id,
                'priority' => 'High',
                'due_date' => now()->addDays(2)->format('Y-m-d'),
            ])
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'is_billed' => true,
        ]);
        $this->assertDatabaseHas('tasks', [
            'title' => 'Manager Assigned Task',
            'assigned_to' => $staff->id,
            'created_by' => $manager->id,
        ]);
    }

    private function createTask(array $overrides = []): Task
    {
        $client = Client::create([
            'name' => fake()->company(),
            'client_code' => fake()->unique()->bothify('TASK-###'),
            'pan' => fake()->unique()->regexify('[A-Z]{5}[0-9]{4}[A-Z]'),
            'status' => 'Active',
            'category' => 'A',
        ]);

        return Task::create(array_merge([
            'title' => fake()->sentence(3),
            'client_id' => $client->id,
            'priority' => 'Normal',
            'status' => 'Pending',
            'due_date' => now()->addDays(5),
        ], $overrides));
    }
}
