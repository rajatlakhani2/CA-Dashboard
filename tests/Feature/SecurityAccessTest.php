<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesPlatformAdmin;
use Tests\TestCase;

class SecurityAccessTest extends TestCase
{
    use MakesPlatformAdmin;
    use RefreshDatabase;

    public function test_install_db_route_is_not_publicly_registered(): void
    {
        $this->get('/install-db')->assertNotFound();
    }

    public function test_staff_cannot_access_sensitive_modules(): void
    {
        $this->actingAsRole('staff');

        foreach ([
            '/credentials',
            '/billing',
            '/invoices',
            '/reports/financial',
            '/staff',
            '/activity',
            '/compliance-360',
            '/payments',
            '/expenses',
            '/dscs',
            '/tds',
            '/subscriptions',
        ] as $path) {
            $this->get($path)->assertForbidden();
        }
    }

    public function test_manager_can_access_manager_level_modules(): void
    {
        $this->actingAsRole('manager');

        foreach ([
            '/credentials',
            '/billing',
            '/reports',
            '/staff',
            '/activity',
            '/compliance-360',
            '/payments',
            '/expenses',
            '/dscs',
            '/tds',
            '/subscriptions',
        ] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_partner_only_modules_block_managers(): void
    {
        $this->actingAsRole('manager');

        foreach ([
            '/system',
            '/users',
            '/branches',
        ] as $path) {
            $this->get($path)->assertForbidden();
        }
    }

    public function test_partner_can_access_partner_only_modules(): void
    {
        $this->actingAsRole('partner');

        foreach ([
            '/users',
            '/branches',
        ] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_firm_partner_cannot_open_platform_system_or_backups(): void
    {
        // Any sign-up becomes partner of its own firm; backups hold every firm's data.
        $org = \App\Models\Organization::create(['name' => 'Stranger Firm', 'slug' => 'stranger', 'plan' => 'starter', 'seat_limit' => 5]);
        $this->platformAdmin(); // platform workspace exists, but this partner isn't in it
        $partner = User::factory()->create(['role' => 'partner', 'organization_id' => $org->id]);
        $this->actingAs($partner);

        $this->get('/system')->assertForbidden();
        $this->post(route('system.backup.run'))->assertForbidden();
        $this->get(route('system.backup.download', ['filename' => 'backup-x.zip']))->assertForbidden();
        $this->get('/dashboard')->assertDontSee('System Health');
    }

    public function test_system_is_closed_to_everyone_when_no_platform_workspace_is_set(): void
    {
        config(['app.platform_admin_workspace' => null]);
        $this->actingAsRole('partner');

        $this->get('/system')->assertForbidden();
    }

    public function test_platform_admin_can_open_system_health(): void
    {
        $this->actingAs($this->platformAdmin());

        $this->get('/system')->assertOk();
    }

    public function test_same_email_in_another_firm_is_not_platform_admin(): void
    {
        $admin = $this->platformAdmin(['email' => 'owner@example.test']);
        $org = \App\Models\Organization::create(['name' => 'Copycat', 'slug' => 'copycat', 'plan' => 'starter', 'seat_limit' => 5]);
        $copy = User::factory()->create(['role' => 'partner', 'organization_id' => $org->id, 'email' => 'owner@example.test']);

        $this->assertTrue($admin->isPlatformAdmin());
        $this->assertFalse($copy->isPlatformAdmin());
    }

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user);

        return $user;
    }
}

