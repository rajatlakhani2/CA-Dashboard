<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Service;
use App\Services\ServiceDueGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ServiceDueGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new ServiceDueGenerator();
        // Set fixed time for all tests
        Carbon::setTestNow(Carbon::create(2025, 1, 1)); // Jan 1st 2025
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset
        parent::tearDown();
    }

    #[Test]
    public function it_calculates_monthly_due_date_correctly()
    {
        // 2025-01-01 -> Due Day 10 -> 2025-01-10 (This month)
        $this->assertDueDate('Monthly', 10, Carbon::create(2025, 1, 10));
    }

    #[Test]
    public function it_calculates_quarterly_due_date_correctly()
    {
        // 2025-01-01 -> Due Day 10 -> 2025-01-10 (This quarter)
        $this->assertDueDate('Quarterly', 10, Carbon::create(2025, 1, 10));
    }

    #[Test]
    public function it_calculates_half_yearly_due_date_correctly()
    {
        // 2025-01-01 -> Due Day 10 -> 2025-01-10 (This half)
        $this->assertDueDate('Half-Yearly', 10, Carbon::create(2025, 1, 10));
    }

    #[Test]
    public function it_calculates_annually_due_date_correctly()
    {
        // 2025-01-01 -> Due Day 10 -> 2025-01-10 is FUTURE, but Annual logic with no month defaults to addYear()?
        // Let's check logic:
        // if frequency == Annually:
        //    if no dueMonth -> $date->addYear();
        // So yes, it becomes 2026-01-10.
        $this->assertDueDate('Annually', 10, Carbon::create(2026, 1, 10));
    }

    #[Test]
    public function it_does_not_duplicate_dues()
    {
        $client = Client::factory()->create(['status' => 'Active']);
        $service = Service::create([
            'name' => 'Test Service',
            'code' => 'TEST',
            'frequency' => 'Monthly',
            'due_day' => 10,
            'is_statutory' => false
        ]);

        $clientService = ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'Active'
        ]);

        // First generation
        $this->generator->generateForClientService($clientService);
        $this->assertDatabaseCount('service_dues', 1);

        // Second generation (should not create duplicate)
        $this->generator->generateForClientService($clientService);
        $this->assertDatabaseCount('service_dues', 1);
    }

    private function assertDueDate($frequency, $dueDay, $expectedDate)
    {
        $client = Client::factory()->create(['status' => 'Active']);
        $service = Service::create([
            'name' => "Test $frequency",
            'code' => "TEST_$frequency",
            'frequency' => $frequency,
            'due_day' => $dueDay,
            'is_statutory' => false
        ]);

        $clientService = ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'Active'
        ]);

        $this->generator->generateForClientService($clientService);

        $this->assertDatabaseHas('service_dues', [
            'client_service_id' => $clientService->id,
            'due_date' => $expectedDate->format('Y-m-d 00:00:00'),
        ]);
    }
}
