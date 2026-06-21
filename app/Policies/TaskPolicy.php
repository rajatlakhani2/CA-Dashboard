<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return ! $user->isArticle();
    }

    public function assign(User $user, ?int $assigneeId = null): bool
    {
        if ($user->isArticle()) {
            return false;
        }

        return $this->canManageAllTasks($user)
            || $assigneeId === null
            || (int) $assigneeId === (int) $user->id;
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->isArticle()) {
            return (int) $task->assigned_to === (int) $user->id;
        }

        return $this->canManageTask($user, $task);
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->isArticle()) {
            return false;
        }

        return $this->canManageTask($user, $task);
    }

    public function updateStatus(User $user, Task $task): bool
    {
        if ($user->isArticle()) {
            return (int) $task->assigned_to === (int) $user->id;
        }

        return $this->canManageTask($user, $task);
    }

    public function appendNote(User $user, Task $task): bool
    {
        return $this->updateStatus($user, $task);
    }

    public function logTimeOnTask(User $user, Task $task): bool
    {
        return (int) $task->assigned_to === (int) $user->id
            || $this->canManageTask($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        if ($task->is_billed || $task->invoice_id) {
            return $this->canManageAllTasks($user);
        }

        if ($this->canManageAllTasks($user)) {
            return true;
        }

        return (int) $task->created_by === (int) $user->id;
    }

    public function markFoc(User $user, Task $task): bool
    {
        return $this->canManageAllTasks($user);
    }

    private function canManageTask(User $user, Task $task): bool
    {
        if ($user->isArticle()) {
            return false;
        }

        return $this->canManageAllTasks($user)
            || (int) $task->assigned_to === (int) $user->id
            || (int) $task->created_by === (int) $user->id;
    }

    private function canManageAllTasks(User $user): bool
    {
        return $user->hasRole('partner', 'manager');
    }
}
