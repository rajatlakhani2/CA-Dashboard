<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_sidebar_hides_manager_only_modules(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('href="' . route('credentials.index'), false);
        $response->assertDontSee('href="' . route('invoices.index'), false);
        $response->assertDontSee('href="' . route('staff.index'), false);
        $response->assertDontSee('Reports &amp; 360°', false);
        $response->assertDontSee('Reports & 360°', false);
        $response->assertSee(route('clients.index'), false);
        $response->assertSee(route('tasks.index'), false);
    }

    public function test_staff_dashboard_hides_finance_kpis_and_tab(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('href="' . route('billing.index'), false);
        $response->assertDontSee('Outstanding</p>', false);
        $response->assertDontSee('Unbilled Work</p>', false);
        $response->assertDontSee('💰 Financials', false);
        $response->assertDontSee('Outstanding Fees</p>', false);
        $response->assertSee('View Reminders →', false);
    }

    public function test_manager_sidebar_shows_finance_modules(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($manager)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('invoices.index'), false);
        $response->assertSee(route('credentials.index'), false);
        $response->assertSee('💰 Financials', false);
    }

    public function test_associate_client_show_shows_finance_summary_read_only(): void
    {
        $associate = User::factory()->create(['role' => 'associate']);
        $client = Client::create([
            'client_code' => 'ASC-S1',
            'name' => 'Associate Portfolio Client',
            'pan' => 'ASCP1234A',
            'status' => Client::STATUS_ACTIVE,
            'category' => 'A',
            'manager_id' => $associate->id,
            'approval_status' => Client::APPROVAL_APPROVED,
        ]);

        $response = $this->actingAs($associate)->get(route('clients.show', $client));

        $response->assertOk();
        $response->assertSee('Finance', false);
        $response->assertSee('Total billed', false);
        $response->assertDontSee('+ New Invoice', false);
        $response->assertDontSee('Client Ledger', false);
    }

    public function test_staff_client_show_hides_finance_summary_and_invoices(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'code' => 'A']);
        $staff = User::factory()->create(['role' => 'staff', 'branch_id' => $branch->id]);
        $client = Client::create([
            'client_code' => 'NAV-S1',
            'name' => 'Nav Test Client',
            'pan' => 'NAVS1234A',
            'status' => Client::STATUS_ACTIVE,
            'category' => 'A',
            'branch_id' => $branch->id,
            'manager_id' => $staff->id,
        ]);

        $response = $this->actingAs($staff)->get(route('clients.show', $client));

        $response->assertOk();
        $response->assertDontSee('Total billed', false);
        $response->assertDontSee('Finance', false);
        $response->assertSee('Active tasks', false);
    }

    public function test_pagination_renders_as_html_not_escaped_entities(): void
    {
        $branch = Branch::create(['name' => 'Branch A', 'code' => 'A']);
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branch->id]);

        for ($i = 1; $i <= 15; $i++) {
            Client::create([
                'client_code' => 'PG' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'name' => 'Pagination Client ' . $i,
                'pan' => 'PG' . $i . '234A',
                'status' => Client::STATUS_ACTIVE,
                'category' => 'A',
                'branch_id' => $branch->id,
                'manager_id' => $manager->id,
            ]);
        }

        $response = $this->actingAs($manager)->get(route('clients.index'));

        $response->assertOk();
        $response->assertSee('rel="next"', false);
        $response->assertSee('Pagination Client 1', false);
    }
}
