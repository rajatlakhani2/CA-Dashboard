@extends('layouts.app')

@section('header', 'Record Payment')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden animate-enter">
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-500 px-8 py-6">
            <h3 class="text-xl font-bold text-white">New Receipt</h3>
            <p class="text-indigo-100 text-sm mt-1">Record a payment received from a client against an invoice.</p>
        </div>

        <form action="{{ route('payments.store') }}" method="POST" class="p-8 space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Client/Invoice Selection -->
                <div class="col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Select Invoice <span class="text-red-500">*</span></label>
                    <select name="invoice_id" required class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all py-3 px-4 shadow-sm bg-gray-50/50">
                        <option value="">-- Choose an Invoice --</option>
                        @foreach($invoices as $invoice)
                        <option value="{{ $invoice->id }}" {{ old('invoice_id', request('invoice_id')) == $invoice->id ? 'selected' : '' }}>
                            {{ $invoice->invoice_number }} - {{ $invoice->client->name }} (Due: ₹{{ number_format($invoice->total_amount - $invoice->payments->sum('amount'), 2) }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Amount -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Amount Received (₹) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <span class="text-gray-500 font-bold">₹</span>
                        </div>
                        <input type="number" step="0.01" name="amount" value="{{ old('amount') }}" required placeholder="0.00" class="pl-8 w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all py-3 shadow-sm bg-gray-50/50">
                    </div>
                </div>

                <!-- Date -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Payment Date <span class="text-red-500">*</span></label>
                    <input type="date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all py-3 shadow-sm bg-gray-50/50">
                </div>

                <!-- Payment Mode -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Mode of Payment</label>
                    <select name="payment_mode" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all py-3 shadow-sm bg-gray-50/50">
                        <option value="Bank Transfer">Bank Transfer / NEFT</option>
                        <option value="UPI">UPI / QR Scan</option>
                        <option value="Cash">Cash</option>
                        <option value="Cheque">Cheque</option>
                    </select>
                </div>

                <!-- Reference -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Reference No. (Txn ID/Cheque No)</label>
                    <input type="text" name="reference_number" value="{{ old('reference_number') }}" placeholder="e.g. TXN123456" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all py-3 shadow-sm bg-gray-50/50">
                </div>

                <!-- Notes -->
                <div class="col-span-2">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Internal Notes</label>
                    <textarea name="notes" rows="3" placeholder="Any internal notes or remarks..." class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all py-3 shadow-sm bg-gray-50/50">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                <a href="{{ route('payments.index') }}" class="text-gray-500 font-bold hover:text-gray-800 transition-colors">Cancel</a>
                <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-indigo-700 transition-all transform hover:scale-105 shadow-lg shadow-indigo-200 active:scale-95">
                    Generate Receipt
                </button>
            </div>
        </form>
    </div>
</div>
@endsection