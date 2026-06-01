<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommandPaletteTest extends TestCase
{
    use RefreshDatabase;

    public function test_palette_returns_quick_actions_for_partner(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);

        $response = $this->actingAs($partner)
            ->getJson(route('search.palette'));

        $response->assertOk();
        $response->assertJsonStructure(['actions', 'navigation']);

        $actionTitles = collect($response->json('actions'))->pluck('title');
        $this->assertTrue($actionTitles->contains('Create New Task'));
        $this->assertTrue($actionTitles->contains('Log Payment'));
    }

    public function test_global_search_finds_create_task_action(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);

        $response = $this->actingAs($partner)
            ->getJson(route('search.global', ['query' => 'Create New']));

        $response->assertOk();
        $titles = collect($response->json())->pluck('title');
        $this->assertTrue($titles->contains('Create New Task'));
    }

    public function test_hash_prefix_limits_to_actions(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);

        $response = $this->actingAs($partner)
            ->getJson(route('search.global', ['query' => '#payment']));

        $response->assertOk();
        $categories = collect($response->json())->pluck('category')->unique();
        $this->assertTrue($categories->contains('Actions'));
        $this->assertFalse($categories->contains('Clients'));
    }

    public function test_associate_palette_excludes_billing_navigation(): void
    {
        $associate = User::factory()->create([
            'role' => 'associate',
            'module_access' => \App\Support\ModuleAccess::defaultsForRole('associate'),
        ]);

        $response = $this->actingAs($associate)
            ->getJson(route('search.palette'));

        $response->assertOk();
        $navTitles = collect($response->json('navigation'))->pluck('title');
        $this->assertFalse($navTitles->contains('Billing Queue'));
    }
}
