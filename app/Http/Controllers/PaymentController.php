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
        $this->authorize('viewAny', Payment::class);

        $query = Payment::with(['invoice.client', 'receiver']);
        $this->scopePaymentsToUser($query);

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
        $totalReceived = (clone $query)->sum('amount');

        return view('payments.index', compact('payments', 'totalReceived'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Payment::class);

        $invoice = null;
        if ($request->filled('invoice_id')) {
            $invoice = Invoice::with('client', 'payments')->find($request->invoice_id);
            if ($invoice) {
                $this->authorize('view', $invoice);
            }
        }

        $invoiceQuery = Invoice::with(['client', 'payments'])
            ->whereIn('status', Invoice::PAYABLE_STATUSES)
            ->whereHas('client');
        $this->scopeInvoicesToUser($invoiceQuery);
        $invoices = $invoiceQuery->orderBy('due_date')->get();

        $nextReceiptNumber = Payment::nextReceiptNumber();

        return view('payments.create', compact('invoice', 'invoices', 'nextReceiptNumber'));
    }

    public function store(\App\Http\Requests\StorePaymentRequest $request, \App\Services\SensitiveActionLogger $audit)
    {
        $this->authorize('create', Payment::class);

        $invoice = Invoice::find($request->invoice_id);
        $this->authorize('view', $invoice);

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

        $audit->paymentCreated($payment);

        $this->syncInvoiceStatus($invoice->fresh('payments'));

        return redirect()->route('payments.index')->with('success', "Payment recorded. Receipt #{$payment->receipt_number}");
    }

    public function show(Payment $payment)
    {
        $this->authorize('view', $payment);

        $payment->load(['invoice.client', 'receiver']);
        return view('payments.show', compact('payment'));
    }

    public function downloadReceipt(Payment $payment)
    {
        $this->authorize('download', $payment);

        $payment->load(['invoice.client', 'receiver']);
        $pdf = Pdf::loadView('payments.receipt-pdf', compact('payment'));
        return $pdf->download($payment->receipt_number . '.pdf');
    }

    public function destroy(Payment $payment, \App\Services\SensitiveActionLogger $audit)
    {
        $this->authorize('delete', $payment);

        $audit->paymentDeleted($payment);
        $invoice = $payment->invoice;
        $payment->delete();

        $this->syncInvoiceStatus($invoice->fresh('payments'));

        return redirect()->route('payments.index')->with('success', 'Payment deleted and invoice status updated.');
    }

    private function syncInvoiceStatus(Invoice $invoice): void
    {
        $totalPaid = (float) $invoice->payments()->sum('amount');

        if ($totalPaid >= (float) $invoice->total_amount) {
            $invoice->update(['status' => Invoice::STATUS_PAID]);
            return;
        }

        if ($totalPaid > 0) {
            $invoice->update(['status' => Invoice::STATUS_PARTIALLY_PAID]);
            return;
        }

        $status = $invoice->due_date && $invoice->due_date->isPast()
            ? Invoice::STATUS_OVERDUE
            : Invoice::STATUS_DRAFT;
        $invoice->update(['status' => $status]);
    }

    private function scopePaymentsToUser($query): void
    {
        $user = auth()->user();

        if (! $user?->isManager() || ! $user->branch_id) {
            return;
        }

        $query->whereHas('invoice', function ($invoiceQuery) use ($user) {
            $this->scopeInvoicesToUser($invoiceQuery);
        });
    }

    private function scopeInvoicesToUser($query): void
    {
        $user = auth()->user();

        if (! $user?->isManager() || ! $user->branch_id) {
            return;
        }

        $query->where(function ($q) use ($user) {
            $q->where('branch_id', $user->branch_id)
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
