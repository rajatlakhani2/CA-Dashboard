<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\Service;
use App\Models\Task;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Services\TaskTemplateSpawner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTemplateSpawnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_spawn_creates_tasks_from_active_templates_only(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $client = Client::factory()->create(['manager_id' => $manager->id]);
        $service = Service::create(['name' => 'ITR', 'code' => 'ITR', 'frequency' => 'Annually']);

        TaskTemplate::create([
            'service_id' => $service->id,
            'title' => 'Active step',
            'due_days_offset' => 1,
            'priority' => 'High',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        TaskTemplate::create([
            'service_id' => $service->id,
            'title' => 'Inactive step',
            'due_days_offset' => 2,
            'priority' => 'Low',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $spawner = new TaskTemplateSpawner;
        $count = $spawner->spawnForClient($service, $client, $manager);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('tasks', ['title' => 'Active step', 'client_id' => $client->id]);
        $this->assertDatabaseMissing('tasks', ['title' => 'Inactive step']);
    }

    public function test_spawn_returns_zero_without_templates(): void
    {
        $service = Service::create(['name' => 'Empty', 'code' => 'EMP', 'frequency' => 'Monthly']);
        $client = Client::factory()->create();

        $this->assertSame(0, (new TaskTemplateSpawner)->spawnForClient($service, $client));
        $this->assertSame(0, Task::count());
    }
}
