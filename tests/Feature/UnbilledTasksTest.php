<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnbilledTasksTest extends TestCase
{
    use RefreshDatabase;

    private function makeClient(): Client
    {
        return Client::create([
            'name' => 'Unbilled Test Client',
            'client_code' => 'TC-UNB-001',
            'pan' => 'UNBDE1234F',
            'status' => 'Active',
            'category' => 'A',
        ]);
    }

    public function test_partner_sees_unassigned_completed_task_in_unbilled_tab(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $client = $this->makeClient();

        Task::create([
            'client_id' => $client->id,
            'assigned_to' => null,
            'created_by' => $partner->id,
            'status' => Task::STATUS_COMPLETED,
            'is_billed' => false,
            'title' => 'Unassigned compliance review',
            'priority' => 'Normal',
        ]);

        $response = $this->actingAs($partner)->get(route('invoices.index', ['tab' => 'unbilled']));

        $response->assertOk();
        $response->assertSee('Unassigned compliance review');
        $response->assertSee('Unassigned');
    }

    public function test_manager_sees_unassigned_task_created_by_staff(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $staff = User::factory()->create(['role' => 'staff']);

        Task::create([
            'assigned_to' => null,
            'created_by' => $staff->id,
            'status' => Task::STATUS_COMPLETED,
            'is_billed' => false,
            'title' => 'Staff created unassigned',
            'priority' => 'Normal',
        ]);

        $response = $this->actingAs($manager)->get(route('invoices.index', ['tab' => 'unbilled']));

        $response->assertOk();
        $response->assertSee('Staff created unassigned');
    }
}
