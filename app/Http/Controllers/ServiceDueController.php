<?php

namespace App\Http\Controllers;

use App\Models\ServiceDue;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ServiceDueController extends Controller
{
    /**
     * Display a listing of service dues.
     */
    public function index(Request $request)
    {
        $query = ServiceDue::with(['clientService.client', 'clientService.service']);

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default to showing Pending/Overdue if no filter? Or just all? 
            // Let's show all by default, or maybe just exclude 'Completed' unless asked?
            // For now, let's show all but order by due_date
        }

        // Filter by Client
        if ($request->filled('client_id')) {
            $query->whereHas('clientService', function ($q) use ($request) {
                $q->where('client_id', $request->client_id);
            });
        }

        // Filter by Service
        if ($request->filled('service_id')) {
            $query->whereHas('clientService', function ($q) use ($request) {
                $q->where('service_id', $request->service_id);
            });
        }

        // Filter by Date Range (Month/Year or specific range)
        if ($request->filled('month')) {
            $query->whereMonth('due_date', Carbon::parse($request->month)->month)
                ->whereYear('due_date', Carbon::parse($request->month)->year);
        }

        $dues = $query->orderBy('due_date', 'asc')->paginate(20);
        $clients = \App\Models\Client::orderBy('name')->get(); // For filter dropdown

        return view('service-dues.index', compact('dues', 'clients'));
    }

    /**
     * Mark the specified service due as complete.
     */
    public function markComplete(Request $request, ServiceDue $serviceDue)
    {
        $validated = $request->validate([
            'billing_status' => 'nullable|in:Pending,Unbilled,Non-Billable',
            'billing_amount' => 'nullable|numeric|min:0',
        ]);

        $updateData = [
            'status' => 'Completed',
            'completed_at' => Carbon::now(),
            'completed_by' => auth()->id(),
        ];

        if ($request->filled('billing_status')) {
            $updateData['billing_status'] = $request->billing_status;
        }

        if ($request->filled('billing_amount')) {
            $updateData['billing_amount'] = $request->billing_amount;
        }

        // If marked as Unbilled but no amount provided, maybe we should warn? 
        // For now, let's allow it (amount can be added later in Queue).

        $serviceDue->update($updateData);


        return redirect()->back()->with('success', 'Service marked as completed successfully.');
    }

    /**
     * Trigger manual generation of service dues.
     */
    public function generate(\App\Services\ServiceDueGenerator $generator)
    {
        $count = $generator->generateAll();
        return redirect()->back()->with('success', "Service dues generated successfully. ($count new dues created)");
    }
}
