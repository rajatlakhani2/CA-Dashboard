<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SaasTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_firm_registration_creates_isolated_organization(): void
    {
        $response = $this->post(route('register.organization'), [
            'firm_name' => 'Beta CA Firm',
            'workspace' => 'beta-ca',
            'admin_name' => 'Beta Partner',
            'admin_email' => 'partner@beta.test',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('organizations', ['slug' => 'beta-ca', 'name' => 'Beta CA Firm']);
        $this->assertDatabaseHas('users', [
            'email' => 'partner@beta.test',
            'role' => 'partner',
        ]);
    }

    public function test_login_requires_correct_workspace(): void
    {
        $orgA = Organization::create(['name' => 'Firm A', 'slug' => 'firm-a', 'plan' => 'starter', 'seat_limit' => 5]);
        $orgB = Organization::create(['name' => 'Firm B', 'slug' => 'firm-b', 'plan' => 'starter', 'seat_limit' => 5]);

        User::create([
            'organization_id' => $orgA->id,
            'name' => 'User A',
            'email' => 'same@email.test',
            'password' => Hash::make('secret'),
            'role' => 'partner',
        ]);

        User::create([
            'organization_id' => $orgB->id,
            'name' => 'User B',
            'email' => 'same@email.test',
            'password' => Hash::make('secret'),
            'role' => 'partner',
        ]);

        $this->post(route('login'), [
            'workspace' => 'firm-b',
            'email' => 'same@email.test',
            'password' => 'secret',
        ])->assertRedirect(route('dashboard'));

        $this->assertSame($orgB->id, auth()->user()->organization_id);
    }

    public function test_clients_are_not_visible_across_organizations(): void
    {
        $orgA = Organization::create(['name' => 'A', 'slug' => 'a', 'plan' => 'starter', 'seat_limit' => 5]);
        $orgB = Organization::create(['name' => 'B', 'slug' => 'b', 'plan' => 'starter', 'seat_limit' => 5]);

        OrganizationContext::set($orgA->id);
        Client::create([
            'organization_id' => $orgA->id,
            'client_code' => 'A-1',
            'name' => 'Client A',
            'status' => Client::STATUS_ACTIVE,
        ]);

        OrganizationContext::set($orgB->id);
        $this->assertSame(0, Client::count());

        OrganizationContext::clear();
    }
}
