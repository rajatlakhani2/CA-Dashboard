<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Organization;
use App\Models\Service;
use App\Models\ServiceDue;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneBoardAndCleanupTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $partner;
    private User $staff;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->org = Organization::create(['name' => 'Board Firm', 'slug' => 'boardfirm', 'plan' => 'professional', 'seat_limit' => 10]);
        $this->partner = User::factory()->create(['role' => 'partner', 'organization_id' => $this->org->id, 'dashboard_layout' => 'board']);
        $this->staff = User::factory()->create(['role' => 'staff', 'organization_id' => $this->org->id]);
        $this->client = Client::factory()->create(['organization_id' => $this->org->id, 'manager_id' => $this->partner->id, 'primary_contact_phone' => '9876543210']);
    }

    private function task(int $daysLate, array $extra = []): Task
    {
        return Task::create(array_merge([
            'title' => 'Late task ' . $daysLate,
            'status' => Task::STATUS_PENDING,
            'priority' => 'Normal',
            'assigned_to' => $this->staff->id,
            'created_by' => $this->partner->id,
            'client_id' => $this->client->id,
            'due_date' => now()->subDays($daysLate),
        ], $extra));
    }

    private function filing(int $daysLate): ServiceDue
    {
        $service = Service::firstOrCreate(['code' => 'GSTR3B'], ['name' => 'GSTR-3B', 'frequency' => 'Monthly', 'due_day' => 20, 'description' => 'x']);
        $cs = ClientService::firstOrCreate(['client_id' => $this->client->id, 'service_id' => $service->id], ['status' => 'Active']);

        return ServiceDue::create(['client_service_id' => $cs->id, 'due_date' => now()->subDays($daysLate), 'status' => ServiceDue::STATUS_PENDING]);
    }

    public function test_board_has_phone_tabs_age_groups_and_quick_panel_data(): void
    {
        $this->task(2);
        $this->task(15);
        $this->task(60);

        $html = $this->actingAs($this->partner)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('data-phone-tabs', $html);
        $this->assertStringContainsString('data-tab-count="over"', $html);
        $this->assertStringContainsString('Last 7 days', $html);
        $this->assertStringContainsString('8–30 days late', $html);
        $this->assertStringContainsString('Over a month late', $html);
        $this->assertStringContainsString('id="dash-sheet"', $html);
        $this->assertStringContainsString('id="dash-filter-sheet"', $html);
        // Quick panel knows the client's phone and the task's note / time links.
        $this->assertStringContainsString('9876543210', $html);
        $this->assertStringContainsString('mobile-note', $html);
        // Laptop drag target is still there.
        $this->assertStringContainsString('data-drop-col="done"', $html);
    }

    public function test_cleanup_link_only_for_partners_with_a_pile_of_overdue(): void
    {
        foreach (range(1, 5) as $i) {
            $this->task(10 + $i);
        }
        $this->actingAs($this->partner)->get(route('dashboard'))->assertSee('Clean up overdue');
        $this->actingAs($this->staff)->get(route('dashboard.cleanup'))->assertForbidden();
    }

    public function test_cleanup_lists_overdue_tasks_and_filings_filtered_by_age(): void
    {
        $this->task(3);
        $this->task(45);
        $this->filing(100);
        $this->task(0, ['due_date' => now()->addDays(2), 'title' => 'Not late']);

        $this->actingAs($this->partner)->get(route('dashboard.cleanup'))
            ->assertOk()
            ->assertSee('Late task 3')->assertSee('Late task 45')->assertSee('GSTR-3B')
            ->assertDontSee('Not late');

        $this->actingAs($this->partner)->get(route('dashboard.cleanup', ['age' => 30]))
            ->assertDontSee('Late task 3')->assertSee('Late task 45')->assertSee('GSTR-3B');

        $this->actingAs($this->partner)->get(route('dashboard.cleanup', ['type' => 'tasks']))
            ->assertSee('Late task 45')->assertDontSee('GSTR-3B');
    }

    public function test_bulk_mark_done_closes_only_the_ticked_items(): void
    {
        $a = $this->task(40);
        $b = $this->task(50);
        $keep = $this->task(60);
        $due = $this->filing(90);

        $this->actingAs($this->partner)
            ->from(route('dashboard.cleanup'))
            ->post(route('dashboard.cleanup.apply'), ['action' => 'done', 'tasks' => [$a->id, $b->id], 'filings' => [$due->id]])
            ->assertRedirect(route('dashboard.cleanup'))
            ->assertSessionHas('success', '2 tasks and 1 filing marked done.');

        $this->assertSame(Task::STATUS_COMPLETED, $a->fresh()->status);
        $this->assertSame(Task::STATUS_COMPLETED, $b->fresh()->status);
        $this->assertSame(Task::STATUS_PENDING, $keep->fresh()->status);
        $this->assertSame(ServiceDue::STATUS_COMPLETED, $due->fresh()->status);
        $this->assertSame($this->partner->id, $due->fresh()->completed_by);
    }

    public function test_bulk_move_changes_task_dates_but_never_filings(): void
    {
        $t = $this->task(20);
        $due = $this->filing(20);
        $date = now()->addDays(5)->toDateString();

        $this->actingAs($this->partner)
            ->post(route('dashboard.cleanup.apply'), ['action' => 'move', 'date' => $date, 'tasks' => [$t->id], 'filings' => [$due->id]])
            ->assertSessionHas('success');

        $this->assertSame($date, $t->fresh()->due_date->toDateString());
        $this->assertSame(now()->subDays(20)->toDateString(), $due->fresh()->due_date->toDateString());
        $this->assertSame(ServiceDue::STATUS_PENDING, $due->fresh()->status);
    }

    public function test_move_needs_a_date_from_today_on(): void
    {
        $t = $this->task(20);

        $this->actingAs($this->partner)
            ->post(route('dashboard.cleanup.apply'), ['action' => 'move', 'tasks' => [$t->id], 'date' => now()->subDay()->toDateString()])
            ->assertSessionHasErrors('date');
    }

    public function test_cleanup_cannot_touch_another_firms_work(): void
    {
        $otherOrg = Organization::create(['name' => 'Other', 'slug' => 'otherboard', 'plan' => 'professional', 'seat_limit' => 5]);
        \App\Support\OrganizationContext::set($otherOrg->id);
        $otherUser = User::factory()->create(['role' => 'partner', 'organization_id' => $otherOrg->id]);
        $foreign = Task::create(['title' => 'Foreign', 'status' => Task::STATUS_PENDING, 'priority' => 'Normal', 'assigned_to' => $otherUser->id, 'created_by' => $otherUser->id, 'due_date' => now()->subDays(30)]);
        \App\Support\OrganizationContext::set($this->org->id);

        $this->actingAs($this->partner)
            ->post(route('dashboard.cleanup.apply'), ['action' => 'done', 'tasks' => [$foreign->id]]);

        $this->assertSame(Task::STATUS_PENDING, Task::withoutGlobalScopes()->find($foreign->id)->status);
    }
}
