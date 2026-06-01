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
        $query = ServiceDue::with(['clientService.client', 'clientService.service'])
            ->whereHas('clientService', function ($q) {
                $q->whereHas('client')->whereHas('service');
            });

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
        app(\App\Services\ServiceDocumentChecklistService::class)->attachToDues($dues->getCollection());
        $clients = \App\Models\Client::orderBy('name')->get();

        return view('service-dues.index', compact('dues', 'clients'));
    }

    /**
     * Mark the specified service due as complete.
     */
    public function markComplete(Request $request, ServiceDue $serviceDue)
    {
        $validated = $request->validate([
            'billing_status' => 'nullable|in:' . implode(',', [
                ServiceDue::BILLING_STATUS_PENDING,
                ServiceDue::BILLING_STATUS_UNBILLED,
                ServiceDue::BILLING_STATUS_NON_BILLABLE,
            ]),
            'billing_amount' => 'nullable|numeric|min:0',
        ]);

        $updateData = [
            'status' => ServiceDue::STATUS_COMPLETED,
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

    public function sendWhatsApp(ServiceDue $alert, \App\Services\WhatsAppService $whatsAppService)
    {
        $alert->load(['clientService.client', 'clientService.service']);
        $client = $alert->clientService->client ?? null;

        if (!$client || empty($client->mobile_number)) {
            return back()->with('error', 'Client does not have a valid mobile number.');
        }

        $serviceName = $alert->clientService->service->name ?? 'Service';
        $dueDate = $alert->due_date ? \Carbon\Carbon::parse($alert->due_date)->format('d M Y') : 'Soon';
        
        $message = "🔔 *Service Due Reminder*\n\nDear {$client->name},\nThis is a gentle reminder regarding your pending service: *{$serviceName}*. The deadline for this is {$dueDate}.\n\nPlease ensure necessary actions or documents are provided on time.";

        $result = $whatsAppService->sendMessage($client->mobile_number, $message);

        if ($result['success']) {
            return back()->with('success', "WhatsApp reminder sent to {$client->name} successfully.");
        } else {
            return back()->with('error', "Failed to send WhatsApp: " . $result['message']);
        }
    }
}
