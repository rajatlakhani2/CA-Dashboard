<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\WhatsAppService;
use Illuminate\Database\Eloquent\Builder;

class TaskController extends Controller
{
    public function myDay(Request $request)
    {
        $this->authorize('viewAny', Task::class);

        $user = $request->user();
        $query = Task::with(['client'])->whereNotIn('status', Task::TERMINAL_STATUSES);
        $this->scopeVisibleTasks($query, $user);
        $query->where('assigned_to', $user->id);

        $today = now()->startOfDay();
        $tasksToday = (clone $query)->whereDate('due_date', '<=', $today)->orderBy('due_date')->get();
        $tasksUpcoming = (clone $query)->whereDate('due_date', '>', $today)->orderBy('due_date')->limit(15)->get();

        return view('tasks.my-day', compact('tasksToday', 'tasksUpcoming', 'user'));
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Task::class);

        $query = Task::with(['client', 'assignee']);
        $this->scopeVisibleTasks($query, $request->user());

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

        $users = $this->assignableUsers($request->user())->get();

        return view('tasks.index', compact('tasks', 'users', 'view'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Task::class);

        $clients = Client::where('status', Client::STATUS_ACTIVE)->orderBy('name')->get();
        $users = $this->assignableUsers($request->user())->get();
        $prefillDueDate = $request->input('due_date', now()->addDays(7)->format('Y-m-d'));
        $defaultAssignTo = $request->old('assigned_to', $request->input('assign_to_me') ? (string) $request->user()->id : '');

        $clientsForPicker = $clients->map(fn (Client $c) => ['id' => $c->id, 'name' => $c->name])->values();
        $usersForPicker = $users
            ->reject(fn (User $u) => $u->isSeedPlaceholder())
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'role' => \App\Support\WorkspaceProfile::roles()[$u->role] ?? ucfirst((string) $u->role),
            ])
            ->values();
        $recentClientsForPicker = $clientsForPicker->take(5)->values();

        return view('tasks.create', compact(
            'clients',
            'users',
            'prefillDueDate',
            'defaultAssignTo',
            'clientsForPicker',
            'usersForPicker',
            'recentClientsForPicker',
        ));
    }

    public function store(\App\Http\Requests\StoreTaskRequest $request)
    {
        $this->authorize('create', Task::class);

        $validated = $request->validated();

        $this->authorize('assign', [Task::class, $validated['assigned_to'] ?? null]);

        $validated['status'] = Task::STATUS_PENDING;


        $task = Task::create(array_merge($validated, [
            'created_by' => $request->user()->id,
        ]));

        if ($task->assigned_to) {
            $assignee = User::find($task->assigned_to);
            if ($assignee && !empty($assignee->mobile)) {
                $clientName = $task->client ? $task->client->name : 'Internal';
                $dueDate = $task->due_date ? $task->due_date->format('d M Y') : 'No Date';
                $message = "🔔 *New Task Assigned*\n\nHello {$assignee->name},\nA new task has been assigned to you:\n\n*Task:* {$task->title}\n*Client:* {$clientName}\n*Due Date:* {$dueDate}\n\nPlease check your CA Dashboard for details.";

                $whatsAppService = app(WhatsAppService::class);
                $whatsAppService->sendMessage($assignee->mobile, $message);
            }
        }

        return redirect()->route('tasks.index')->with('success', 'Task created successfully.');
    }

    public function edit(Task $task)
    {
        $this->authorize('update', $task);

        $clients = Client::where('status', Client::STATUS_ACTIVE)->orderBy('name')->get();
        $users = $this->assignableUsers(request()->user())->get();
        return view('tasks.edit', compact('task', 'clients', 'users'));
    }

    public function update(\App\Http\Requests\UpdateTaskRequest $request, Task $task)
    {
        $this->authorize('update', $task);

        $validated = $request->validated();

        $this->authorize('assign', [Task::class, $validated['assigned_to'] ?? null]);

        $originalAssignee = $task->assigned_to;

        $task->update($validated);

        if ($task->assigned_to && $task->assigned_to != $originalAssignee) {
            $assignee = User::find($task->assigned_to);
            if ($assignee && !empty($assignee->mobile)) {
                $clientName = $task->client ? $task->client->name : 'Internal';
                $dueDate = $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d M Y') : 'No Date';
                $message = "🔔 *New Task Assigned*\n\nHello {$assignee->name},\nA task has been reassigned to you:\n\n*Task:* {$task->title}\n*Client:* {$clientName}\n*Due Date:* {$dueDate}\n\nPlease check your CA Dashboard for details.";

                $whatsAppService = app(WhatsAppService::class);
                $whatsAppService->sendMessage($assignee->mobile, $message);
            }
        }

        return redirect()->route('tasks.index')->with('success', 'Task updated successfully.');
    }

    public function updateStatus(\App\Http\Requests\UpdateTaskStatusRequest $request, Task $task)
    {
        $this->authorize('updateStatus', $task);

        $status = $request->validated('status');
        $payload = ['status' => $status];

        if (in_array($status, Task::TERMINAL_STATUSES, true) && ! $task->is_billed) {
            $payload['is_billed'] = false;
        }

        $task->update($payload);

        $message = 'Task status updated.';
        if (in_array($status, Task::TERMINAL_STATUSES, true)) {
            $message = 'Task completed. It will appear under Invoices → Unbilled Work.';
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();
        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
    }

    public function markFoc(Task $task)
    {
        $this->authorize('markFoc', $task);

        $task->update(['is_billed' => true]);
        return redirect()->back()->with('success', 'Task marked as Free of Cost.');
    }

    private function scopeVisibleTasks(Builder $query, User $user): void
    {
        if ($user->hasRole('partner', 'manager')) {
            return;
        }

        if ($user->isArticle()) {
            $query->where('assigned_to', $user->id);

            return;
        }

        $query->where(function (Builder $query) use ($user) {
            $query->where('assigned_to', $user->id)
                ->orWhere('created_by', $user->id);
        });
    }

    private function assignableUsers(User $user): Builder
    {
        $query = User::query()->orderBy('name');

        if (! $user->managesFirmModules()) {
            $query->whereKey($user->id);
        }

        return $query;
    }
}
