<?php

namespace App\Http\Controllers;

use App\Models\ServiceDue;
use App\Models\Task;
use App\Services\DashboardLayoutDataService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Bulk clean-up of overdue work for partners and managers: tick many overdue tasks / filings,
 * then mark them done or (tasks) move them to a new date. Nothing happens without a click.
 */
class OverdueCleanupController extends Controller
{
    private const AGES = [0 => 'All overdue', 7 => 'Over a week late', 30 => 'Over a month late', 90 => 'Over 3 months late'];

    public function index(Request $request, DashboardLayoutDataService $data)
    {
        $user = $request->user();
        $f = $data->filters($request, $user);
        $type = in_array($request->query('type'), ['tasks', 'filings'], true) ? $request->query('type') : 'all';
        $age = array_key_exists((int) $request->query('age'), self::AGES) ? (int) $request->query('age') : 0;
        $cutoff = Carbon::today()->subDays($age);
        $clientIds = $data->clientScope($user, $f)->pluck('id');

        $tasks = collect();
        if ($type !== 'filings' && $user->canAccessModule('tasks')) {
            $tasks = Task::query()
                ->with(['client:id,name', 'assignee:id,name'])
                ->whereNotIn('status', Task::TERMINAL_STATUSES)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', $cutoff)
                ->where(function ($q) use ($f, $clientIds) {
                    if ($f['staff']) {
                        $q->where('assigned_to', $f['staff']);
                    } else {
                        $q->whereIn('client_id', $clientIds)->orWhereNull('client_id');
                    }
                })
                ->orderBy('due_date')
                ->limit(500)
                ->get();
        }

        $filings = collect();
        if ($type !== 'tasks' && $user->canAccessModule('service_dues')) {
            $filings = ServiceDue::query()
                ->with(['clientService.client:id,name,manager_id', 'clientService.client.manager:id,name', 'clientService.service:id,name'])
                ->whereIn('status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_OVERDUE, ServiceDue::STATUS_EXTENDED])
                ->whereDate('due_date', '<', $cutoff)
                ->whereHas('clientService', fn ($q) => $q->whereIn('client_id', $clientIds))
                ->orderBy('due_date')
                ->limit(500)
                ->get();
        }

        $today = Carbon::today();
        $rows = $tasks->map(fn (Task $t) => [
            'kind' => 'task',
            'id' => $t->id,
            'title' => $t->title,
            'client' => $t->client?->name ?? 'Internal',
            'who' => $t->assignee?->name ?? 'Unassigned',
            'due' => $t->due_date,
            'late' => (int) $t->due_date->diffInDays($today),
            'url' => route('tasks.edit', $t),
        ])->concat($filings->map(fn (ServiceDue $d) => [
            'kind' => 'filing',
            'id' => $d->id,
            'title' => ($d->clientService?->service?->name ?? 'Filing') . ' · ' . $d->due_date->format('M Y'),
            'client' => $d->clientService?->client?->name ?? '—',
            'who' => $d->clientService?->client?->manager?->name ?? '—',
            'due' => $d->due_date,
            'late' => (int) $d->due_date->diffInDays($today),
            'url' => $d->clientService?->client ? route('clients.show', $d->clientService->client) : route('service-dues.index'),
        ]))->sortByDesc('late')->values();

        return view('dashboard.cleanup', [
            'rows' => $rows,
            'type' => $type,
            'age' => $age,
            'ages' => self::AGES,
            'filters' => $f,
            'filterOptions' => $data->filterOptions($user),
            'capped' => $tasks->count() >= 500 || $filings->count() >= 500,
        ]);
    }

    public function apply(Request $request, DashboardLayoutDataService $data)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:done,move'],
            'tasks' => ['array'],
            'tasks.*' => ['integer'],
            'filings' => ['array'],
            'filings.*' => ['integer'],
            'date' => ['required_if:action,move', 'nullable', 'date', 'after_or_equal:today'],
        ]);

        $user = $request->user();
        $taskIds = $validated['tasks'] ?? [];
        $filingIds = $validated['filings'] ?? [];
        $tasksDone = 0;
        $filingsDone = 0;
        $skipped = 0;

        if ($taskIds && $user->canAccessModule('tasks')) {
            foreach (Task::whereIn('id', $taskIds)->whereNotIn('status', Task::TERMINAL_STATUSES)->get() as $task) {
                if ($validated['action'] === 'done') {
                    if (Gate::forUser($user)->denies('updateStatus', $task)) { $skipped++; continue; }
                    $task->update(['status' => Task::STATUS_COMPLETED]);
                } else {
                    if (Gate::forUser($user)->denies('update', $task)) { $skipped++; continue; }
                    $task->update(['due_date' => $validated['date']]);
                }
                $tasksDone++;
            }
        }

        if ($filingIds && $validated['action'] === 'done' && $user->canAccessModule('service_dues')) {
            $f = ['scope' => 'firm', 'staff' => null, 'branch' => null, 'period' => '6m', 'can_see_firm' => true];
            $clientIds = $data->clientScope($user, $f)->pluck('id');
            $filingsDone = ServiceDue::whereIn('id', $filingIds)
                ->whereIn('status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_OVERDUE, ServiceDue::STATUS_EXTENDED])
                ->whereHas('clientService', fn ($q) => $q->whereIn('client_id', $clientIds))
                ->get()
                ->each(fn (ServiceDue $d) => $d->update([
                    'status' => ServiceDue::STATUS_COMPLETED,
                    'completed_at' => Carbon::now(),
                    'completed_by' => $user->id,
                ]))
                ->count();
        }

        $parts = [];
        if ($tasksDone) {
            $parts[] = $tasksDone . ' ' . ($tasksDone === 1 ? 'task' : 'tasks');
        }
        if ($filingsDone) {
            $parts[] = $filingsDone . ' ' . ($filingsDone === 1 ? 'filing' : 'filings');
        }
        $what = $parts ? implode(' and ', $parts) : 'Nothing';
        $message = $validated['action'] === 'done'
            ? "{$what} marked done."
            : "{$what} moved to " . Carbon::parse($validated['date'])->format('d M Y') . '.';
        if ($skipped) {
            $message .= " {$skipped} skipped (no permission).";
        }
        if ($validated['action'] === 'move' && $filingIds) {
            $message .= ' Filing due dates are statutory, so filings were not moved.';
        }

        return redirect()->back()->with('success', $message);
    }
}
