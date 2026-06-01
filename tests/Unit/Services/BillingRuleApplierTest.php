<?php

namespace Tests\Unit\Services;

use App\Models\BillingRule;
use App\Models\Client;
use App\Models\ClientService;
use App\Models\Service;
use App\Models\ServiceDue;
use App\Services\BillingRuleApplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class BillingRuleApplierTest extends TestCase
{
    use RefreshDatabase;

    private BillingRuleApplier $applier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->applier = new BillingRuleApplier;
    }

    public function test_match_rule_prefers_client_specific_rule(): void
    {
        $client = Client::factory()->create();
        $service = Service::create(['name' => 'GST', 'code' => 'GST1', 'frequency' => 'Monthly']);
        $clientService = ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'status' => ClientService::STATUS_ACTIVE,
        ]);
        $due = ServiceDue::create([
            'client_service_id' => $clientService->id,
            'due_date' => now(),
            'status' => ServiceDue::STATUS_COMPLETED,
            'billing_status' => ServiceDue::BILLING_STATUS_UNBILLED,
        ]);

        $generic = BillingRule::create([
            'name' => 'Generic',
            'service_id' => $service->id,
            'rule_type' => BillingRule::TYPE_FIXED_FEE,
            'fixed_amount' => 100,
            'is_active' => true,
        ]);
        $specific = BillingRule::create([
            'name' => 'Client',
            'service_id' => $service->id,
            'client_id' => $client->id,
            'rule_type' => BillingRule::TYPE_FIXED_FEE,
            'fixed_amount' => 500,
            'is_active' => true,
        ]);

        $rules = Collection::make([$generic, $specific]);
        $matched = $this->applier->matchRule($rules, $due->load('clientService'));

        $this->assertSame($specific->id, $matched->id);
    }

    public function test_resolve_amount_uses_fixed_fee(): void
    {
        $rule = new BillingRule([
            'rule_type' => BillingRule::TYPE_FIXED_FEE,
            'fixed_amount' => 2500,
            'use_due_amount' => false,
        ]);
        $due = new ServiceDue(['billing_amount' => null]);

        $this->assertSame(2500.0, $this->applier->resolveAmount($rule, $due));
    }

    public function test_apply_to_unbilled_dues_sets_billing_amount(): void
    {
        $client = Client::factory()->create();
        $service = Service::create(['name' => 'TDS', 'code' => 'TDS1', 'frequency' => 'Quarterly']);
        $clientService = ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'status' => ClientService::STATUS_ACTIVE,
        ]);
        ServiceDue::create([
            'client_service_id' => $clientService->id,
            'due_date' => now(),
            'status' => ServiceDue::STATUS_COMPLETED,
            'billing_status' => ServiceDue::BILLING_STATUS_UNBILLED,
        ]);
        BillingRule::create([
            'name' => 'TDS fee',
            'service_id' => $service->id,
            'rule_type' => BillingRule::TYPE_FIXED_FEE,
            'fixed_amount' => 1500,
            'is_active' => true,
        ]);

        $result = $this->applier->applyToUnbilledDues();

        $this->assertSame(1, $result['applied']);
        $this->assertSame(1500.0, (float) ServiceDue::first()->billing_amount);
    }
}
