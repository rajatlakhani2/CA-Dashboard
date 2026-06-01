<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class StaffPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('partner', 'manager');
    }

    public function create(User $user, ?string $role = null, ?int $branchId = null): bool
    {
        if ($user->isPartner()) {
            return true;
        }

        if (! $user->isManager() || ! $user->branch_id) {
            return false;
        }

        return in_array($role, ['staff', 'intern'], true)
            && (int) $branchId === (int) $user->branch_id;
    }

    public function view(User $user, User $employee): bool
    {
        return $this->canManageEmployee($user, $employee);
    }

    public function allotWork(User $user, User $employee): bool
    {
        return $this->canManageEmployee($user, $employee);
    }

    public function sendReminder(User $user, User $employee): bool
    {
        return $this->canManageEmployee($user, $employee);
    }

    public function allotTask(User $user, Task $task): bool
    {
        if ($user->isPartner()) {
            return true;
        }

        if (! $user->isManager() || ! $user->branch_id) {
            return false;
        }

        if ($task->assigned_to && (int) $task->assigned_to !== 0) {
            return false;
        }

        $task->loadMissing('client');

        if (! $task->client?->branch_id) {
            return true;
        }

        return (int) $task->client->branch_id === (int) $user->branch_id;
    }

    private function canManageEmployee(User $user, User $employee): bool
    {
        if ($user->isPartner()) {
            return true;
        }

        if (! $user->isManager() || ! $user->branch_id) {
            return (int) $user->id === (int) $employee->id;
        }

        if ($employee->isPartner()) {
            return false;
        }

        return (int) $employee->branch_id === (int) $user->branch_id;
    }
}
