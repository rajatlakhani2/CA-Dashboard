<?php

namespace App\Http\Controllers;

use App\Models\ServiceDue;
use App\Models\Client;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index()
    {
        // Fetch all unbilled service dues, grouped by Client
        $clients = Client::whereHas('optedServices', function ($q) {
            $q->whereHas('dues', function ($d) {
                $d->where('status', 'Completed')
                    ->where('billing_status', 'Unbilled')
                    ->whereNull('invoice_id');
            });
        })->with(['optedServices.dues' => function ($q) {
            $q->where('status', 'Completed')
                ->where('billing_status', 'Unbilled')
                ->whereNull('invoice_id');
        }, 'optedServices.service'])->get();

        return view('billing.index', compact('clients'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'dues' => 'required|array|min:1',
            'dues.*' => 'exists:service_dues,id'
        ]);

        $dues = ServiceDue::with('clientService.service', 'clientService.client')->whereIn('id', $request->dues)->get();

        // Check if all dues belong to the same client
        $clientId = $dues->first()->clientService->client_id;
        foreach ($dues as $due) {
            if ($due->clientService->client_id !== $clientId) {
                return back()->with('error', 'All selected items must belong to the same client.');
            }
        }

        // Prepare items for Invoice Create
        $items = [];
        foreach ($dues as $due) {
            $items[] = [
                'description' => $due->clientService->service->name . ' - ' . $due->due_date->format('M Y'),
                'quantity' => 1,
                'rate' => $due->billing_amount ?? 0,
                'service_due_id' => $due->id // Pass this to invoice create to link it later
            ];
        }

        // Store items in session to retrieve in Invoice Create
        session(['invoice_prefill_items' => $items, 'invoice_prefill_dues' => $request->dues]);

        return redirect()->route('invoices.create', ['client_id' => $clientId]);
    }
}
