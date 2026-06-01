<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ComplianceRiskScore;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\ServiceDue;
use App\Models\User;
use App\Services\Intelligence\ClientTimelineBuilder;
use App\Services\Intelligence\CollectionsCallListBuilder;
use App\Services\Intelligence\ComplianceRiskScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntelligencePhaseTwoTest extends TestCase
{
    use RefreshDatabase;

    public function test_collections_index_requires_partner(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)->get(route('collections.index'))->assertForbidden();
    }

    public function test_partner_can_view_collections_and_log_follow_up(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $client = Client::factory()->create();

        Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-9001',
            'date' => now(),
            'due_date' => now()->subDays(45),
            'status' => Invoice::STATUS_OVERDUE,
            'subtotal' => 5000,
            'total_amount' => 5000,
        ]);

        $this->actingAs($partner)->get(route('collections.index'))->assertOk()->assertSee($client->name, false);

        $this->actingAs($partner)
            ->post(route('collections.follow-up', $client), [
                'channel' => 'whatsapp',
                'notes' => 'Promised payment next week',
                'promise_date' => now()->addWeek()->format('Y-m-d'),
            ])
            ->assertRedirect(route('collections.index', ['client_id' => $client->id]));

        $this->assertDatabaseHas('collection_follow_ups', [
            'client_id' => $client->id,
            'channel' => 'whatsapp',
        ]);
    }

    public function test_compliance_risk_scorer_persists_high_risk(): void
    {
        $client = Client::factory()->create();
        $service = Service::create(['name' => 'GSTR-1', 'code' => 'GSTR1', 'frequency' => 'Monthly', 'due_day' => 11]);
        $clientService = \App\Models\ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
        ]);

        for ($i = 0; $i < 3; $i++) {
            ServiceDue::create([
                'client_service_id' => $clientService->id,
                'due_date' => now()->subDays(5 + $i),
                'status' => ServiceDue::STATUS_OVERDUE,
            ]);
        }

        app(ComplianceRiskScorer::class)->score();

        $this->assertDatabaseHas('compliance_risk_scores', [
            'client_id' => $client->id,
            'service_id' => $service->id,
        ]);

        $score = ComplianceRiskScore::where('client_id', $client->id)->first();
        $this->assertGreaterThanOrEqual(35, $score->score);
    }

    public function test_client_timeline_includes_tasks_and_invoices(): void
    {
        $client = Client::factory()->create();
        Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-T1',
            'date' => now(),
            'due_date' => now()->addDays(15),
            'status' => Invoice::STATUS_DRAFT,
            'subtotal' => 100,
            'total_amount' => 100,
        ]);

        $timeline = app(ClientTimelineBuilder::class)->build($client);

        $this->assertTrue($timeline->contains(fn ($e) => $e['type'] === 'invoice'));
    }

    public function test_call_list_prioritizes_outstanding_clients(): void
    {
        $client = Client::factory()->create(['name' => 'Big Debtor Ltd']);
        Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-BIG',
            'date' => now(),
            'due_date' => now()->subDays(60),
            'status' => Invoice::STATUS_OVERDUE,
            'subtotal' => 200000,
            'total_amount' => 200000,
        ]);

        $list = app(CollectionsCallListBuilder::class)->build();

        $this->assertTrue($list->first()->client->id === $client->id);
    }
}
