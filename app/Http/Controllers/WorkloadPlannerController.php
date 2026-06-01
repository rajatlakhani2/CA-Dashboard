<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Services\WorkloadPlannerBuilder;
use Illuminate\Http\Request;

class WorkloadPlannerController extends Controller
{
    public function index(Request $request, WorkloadPlannerBuilder $builder)
    {
        $this->authorize('viewAny', User::class);

        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $plan = $builder->build($request->user(), $branchId);
        $branches = $builder->branchFilters($request->user());
        $canReassign = $request->user()->hasRole('partner', 'manager');

        return view('workload.index', [
            'members' => $plan['members'],
            'unassigned' => $plan['unassigned'],
            'totals' => $plan['totals'],
            'branches' => $branches,
            'branchId' => $branchId,
            'canReassign' => $canReassign,
        ]);
    }

    public function reassign(Request $request, WorkloadPlannerBuilder $builder)
    {
        $validated = $request->validate([
            'task_id' => 'required|integer|exists:tasks,id',
            'assigned_to' => 'required|integer|min:0',
        ]);

        $task = Task::findOrFail($validated['task_id']);
        $this->authorize('update', $task);

        $assigneeId = (int) $validated['assigned_to'];

        if ($assigneeId === 0) {
            $task->update(['assigned_to' => null]);
            $message = 'Task moved to unassigned.';

            return $this->reassignResponse($request, $message);
        }

        $this->authorize('assign', [Task::class, $assigneeId]);

        $allowed = $builder->assignableMemberIds(
            $request->user(),
            $request->filled('branch_id') ? (int) $request->branch_id : null
        );

        abort_unless(in_array($assigneeId, $allowed, true), 403, 'Cannot assign to this team member.');

        $assignee = User::findOrFail($assigneeId);
        $task->update(['assigned_to' => $assignee->id]);

        return $this->reassignResponse($request, "Task reassigned to {$assignee->name}.");
    }

    protected function reassignResponse(Request $request, string $message)
    {
        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return redirect()
            ->route('workload.index', $request->only('branch_id'))
            ->with('success', $message);
    }
}
