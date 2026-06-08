<?php

namespace Tests\Unit\Support;

use App\Models\Setting;
use App\Support\ModuleGate;
use App\Support\WorkspaceProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_ca_firm_roles(): void
    {
        Setting::set('workspace_type', WorkspaceProfile::TYPE_CA_FIRM);

        $roles = WorkspaceProfile::roles();

        $this->assertEquals(['partner', 'manager', 'article'], array_keys($roles));
    }

    public function test_executive_roles(): void
    {
        Setting::set('workspace_type', WorkspaceProfile::TYPE_EXECUTIVE);

        $roles = WorkspaceProfile::roles();

        $this->assertEquals(['ceo', 'manager'], array_keys($roles));
    }

    public function test_executive_preset_disables_finance(): void
    {
        WorkspaceProfile::applyModulePreset(WorkspaceProfile::TYPE_EXECUTIVE);

        $modules = ModuleGate::firmModules();

        $this->assertFalse($modules['invoices']);
        $this->assertFalse($modules['billing']);
        $this->assertTrue($modules['personal_renewals']);
        $this->assertTrue($modules['clients']);
    }

    public function test_ca_firm_preset_enables_all_modules(): void
    {
        WorkspaceProfile::applyModulePreset(WorkspaceProfile::TYPE_CA_FIRM);

        $modules = ModuleGate::firmModules();

        $this->assertTrue($modules['invoices']);
        $this->assertTrue($modules['billing']);
        $this->assertTrue($modules['staff']);
    }
}
