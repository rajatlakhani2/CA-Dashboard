<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::with(['client', 'assignee']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $view = $request->input('view', 'list');

        if ($view === 'board') {
            // For board, we need all tasks to organize them columns. 
            // In a real app we might paginate per column or use infinite scroll.
            // For now, let's limit to recent 100 to avoid overloading.
            $tasks = $query->orderBy('due_date', 'asc')->limit(100)->get();
        } else {
            // Sorting: Due Date Ascending (so overdue/soonest tasks are first)
            $tasks = $query->orderBy('due_date', 'asc')->paginate(10);
        }

        $users = User::all();

        return view('tasks.index', compact('tasks', 'users', 'view'));
    }

    public function create(Request $request)
    {
        $clients = Client::where('status', 'Active')->orderBy('name')->get();
        $users = User::all();
        $prefillDueDate = $request->input('due_date');
        return view('tasks.create', compact('clients', 'users', 'prefillDueDate'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'nullable|in:High,Medium,Normal,Low',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        // $validated['created_by'] = auth()->id() ?? 1; // Default to ID 1 if no auth (e.g. cli)
        $validated['status'] = 'Pending';


        Task::create(array_merge($validated, [
            'created_by' => $request->input('created_by', auth()->id() ?? 1)
        ]));

        return redirect()->route('tasks.index')->with('success', 'Task created successfully.');
    }

    public function edit(Task $task)
    {
        $clients = Client::where('status', 'Active')->orderBy('name')->get();
        $users = User::all();
        return view('tasks.edit', compact('task', 'clients', 'users'));
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'required|in:High,Medium,Normal,Low',
            'status' => 'required|in:Pending,In Progress,On Hold,Completed',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        $task->update($validated);

        return redirect()->route('tasks.index')->with('success', 'Task updated successfully.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        $request->validate([
            'status' => 'required|in:Pending,In Progress,On Hold,Completed',
        ]);

        $task->update(['status' => $request->status]);

        return response()->json(['success' => true, 'message' => 'Task status updated.']);
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
    }
    public function markFoc(Task $task)
    {
        $task->update(['is_billed' => true]);
        return redirect()->back()->with('success', 'Task marked as Free of Cost.');
    }
}
