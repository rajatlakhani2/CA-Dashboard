<?php

namespace App\Services\Reports;

use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StaffProductivityReportBuilder
{
    /**
     * @return array{rows: Collection, totals: array<string, float|int>}
     */
    public function build(User $actor, Carbon $start, Carbon $end): array
    {
        $usersQuery = User::query()
            ->whereIn('role', ['manager', 'staff', 'associate', 'article', 'intern'])
            ->orderBy('name');

        ReportScopeHelper::scopeUsers($usersQuery, $actor);

        $rows = $usersQuery->get()->map(function (User $user) use ($start, $end, $actor) {
            $tasksQuery = Task::query()->where('assigned_to', $user->id);
            ReportScopeHelper::scopeTasks($tasksQuery, $actor);

            $completedInPeriod = (clone $tasksQuery)
                ->whereIn('status', Task::TERMINAL_STATUSES)
                ->whereBetween('updated_at', [$start, $end])
                ->get();

            $openNow = (clone $tasksQuery)
                ->whereNotIn('status', Task::TERMINAL_STATUSES)
                ->count();

            $overdueOpen = (clone $tasksQuery)
                ->whereNotIn('status', Task::TERMINAL_STATUSES)
                ->whereDate('due_date', '<', Carbon::today())
                ->count();

            $lateCompletions = $completedInPeriod->filter(
                fn (Task $t) => $t->due_date && $t->updated_at->gt($t->due_date->endOfDay())
            );

            $avgDelayDays = $lateCompletions->isEmpty()
                ? 0
                : round($lateCompletions->avg(fn (Task $t) => $t->due_date->diffInDays($t->updated_at)), 1);

            $hoursQuery = TimeEntry::query()
                ->where('user_id', $user->id)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()]);

            $totalHours = (float) $hoursQuery->sum('hours');
            $billableHours = (float) (clone $hoursQuery)->where('is_billable', true)->sum('hours');

            $completedCount = $completedInPeriod->count();
            $onTimeCount = $completedInPeriod->count() - $lateCompletions->count();
            $onTimeRate = $completedCount > 0 ? round(($onTimeCount / $completedCount) * 100, 1) : 100;

            return (object) [
                'user' => $user,
                'completed_count' => $completedCount,
                'open_count' => $openNow,
                'overdue_open' => $overdueOpen,
                'avg_delay_days' => $avgDelayDays,
                'on_time_rate' => $onTimeRate,
                'total_hours' => round($totalHours, 1),
                'billable_hours' => round($billableHours, 1),
                'non_billable_hours' => round($totalHours - $billableHours, 1),
                'productivity_score' => $this->score($completedCount, $onTimeRate, $overdueOpen),
            ];
        })->sortByDesc('productivity_score')->values();

        return [
            'rows' => $rows,
            'totals' => [
                'completed' => $rows->sum('completed_count'),
                'hours' => round($rows->sum('total_hours'), 1),
                'billable_hours' => round($rows->sum('billable_hours'), 1),
            ],
        ];
    }

    protected function score(int $completed, float $onTimeRate, int $overdueOpen): int
    {
        return (int) min(100, ($completed * 3) + ($onTimeRate * 0.5) - ($overdueOpen * 8));
    }
}
