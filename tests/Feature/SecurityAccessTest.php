<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAccessTest extends TestCase
{
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
            '/system',
            '/users',
            '/branches',
        ] as $path) {
            $this->get($path)->assertOk();
        }
    }

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user);

        return $user;
    }
}

