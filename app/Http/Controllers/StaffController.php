<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Task;
use App\Models\Branch;
use Illuminate\Http\Request;
use App\Services\WhatsAppService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $employeesQuery = User::withCount(['tasks', 'managedClients'])->with('branch')->orderBy('name');
        $this->scopeEmployeesToActor($employeesQuery, $request->user());

        $employees = $employeesQuery->get();
        $branches = $this->availableBranches($request->user())->get();

        return view('staff.index', compact('employees', 'branches'));
    }

    public function store(\App\Http\Requests\StoreStaffRequest $request)
    {
        $validated = $request->validated();

        $this->authorize('create', [User::class, $validated['role'], $validated['branch_id'] ?? null]);

        if ($request->user()->isManager()) {
            $validated['branch_id'] = $request->user()->branch_id;
        }

        $validated['password'] = Hash::make($request->password);

        User::create($validated);

        return redirect()->route('staff.index')->with('success', 'Staff member registered successfully.');
    }

    public function show(User $employee)
    {
        $this->authorize('view', $employee);

        $employee->load(['tasks', 'managedClients']);

        // Stats
        $totalTasks = $employee->tasks()->count();
        $completedTasks = $employee->tasks()->whereIn('status', Task::TERMINAL_STATUSES)->count();
        $pendingTasks = $totalTasks - $completedTasks;

        // Calculate Efficiency
        $efficiency = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 100;

        // Active Tasks
        $activeTasks = $employee->tasks()->whereNotIn('status', Task::TERMINAL_STATUSES)->with('client')->get();

        // Get unassigned tasks for allotment
        $unassignedTasksQuery = Task::with('client')
            ->where(function (Builder $query) {
                $query->whereNull('assigned_to')->orWhere('assigned_to', 0);
            })
            ->orderBy('due_date');
        $this->scopeUnassignedTasksToActor($unassignedTasksQuery, request()->user());
        $unassignedTasks = $unassignedTasksQuery->get();

        return view('staff.show', compact('employee', 'totalTasks', 'completedTasks', 'pendingTasks', 'efficiency', 'activeTasks', 'unassignedTasks'));
    }

    public function allotWork(\App\Http\Requests\AllotWorkRequest $request, User $employee)
    {
        $this->authorize('allotWork', $employee);

        $task = Task::findOrFail($request->validated('task_id'));
        $this->authorize('allotTask', [User::class, $task]);

        $task->update([
            'assigned_to' => $employee->id,
            'status' => Task::STATUS_PENDING
        ]);

        // Send instant WhatsApp notification if assignee has mobile
        if (!empty($employee->mobile)) {
            $clientName = $task->client ? $task->client->name : 'Internal';
            $dueDate = $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d M Y') : 'No Date';
            $message = "🔔 *New Task Assigned*\n\nHello {$employee->name},\nA new task has been assigned to you:\n\n*Task:* {$task->title}\n*Client:* {$clientName}\n*Due Date:* {$dueDate}\n\nPlease check your CA Dashboard for details.";
            
            $whatsAppService = app(WhatsAppService::class);
            $whatsAppService->sendMessage($employee->mobile, $message);
        }

        return back()->with('success', 'Work allotted successfully to ' . $employee->name);
    }

    public function sendReminder(Request $request, User $user, WhatsAppService $whatsAppService)
    {
        $this->authorize('sendReminder', $user);

        if (empty($user->mobile)) {
            return back()->with('error', 'Staff member does not have a valid mobile number.');
        }

        $type = $request->input('type', 'summary');

        if ($type === 'single_task') {
            $request->validate([
                'task_id' => 'required|exists:tasks,id',
            ]);
            $task = Task::findOrFail($request->task_id);

            if ((int) $task->assigned_to !== (int) $user->id) {
                abort(403, 'The selected task is not assigned to this staff member.');
            }

            $clientName = $task->client ? $task->client->name : 'Internal';
            $dueDate = $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d M Y') : 'No Date';

            $message = "🔔 *Pending Task Reminder*\n\nHello {$user->name},\nThis is a reminder regarding your pending task:\n\n*Task:* {$task->title}\n*Client:* {$clientName}\n*Due Date:* {$dueDate}\n\nPlease update the status on the CA Dashboard.";
        } else {
            // Summary reminder
            $pendingTasks = $user->tasks()->whereNotIn('status', Task::TERMINAL_STATUSES)->with('client')->get();

            if ($pendingTasks->isEmpty()) {
                return back()->with('warning', "{$user->name} has no pending tasks.");
            }

            $message = "🔔 *Pending Workload Summary*\n\nHello {$user->name},\nYou have {$pendingTasks->count()} pending task(s) assigned to you:\n\n";

            foreach ($pendingTasks->take(5) as $index => $task) {
                $clientName = $task->client ? $task->client->name : 'Internal';
                $dueDate = $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d M Y') : 'No Date';
                $message .= ($index + 1) . ". *{$task->title}* (Client: {$clientName}, Due: {$dueDate})\n";
            }

            if ($pendingTasks->count() > 5) {
                $message .= "...and " . ($pendingTasks->count() - 5) . " more task(s).\n";
            }

            $message .= "\nPlease check your CA Dashboard to view all tasks and update their status.";
        }

        $result = $whatsAppService->sendMessage($user->mobile, $message);

        if ($result['success']) {
            return back()->with('success', 'WhatsApp reminder sent successfully!');
        } else {
            return back()->with('error', 'Failed to send WhatsApp reminder: ' . $result['message']);
        }
    }

    private function scopeEmployeesToActor(Builder $query, User $user): void
    {
        if ($user->isPartner()) {
            return;
        }

        if ($user->isManager() && $user->branch_id) {
            $query->where('branch_id', $user->branch_id)
                ->where('role', '!=', 'partner');

            return;
        }

        $query->whereKey($user->id);
    }

    private function scopeUnassignedTasksToActor(Builder $query, User $user): void
    {
        if ($user->isPartner()) {
            return;
        }

        if (! $user->isManager() || ! $user->branch_id) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $query) use ($user) {
            $query->whereDoesntHave('client')
                ->orWhereHas('client', function (Builder $clientQuery) use ($user) {
                    $clientQuery->where('branch_id', $user->branch_id);
                });
        });
    }

    private function availableBranches(User $user): Builder
    {
        $query = Branch::query()->orderBy('name');

        if ($user->isManager()) {
            $query->whereKey($user->branch_id);
        }

        return $query;
    }
}
