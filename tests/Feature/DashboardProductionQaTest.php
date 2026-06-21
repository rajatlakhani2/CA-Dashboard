<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Organization;
use App\Models\ServiceDue;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Services\DashboardMissionControlService;
use App\Support\ModuleAccess;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Production QA checklist — automated coverage for dashboard deployment gate.
 *
 * @see Vouchex Dashboard Full QA Testing Checklist (TC-001 – TC-060)
 */
class DashboardProductionQaTest extends TestCase
{
    use RefreshDatabase;

    private function createOrganizationUser(string $role = 'manager', ?array $moduleAccess = null): User
    {
        $organization = Organization::create([
            'name' => 'QA Firm',
            'slug' => 'qafirm',
            'plan' => Organization::PLAN_PROFESSIONAL,
            'seat_limit' => 10,
            'is_active' => true,
        ]);

        return User::factory()->create([
            'organization_id' => $organization->id,
            'role' => $role,
            'password' => Hash::make('secret'),
            'module_access' => $moduleAccess ?? ModuleAccess::defaultsForRole($role),
        ]);
    }

    /** TC-001 Login success → dashboard loads */
    public function test_tc_001_authenticated_user_dashboard_loads(): void
    {
        $user = $this->createOrganizationUser('manager');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Executive Summary', false);
    }

    /** TC-002 Invalid workspace — login page does not crash */
    public function test_tc_002_invalid_workspace_shows_login_without_crash(): void
    {
        $this->get(route('login', ['workspace' => 'invalidworkspace']))
            ->assertOk();

        $user = $this->createOrganizationUser('staff');

        $this->post(route('login'), [
            'workspace' => 'invalidworkspace',
            'email' => $user->email,
            'password' => 'secret',
        ])->assertSessionHasErrors('workspace');
    }

    /** TC-003 Unauthorized dashboard access */
    public function test_tc_003_guest_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    /** TC-004 Tasks module disabled */
    public function test_tc_004_tasks_disabled_hides_my_day_and_task_kpis(): void
    {
        $access = ModuleAccess::defaultsForRole('staff');
        $access['tasks'] = false;
        $access['service_dues'] = false;

        $user = $this->createOrganizationUser('staff', $access);

        Setting::set('enabled_modules', json_encode(array_merge(ModuleAccess::defaultsForRole('staff'), [
            'tasks' => false,
        ])));

        $html = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('data-dashboard-widget="exec-my-day"', $html);
        $this->assertStringNotContainsString('Tasks due today', $html);

        $kpis = app(DashboardMissionControlService::class)->build($user)['executive_kpis'];
        $this->assertEmpty(collect($kpis)->firstWhere('label', 'Tasks due today'));
    }

    /** TC-005 Service dues disabled — due tomorrow panel still available for tasks */
    public function test_tc_005_service_dues_disabled_due_tomorrow_tasks_only(): void
    {
        $access = ModuleAccess::defaultsForRole('staff');
        $access['service_dues'] = false;

        $user = $this->createOrganizationUser('staff', $access);

        $tomorrow = now()->addDay()->toDateString();
        Task::create([
            'title' => 'Tomorrow only task',
            'assigned_to' => $user->id,
            'due_date' => $tomorrow,
            'status' => Task::STATUS_PENDING,
            'priority' => 'Normal',
            'created_by' => $user->id,
        ]);

        $html = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-dashboard-widget="exec-due-tomorrow"', $html);
        $this->assertStringContainsString('Tomorrow only task', $html);
        $this->assertStringNotContainsString('text-violet-700', $html);
    }

    /** TC-006 Finance disabled */
    public function test_tc_006_finance_disabled_hides_finance_widget(): void
    {
        $access = ModuleAccess::defaultsForRole('staff');
        $access['invoices'] = false;
        $access['billing'] = false;
        $access['payments'] = false;
        $access['expenses'] = false;
        $access['subscriptions'] = false;

        $user = $this->createOrganizationUser('staff', $access);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-dashboard-widget="exec-finance"', false);
    }

    /** TC-007 Partner sees firm overview */
    public function test_tc_007_partner_sees_firm_overview_and_finance(): void
    {
        $partner = $this->createOrganizationUser('partner');

        $html = $this->actingAs($partner)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-dashboard-widget="exec-firm"', $html);
        $this->assertStringContainsString('data-dashboard-widget="exec-finance"', $html);
    }

    /** TC-008 Regular user — no firm overview in DOM */
    public function test_tc_008_staff_dom_excludes_partner_firm_widget(): void
    {
        $staff = $this->createOrganizationUser('staff');

        $html = $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('data-dashboard-widget="exec-firm"', $html);
        $this->assertStringNotContainsString('Firm overview', $html);
    }

    /** TC-009–TC-011 Collapse persistence — script + storage key present */
    public function test_tc_009_collapse_state_persistence_hooks_present(): void
    {
        $user = $this->createOrganizationUser('manager');

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('executive-summary-collapsed', $html);
        $this->assertStringContainsString('exec-widget__collapse', $html);
        $this->assertStringContainsString('vouchex_dashboard_layout_', $html);
    }

