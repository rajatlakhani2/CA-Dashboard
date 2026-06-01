<?php

namespace App\Http\Controllers;

use App\Models\TdsEntry;
use App\Models\Invoice;
use Illuminate\Http\Request;

class TdsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', TdsEntry::class);

        $query = TdsEntry::with('invoice.client');
        $this->scopeTdsEntriesToUser($query);

        if ($request->filled('certificate_received')) {
            $query->where('certificate_received', $request->certificate_received === 'yes');
        }

        $tdsEntries = $query->latest()->paginate(20);
        $summaryQuery = TdsEntry::query();
        $this->scopeTdsEntriesToUser($summaryQuery);
        $totalTds = (clone $summaryQuery)->sum('tds_amount');
        $pendingCertificates = (clone $summaryQuery)->where('certificate_received', false)->count();

        return view('tds.index', compact('tdsEntries', 'totalTds', 'pendingCertificates'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', TdsEntry::class);

        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'tds_rate' => 'required|numeric|min:0|max:100',
            'tds_amount' => 'required|numeric|min:0',
            'certificate_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $this->authorize('createForInvoice', [TdsEntry::class, Invoice::findOrFail($request->invoice_id)]);

        TdsEntry::create($request->all());
        return back()->with('success', 'TDS entry added.');
    }

    public function update(Request $request, TdsEntry $tdsEntry)
    {
        $this->authorize('update', $tdsEntry);

        $tdsEntry->update([
            'certificate_received' => $request->boolean('certificate_received'),
            'certificate_date' => $request->certificate_date,
            'certificate_number' => $request->certificate_number,
        ]);

        return back()->with('success', 'TDS entry updated.');
    }

    public function destroy(TdsEntry $tdsEntry)
    {
        $this->authorize('delete', $tdsEntry);

        $tdsEntry->delete();
        return back()->with('success', 'TDS entry deleted.');
    }

    private function scopeTdsEntriesToUser($query): void
    {
        $user = auth()->user();

        if (! $user?->isManager() || ! $user->branch_id) {
            return;
        }

        $query->whereHas('invoice', function ($invoiceQuery) use ($user) {
            $invoiceQuery->where('branch_id', $user->branch_id)
                ->orWhere(function ($q) use ($user) {
                    $q->whereNull('branch_id')
                        ->whereHas('client', function ($clientQuery) use ($user) {
                            $clientQuery->whereNull('branch_id')
                                ->orWhere('branch_id', $user->branch_id);
                        });
                });
        });
    }
}
