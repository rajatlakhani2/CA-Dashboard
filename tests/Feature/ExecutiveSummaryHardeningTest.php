<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use App\Services\DashboardMissionControlService;
use App\Support\ModuleAccess;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutiveSummaryHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role = 'staff', ?array $moduleAccess = null): User
    {
        $organization = Organization::create([
            'name' => 'Hardening Firm',
            'slug' => 'hardening',
            'plan' => Organization::PLAN_PROFESSIONAL,
            'seat_limit' => 5,
            'is_active' => true,
        ]);

        return User::factory()->create([
            'organization_id' => $organization->id,
            'role' => $role,
            'module_access' => $moduleAccess ?? ModuleAccess::defaultsForRole($role),
        ]);
    }

    private function createTask(User $user, array $overrides = []): Task
    {
        return Task::create(array_merge([
            'title' => 'Hardening task',
            'assigned_to' => $user->id,
            'due_date' => now(),
            'status' => Task::STATUS_PENDING,
            'priority' => 'Normal',
            'created_by' => $user->id,
            'organization_id' => $user->organization_id,
        ], $overrides));
    }

    public function test_non_partner_dashboard_excludes_firm_widget_from_dom(): void
    {
        $staff = $this->createUser('staff', ['dashboard' => true, 'tasks' => true]);

        $this->actingAs($staff)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('data-dashboard-widget="exec-firm"', false);
    }

    public function test_partner_dashboard_includes_firm_widget(): void
    {
        $partner = $this->createUser('partner');

        $this->actingAs($partner)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-dashboard-widget="exec-firm"', false);
    }

    public function test_user_without_finance_modules_excludes_finance_widget(): void
    {
        $access = ModuleAccess::defaultsForRole('staff');
        $access['invoices'] = false;
        $access['billing'] = false;
        $access['payments'] = false;
        $access['expenses'] = false;
        $access['subscriptions'] = false;

        $staff = $this->createUser('staff', $access);

        $this->actingAs($staff)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('data-dashboard-widget="exec-finance"', false);
    }

    public function test_finance_widget_masks_values_with_x_cloak_and_xxx_placeholder(): void
    {
        $partner = $this->createUser('partner');

        $html = $this->actingAs($partner)
            ->get('/dashboard')
            ->assertOk()
            ->getContent();

        if (! str_contains($html, 'data-dashboard-widget="exec-finance"')) {
            $this->markTestSkipped('Finance widget not rendered for this user configuration.');
        }

        $this->assertStringContainsString('x-cloak', $html);
        $this->assertStringContainsString('>xxx<', $html);
        $this->assertStringContainsString('[x-cloak]', $html);
        $this->assertStringContainsString('data-finance-snapshot-url', $html);
        $this->assertStringContainsString('executiveFinanceMask', $html);
    }

    public function test_finance_snapshot_endpoint_returns_figures_for_partner(): void
    {
        $partner = $this->createUser('partner');

        $response = $this->actingAs($partner)
            ->getJson(route('dashboard.finance-snapshot'));

        $response->assertOk();
        $response->assertJsonStructure([
            'target',
            'achieved',
            'efficiency',
            'outstanding',
            'collected_mtd',
            'overdue',
            'collected_today',
            'progress_percent',
        ]);
    }

    public function test_finance_snapshot_forbidden_without_finance_modules(): void
    {
        $access = ModuleAccess::defaultsForRole('staff');
        $access['invoices'] = false;
        $access['billing'] = false;
        $access['payments'] = false;
        $access['expenses'] = false;
        $access['subscriptions'] = false;

        $staff = $this->createUser('staff', $access);

        $this->actingAs($staff)
            ->getJson(route('dashboard.finance-snapshot'))
            ->assertForbidden();
    }

    public function test_dashboard_html_excludes_finance_snapshot_figures(): void
    {
        $partner = $this->createUser('partner');

        $html = $this->actingAs($partner)
            ->get('/dashboard')
            ->assertOk()
            ->getContent();

        if (! str_contains($html, 'data-dashboard-widget="exec-finance"')) {
            $this->markTestSkipped('Finance widget not rendered for this user configuration.');
        }

        $snapshot = $this->actingAs($partner)
            ->getJson(route('dashboard.finance-snapshot'))
            ->json();

        foreach (['achieved', 'outstanding', 'collected_mtd', 'collected_today'] as $key) {
            $value = trim((string) ($snapshot[$key] ?? ''));
            if ($value !== '' && $value !== '₹ 0' && $value !== '—') {
                $this->assertStringNotContainsString($value, $html, "Finance figure leaked in HTML: {$key}");
            }
        }
    }

    public function test_allowed_widgets_json_present_on_sortable_container(): void
    {
        $user = $this->createUser('manager');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-allowed-widgets=', false)
            ->assertSee('exec-kpis', false);
    }

    public function test_layout_script_includes_corrupt_storage_recovery(): void
    {
        $user = $this->createUser('manager');

        $html = $this->actingAs($user)
            ->get('/dashboard')
            ->getContent();

        $this->assertStringContainsString('Vouchex: layout parse error', $html);
        $this->assertStringContainsString('VouchexExecLayout', $html);
        $this->assertStringContainsString('migratePixelSizes', $html);
        $this->assertStringContainsString('notifyLayoutSaveFailed', $html);
    }

    public function test_tasks_overdue_kpi_uses_sky_tone_when_zero(): void
    {
        $user = $this->createUser('staff', ['dashboard' => true, 'tasks' => true]);

        $kpis = app(DashboardMissionControlService::class)->build($user)['executive_kpis'];
        $overdue = collect($kpis)->firstWhere('label', 'Tasks overdue');

        $this->assertNotNull($overdue);
        $this->assertSame(0, $overdue['value']);
        $this->assertSame('sky', $overdue['tone']);
    }

    public function test_tasks_overdue_kpi_uses_rose_tone_when_positive(): void
    {
        $user = $this->createUser('staff', ['dashboard' => true, 'tasks' => true]);

        $this->createTask($user, [
            'title' => 'Overdue hardening task',
            'due_date' => Carbon::yesterday(),
        ]);

        $kpis = app(DashboardMissionControlService::class)->build($user)['executive_kpis'];
        $overdue = collect($kpis)->firstWhere('label', 'Tasks overdue');

        $this->assertNotNull($overdue);
        $this->assertGreaterThan(0, $overdue['value']);
        $this->assertSame('rose', $overdue['tone']);
    }

    public function test_task_due_today_respects_timezone_boundary(): void
    {
        config(['app.timezone' => 'Asia/Kolkata']);
        Carbon::setTestNow(Carbon::parse('2026-06-15 20:00:00', 'Asia/Kolkata'));

        $user = $this->createUser('staff', ['dashboard' => true, 'tasks' => true]);

        $this->createTask($user, [
            'title' => 'Today boundary task',
            'due_date' => '2026-06-15',
        ]);

        $this->createTask($user, [
            'title' => 'Tomorrow boundary task',
            'due_date' => '2026-06-16',
        ]);

        $kpis = app(DashboardMissionControlService::class)->build($user)['executive_kpis'];
        $dueToday = collect($kpis)->firstWhere('label', 'Tasks due today');

        $this->assertSame(1, $dueToday['value']);

        Carbon::setTestNow();
    }

    public function test_dashboard_build_id_matches_controller_constant(): void
    {
        $user = $this->createUser('manager');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('executive-summary-v5-hardening-20260612', false);
    }
}
