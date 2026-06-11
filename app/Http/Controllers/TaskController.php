<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\WhatsAppService;
use App\Support\DemoWorkspace;
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

        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->trim().'%';
            $query->where(function (Builder $query) use ($term) {
                $query->where('title', 'like', $term)
                    ->orWhereHas('client', fn (Builder $client) => $client->where('name', 'like', $term))
                    ->orWhereHas('assignee', fn (Builder $user) => $user->where('name', 'like', $term));
            });
        }

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
        $sort = $request->input('sort', 'due');

        if ($sort === 'priority') {
            $query->orderByRaw("CASE priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 WHEN 'Normal' THEN 3 WHEN 'Low' THEN 4 ELSE 5 END");
            $query->orderBy('due_date');
        } elseif ($sort === 'title') {
            $query->orderBy('title');
        } else {
            $query->orderBy('due_date', 'asc');
        }

        if ($view === 'board') {
            $tasks = $query->limit(100)->get();
        } else {
            $tasks = $query->paginate(15)->withQueryString();
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
        $recentClientIds = Task::query()
            ->whereNotNull('client_id')
            ->orderByDesc('updated_at')
            ->limit(30)
            ->pluck('client_id')
            ->unique()
            ->take(4)
            ->values();

        $recentClientsForPicker = $clientsForPicker
            ->filter(fn (array $c) => $recentClientIds->contains($c['id']))
            ->values();

        if ($recentClientsForPicker->isEmpty()) {
            $recentClientsForPicker = $clientsForPicker->take(4)->values();
        }

        if (DemoWorkspace::isDemoUser($request->user())) {
            $demoNames = ['Acme Corp', 'ABC Pvt Ltd', 'XYZ LLP', 'PQR Ltd'];
            $recentClientsForPicker = $clientsForPicker
                ->filter(fn (array $c) => in_array($c['name'], $demoNames, true))
                ->values();
        }

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

        if (DemoWorkspace::isDemoUser($request->user()) && $request->boolean('demo_tour')) {
            $resumeStep = (int) $request->input('demo_tour_step', 0);

            return redirect()
                ->route('dashboard', ['tab' => 'calendar'])
                ->with('success', 'Task created — see it on the calendar.')
                ->with('demo_tour_resume_step', $resumeStep);
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
