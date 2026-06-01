<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\ClientCredential;
use App\Models\FirmAlert;
use App\Models\Service;
use App\Models\ServiceDue;
use App\Models\User;
use App\Services\Intelligence\AnomalyScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnomalyScannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_command_completes(): void
    {
        $this->artisan('anomaly:scan')->assertSuccessful();
    }

    public function test_detects_compliance_stack(): void
    {
        $client = Client::factory()->create();
        $service = Service::create([
            'name' => 'GSTR-1',
            'code' => 'GSTR1',
            'frequency' => 'Monthly',
            'due_day' => 11,
        ]);
        $clientService = \App\Models\ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
        ]);

        for ($i = 0; $i < AnomalyScanner::COMPLIANCE_STACK_MIN; $i++) {
            ServiceDue::create([
                'client_service_id' => $clientService->id,
                'due_date' => now()->addDays($i),
                'status' => ServiceDue::STATUS_PENDING,
            ]);
        }

        app(AnomalyScanner::class)->scan();

        $this->assertDatabaseHas('firm_alerts', [
            'type' => FirmAlert::TYPE_COMPLIANCE_STACK,
            'client_id' => $client->id,
        ]);
    }

    public function test_idle_credential_alert(): void
    {
        $client = Client::factory()->create();
        $credential = ClientCredential::create([
            'client_id' => $client->id,
            'portal_name' => 'GST Portal',
            'username' => 'user',
            'password' => 'secret-pass',
        ]);
        \Illuminate\Support\Facades\DB::table('client_credentials')
            ->where('id', $credential->id)
            ->update(['created_at' => now()->subDays(120), 'updated_at' => now()->subDays(120)]);
        \Spatie\Activitylog\Models\Activity::query()
            ->where('subject_type', ClientCredential::class)
            ->where('subject_id', $credential->id)
            ->delete();

        app(AnomalyScanner::class)->scan();

        $this->assertDatabaseHas('firm_alerts', [
            'type' => FirmAlert::TYPE_CREDENTIAL_IDLE,
            'related_id' => $credential->id,
        ]);
    }
}
