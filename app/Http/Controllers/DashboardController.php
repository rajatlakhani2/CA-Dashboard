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
        ]));
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
