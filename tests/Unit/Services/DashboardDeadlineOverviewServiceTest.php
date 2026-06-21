<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Service;
use App\Models\ServiceDue;
use App\Models\User;
use App\Services\DashboardDeadlineOverviewService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardDeadlineOverviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_service_deadlines_groups_clients_with_progress(): void
    {
        Carbon::setTestNow('2026-06-12');

        $partner = User::factory()->create(['role' => 'partner']);
        $service = Service::create([
            'name' => 'ITR Filing',
            'code' => 'ITR',
            'frequency' => 'Yearly',
            'due_day' => 31,
        ]);

        foreach (['Alpha Corp', 'Beta LLP'] as $index => $name) {
            $client = Client::factory()->create(['name' => $name]);
            $clientService = ClientService::create([
                'client_id' => $client->id,
                'service_id' => $service->id,
                'status' => 'Active',
            ]);
            ServiceDue::create([
                'client_service_id' => $clientService->id,
                'due_date' => '2026-07-31',
                'status' => $index === 0 ? ServiceDue::STATUS_COMPLETED : ServiceDue::STATUS_PENDING,
            ]);
        }

        $rows = app(DashboardDeadlineOverviewService::class)->monthlyServiceDeadlines($partner);

        $this->assertCount(1, $rows);
        $this->assertSame('ITR Filing', $rows[0]['service_name']);
        $this->assertSame(2, $rows[0]['total']);
        $this->assertSame(1, $rows[0]['completed']);
        $this->assertSame(1, $rows[0]['pending']);
        $this->assertCount(2, $rows[0]['clients']);

        Carbon::setTestNow();
    }
}
