<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class WorkloadPlannerBuilder
{
    public const HOURS_PER_OPEN_TASK = 2;

    /**
     * @return array{
     *   members: Collection<int, object>,
     *   unassigned: Collection<int, Task>,
     *   totals: array{open: int, overdue: int, unassigned: int}
     * }
     */
    public function build(User $actor, ?int $branchId = null): array
    {
        $today = Carbon::today();
        $hoursSince = $today->copy()->subDays(30);

        $membersQuery = User::query()
            ->with('branch')
            ->whereIn('role', ['manager', 'staff', 'associate', 'article', 'intern'])
            ->orderBy('name');

        $this->scopeMembers($membersQuery, $actor, $branchId);

        $memberIds = (clone $membersQuery)->pluck('id');

        $openTasks = Task::query()
            ->with(['client', 'assignee'])
            ->whereNotIn('status', Task::TERMINAL_STATUSES)
            ->where(function (Builder $q) use ($memberIds) {
                $q->whereIn('assigned_to', $memberIds)
                    ->orWhereNull('assigned_to')
                    ->orWhere('assigned_to', 0);
            });

        $this->scopeTasksToActor($openTasks, $actor, $branchId);

        $tasks = $openTasks->orderBy('due_date')->get();

        $loggedHours = TimeEntry::query()
            ->selectRaw('user_id, SUM(hours) as total_hours')
            ->whereIn('user_id', $memberIds)
            ->where('date', '>=', $hoursSince)
            ->groupBy('user_id')
            ->pluck('total_hours', 'user_id');

        $members = $membersQuery->get()->map(function (User $user) use ($tasks, $loggedHours, $today) {
            $userTasks = $tasks->where('assigned_to', $user->id);
            $overdue = $userTasks->filter(fn (Task $t) => $t->due_date && $t->due_date->lt($today))->count();

            return (object) [
                'user' => $user,
                'open_count' => $userTasks->count(),
                'overdue_count' => $overdue,
                'due_this_week' => $userTasks->filter(fn (Task $t) => $t->due_date && $t->due_date->between($today, $today->copy()->endOfWeek()))->count(),
                'logged_hours_30d' => round((float) ($loggedHours[$user->id] ?? 0), 1),
                'planned_hours' => $userTasks->count() * self::HOURS_PER_OPEN_TASK,
                'load_score' => $this->loadScore($userTasks->count(), $overdue),
                'tasks' => $userTasks->values(),
            ];
        })->sortByDesc('load_score')->values();

        $unassigned = $tasks->filter(fn (Task $t) => empty($t->assigned_to))->values();

        return [
            'members' => $members,
            'unassigned' => $unassigned,
            'totals' => [
                'open' => $tasks->count(),
                'overdue' => $tasks->filter(fn (Task $t) => $t->due_date && $t->due_date->lt($today))->count(),
                'unassigned' => $unassigned->count(),
            ],
        ];
    }

    public function assignableMemberIds(User $actor, ?int $branchId = null): array
    {
        $query = User::query()->whereIn('role', ['manager', 'staff', 'associate', 'article', 'intern']);
        $this->scopeMembers($query, $actor, $branchId);

        return $query->pluck('id')->all();
    }

    protected function loadScore(int $open, int $overdue): int
    {
        return ($open * 2) + ($overdue * 5);
    }

    protected function scopeMembers(Builder $query, User $actor, ?int $branchId): void
    {
        if ($actor->isPartner() && $branchId) {
            $query->where('branch_id', $branchId);

            return;
        }

        if ($actor->isPartner()) {
            return;
        }

        if ($actor->isManager() && $actor->branch_id) {
            $query->where('branch_id', $actor->branch_id)
                ->where('role', '!=', 'partner');
        }
    }

    protected function scopeTasksToActor(Builder $query, User $actor, ?int $branchId): void
    {
        if ($actor->isPartner() && ! $branchId) {
            return;
        }

        $branch = $branchId ?? ($actor->isManager() ? $actor->branch_id : null);

        if (! $branch) {
            return;
        }

        $query->where(function (Builder $q) use ($branch) {
            $q->whereDoesntHave('client')
                ->orWhereHas('client', fn (Builder $c) => $c->where('branch_id', $branch))
                ->orWhereNull('client_id');
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Branch>
     */
    public function branchFilters(User $actor): Collection
    {
        if (! $actor->isPartner()) {
            return collect();
        }

        return Branch::query()->orderBy('name')->get();
    }
}
