<?php

namespace App\Http\Controllers;

use App\Models\TdsEntry;
use App\Models\Invoice;
use Illuminate\Http\Request;

class TdsController extends Controller
{
    public function index(Request $request)
    {
        $query = TdsEntry::with('invoice.client');

        if ($request->filled('certificate_received')) {
            $query->where('certificate_received', $request->certificate_received === 'yes');
        }

        $tdsEntries = $query->latest()->paginate(20);
        $totalTds = TdsEntry::sum('tds_amount');
        $pendingCertificates = TdsEntry::where('certificate_received', false)->count();

        return view('tds.index', compact('tdsEntries', 'totalTds', 'pendingCertificates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'tds_rate' => 'required|numeric|min:0|max:100',
            'tds_amount' => 'required|numeric|min:0',
            'certificate_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        TdsEntry::create($request->all());
        return back()->with('success', 'TDS entry added.');
    }

    public function update(Request $request, TdsEntry $tdsEntry)
    {
        $tdsEntry->update([
            'certificate_received' => $request->boolean('certificate_received'),
            'certificate_date' => $request->certificate_date,
            'certificate_number' => $request->certificate_number,
        ]);

        return back()->with('success', 'TDS entry updated.');
    }

    public function destroy(TdsEntry $tdsEntry)
    {
        $tdsEntry->delete();
        return back()->with('success', 'TDS entry deleted.');
    }
}
