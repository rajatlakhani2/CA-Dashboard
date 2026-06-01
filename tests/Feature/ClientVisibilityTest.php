<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ClientVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_client_index_is_scoped_to_branch(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);

        $ownClient = $this->clientForBranch($branchA, 'VIS-A', 'Visible Client A');
        $otherClient = $this->clientForBranch($branchB, 'VIS-B', 'Hidden Client B');

        $response = $this->actingAs($manager)->get(route('clients.index'));

        $response->assertOk();
        $response->assertSee($ownClient->name);
        $response->assertDontSee($otherClient->name);
    }

    public function test_staff_only_sees_assigned_or_task_linked_clients(): void
    {
        [$branchA] = $this->branches();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branchA->id]);
        $otherStaff = User::factory()->create(['role' => 'staff', 'branch_id' => $branchA->id]);

        $managedClient = $this->clientForBranch($branchA, 'STAFF-M', 'Managed By Staff', $staff->id);
        $taskClient = $this->clientForBranch($branchA, 'STAFF-T', 'Task Linked Client');
        $hiddenClient = $this->clientForBranch($branchA, 'STAFF-H', 'Hidden Client', $otherStaff->id);

        Task::create([
            'client_id' => $taskClient->id,
            'assigned_to' => $staff->id,
            'created_by' => $otherStaff->id,
            'title' => 'Assigned task',
            'status' => Task::STATUS_PENDING,
            'priority' => 'Medium',
        ]);

        $response = $this->actingAs($staff)->get(route('clients.index'));

        $response->assertOk();
        $response->assertSee($managedClient->name);
        $response->assertSee($taskClient->name);
        $response->assertDontSee($hiddenClient->name);
    }

    public function test_staff_cannot_view_or_update_unassigned_client_pages(): void
    {
        [$branchA] = $this->branches();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branchA->id]);
        $hiddenClient = $this->clientForBranch($branchA, 'STAFF-X', 'Blocked Client');

        $this->actingAs($staff)
            ->get(route('clients.show', $hiddenClient))
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(route('clients.edit', $hiddenClient))
            ->assertForbidden();

        $this->actingAs($staff)
            ->put(route('clients.update', $hiddenClient), [
                'name' => 'Should Not Update',
                'pan' => $hiddenClient->pan,
                'category' => 'A',
                'status' => Client::STATUS_ACTIVE,
            ])
            ->assertForbidden();
    }

    public function test_staff_cannot_create_clients_but_can_view_assigned_client(): void
    {
        [$branchA] = $this->branches();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branchA->id]);
        $managedClient = $this->clientForBranch($branchA, 'STAFF-V', 'Visible Managed Client', $staff->id);

        $this->assertFalse(Gate::forUser($staff)->allows('create', Client::class));

        $this->actingAs($staff)
            ->get(route('clients.create'))
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(route('clients.store'), [
                'name' => 'Blocked Create',
                'pan' => 'BLOCK1234A',
                'category' => 'A',
                'status' => Client::STATUS_ACTIVE,
            ])
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(route('clients.show', $managedClient))
            ->assertOk();
    }

    public function test_manager_cannot_view_other_branch_client_show_page(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);
        $otherClient = $this->clientForBranch($branchB, 'MGR-B', 'Other Branch Client');

        $this->actingAs($manager)
            ->get(route('clients.show', $otherClient))
            ->assertForbidden();
    }

    public function test_global_search_only_returns_visible_clients(): void
    {
        [$branchA] = $this->branches();
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branchA->id]);
        $visibleClient = $this->clientForBranch($branchA, 'SEARCH-V', 'Search Visible Client', $staff->id);
        $hiddenClient = $this->clientForBranch($branchA, 'SEARCH-H', 'Search Hidden Client');

        $response = $this->actingAs($staff)
            ->getJson(route('search.global', ['query' => 'Search']));

        $response->assertOk();
        $response->assertJsonFragment(['title' => $visibleClient->name]);
        $response->assertJsonMissing(['title' => $hiddenClient->name]);
    }

    private function branches(): array
    {
        $branchA = Branch::create(['name' => 'Branch A', 'code' => 'A']);
        $branchB = Branch::create(['name' => 'Branch B', 'code' => 'B']);

        return [$branchA, $branchB];
    }

    private function clientForBranch(Branch $branch, string $code, string $name, ?int $managerId = null): Client
    {
        return Client::create([
            'client_code' => $code,
            'name' => $name,
            'pan' => strtoupper($code).'1234A',
            'status' => Client::STATUS_ACTIVE,
            'category' => 'A',
            'branch_id' => $branch->id,
            'manager_id' => $managerId,
        ]);
    }
}
