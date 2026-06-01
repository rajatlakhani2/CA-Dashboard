<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\WorkloadPlannerBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkloadPlannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_access_workload_planner(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get(route('workload.index'))->assertForbidden();
    }

    public function test_partner_sees_team_columns_and_metrics(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $member = User::factory()->create(['role' => 'staff', 'name' => 'Workload Tester']);
        $client = Client::factory()->create();

        Task::create([
            'client_id' => $client->id,
            'assigned_to' => $member->id,
            'title' => 'Overdue filing',
            'status' => Task::STATUS_PENDING,
            'due_date' => now()->subDays(3),
            'priority' => 'High',
            'created_by' => $partner->id,
        ]);

        $this->actingAs($partner)
            ->get(route('workload.index'))
            ->assertOk()
            ->assertSee('Workload Planner', false)
            ->assertSee('Workload Tester', false)
            ->assertSee('Overdue filing', false);
    }

    public function test_manager_can_reassign_task_within_branch(): void
    {
        $branch = Branch::create(['name' => 'WL Branch', 'code' => 'WLB']);
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branch->id]);
        $from = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id]);
        $to = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id, 'name' => 'New Assignee']);
        $client = Client::factory()->create(['branch_id' => $branch->id]);

        $task = Task::create([
            'client_id' => $client->id,
            'assigned_to' => $from->id,
            'title' => 'Move me',
            'status' => Task::STATUS_PENDING,
            'due_date' => now()->addDays(5),
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->patch(route('workload.reassign'), [
                'task_id' => $task->id,
                'assigned_to' => $to->id,
            ])
            ->assertRedirect(route('workload.index'));

        $this->assertSame($to->id, $task->fresh()->assigned_to);
    }

    public function test_builder_includes_logged_hours(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $member = User::factory()->create(['role' => 'staff']);
        $client = Client::factory()->create();

        $task = Task::create([
            'client_id' => $client->id,
            'assigned_to' => $member->id,
            'title' => 'Timed work',
            'status' => Task::STATUS_IN_PROGRESS,
            'created_by' => $partner->id,
        ]);

        TimeEntry::create([
            'task_id' => $task->id,
            'user_id' => $member->id,
            'date' => now()->subDays(2),
            'hours' => 4.5,
            'is_billable' => true,
        ]);

        $plan = app(WorkloadPlannerBuilder::class)->build($partner);
        $row = $plan['members']->firstWhere(fn ($m) => $m->user->id === $member->id);

        $this->assertNotNull($row);
        $this->assertSame(4.5, $row->logged_hours_30d);
        $this->assertGreaterThanOrEqual(2, $row->planned_hours);
    }
}
