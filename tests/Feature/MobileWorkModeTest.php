<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileWorkModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_login_redirects_to_my_day(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'module_access' => ['tasks' => true, 'dashboard' => true],
        ]);

        $this->post(route('login'), [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertRedirect(route('tasks.my-day'));
    }

    public function test_staff_can_append_note_on_assigned_task(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'module_access' => ['tasks' => true]]);
        $task = $this->taskForUser($staff, ['description' => 'Existing']);

        $this->actingAs($staff)
            ->patch(route('tasks.mobile-note', $task), ['note' => 'Called client'])
            ->assertRedirect();

        $this->assertStringContainsString('Called client', $task->fresh()->description);
        $this->assertStringContainsString($staff->name, $task->fresh()->description);
    }

    public function test_staff_can_log_time_from_my_day_endpoint(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'module_access' => ['tasks' => true]]);
        $task = $this->taskForUser($staff);

        $this->actingAs($staff)
            ->post(route('tasks.mobile-time', $task), [
                'hours' => 1.5,
                'date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('time_entries', [
            'task_id' => $task->id,
            'user_id' => $staff->id,
            'hours' => 1.5,
        ]);
    }

    public function test_staff_cannot_append_note_on_another_users_task(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $other = User::factory()->create(['role' => 'staff']);
        $task = $this->taskForUser($other);

        $this->actingAs($staff)
            ->patch(route('tasks.mobile-note', $task), ['note' => 'Nope'])
            ->assertForbidden();
    }

    private function taskForUser(User $user, array $overrides = []): Task
    {
        return Task::create(array_merge([
            'title' => 'Mobile test task',
            'status' => Task::STATUS_PENDING,
            'priority' => 'Normal',
            'assigned_to' => $user->id,
            'created_by' => $user->id,
            'due_date' => now()->addDay(),
        ], $overrides));
    }
}