    /** TC-012 Collapse disables resize layer */
    public function test_tc_012_collapsed_widget_hides_resize_layer_in_css(): void
    {
        $user = $this->createOrganizationUser('manager');

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('.exec-widget--collapsed .exec-widget__resize-layer', $html);
        $this->assertStringContainsString('display: none', $html);
    }

    /** TC-013 Reorder persistence — sortable + storage */
    public function test_tc_013_widget_reorder_persistence_script_present(): void
    {
        $user = $this->createOrganizationUser('manager');

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('executive-summary-sortable', $html);
        $this->assertStringContainsString('Sortable', $html);
        $this->assertStringContainsString('scheduleSave', $html);
    }

    /** TC-017–TC-020 Grid layout CSS markers */
    public function test_tc_017_grid_column_spans_and_dense_pack_defined(): void
    {
        $user = $this->createOrganizationUser('manager');

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('grid-auto-flow: row dense', $html);
        $this->assertStringContainsString('exec-widget--col-6', $html);
        $this->assertStringContainsString('exec-widget--col-4', $html);
    }

    /** TC-020 Mobile full-width widgets */
    public function test_tc_020_mobile_breakpoint_forces_full_width_columns(): void
    {
        $user = $this->createOrganizationUser('manager');

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('@media (max-width: 639px)', $html);
        $this->assertStringContainsString('grid-column: span 12', $html);
    }

    /** TC-022–TC-025 Resize constraints in script */
    public function test_tc_022_resize_height_bounds_in_script(): void
    {
        $user = $this->createOrganizationUser('manager');

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('MIN_H = 72', $html);
        $this->assertStringContainsString('MAX_H = 720', $html);
        $this->assertStringContainsString('COL_SPANS = [3, 4, 6, 8, 12]', $html);
        $this->assertStringContainsString('resetWidgetSize', $html);
    }

    /** TC-026 Corrupted JSON recovery */
    public function test_tc_026_corrupt_layout_json_recovery_script(): void
    {
        $user = $this->createOrganizationUser('manager');

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('Vouchex: layout parse error', $html);
        $this->assertStringContainsString('getDefaultLayout', $html);
    }

    /** TC-031 Calendar updateSize after expand */
    public function test_tc_031_calendar_update_size_deferred_after_layout_change(): void
    {
        $user = $this->createOrganizationUser('manager');

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('calendar.updateSize', $html);
        $this->assertStringContainsString('350', $html);
    }

