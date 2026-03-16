<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Models\Task;
use Illuminate\Http\Request;

class TimeEntryController extends Controller
{
    public function index(Request $request)
    {
        $query = TimeEntry::with(['task.client', 'user']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('from_date')) {
            $query->where('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }

        $timeEntries = $query->latest('date')->paginate(20);
        $totalHours = TimeEntry::sum('hours');
        $billableHours = TimeEntry::where('is_billable', true)->sum('hours');
        $tasks = Task::with('client')->paginate(10);

        return view('time-entries.index', compact('timeEntries', 'totalHours', 'billableHours', 'tasks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0.25|max:24',
            'description' => 'nullable|string|max:255',
            'is_billable' => 'boolean',
        ]);

        TimeEntry::create(array_merge($request->all(), [
            'user_id' => auth()->id(),
            'is_billable' => $request->boolean('is_billable', true),
        ]));

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Time entry added.');
    }

    public function destroy(TimeEntry $timeEntry)
    {
        $timeEntry->delete();
        return back()->with('success', 'Time entry deleted.');
    }
}
