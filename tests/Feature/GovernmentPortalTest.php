<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Client;
use App\Models\ClientCredential;
use App\Models\User;
use App\Support\GovernmentPortals;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GovernmentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_list_gst_portal_clients(): void
    {
        [$branch] = $this->branches();
        $partner = User::factory()->create(['role' => 'partner', 'branch_id' => $branch->id]);
        $client = $this->clientForBranch($branch, 'GST-CLIENT');
        $credential = ClientCredential::create([
            'client_id' => $client->id,
            'portal_name' => 'GST Portal',
            'category' => ClientCredential::CATEGORY_GST,
            'username' => 'gst-user',
            'password' => 'secret',
        ]);

        $this->actingAs($partner)
            ->getJson(route('gov-portals.clients', ['portal' => GovernmentPortals::PORTAL_GST]))
            ->assertOk()
            ->assertJsonPath('label', 'GST')
            ->assertJsonPath('clients.0.credential_id', $credential->id)
            ->assertJsonPath('clients.0.client_name', 'Client GST-CLIENT')
            ->assertJsonMissing(['password']);
    }

    public function test_associate_cannot_access_gov_portal_clients(): void
    {
        $associate = User::factory()->create(['role' => 'associate']);

        $this->actingAs($associate)
            ->getJson(route('gov-portals.clients', ['portal' => GovernmentPortals::PORTAL_GST]))
            ->assertForbidden();
    }

    public function test_launch_page_requires_authorization_and_records_access(): void
    {
        [$branchA, $branchB] = $this->branches();
        $manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branchA->id]);
        $ownCredential = $this->credentialForBranch($branchA, 'TRACES Portal', ClientCredential::CATEGORY_TRACES);
        $otherCredential = $this->credentialForBranch($branchB, 'Other TRACES', ClientCredential::CATEGORY_TRACES);

        $this->actingAs($manager)
            ->get(route('gov-portals.launch', [
                'portal' => GovernmentPortals::PORTAL_TRACES,
                'credential' => $otherCredential,
            ]))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('gov-portals.launch', [
                'portal' => GovernmentPortals::PORTAL_TRACES,
                'credential' => $ownCredential,
            ]))
            ->assertOk()
            ->assertSee('TRACES')
            ->assertSee('vault-user');

        $ownCredential->refresh();
        $this->assertNotNull($ownCredential->last_accessed_at);
        $this->assertSame($manager->id, $ownCredential->last_accessed_by);
    }

    public function test_income_tax_matches_it_category_credentials(): void
    {
        [$branch] = $this->branches();
        $partner = User::factory()->create(['role' => 'partner', 'branch_id' => $branch->id]);
        $client = $this->clientForBranch($branch, 'IT-CLIENT');
        ClientCredential::create([
            'client_id' => $client->id,
            'portal_name' => 'Income Tax e-Filing',
            'category' => ClientCredential::CATEGORY_IT,
            'username' => 'ABCDE1234F',
            'password' => 'secret',
        ]);

        $this->actingAs($partner)
            ->getJson(route('gov-portals.clients', ['portal' => GovernmentPortals::PORTAL_INCOME_TAX]))
            ->assertOk()
            ->assertJsonCount(1, 'clients');
    }

    private function branches(): array
    {
        $branchA = Branch::create(['name' => 'Branch A', 'code' => 'A']);
        $branchB = Branch::create(['name' => 'Branch B', 'code' => 'B']);

        return [$branchA, $branchB];
    }

    private function clientForBranch(Branch $branch, string $code): Client
    {
        return Client::create([
            'client_code' => $code,
            'name' => "Client {$code}",
            'status' => Client::STATUS_ACTIVE,
            'branch_id' => $branch->id,
            'tan' => 'DELH12345A',
        ]);
    }

    private function credentialForBranch(Branch $branch, string $portalName, string $category): ClientCredential
    {
        return ClientCredential::create([
            'client_id' => $this->clientForBranch($branch, strtoupper(substr($portalName, 0, 6)))->id,
            'portal_name' => $portalName,
            'category' => $category,
            'username' => 'vault-user',
            'password' => 'vault-secret',
        ]);
    }
}