    /** TC-033 Invalid past date — server rejects reschedule */
    public function test_tc_033_calendar_reschedule_rejects_past_date(): void
    {
        $user = $this->createOrganizationUser('staff');
        $task = Task::create([
            'title' => 'Move me',
            'assigned_to' => $user->id,
            'due_date' => now()->addDay(),
            'status' => Task::STATUS_PENDING,
            'priority' => 'Normal',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->postJson(route('calendar.update'), [
                'type' => 'task',
                'id' => $task->id,
                'new_date' => now()->subDay()->toDateString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['new_date']);
    }

    /** TC-034 Concurrent edit — missing task returns graceful failure */
    public function test_tc_034_calendar_reschedule_missing_task_returns_failure(): void
    {
        $user = $this->createOrganizationUser('staff');

        $this->actingAs($user)
            ->postJson(route('calendar.update'), [
                'type' => 'task',
                'id' => 999999,
                'new_date' => now()->addDays(2)->toDateString(),
            ])
            ->assertOk()
            ->assertJson(['success' => false]);
    }

    /** TC-036 My Day empty state */
    public function test_tc_036_my_day_empty_state_when_no_tasks(): void
    {
        $user = $this->createOrganizationUser('staff');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Nothing due today', false);
    }

    /** TC-038 Overdue task appears in My Day */
    public function test_tc_038_overdue_task_shows_in_my_day(): void
    {
        $user = $this->createOrganizationUser('staff');

        Task::create([
            'title' => 'Overdue QA task',
            'assigned_to' => $user->id,
            'due_date' => Carbon::yesterday(),
            'status' => Task::STATUS_PENDING,
            'priority' => 'High',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Overdue QA task', false);
    }

    /** TC-042 Due tomorrow count = tasks + dues */
    public function test_tc_042_due_tomorrow_badge_matches_items(): void
    {
        $partner = $this->createOrganizationUser('partner');
        $tomorrow = now()->addDay()->toDateString();

        Task::create([
            'title' => 'T1',
            'assigned_to' => $partner->id,
            'due_date' => $tomorrow,
            'status' => Task::STATUS_PENDING,
            'priority' => 'Normal',
            'created_by' => $partner->id,
        ]);
        Task::create([
            'title' => 'T2',
            'assigned_to' => $partner->id,
            'due_date' => $tomorrow,
            'status' => Task::STATUS_PENDING,
            'priority' => 'Normal',
            'created_by' => $partner->id,
        ]);

        $this->actingAs($partner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Due tomorrow (2)', false)
            ->assertSee('T1', false)
            ->assertSee('T2', false);
    }

    /** TC-044 KPI order for partner with tasks + dues */
    public function test_tc_044_kpi_order_for_partner(): void
    {
        $partner = $this->createOrganizationUser('partner');

        $labels = collect(app(DashboardMissionControlService::class)->build($partner)['executive_kpis'])
            ->pluck('label')
            ->values()
            ->all();

        $expectedPrefix = [
            'Tasks due today',
            'Tasks overdue',
            'Tasks next 7 days',
            'Tasks next 15 days',
            'Compliance overdue',
            'Overdue invoices',
            'Total clients',
            'Due tomorrow',
        ];

        $this->assertSame($expectedPrefix, array_slice($labels, 0, count($expectedPrefix)));
    }

    /** TC-045 KPI links resolve to real routes */
    public function test_tc_045_kpi_links_are_valid_routes(): void
    {
        $partner = $this->createOrganizationUser('partner');

        $kpis = app(DashboardMissionControlService::class)->build($partner)['executive_kpis'];

        foreach ($kpis as $kpi) {
            $this->assertNotEmpty($kpi['url'] ?? null, 'KPI missing url: ' . ($kpi['label'] ?? '?'));

            $this->actingAs($partner)
                ->get($kpi['url'])
                ->assertOk();
        }
    }

    /** TC-046 Invoice KPI hidden without invoice module */
    public function test_tc_046_invoice_kpi_hidden_without_invoices_module(): void
    {
        $access = ModuleAccess::defaultsForRole('staff');
        $access['invoices'] = false;

        $user = $this->createOrganizationUser('staff', $access);

        $labels = collect(app(DashboardMissionControlService::class)->build($user)['executive_kpis'])
            ->pluck('label')
            ->all();

        $this->assertNotContains('Overdue invoices', $labels);
    }

    /** TC-047–TC-049 Finance default mask */
    public function test_tc_047_finance_defaults_to_xxx_mask(): void
    {
        $partner = $this->createOrganizationUser('partner');

        $html = $this->actingAs($partner)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('>xxx<', $html);
        $this->assertStringContainsString('x-cloak', $html);
        $this->assertStringContainsString('Tap a card to reveal', $html);
    }

    /** TC-050 JS disabled — no blade-echoed finance figures in static HTML */
    public function test_tc_050_finance_values_not_blade_echoed_in_static_html(): void
    {
        $partner = $this->createOrganizationUser('partner');

        $html = $this->actingAs($partner)->get(route('dashboard'))->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/exec-finance-card__value[^>]*>\s*₹\s*[\d,]+/u',
            $html
        );
        $this->assertStringContainsString('data-finance-snapshot-url', $html);
        $this->assertStringNotContainsString('x-text="revealed ?', $html);

        $snapshot = $this->actingAs($partner)->getJson(route('dashboard.finance-snapshot'))->json();
        $achieved = trim((string) ($snapshot['achieved'] ?? ''));
        if ($achieved !== '' && $achieved !== '₹ 0') {
            $this->assertStringNotContainsString($achieved, $html);
        }
    }

    /** TC-051 DOM inspection — staff HTML excludes firm widget id */
    public function test_tc_051_staff_html_excludes_partner_widgets(): void
    {
        $staff = $this->createOrganizationUser('staff');

        $html = $this->actingAs($staff)->get(route('dashboard'))->getContent();

        $this->assertStringNotContainsString('data-dashboard-widget="exec-firm"', $html);
    }

    /** TC-052 API authorization — deploy probe restricted */
    public function test_tc_052_deploy_probe_forbidden_for_staff(): void
    {
        $staff = $this->createOrganizationUser('staff');

        $this->actingAs($staff)
            ->get(route('dashboard.deploy-probe'))
            ->assertForbidden();
    }

    /** TC-053 XSS — task title escaped on dashboard */
    public function test_tc_053_xss_task_title_escaped_on_dashboard(): void
    {
        $user = $this->createOrganizationUser('staff');

        Task::create([
            'title' => '<script>alert(1)</script>',
            'assigned_to' => $user->id,
            'due_date' => now(),
            'status' => Task::STATUS_PENDING,
            'priority' => 'Normal',
            'created_by' => $user->id,
        ]);

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    /** TC-054 SQL injection — client search does not error */
    public function test_tc_054_sql_injection_search_handled_safely(): void
    {
        $user = $this->createOrganizationUser('manager');

        $this->actingAs($user)
            ->get(route('clients.index', ['search' => "' OR 1=1 --"]))
            ->assertOk();
    }

    /** TC-058–TC-059 Accessibility markers */
    public function test_tc_058_keyboard_and_aria_markers_present(): void
    {
        $user = $this->createOrganizationUser('manager');

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('aria-label="Drag to reorder"', $html);
        $this->assertStringContainsString('aria-expanded', $html);
        $this->assertStringContainsString('aria-label="Collapse or expand', $html);
    }
}
