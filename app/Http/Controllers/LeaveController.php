<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index()
    {
        $leaves = \App\Models\Leave::with('user')->orderBy('leave_date', 'desc')->paginate(20);

        $cumulativeLeaves = \App\Models\Leave::selectRaw('user_id, count(*) as total_leaves')
            ->where('status', 'approved')
            ->groupBy('user_id')
            ->with('user')
            ->get();

        return view('leaves.index', compact('leaves', 'cumulativeLeaves'));
    }

    public function create()
    {
        $users = \App\Models\User::all();
        return view('leaves.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'leave_date' => 'required|date',
            'reason' => 'required|string',
            'informed_at' => 'required|date',
        ]);

        $userId = auth()->id();

        if (\App\Models\Leave::where('user_id', $userId)->whereDate('leave_date', $validated['leave_date'])->exists()) {
            return redirect()->back()->withErrors(['leave_date' => 'You have already applied for leave on this date.'])->withInput();
        }

        $validated['user_id'] = $userId;

        \App\Models\Leave::create($validated);

        return redirect()->route('leaves.index')->with('success', 'Leave recorded successfully.');
    }

    public function updateStatus(Request $request, \App\Models\Leave $leave)
    {
        // Simple admin authorization using the first user (matching dashboard pattern)
        $admin = \App\Models\User::first();
        if (auth()->id() !== $admin->id) {
            abort(403, 'Unauthorized action. Only admins can update leave status.');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $leave->update([
            'status' => $validated['status'],
            'approved_at' => $validated['status'] === 'approved' ? now() : null,
        ]);

        return redirect()->back()->with('success', 'Leave status updated.');
    }
}
