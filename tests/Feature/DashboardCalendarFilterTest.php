<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Service;
use App\Models\ServiceDue;
use App\Models\Task;
use App\Models\User;
use App\Services\DashboardCalendarBuilder;
use App\Services\DashboardCalendarFilters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardCalendarFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_events_endpoint_filters_by_service(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $client = Client::factory()->create();

        $gst = Service::create(['name' => 'GSTR-1', 'code' => 'G1', 'frequency' => 'Monthly', 'due_day' => 11]);
        $itr = Service::create(['name' => 'ITR', 'code' => 'ITR', 'frequency' => 'Annually', 'due_day' => 31, 'due_month' => 7]);

        $gstCs = \App\Models\ClientService::create(['client_id' => $client->id, 'service_id' => $gst->id]);
        $itrCs = \App\Models\ClientService::create(['client_id' => $client->id, 'service_id' => $itr->id]);

        $gstDue = ServiceDue::create([
            'client_service_id' => $gstCs->id,
            'due_date' => now()->addDays(5),
            'status' => ServiceDue::STATUS_PENDING,
        ]);
        ServiceDue::create([
            'client_service_id' => $itrCs->id,
            'due_date' => now()->addDays(5),
            'status' => ServiceDue::STATUS_PENDING,
        ]);

        $response = $this->actingAs($partner)->getJson(route('calendar.events', [
            'show_tasks' => '0',
            'show_dues' => '1',
            'service_id' => $gst->id,
        ]));

        $response->assertOk();
        $ids = collect($response->json('events'))->pluck('id')->all();

        $this->assertContains('due_' . $gstDue->id, $ids);
        $this->assertCount(1, $ids);
    }

    public function test_builder_filters_tasks_by_assignee(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $alice = User::factory()->create(['role' => 'staff', 'name' => 'Alice']);
        $bob = User::factory()->create(['role' => 'staff', 'name' => 'Bob']);
        $client = Client::factory()->create();

        Task::create([
            'client_id' => $client->id,
            'assigned_to' => $alice->id,
            'title' => 'Alice task',
            'status' => Task::STATUS_PENDING,
            'due_date' => now()->addDays(3),
            'created_by' => $partner->id,
        ]);
        Task::create([
            'client_id' => $client->id,
            'assigned_to' => $bob->id,
            'title' => 'Bob task',
            'status' => Task::STATUS_PENDING,
            'due_date' => now()->addDays(3),
            'created_by' => $partner->id,
        ]);

        $filters = new DashboardCalendarFilters(showDues: false, assignedTo: $alice->id);
        $events = app(DashboardCalendarBuilder::class)->buildEvents($partner, $filters);

        $this->assertCount(1, $events);
        $this->assertStringContainsString('Alice task', $events[0]['title']);
    }

    public function test_completed_filter_shows_green_dues(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $client = Client::factory()->create();
        $service = Service::create(['name' => 'TDS', 'code' => 'TDS', 'frequency' => 'Quarterly', 'due_day' => 7]);
        $cs = \App\Models\ClientService::create(['client_id' => $client->id, 'service_id' => $service->id]);

        ServiceDue::create([
            'client_service_id' => $cs->id,
            'due_date' => now()->subDays(2),
            'status' => ServiceDue::STATUS_COMPLETED,
        ]);

        $filters = new DashboardCalendarFilters(dueStatus: 'completed', showTasks: false);
        $events = app(DashboardCalendarBuilder::class)->buildEvents($partner, $filters);

        $this->assertCount(1, $events);
        $this->assertSame('#22c55e', $events[0]['backgroundColor']);
    }

    public function test_category_filter_limits_clients(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $clientA = Client::factory()->create(['category' => 'A']);
        $clientB = Client::factory()->create(['category' => 'B']);
        $service = Service::create(['name' => 'GST', 'code' => 'GST', 'frequency' => 'Monthly', 'due_day' => 10]);

        $csA = \App\Models\ClientService::create(['client_id' => $clientA->id, 'service_id' => $service->id]);
        \App\Models\ClientService::create(['client_id' => $clientB->id, 'service_id' => $service->id]);

        $dueA = ServiceDue::create([
            'client_service_id' => $csA->id,
            'due_date' => now()->addDays(4),
            'status' => ServiceDue::STATUS_PENDING,
        ]);

        $filters = new DashboardCalendarFilters(category: 'A', showTasks: false);
        $events = app(DashboardCalendarBuilder::class)->buildEvents($partner, $filters);

        $this->assertCount(1, $events);
        $this->assertSame('due_' . $dueA->id, $events[0]['id']);
    }

    public function test_manager_calendar_reschedule_still_works(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $client = Client::factory()->create();
        $service = Service::create(['name' => 'Cal', 'code' => 'CAL', 'frequency' => 'Monthly', 'due_day' => 10]);
        $cs = \App\Models\ClientService::create(['client_id' => $client->id, 'service_id' => $service->id]);

        $due = ServiceDue::create([
            'client_service_id' => $cs->id,
            'due_date' => '2025-01-10',
            'status' => ServiceDue::STATUS_PENDING,
        ]);

        $this->actingAs($manager)
            ->postJson(route('calendar.update'), [
                'type' => 'due',
                'id' => $due->id,
                'new_date' => '2025-01-22',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    }
}
