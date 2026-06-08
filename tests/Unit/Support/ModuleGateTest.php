<?php

namespace Tests\Unit\Support;

use App\Models\Setting;
use App\Models\User;
use App\Support\ModuleGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_firm_disabled_module_blocks_partner(): void
    {
        Setting::set('enabled_modules', json_encode([
            'dashboard' => true,
            'settings' => true,
            'invoices' => false,
            'clients' => true,
        ]));

        $partner = User::factory()->create(['role' => 'partner']);

        $this->assertFalse($partner->canAccessModule('invoices'));
        $this->assertTrue($partner->canAccessModule('clients'));
        $this->assertTrue($partner->canAccessModule('settings'));
    }

    public function test_has_finance_module_false_when_all_finance_off(): void
    {
        Setting::set('enabled_modules', json_encode([
            'invoices' => false,
            'billing' => false,
            'payments' => false,
            'expenses' => false,
            'subscriptions' => false,
        ]));

        $partner = User::factory()->create(['role' => 'partner']);

        $this->assertFalse(ModuleGate::hasFinanceModule($partner));
    }
}
