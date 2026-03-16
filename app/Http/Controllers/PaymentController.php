<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;

use Barryvdh\DomPDF\Facade\Pdf;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['invoice.client', 'receiver']);

        if ($request->filled('client_id')) {
            $query->whereHas('invoice', fn($q) => $q->where('client_id', $request->client_id));
        }

        if ($request->filled('payment_mode')) {
            $query->where('payment_mode', $request->payment_mode);
        }

        if ($request->filled('from_date')) {
            $query->where('payment_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('payment_date', '<=', $request->to_date);
        }

        $payments = $query->latest('payment_date')->paginate(20);
        $totalReceived = Payment::sum('amount');

        return view('payments.index', compact('payments', 'totalReceived'));
    }

    public function create(Request $request)
    {
        $invoice = null;
        if ($request->filled('invoice_id')) {
            $invoice = Invoice::with('client', 'payments')->find($request->invoice_id);
        }

        $invoices = Invoice::with('client')
            ->whereIn('status', ['Draft', 'Sent', 'Overdue', 'Partially Paid'])
            ->orderBy('due_date')
            ->get();

        $nextReceiptNumber = Payment::nextReceiptNumber();

        return view('payments.create', compact('invoice', 'invoices', 'nextReceiptNumber'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'receipt_number' => 'required|unique:payments,receipt_number',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_mode' => 'required|in:Cash,Cheque,UPI,Bank Transfer,Online',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $invoice = Invoice::find($request->invoice_id);

        // Check if payment amount exceeds balance
        $currentBalance = $invoice->total_amount - $invoice->payments()->sum('amount');
        if ($request->amount > $currentBalance + 0.01) {
            return back()->withErrors(['amount' => "Payment amount (₹{$request->amount}) exceeds outstanding balance (₹" . number_format($currentBalance, 2) . ")."]);
        }

        $payment = Payment::create([
            'invoice_id' => $request->invoice_id,
            'receipt_number' => $request->receipt_number,
            'amount' => $request->amount,
            'payment_date' => $request->payment_date,
            'payment_mode' => $request->payment_mode,
            'reference_number' => $request->reference_number,
            'notes' => $request->notes,
            'received_by' => auth()->id(),
        ]);

        // Auto-update invoice status
        $totalPaid = $invoice->payments()->sum('amount');
        if ($totalPaid >= $invoice->total_amount) {
            $invoice->update(['status' => 'Paid']);
        } elseif ($totalPaid > 0) {
            $invoice->update(['status' => 'Partially Paid']);
        }

        return redirect()->route('payments.index')->with('success', "Payment recorded. Receipt #{$payment->receipt_number}");
    }

    public function show(Payment $payment)
    {
        $payment->load(['invoice.client', 'receiver']);
        return view('payments.show', compact('payment'));
    }

    public function downloadReceipt(Payment $payment)
    {
        $payment->load(['invoice.client', 'receiver']);
        $pdf = Pdf::loadView('payments.receipt-pdf', compact('payment'));
        return $pdf->download($payment->receipt_number . '.pdf');
    }

    public function destroy(Payment $payment)
    {
        $invoice = $payment->invoice;
        $payment->delete();

        // Recalculate invoice status
        $totalPaid = $invoice->payments()->sum('amount');
        if ($totalPaid <= 0) {
            $invoice->update(['status' => 'Sent']);
        } elseif ($totalPaid < $invoice->total_amount) {
            $invoice->update(['status' => 'Partially Paid']);
        }

        return redirect()->route('payments.index')->with('success', 'Payment deleted and invoice status updated.');
    }
}
