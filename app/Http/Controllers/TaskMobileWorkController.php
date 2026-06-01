<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppendTaskNoteRequest;
use App\Http\Requests\QuickTaskTimeEntryRequest;
use App\Models\Task;
use App\Models\TimeEntry;

class TaskMobileWorkController extends Controller
{
    public function appendNote(AppendTaskNoteRequest $request, Task $task)
    {
        $this->authorize('appendNote', $task);

        $note = trim($request->validated('note'));
        $line = sprintf(
            '[%s %s] %s',
            now()->format('d M Y H:i'),
            $request->user()->name,
            $note,
        );

        $task->description = trim(($task->description ? $task->description . "\n" : '') . $line);
        $task->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'description' => $task->description,
            ]);
        }

        return back()->with('success', 'Note saved on task.');
    }

    public function logTime(QuickTaskTimeEntryRequest $request, Task $task)
    {
        $this->authorize('logTimeOnTask', $task);

        $validated = $request->validated();

        TimeEntry::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'date' => $validated['date'] ?? now()->toDateString(),
            'hours' => $validated['hours'],
            'description' => $validated['description'] ?? null,
            'is_billable' => $request->boolean('is_billable', true),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Time logged.');
    }
}
