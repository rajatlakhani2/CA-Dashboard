<?php

namespace Tests\Unit\Support;

use App\Models\Organization;
use App\Models\Setting;
use App\Models\User;
use App\Support\ModuleGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleGateTest extends TestCase
{
    use RefreshDatabase;

    private function partnerWithOrg(): User
    {
        $org = Organization::create([
            'name' => 'Gate Test Firm',
            'slug' => 'gatetest',
            'plan' => 'professional',
            'seat_limit' => 10,
        ]);

        return User::factory()->create([
            'role' => 'partner',
            'organization_id' => $org->id,
        ]);
    }

    public function test_firm_disabled_module_blocks_partner(): void
    {
        $partner = $this->partnerWithOrg();
        $this->actingAs($partner);

        Setting::set('enabled_modules', json_encode([
            'dashboard' => true,
            'settings' => true,
            'invoices' => false,
            'clients' => true,
        ]));

        $this->assertFalse($partner->canAccessModule('invoices'));
        $this->assertTrue($partner->canAccessModule('clients'));
        $this->assertTrue($partner->canAccessModule('settings'));
    }

    public function test_has_finance_module_false_when_all_finance_off(): void
    {
        $partner = $this->partnerWithOrg();
        $this->actingAs($partner);

        Setting::set('enabled_modules', json_encode([
            'invoices' => false,
            'billing' => false,
            'payments' => false,
            'expenses' => false,
            'subscriptions' => false,
        ]));

        $this->assertFalse(ModuleGate::hasFinanceModule($partner));
    }
}
