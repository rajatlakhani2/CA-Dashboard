<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\User;
use App\Services\PushNotificationService;

/** Phone notification when a task is assigned to someone (not when you assign it to yourself). */
class PushTaskObserver
{
    public function created(Task $task): void
    {
        $this->notifyAssignee($task);
    }

    public function updated(Task $task): void
    {
        if ($task->wasChanged('assigned_to')) {
            $this->notifyAssignee($task);
        }
    }

    private function notifyAssignee(Task $task): void
    {
        try {
            $push = app(PushNotificationService::class);
            if (! $push->isConfigured() || ! $task->assigned_to || (int) $task->assigned_to === (int) auth()->id()) {
                return;
            }

            $assignee = User::withoutGlobalScopes()->find($task->assigned_to);
            if (! $assignee) {
                return;
            }

            $details = array_filter([
                $task->client?->name,
                $task->due_date ? 'Due ' . $task->due_date->format('d M') : null,
                auth()->user()?->name ? 'from ' . auth()->user()->name : null,
            ]);

            $push->sendToUser(
                $assignee,
                'task_assigned',
                'New task: ' . $task->title,
                implode(' · ', $details) ?: 'Open the app to see the details.',
                route('tasks.edit', $task),
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
