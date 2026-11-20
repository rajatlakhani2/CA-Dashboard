<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Services\ClientHealthScoreService;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Guards against pages going back to running a query per setting / per client. */
class PagePerformanceTest extends TestCase
{
    use RefreshDatabase;

    private function firm(): array
    {
        $org = Organization::create(['name' => 'Speed Firm', 'slug' => 'speedfirm', 'plan' => 'professional', 'seat_limit' => 10]);
        $partner = User::factory()->create(['role' => 'partner', 'organization_id' => $org->id, 'mobile' => '919999000000']);

        return [$org, $partner];
    }

    public function test_settings_are_read_with_one_query_and_see_new_values(): void
    {
        $this->firm();
        Setting::set('company_name', 'First Name');
        Setting::set('reminder_time_1', '09:00');

        DB::enableQueryLog();
        foreach (range(1, 20) as $i) {
            Setting::get('company_name');
            Setting::get('reminder_time_1');
            Setting::get('missing_key', 'fallback');
        }
        $this->assertLessThanOrEqual(2, count(DB::getQueryLog()));
        DB::disableQueryLog();

        $this->assertSame('First Name', Setting::get('company_name'));
        $this->assertSame('fallback', Setting::get('missing_key', 'fallback'));

        Setting::set('company_name', 'Renamed');
        $this->assertSame('Renamed', Setting::get('company_name'));
    }

    public function test_settings_stay_separate_per_firm(): void
    {
        [$orgA] = $this->firm();
        Setting::set('company_name', 'Firm A');

        $orgB = Organization::create(['name' => 'B', 'slug' => 'speedb', 'plan' => 'professional', 'seat_limit' => 5]);
        OrganizationContext::set($orgB->id);
        $this->assertNull(Setting::get('company_name'));
        Setting::set('company_name', 'Firm B');

        OrganizationContext::set($orgA->id);
        $this->assertSame('Firm A', Setting::get('company_name'));
        OrganizationContext::set($orgB->id);
        $this->assertSame('Firm B', Setting::get('company_name'));
    }

    public function test_client_health_for_many_clients_uses_a_fixed_number_of_queries(): void
    {
        [$org, $partner] = $this->firm();
        $clients = Client::factory()->count(12)->create(['organization_id' => $org->id]);
        foreach ($clients as $i => $c) {
            Task::create(['title' => 'T'.$i, 'status' => Task::STATUS_PENDING, 'priority' => 'Normal', 'client_id' => $c->id,
                'assigned_to' => $partner->id, 'created_by' => $partner->id, 'due_date' => now()->subDays($i)]);
        }

        DB::enableQueryLog();
        $scores = app(ClientHealthScoreService::class)->forClients($clients);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(12, $scores);
        $this->assertLessThanOrEqual(12, $queries, "Health scores ran {$queries} queries for 12 clients");
        // Same answer as scoring one client on its own.
        $this->assertEquals(app(ClientHealthScoreService::class)->forClient($clients[5]), $scores[$clients[5]->id]);
    }

    public function test_client_list_query_count_does_not_grow_with_clients(): void
    {
        [$org, $partner] = $this->firm();
        Client::factory()->count(3)->create(['organization_id' => $org->id]);
        $small = $this->countQueries(fn () => $this->actingAs($partner)->get(route('clients.index'))->assertOk());

        Client::factory()->count(9)->create(['organization_id' => $org->id]);
        $large = $this->countQueries(fn () => $this->actingAs($partner)->get(route('clients.index'))->assertOk());

        $this->assertLessThanOrEqual($small + 3, $large, "Clients page went from {$small} to {$large} queries");
    }

    private function countQueries(callable $fn): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $fn();
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    }
}
