<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Service;
use App\Models\ServiceDue;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class DashboardCalendarBuilder
{
    /**
     * @return array{services: \Illuminate\Support\Collection, assignees: \Illuminate\Support\Collection, branches: \Illuminate\Support\Collection, categories: array<int, string>}
     */
    public function filterOptions(User $user): array
    {
        $assignees = User::query()
            ->whereIn('role', ['manager', 'staff', 'associate', 'article', 'intern'])
            ->orderBy('name');

        if ($user->isManager() && $user->branch_id) {
            $assignees->where('branch_id', $user->branch_id);
        }

        $branches = $user->isPartner()
            ? Branch::query()->orderBy('name')->get()
            : collect();

        return [
            'services' => Service::query()->orderBy('name')->get(['id', 'name']),
            'assignees' => $assignees->get(['id', 'name', 'role']),
            'branches' => $branches,
            'categories' => ['A', 'B', 'C'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildEvents(User $user, DashboardCalendarFilters $filters): array
    {
        $events = [];

        if ($filters->showTasks) {
            $events = array_merge($events, $this->buildTaskEvents($user, $filters));
        }

        if ($filters->showDues) {
            $events = array_merge($events, $this->buildDueEvents($user, $filters));
        }

        return $events;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildTaskEvents(User $user, DashboardCalendarFilters $filters): array
    {
        $start = Carbon::now()->startOfMonth()->subMonths(12);
        $end = Carbon::now()->endOfMonth()->addMonths(6);

        $query = Task::query()
            ->with(['client'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$start, $end]);

        $this->scopeTasks($query, $user);

        if ($filters->assignedTo) {
            $query->where('assigned_to', $filters->assignedTo);
        }

        if ($filters->branchId) {
            $query->where(function (Builder $q) use ($filters) {
                $q->whereHas('client', fn (Builder $c) => $c->where('branch_id', $filters->branchId))
                    ->orWhereNull('client_id');
            });
        }

        if ($filters->category) {
            $query->whereHas('client', fn (Builder $c) => $c->where('category', $filters->category));
        }

        if ($filters->dueStatus === 'completed') {
            $query->whereIn('status', Task::TERMINAL_STATUSES);
        } elseif (in_array($filters->dueStatus, ['pending', 'overdue', 'active'], true)) {
            $query->whereNotIn('status', Task::TERMINAL_STATUSES);
            if ($filters->dueStatus === 'overdue') {
                $query->whereDate('due_date', '<', Carbon::today());
            } elseif ($filters->dueStatus === 'pending') {
                $query->whereDate('due_date', '>=', Carbon::today());
            }
        }

        $events = [];
        foreach ($query->get() as $task) {
            $isOverdue = $task->due_date->isPast() && ! in_array($task->status, Task::TERMINAL_STATUSES, true);
            $isDone = in_array($task->status, Task::TERMINAL_STATUSES, true);
            $color = $isDone ? '#22c55e' : ($isOverdue ? '#ef4444' : '#3b82f6');

            $events[] = [
                'id' => 'task_' . $task->id,
                'title' => ($task->client?->name ?? 'Internal Task') . ' - Task: ' . $task->title,
                'start' => $task->due_date->format('Y-m-d'),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'allDay' => true,
                'editable' => ! $isDone && ($user->hasRole('partner', 'manager') || (int) $task->assigned_to === (int) $user->id),
                'extendedProps' => [
                    'type' => 'task',
                    'db_id' => $task->id,
                    'client_name' => $task->client?->name ?? 'Internal Task',
                    'details' => $task->title,
                    'status' => $task->status,
                ],
            ];
        }

        return $events;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildDueEvents(User $user, DashboardCalendarFilters $filters): array
    {
        $start = Carbon::now()->startOfMonth()->subMonths(12);
        $end = Carbon::now()->endOfMonth()->addMonths(6);

        $query = ServiceDue::query()
            ->with(['clientService.client', 'clientService.service'])
            ->whereBetween('due_date', [$start, $end]);

        $this->applyDueStatusFilter($query, $filters->dueStatus);

        if ($filters->serviceId) {
            $query->whereHas('clientService', fn (Builder $q) => $q->where('service_id', $filters->serviceId));
        }

        if ($filters->branchId) {
            $query->whereHas('clientService.client', fn (Builder $c) => $c->where('branch_id', $filters->branchId));
        } elseif ($user->isManager() && $user->branch_id) {
            $query->whereHas('clientService.client', fn (Builder $c) => $c->where('branch_id', $user->branch_id));
        } elseif (! $user->hasRole('partner', 'manager')) {
            $visibleIds = Client::visibleTo($user)->pluck('id');
            $query->whereHas('clientService', fn (Builder $q) => $q->whereIn('client_id', $visibleIds));
        }

        if ($filters->category) {
            $query->whereHas('clientService.client', fn (Builder $c) => $c->where('category', $filters->category));
        }

        if ($filters->assignedTo) {
            $query->whereHas('clientService.client', fn (Builder $c) => $c->where('manager_id', $filters->assignedTo));
        }

        $events = [];
        foreach ($query->get() as $due) {
            $clientName = $due->clientService?->client?->name ?? 'Internal';
            $serviceName = $due->clientService?->service?->name ?? 'Service';
            $isCompleted = $due->status === ServiceDue::STATUS_COMPLETED;
            $isOverdue = $due->status === ServiceDue::STATUS_OVERDUE
                || ($due->due_date->isPast() && $due->status === ServiceDue::STATUS_PENDING);

            $color = $isCompleted ? '#22c55e' : ($isOverdue ? '#b91c1c' : '#8b5cf6');

            $events[] = [
                'id' => 'due_' . $due->id,
                'title' => "{$clientName} - {$serviceName}",
                'start' => $due->due_date->format('Y-m-d'),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'allDay' => true,
                'editable' => ! $isCompleted && $user->hasRole('partner', 'manager'),
                'extendedProps' => [
                    'type' => 'due',
                    'db_id' => $due->id,
                    'client_name' => $clientName,
                    'details' => $serviceName,
                    'status' => $due->status,
                ],
            ];
        }

        return $events;
    }

    protected function applyDueStatusFilter(Builder $query, string $dueStatus): void
    {
        match ($dueStatus) {
            'pending' => $query->where('status', ServiceDue::STATUS_PENDING),
            'overdue' => $query->where('status', ServiceDue::STATUS_OVERDUE),
            'completed' => $query->where('status', ServiceDue::STATUS_COMPLETED),
            'all' => null,
            default => $query->whereIn('status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_OVERDUE]),
        };
    }

    protected function scopeTasks(Builder $query, User $user): void
    {
        if ($user->hasRole('partner', 'manager')) {
            if ($user->isManager() && $user->branch_id) {
                $query->where(function (Builder $q) use ($user) {
                    $q->whereHas('client', fn (Builder $c) => $c->where('branch_id', $user->branch_id))
                        ->orWhereNull('client_id');
                });
            }

            return;
        }

        $query->where(function (Builder $q) use ($user) {
            $q->where('assigned_to', $user->id)
                ->orWhere(function (Builder $sub) use ($user) {
                    $sub->whereNull('assigned_to')->where('created_by', $user->id);
                });
        });
    }
}
