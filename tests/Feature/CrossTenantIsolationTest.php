<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_another_organizations_client(): void
    {
        $orgA = Organization::create([
            'name' => 'Firm A',
            'slug' => 'firm-a',
            'plan' => 'starter',
            'seat_limit' => 5,
        ]);
        $orgB = Organization::create([
            'name' => 'Firm B',
            'slug' => 'firm-b',
            'plan' => 'starter',
            'seat_limit' => 5,
        ]);

        $partnerA = User::factory()->create([
            'organization_id' => $orgA->id,
            'role' => 'partner',
        ]);

        $clientB = Client::factory()->create([
            'organization_id' => $orgB->id,
            'approval_status' => Client::APPROVAL_APPROVED,
        ]);

        $this->actingAs($partnerA)
            ->get(route('clients.show', $clientB))
            ->assertForbidden();
    }

    public function test_finance_snapshot_requires_authentication(): void
    {
        $this->getJson(route('dashboard.finance-snapshot'))
            ->assertUnauthorized();
    }
}
