<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCalendarDateRequest;
use App\Models\PersonalRenewal;
use App\Models\ServiceDue;
use App\Models\Task;
use App\Services\DashboardCalendarBuilder;
use App\Services\DashboardCalendarFilters;
use App\Services\DashboardMetricsService;
use App\Services\DashboardMissionControlService;
use App\Services\NotificationSummaryService;
use App\Services\OrganizationWorkspaceService;
use App\Services\PartnerFirmOverviewService;
use App\Services\WorkspaceOnboardingService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        DashboardMetricsService $metrics,
        DashboardMissionControlService $missionControl,
        OrganizationWorkspaceService $workspaceService,
        NotificationSummaryService $notificationSummary,
        WorkspaceOnboardingService $onboarding,
    ) {
        $user = $request->user();
        $data = $metrics->build($user);
        $workspace = $workspaceService->forUser($user);

        $calendarBuilder = app(DashboardCalendarBuilder::class);
        $calendarFilters = DashboardCalendarFilters::fromRequest($request);
        $calendarEvents = $calendarBuilder->buildEvents($request->user(), $calendarFilters);
        $calendarFilterOptions = $calendarBuilder->filterOptions($request->user());

        $showWelcomeModal = ! session()->has('welcome_shown');
        if ($showWelcomeModal) {
            session(['welcome_shown' => true]);
        }

        $isPartner = $user?->isPartner() ?? false;

        return view('dashboard', array_merge($data, [
            'calendarEvents' => $calendarEvents,
            'calendarFilters' => $calendarFilters,
            'calendarFilterOptions' => $calendarFilterOptions,
            'showWelcomeModal' => $showWelcomeModal,
            'positiveThought' => $metrics->randomPositiveThought(),
            'workspace' => $workspace,
            'missionControl' => $missionControl->build($user),
            'notificationGroups' => $notificationSummary->groups(),
            'onboarding' => $onboarding->forUser($user),
            'initialDashboardTab' => $this->initialDashboardTab($request, $isPartner),
            'firmOverview' => $isPartner ? app(PartnerFirmOverviewService::class)->build() : null,
            'showFirmOverviewTab' => $isPartner,
            'dashboardBuildId' => 'tabs-v2-20260604',
        ]));
    }

    /** JSON probe for live deploy verification (partner/manager only). */
    public function deployProbe()
    {
        abort_unless(auth()->user()?->hasRole('partner', 'manager'), 403);

        $path = resource_path('views/dashboard.blade.php');
        $content = is_readable($path) ? (string) file_get_contents($path) : '';

        $buildFile = public_path('dashboard-build.txt');
        $buildStamp = is_readable($buildFile) ? trim((string) file_get_contents($buildFile)) : null;

        return response()->json([
            'build' => 'tabs-v2-20260604',
            'deploy_stamp' => $buildStamp,
            'view_path' => $path,
            'view_mtime' => is_readable($path) ? date('c', filemtime($path)) : null,
            'tabs_v2_marker' => str_contains($content, 'dashboard-tabs-v2'),
            'workspace_header_in_view' => str_contains($content, 'workspace-header'),
            'tab_root_marker' => str_contains($content, 'dashboard-tab-root'),
            'controller_deploy_probe' => true,
        ]);
    }

    private function initialDashboardTab(Request $request, bool $isPartner): string
    {
        $tab = $request->query('tab');

        if ($tab === 'firm' && $isPartner) {
            return 'firm';
        }

        if (in_array($tab, ['calendar', 'schedule'], true)) {
            return 'calendar';
        }

        if (in_array($tab, ['workload', 'financials', 'overview'], true)) {
            return $tab;
        }

        return 'overview';
    }

    public function calendarEvents(Request $request, DashboardCalendarBuilder $builder)
    {
        $filters = DashboardCalendarFilters::fromRequest($request);

        return response()->json([
            'events' => $builder->buildEvents($request->user(), $filters),
        ]);
    }

    public function updateDate(UpdateCalendarDateRequest $request)
    {
        $validated = $request->validated();

        try {
            if ($validated['type'] == 'task') {
                $item = Task::find($validated['id']);
                $user = auth()->user();
                if ($item && ($item->assigned_to == $user->id || $user->hasRole('partner', 'manager'))) {
                    $this->authorize('update', $item);
                    $item->due_date = $validated['new_date'];
                    $item->save();

                    return response()->json(['success' => true]);
                }
            } elseif ($validated['type'] == 'due') {
                $item = ServiceDue::find($validated['id']);
                if ($item && auth()->user()->hasRole('partner', 'manager')) {
                    $item->due_date = $validated['new_date'];
                    $item->save();

                    return response()->json(['success' => true]);
                }
            } elseif ($validated['type'] == 'renewal') {
                $item = PersonalRenewal::find($validated['id']);
                if ($item && $item->user_id == auth()->id()) {
                    $item->due_date = $validated['new_date'];
                    $item->save();

                    return response()->json(['success' => true]);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }

        return response()->json(['success' => false, 'message' => 'Item not found']);
    }
}
