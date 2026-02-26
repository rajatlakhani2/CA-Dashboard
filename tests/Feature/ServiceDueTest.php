<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Service;
use App\Models\ServiceDue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceDueTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_service_due_generation()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 13));

        // 1. Create a Service (Monthly)
        $service = Service::create([
            'name' => 'Monthly Test',
            'code' => 'M-TEST',
            'frequency' => 'Monthly',
            'due_day' => 15, // Due on 15th
            'description' => 'Test',
        ]);

        // 2. Create a Client
        $client = Client::factory()->create();

        // 3. Assign Service to Client
        ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'Active',
        ]);

        // 4. Run the Command
        $this->artisan('services:generate-dues')
            ->assertExitCode(0);

        // 5. Assert Due Created
        // If today is Jan 29, next due is Feb 15.
        // If today is Jan 10, next due is Jan 15? Or Feb 15?
        // Logic in command: if (create(year, month, dueDay)->isPast()) addMonth()

        $expectedDue = Carbon::create(2026, 2, 15);
        // If it was past, we'd add month, but 13 < 15, so it is current month.

        $this->assertDatabaseHas('service_dues', [
            'status' => 'Pending',
            'due_date' => $expectedDue->format('Y-m-d H:i:s'),
        ]);

        // Note: Command creates with default time? Let's check command logic again. 
        // It uses Carbon::create(y, m, d) which defaults to 00:00:00 usually.
    }

    public function test_quarterly_service_due_generation()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 13));

        $service = Service::create([
            'name' => 'Quarterly Test',
            'code' => 'Q-TEST',
            'frequency' => 'Quarterly',
            'due_day' => 10,
            'description' => 'Test',
        ]);

        $client = Client::factory()->create();

        ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'Active',
        ]);

        $this->artisan('services:generate-dues')->assertExitCode(0);

        // Command Logic for Quarterly using simple approximation (Current + 3 months if passed)
        // Today Feb 13. Due Day 10.
        // Feb 10 is past. So +3 months = May 10.
        $expectedDue = Carbon::create(2026, 5, 10);

        $this->assertDatabaseHas('service_dues', [
            'due_date' => $expectedDue->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_annually_service_due_generation()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 13));

        $service = Service::create([
            'name' => 'Annual Test',
            'code' => 'A-TEST',
            'frequency' => 'Annually',
            'due_day' => 31,
            'due_month' => 7, // July
            'description' => 'Test',
        ]);

        $client = Client::factory()->create();

        ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'Active',
        ]);

        $this->artisan('services:generate-dues')->assertExitCode(0);

        // Expect July 31, 2026 (Future relative to Feb 13)
        $expectedDue = Carbon::create(2026, 7, 31);

        $this->assertDatabaseHas('service_dues', [
            'due_date' => $expectedDue->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_does_not_duplicate_dues()
    {
        $service = Service::create([
            'name' => 'Dupe Test',
            'code' => 'D-TEST',
            'frequency' => 'Monthly',
            'due_day' => 25,
            'description' => 'Test',
        ]);

        $client = Client::factory()->create();

        $cs = ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'Active',
        ]);

        // Run once
        $this->artisan('services:generate-dues');
        $this->assertDatabaseCount('service_dues', 1);

        // Run again
        $this->artisan('services:generate-dues');
        $this->assertDatabaseCount('service_dues', 1);
    }
}
