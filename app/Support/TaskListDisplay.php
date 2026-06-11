<?php

namespace App\Support;

use App\Models\Task;

class TaskListDisplay
{
    public static function dueContextLabel(Task $task): string
    {
        if (! $task->due_date) {
            return '';
        }

        $today = now()->startOfDay();
        $due = $task->due_date->copy()->startOfDay();
        $diff = (int) $today->diffInDays($due, false);

        if ($diff < 0) {
            return 'Overdue';
        }

        if ($diff === 0) {
            return 'Due Today';
        }

        if ($diff === 1) {
            return 'Due Tomorrow';
        }

        return 'Due '.$due->format('d M');
    }

    public static function progressPercent(Task $task): int
    {
        return match ($task->status) {
            Task::STATUS_COMPLETED, Task::STATUS_DONE, Task::STATUS_CLOSED => 100,
            Task::STATUS_IN_PROGRESS => 75,
            Task::STATUS_ON_HOLD => 40,
            default => 15,
        };
    }

    public static function clientServiceLabel(Task $task): string
    {
        if (! $task->client) {
            return 'Internal';
        }

        $industry = trim((string) $task->client->industry);
        if ($industry !== '') {
            return $industry;
        }

        if ($task->client->gst_applicable) {
            return 'GST';
        }

        return 'Advisory';
    }

    public static function priorityTone(string $priority): array
    {
        return match ($priority) {
            'High' => ['bar' => 'bg-rose-500', 'badge' => 'bg-rose-50 text-rose-700 border-rose-200', 'ring' => 'ring-rose-100'],
            'Medium' => ['bar' => 'bg-amber-500', 'badge' => 'bg-amber-50 text-amber-800 border-amber-200', 'ring' => 'ring-amber-100'],
            'Low' => ['bar' => 'bg-slate-400', 'badge' => 'bg-slate-50 text-slate-600 border-slate-200', 'ring' => 'ring-slate-100'],
            default => ['bar' => 'bg-indigo-500', 'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'ring' => 'ring-indigo-100'],
        };
    }

    public static function assigneeLabel(Task $task): string
    {
        if ($task->assignee) {
            return $task->assignee->name;
        }

        return 'Team';
    }
}
