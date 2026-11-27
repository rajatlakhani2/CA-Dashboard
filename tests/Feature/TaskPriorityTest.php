<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use App\Support\ModuleAccess;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TaskPriorityTest extends TestCase
{
    use RefreshDatabase;

    private User $partner;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-27 11:00:00');
        $org = Organization::create(['name' => 'Prio Firm', 'slug' => 'priofirm', 'plan' => 'professional', 'seat_limit' => 10, 'is_active' => true]);
        OrganizationContext::set($org->id);
        $this->partner = User::factory()->create(['organization_id' => $org->id, 'role' => 'partner', 'module_access' => ModuleAccess::defaultsForRole('partner')]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function task(array $attrs = []): Task
    {
        return Task::create($attrs + ['title' => 'GST return', 'status' => Task::STATUS_PENDING, 'created_by' => $this->partner->id, 'assigned_to' => $this->partner->id]);
    }

    public function test_rule_high_within_3_days_medium_within_6_else_low(): void
    {
        $this->assertSame('High', Task::priorityForDueDate('2026-11-20'));   // overdue
        $this->assertSame('High', Task::priorityForDueDate('2026-11-27'));   // today
        $this->assertSame('High', Task::priorityForDueDate('2026-11-30'));   // 3 days
        $this->assertSame('Medium', Task::priorityForDueDate('2026-12-01')); // 4 days
        $this->assertSame('Medium', Task::priorityForDueDate('2026-12-03')); // 6 days
        $this->assertSame('Low', Task::priorityForDueDate('2026-12-04'));    // 7 days
        $this->assertSame('Low', Task::priorityForDueDate(null));
    }

    public function test_new_task_defaults_to_auto_and_follows_due_date(): void
    {
        $t = $this->task(['due_date' => '2026-12-02']);
        $this->assertTrue($t->priority_auto);
        $this->assertSame('Medium', $t->priority);

        $t->update(['due_date' => '2026-11-28']);
        $this->assertSame('High', $t->fresh()->priority);
    }

    public function test_chosen_priority_is_kept(): void
    {
        $t = $this->task(['priority' => 'High']); // no due date, priority picked by hand
        $this->assertFalse($t->priority_auto);
        $this->assertSame('High', $t->priority);

        $t->update(['due_date' => '2027-01-31', 'status' => Task::STATUS_IN_PROGRESS]);
        $this->assertSame('High', $t->fresh()->priority);
        Task::refreshAutoPriorities();
        $this->assertSame('High', $t->fresh()->priority);
    }

    public function test_daily_refresh_raises_priority_as_due_date_comes_closer(): void
    {
        $t = $this->task(['due_date' => '2026-12-05']);
        $this->assertSame('Low', $t->priority);

        Carbon::setTestNow('2026-11-30 09:00:00'); // 5 days left
        Task::refreshAutoPriorities();
        $this->assertSame('Medium', $t->fresh()->priority);

        Carbon::setTestNow('2026-12-02 09:00:00'); // 3 days left
        Task::refreshAutoPriorities();
        $this->assertSame('High', $t->fresh()->priority);
    }

    public function test_forms_save_auto_or_manual(): void
    {
        $this->actingAs($this->partner)->post(route('tasks.store'), ['title' => 'TDS return', 'priority' => 'Auto', 'due_date' => '2026-11-29', 'assign_to_me' => 1])->assertRedirect();
        $auto = Task::where('title', 'TDS return')->firstOrFail();
        $this->assertTrue($auto->priority_auto);
        $this->assertSame('High', $auto->priority);

        $this->actingAs($this->partner)->post(route('tasks.store'), ['title' => 'Call bank', 'priority' => 'Medium', 'assign_to_me' => 1])->assertRedirect();
        $manual = Task::where('title', 'Call bank')->firstOrFail();
        $this->assertFalse($manual->priority_auto);
        $this->assertSame('Medium', $manual->priority);

        $this->actingAs($this->partner)->get(route('tasks.edit', $manual))->assertOk()->assertSee('Auto, from due date', false);
        $this->actingAs($this->partner)->get(route('tasks.create'))->assertOk()->assertSee("value: 'Auto'", false);
    }
}
