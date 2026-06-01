<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\DashboardMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_returns_expected_metric_keys(): void
    {
        $user = User::factory()->create(['role' => 'manager']);
        $metrics = app(DashboardMetricsService::class)->build($user);

        $this->assertArrayHasKey('summary', $metrics);
        $this->assertArrayHasKey('upcomingCounts', $metrics);
        $this->assertArrayHasKey('serviceWisePending', $metrics);
        $this->assertArrayHasKey('highRiskClients', $metrics);
        $this->assertArrayHasKey('alerts', $metrics);
        $this->assertArrayHasKey('calendarDues', $metrics);
        $this->assertArrayHasKey('myPendingTasks', $metrics);
        $this->assertArrayHasKey('complianceStats', $metrics);
        $this->assertArrayHasKey('recentClients', $metrics);
        $this->assertArrayHasKey('pendingClientApprovals', $metrics);
        $this->assertArrayHasKey('total_clients', $metrics['summary']);
    }

    public function test_pending_client_approvals_only_for_partner(): void
    {
        $service = app(DashboardMetricsService::class);

        $manager = User::factory()->create(['role' => 'manager']);
        $this->assertSame(0, $service->build($manager)['pendingClientApprovals']);

        $partner = User::factory()->create(['role' => 'partner']);
        $this->assertIsInt($service->build($partner)['pendingClientApprovals']);
    }
}
