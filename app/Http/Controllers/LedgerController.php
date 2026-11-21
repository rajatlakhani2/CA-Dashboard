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
        // dates are stored with a time, so include the whole of the first and last day
        $from = \Illuminate\Support\Carbon::parse($startDate)->startOfDay();
        $to = \Illuminate\Support\Carbon::parse($endDate)->endOfDay();

        // Get invoices (debits)
        $invoices = Invoice::where('client_id', $client->id)
            ->whereBetween('date', [$from, $to])
            ->where('status', '!=', Invoice::STATUS_DRAFT)
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
            ->whereBetween('payment_date', [$from, $to])
            ->orderBy('payment_date')
            ->get()
            ->map(function ($pay) {
                return [
                    'date' => $pay->payment_date,
                    'type' => 'Payment',
                    'reference' => $pay->receipt_number,
                    'debit' => 0,
                    'credit' => $pay->amount,
                    'description' => "Receipt #{$pay->receipt_number} (" . \Illuminate\Support\Str::headline((string) $pay->payment_mode) . ")",
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

        $ledger = $ledgerEntries->map(fn (array $entry) => [
            'date' => $entry['date'],
            'type' => $entry['type'],
            'voucher' => $entry['reference'],
            'description' => $entry['description'],
            'amount' => $entry['type'] === 'Invoice' ? $entry['debit'] : $entry['credit'],
            'balance' => $entry['balance'],
        ]);

        // Outstanding and aging use each open invoice's own unpaid balance (total minus payments
        // received against that invoice), so part-payments and fully paid invoices are counted correctly.
        $open = Invoice::where('client_id', $client->id)
            ->whereIn('status', Invoice::OPEN_STATUSES)
            ->withSum('payments', 'amount')
            ->get()
            ->map(fn ($inv) => ['due' => $inv->due_date ?? $inv->date, 'balance' => max(0, (float) $inv->total_amount - (float) $inv->payments_sum_amount)]);

        $totalOutstanding = $open->sum('balance');

        $daysOverdue = fn ($row) => $row['due'] ? (int) \Illuminate\Support\Carbon::parse($row['due'])->startOfDay()->diffInDays(now()->startOfDay(), false) : 0;
        $aging = [
            '0-30' => $open->filter(fn ($r) => $daysOverdue($r) <= 30)->sum('balance'),
            '31-60' => $open->filter(fn ($r) => $daysOverdue($r) >= 31 && $daysOverdue($r) <= 60)->sum('balance'),
            '61-90' => $open->filter(fn ($r) => $daysOverdue($r) >= 61 && $daysOverdue($r) <= 90)->sum('balance'),
            '90+' => $open->filter(fn ($r) => $daysOverdue($r) > 90)->sum('balance'),
        ];

        return view('ledger.show', compact('client', 'ledger', 'ledgerEntries', 'totalOutstanding', 'aging', 'startDate', 'endDate'));
    }

    public function downloadSoa(Client $client)
    {
        $startDate = now()->startOfYear()->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $invoices = Invoice::where('client_id', $client->id)
            ->where('status', '!=', Invoice::STATUS_DRAFT)
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
