<?php

namespace Tests\Unit\Support;

use App\Models\User;
use App\Support\ExecutiveSummaryWidgets;
use App\Support\ModuleAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutiveSummaryWidgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_without_tasks_or_dues_sees_empty_hint_only(): void
    {
        $access = ModuleAccess::defaultsForRole('staff');
        $access['tasks'] = false;
        $access['service_dues'] = false;

        $user = User::factory()->create([
            'role' => 'staff',
            'module_access' => $access,
        ]);

        $allowed = ExecutiveSummaryWidgets::allowed($user);

        $this->assertTrue($allowed['exec-empty-hint'] ?? false);
        $this->assertArrayNotHasKey('exec-my-day', $allowed);
        $this->assertArrayNotHasKey('exec-due-tomorrow', $allowed);
        $this->assertArrayNotHasKey('exec-firm', $allowed);
    }

    public function test_staff_with_tasks_only_sees_my_day_not_firm(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'module_access' => ModuleAccess::defaultsForRole('staff'),
        ]);

        $allowed = ExecutiveSummaryWidgets::allowed($user);

        $this->assertTrue($allowed['exec-my-day'] ?? false);
        $this->assertTrue($allowed['exec-due-tomorrow'] ?? false);
        $this->assertArrayNotHasKey('exec-firm', $allowed);
        $this->assertArrayNotHasKey('exec-empty-hint', $allowed);
    }

    public function test_partner_sees_firm_widget(): void
    {
        $user = User::factory()->create(['role' => 'partner']);

        $allowed = ExecutiveSummaryWidgets::allowed($user);

        $this->assertTrue($allowed['exec-firm'] ?? false);
    }

    public function test_default_order_filters_disallowed_widgets(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'module_access' => [
                'dashboard' => true,
                'tasks' => false,
                'service_dues' => false,
            ],
        ]);

        $order = ExecutiveSummaryWidgets::defaultOrder($user);

        $this->assertContains('exec-empty-hint', $order);
        $this->assertNotContains('exec-my-day', $order);
        $this->assertNotContains('exec-firm', $order);
    }
}
