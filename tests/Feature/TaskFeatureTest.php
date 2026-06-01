<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Task;

class TaskFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'staff']);
        $this->actingAs($this->user);
    }

    public function test_task_index_page_loads()
    {
        $response = $this->get(route('tasks.index'));
        $response->assertStatus(200);
        $response->assertSee('Tasks');
    }

    public function test_can_create_task()
    {
        $client = Client::create([
            'name' => 'Task Client',
            'client_code' => 'TC-TSK-001',
            'pan' => 'TSKDE1234F',
            'status' => 'Active',
            'category' => 'A'
        ]);

        $data = [
            'title' => 'New Audit Task',
            'description' => 'Complete the audit for FY 23-24',
            'client_id' => $client->id,
            'priority' => 'High',
            'status' => 'Pending',
            'due_date' => now()->addDays(10)->format('Y-m-d'),
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id
        ];

        $response = $this->post(route('tasks.store'), $data);

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'title' => 'New Audit Task',
            'priority' => 'High'
        ]);
    }

    public function test_can_update_task_status()
    {
        $client = Client::create([
            'name' => 'Update Task Client',
            'client_code' => 'TC-UPD-001',
            'pan' => 'UPDDE1234F',
            'status' => 'Active',
            'category' => 'A'
        ]);

        $task = Task::create([
            'title' => 'Task to Update',
            'client_id' => $client->id,
            'priority' => 'Medium',
            'status' => 'Pending',
            'due_date' => now()->addDays(5),
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id
        ]);

        $response = $this->patch(route('tasks.update-status', $task), [
            'status' => 'Completed'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'Completed'
        ]);
    }
}
