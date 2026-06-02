<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPurgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_purge_clients_by_group_name(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $keep = Client::factory()->create(['group_name' => 'Other']);
        $remove = Client::factory()->count(2)->create(['group_name' => 'Nileshbhai']);

        $this->actingAs($partner)
            ->delete(route('clients.purge-by-group'), [
                'group_name' => 'Nileshbhai',
                'confirm' => 'DELETE',
            ])
            ->assertRedirect(route('clients.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('clients', ['id' => $remove[0]->id]);
        $this->assertDatabaseHas('clients', ['id' => $keep->id, 'deleted_at' => null]);
    }

    public function test_manager_cannot_purge_by_group(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        Client::factory()->create(['group_name' => 'Nileshbhai']);

        $this->actingAs($manager)
            ->delete(route('clients.purge-by-group'), [
                'group_name' => 'Nileshbhai',
                'confirm' => 'DELETE',
            ])
            ->assertForbidden();
    }
}
