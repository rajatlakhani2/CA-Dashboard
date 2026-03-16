<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class LedgerController extends Controller
{
    public function show(Request $request, Client $client)
    {
        $startDate = $request->input('start_date', now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Get invoices (debits)
        $invoices = Invoice::where('client_id', $client->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', '!=', 'Draft')
            ->orderBy('date')
            ->get()
            ->map(function ($inv) {
                return [
                    'date' => $inv->date,
                    'type' => 'Invoice',
                    'reference' => $inv->invoice_number,
                    'debit' => $inv->total_amount,
                    'credit' => 0,
                    'description' => "Invoice #{$inv->invoice_number}",
                ];
            });

        // Get payments (credits)
        $payments = Payment::whereHas('invoice', fn($q) => $q->where('client_id', $client->id))
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date')
            ->get()
            ->map(function ($pay) {
                return [
                    'date' => $pay->payment_date,
                    'type' => 'Payment',
                    'reference' => $pay->receipt_number,
                    'debit' => 0,
                    'credit' => $pay->amount,
                    'description' => "Receipt #{$pay->receipt_number} ({$pay->payment_mode})",
                ];
            });

        // Merge and sort chronologically
        $ledgerEntries = $invoices->merge($payments)->sortBy('date')->values();

        // Calculate running balance
        $runningBalance = 0;
        $ledgerEntries = $ledgerEntries->map(function ($entry) use (&$runningBalance) {
            $runningBalance += $entry['debit'] - $entry['credit'];
            $entry['balance'] = $runningBalance;
            return $entry;
        });

        // Aging
        $totalOutstanding = Invoice::where('client_id', $client->id)
            ->whereIn('status', ['Sent', 'Overdue', 'Partially Paid'])
            ->sum('total_amount')
            - Payment::whereHas('invoice', fn($q) => $q->where('client_id', $client->id))->sum('amount');

        $aging = [
            '0-30' => Invoice::where('client_id', $client->id)
                ->where('due_date', '>=', now()->subDays(30))
                ->whereIn('status', ['Sent', 'Overdue', 'Partially Paid'])->sum('total_amount'),
            '31-60' => Invoice::where('client_id', $client->id)
                ->whereBetween('due_date', [now()->subDays(60), now()->subDays(31)])
                ->whereIn('status', ['Sent', 'Overdue', 'Partially Paid'])->sum('total_amount'),
            '61-90' => Invoice::where('client_id', $client->id)
                ->whereBetween('due_date', [now()->subDays(90), now()->subDays(61)])
                ->whereIn('status', ['Sent', 'Overdue', 'Partially Paid'])->sum('total_amount'),
            '90+' => Invoice::where('client_id', $client->id)
                ->where('due_date', '<', now()->subDays(90))
                ->whereIn('status', ['Sent', 'Overdue', 'Partially Paid'])->sum('total_amount'),
        ];

        return view('ledger.show', compact('client', 'ledgerEntries', 'totalOutstanding', 'aging', 'startDate', 'endDate'));
    }

    public function downloadSoa(Client $client)
    {
        $startDate = now()->startOfYear()->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $invoices = Invoice::where('client_id', $client->id)
            ->where('status', '!=', 'Draft')
            ->orderBy('date')->get();

        $payments = Payment::whereHas('invoice', fn($q) => $q->where('client_id', $client->id))
            ->orderBy('payment_date')->get();

        $totalInvoiced = $invoices->sum('total_amount');
        $totalPaid = $payments->sum('amount');
        $balance = $totalInvoiced - $totalPaid;

        $pdf = Pdf::loadView('ledger.soa-pdf', compact('client', 'invoices', 'payments', 'totalInvoiced', 'totalPaid', 'balance'));
        return $pdf->download("SOA-{$client->name}.pdf");
    }
}
