<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCalendarDateRequest;
use App\Models\Client;
use App\Models\PersonalRenewal;
use App\Models\ServiceDue;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use App\Services\DashboardCalendarBuilder;
use App\Services\DashboardCalendarFilters;
use App\Services\DashboardMetricsService;
use App\Services\DashboardMissionControlService;
use App\Services\NotificationSummaryService;
use App\Services\OrganizationWorkspaceService;
use App\Services\PartnerFirmOverviewService;
use App\Services\WorkspaceOnboardingService;
use App\Support\ExecutiveSummaryWidgets;
use App\Support\UserTimezone;
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

        $isPartner = $user?->isWorkspaceOwner() ?? false;
        $myDay = $this->myDayTasksFor($user);
        $dueTomorrow = $this->dueTomorrowFor($user);

        return view('dashboard', array_merge($data, [
            'myDayTasksToday' => $myDay['today'],
            'myDayTasksUpcoming' => $myDay['upcoming'],
            'dueTomorrowTasks' => $dueTomorrow['tasks'],
            'dueTomorrowDues' => $dueTomorrow['dues'],
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
            'firmOverview' => $isPartner ? app(PartnerFirmOverviewService::class)->build($user) : null,
            'showFirmOverviewTab' => $isPartner,
            'allowedExecutiveWidgets' => ExecutiveSummaryWidgets::allowed($user),
            'dashboardBuildId' => 'executive-summary-v5-hardening-20260612',
        ]));
    }

    /** JSON finance figures for executive summary tap-to-reveal (not embedded in HTML). */
    public function financeSnapshot(Request $request, DashboardMissionControlService $missionControl)
    {
        $user = $request->user();
        abort_unless($user?->canAccessModule('dashboard'), 403);
        abort_unless(\App\Support\ModuleGate::hasFinanceModule($user), 403);

        return response()->json($missionControl->executiveFinanceSnapshot($user));
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

    /** @return array{today: \Illuminate\Support\Collection, upcoming: \Illuminate\Support\Collection} */
    private function myDayTasksFor(?\App\Models\User $user): array
    {
        if (! $user) {
            return ['today' => collect(), 'upcoming' => collect()];
        }

        $query = Task::with(['client'])
            ->whereNotIn('status', Task::TERMINAL_STATUSES)
            ->where('assigned_to', $user->id);

        $today = now(UserTimezone::for($user))->startOfDay();

        return [
            'today' => (clone $query)->whereDate('due_date', '<=', $today)->orderBy('due_date')->get(),
            'upcoming' => (clone $query)->whereDate('due_date', '>', $today)->orderBy('due_date')->limit(10)->get(),
        ];
    }

    /** @return array{tasks: \Illuminate\Support\Collection, dues: \Illuminate\Support\Collection<int, array<string, mixed>>} */
    private function dueTomorrowFor(?\App\Models\User $user): array
    {
        if (! $user) {
            return ['tasks' => collect(), 'dues' => collect()];
        }

        $tomorrow = now(UserTimezone::for($user))->addDay()->startOfDay();
        $managesFirm = $user->managesFirmModules();
        $tasks = collect();
        $dues = collect();

        if ($user->canAccessModule('tasks')) {
            $tasks = Task::with(['client'])
                ->whereNotIn('status', Task::TERMINAL_STATUSES)
                ->when(! $managesFirm, fn ($q) => $q->where('assigned_to', $user->id))
                ->whereDate('due_date', $tomorrow)
                ->orderBy('title')
                ->get();
        }

        if ($user->canAccessModule('service_dues')) {
            $dueQuery = ServiceDue::query()
                ->with(['clientService.client', 'clientService.service'])
                ->whereDate('due_date', $tomorrow)
                ->where('status', ServiceDue::STATUS_PENDING);

            if ($user->isManager() && $user->branch_id) {
                $dueQuery->whereHas('clientService.client', fn (Builder $c) => $c->where('branch_id', $user->branch_id));
            } elseif (! $user->hasRole('partner', 'manager')) {
                $visibleIds = Client::visibleTo($user)->pluck('id');
                $dueQuery->whereHas('clientService', fn (Builder $q) => $q->whereIn('client_id', $visibleIds));
            }

            $dues = $dueQuery->get()->map(fn (ServiceDue $due) => [
                'id' => $due->id,
                'client_name' => $due->clientService?->client?->name ?? 'Client',
                'service_name' => $due->clientService?->service?->name ?? 'Service',
                'url' => $due->clientService?->client
                    ? route('clients.show', $due->clientService->client_id)
                    : route('service-dues.index'),
            ])->values();
        }

        return ['tasks' => $tasks, 'dues' => $dues];
    }

    private function initialDashboardTab(Request $request, bool $isPartner): string
    {
        $tab = $request->query('tab');

        if ($tab === 'firm' && $isPartner) {
            return 'firm';
        }

        if (in_array($tab, ['calendar', 'schedule'], true)) {
            return 'overview';
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
                if ($item
                    && $item->user_id == auth()->id()
                    && $item->status !== PersonalRenewal::STATUS_PAID) {
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
