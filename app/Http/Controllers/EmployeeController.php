<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Task;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        // scalable: paginate if many employees
        $employees = User::withCount(['tasks', 'managedClients'])->get();
        return view('employees.index', compact('employees'));
    }

    public function show(User $employee)
    {
        $employee->load(['tasks' => function ($q) {
            $q->latest()->take(10);
        }, 'managedClients']);

        // Stats
        $totalTasks = $employee->tasks()->count();
        $completedTasks = $employee->tasks()->where('status', 'Completed')->count();
        $pendingTasks = $employee->tasks()->whereIn('status', ['Pending', 'In Progress'])->count();

        // Calculate Efficiency (Simulated logic: Completed / Total * 100)
        $efficiency = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        // Active Tasks
        $activeTasks = $employee->tasks()->whereIn('status', ['Pending', 'In Progress'])->with('client')->get();

        return view('employees.show', compact('employee', 'totalTasks', 'completedTasks', 'pendingTasks', 'efficiency', 'activeTasks'));
    }
}
