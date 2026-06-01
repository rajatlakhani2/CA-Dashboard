<?php

namespace App\Http\Controllers;

use App\Models\ServiceDue;
use App\Models\Client;
use App\Services\BillingDraftInvoiceBuilder;
use App\Services\BillingRuleApplier;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index()
    {
        // Fetch clients with either unbilled completed service dues OR unbilled worksheets
        $clients = Client::where(function($query) {
            $query->whereHas('services.dues', function ($d) {
                    $d->where('status', ServiceDue::STATUS_COMPLETED)
                        ->where('billing_status', ServiceDue::BILLING_STATUS_UNBILLED)
                        ->whereNull('invoice_id');
            })->orWhereHas('worksheets', function ($w) {
                $w->where('is_billed', false)->whereNull('invoice_id');
            });
        })->with([
            'services' => function ($q) {
                $q->whereHas('dues', function ($d) {
                    $d->where('status', ServiceDue::STATUS_COMPLETED)
                        ->where('billing_status', ServiceDue::BILLING_STATUS_UNBILLED)
                        ->whereNull('invoice_id');
                });
            },
            'services.dues' => function ($q) {
                $q->where('status', ServiceDue::STATUS_COMPLETED)
                    ->where('billing_status', ServiceDue::BILLING_STATUS_UNBILLED)
                    ->whereNull('invoice_id');
            }, 
            'services.service',
            'worksheets' => function($w) {
                $w->where('is_billed', false)->whereNull('invoice_id');
            }
        ])->get();

        return view('billing.index', compact('clients'));
    }

    public function applyRules(BillingRuleApplier $applier)
    {
        abort_unless(auth()->user()?->canAccessModule('billing'), 403);

        $result = $applier->applyToUnbilledDues();

        return back()->with(
            'success',
            "Billing rules applied to {$result['applied']} completed due(s)."
        );
    }

    public function process(Request $request)
    {
        $request->validate([
            'dues' => 'array',
            'dues.*' => 'exists:service_dues,id',
            'worksheets' => 'array',
            'worksheets.*' => 'exists:client_worksheets,id'
        ]);

        if (empty($request->dues) && empty($request->worksheets)) {
            return back()->with('error', 'Select at least one item to invoice.');
        }

        $items = [];
        $clientId = null;

        if (!empty($request->dues)) {
            $dues = ServiceDue::with('clientService.service', 'clientService.client')->whereIn('id', $request->dues)->get();
            $clientId = $dues->first()->clientService->client_id;
            foreach ($dues as $due) {
                if ($due->clientService->client_id !== $clientId) {
                    return back()->with('error', 'All selected items must belong to the same client.');
                }
                $items[] = [
                    'description' => $due->clientService->service->name . ' - ' . $due->due_date->format('M Y'),
                    'quantity' => 1,
                    'rate' => $due->billing_amount ?? 0,
                    'service_due_id' => $due->id
                ];
            }
        }

        if (!empty($request->worksheets)) {
            $worksheets = \App\Models\ClientWorksheet::whereIn('id', $request->worksheets)->get();
            $wsClientId = $worksheets->first()->client_id;
            
            if ($clientId === null) {
                $clientId = $wsClientId;
            } else if ($wsClientId !== $clientId) {
                return back()->with('error', 'All selected items must belong to the same client.');
            }
            
            foreach ($worksheets as $ws) {
                 if ($ws->client_id !== $clientId) {
                    return back()->with('error', 'All selected items must belong to the same client.');
                }
                $items[] = [
                    'description' => $ws->description,
                    'quantity' => 1,
                    'rate' => $ws->amount,
                    'worksheet_id' => $ws->id
                ];
            }
        }

        // Store items in session to retrieve in Invoice Create
        session([
            'invoice_prefill_items' => $items, 
            'invoice_prefill_dues' => $request->dues ?? [],
            'invoice_prefill_worksheets' => $request->worksheets ?? []
        ]);

        return redirect()->route('invoices.create', ['client_id' => $clientId]);
    }

    public function createDraft(Request $request, BillingDraftInvoiceBuilder $builder)
    {
        abort_unless(auth()->user()?->canAccessModule('billing'), 403);

        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'dues' => 'nullable|array',
            'dues.*' => 'exists:service_dues,id',
            'worksheets' => 'nullable|array',
            'worksheets.*' => 'exists:client_worksheets,id',
        ]);

        $client = Client::findOrFail($request->client_id);

        try {
            $invoice = $builder->createDraftForClient(
                $client,
                $request->dues,
                $request->worksheets
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('invoices.edit', $invoice)
            ->with('success', 'Draft invoice created from billing queue. Review and send when ready.');
    }
}
