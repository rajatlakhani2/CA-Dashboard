@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full print:hidden">
    <span>Invoice #{{ $invoice->invoice_number }}</span>
    <div class="flex space-x-2 items-center">
        @if($invoice->status !== 'Paid')
        <form action="{{ route('invoices.whatsapp', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Send WhatsApp reminder?')">
            @csrf
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded shadow flex items-center">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" /></svg>
                WhatsApp
            </button>
        </form>
        @endif
        <button onclick="window.print()" class="bg-white border border-line text-text-secondary hover:bg-gray-50 text-sm px-4 py-2 rounded shadow">
            Print
        </button>
        <a href="{{ route('invoices.edit', $invoice) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded shadow">
            Edit
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto bg-white shadow-lg sm:rounded-lg overflow-hidden border border-gray-200">
    <!-- Invoice Header -->
    <div class="px-8 py-10 border-b border-gray-200">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">INVOICE</h1>
                <p class="text-gray-500 mt-2">#{{ $invoice->invoice_number }}</p>
                <div class="mt-4">
                    <span class="inline-flex items-center rounded-md px-2 py-1 text-sm font-medium 
                        {{ $invoice->status === 'Paid' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $invoice->status === 'Overdue' ? 'bg-red-100 text-red-800' : '' }}
                        {{ $invoice->status === 'Partially Paid' ? 'bg-amber-100 text-amber-800' : '' }}
                        {{ $invoice->status === 'Draft' ? 'bg-gray-100 text-gray-800' : '' }}">
                        {{ $invoice->status }}
                    </span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-right">
                    <h2 class="text-xl font-bold text-gray-700">{{ \App\Models\Setting::get('company_name', 'RLA Dashboard Corp') }}</h2>
                    <p class="text-gray-500 text-sm mt-1 whitespace-pre-line">{{ \App\Models\Setting::get('company_address', "123 Business Street\nTech City, TC 90210") }}</p>
                    <p class="text-gray-500 text-sm">{{ \App\Models\Setting::get('company_email', 'billing@cadashboard.com') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Client & Dates -->
    <div class="px-8 py-8 grid grid-cols-2 gap-8">
        <div>
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Bill To</h3>
            <p class="text-lg font-bold text-gray-800">{{ $invoice->client?->name ?? 'Client record missing' }}</p>
            <p class="text-gray-600 text-sm">{{ $invoice->client->client_code }}</p>
            @if($invoice->client->contact_email)
            <p class="text-gray-600 text-sm">{{ $invoice->client->contact_email }}</p>
            @endif
        </div>
        <div class="text-right">
            <div class="mb-4">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Date</h3>
                <p class="text-base font-medium text-gray-800">{{ $invoice->date->format('d M, Y') }}</p>
            </div>
            <div>
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Due Date</h3>
                <p class="text-base font-medium text-gray-800">{{ $invoice->due_date->format('d M, Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Line Items -->
    <div class="px-8 py-4">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 text-sm font-bold text-gray-600 uppercase tracking-wider">Description</th>
                    <th class="text-right py-3 text-sm font-bold text-gray-600 uppercase tracking-wider">Qty</th>
                    <th class="text-right py-3 text-sm font-bold text-gray-600 uppercase tracking-wider">Rate</th>
                    <th class="text-right py-3 text-sm font-bold text-gray-600 uppercase tracking-wider">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($invoice->items as $item)
                <tr>
                    <td class="py-4 text-sm text-gray-800 font-medium">{{ $item->description }}</td>
                    <td class="py-4 text-right text-sm text-gray-600">{{ $item->quantity + 0 }}</td>
                    <td class="py-4 text-right text-sm text-gray-600">₹ {{ number_format($item->rate, 2) }}</td>
                    <td class="py-4 text-right text-sm text-gray-800 font-bold">₹ {{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Totals -->
    <div class="px-8 py-8 border-t border-gray-200">
        <div class="flex justify-end">
            <div class="w-1/2">
                <div class="flex justify-between py-2">
                    <span class="text-gray-600 text-sm">Subtotal</span>
                    <span class="text-gray-800 font-medium">₹ {{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-600 text-sm">Tax (0%)</span>
                    <span class="text-gray-800 font-medium">₹ {{ number_format($invoice->tax, 2) }}</span>
                </div>
                <div class="flex justify-between py-4">
                    <span class="text-gray-800 text-lg font-bold">Total</span>
                    <span class="text-indigo-600 text-lg font-bold">₹ {{ number_format($invoice->total_amount, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    @if($invoice->payment_url && $invoice->status !== 'Paid')
    <div class="px-8 py-6 border-t border-gray-200 bg-indigo-50/50 print:hidden">
        <h4 class="text-sm font-bold text-gray-900 mb-3">Pay online (UPI)</h4>
        <div class="flex flex-wrap items-start gap-6">
            @if($paymentQrUrl ?? null)
            <img src="{{ $paymentQrUrl }}" alt="UPI QR" class="w-40 h-40 border border-slate-200 rounded-lg bg-white p-2">
            @endif
            <div class="text-sm text-slate-700 max-w-md">
                <p>Balance due: <strong class="text-money-negative">₹ {{ number_format($invoice->balanceDue(), 2) }}</strong></p>
                <p class="mt-2 text-xs text-slate-500 break-all">{{ $invoice->payment_url }}</p>
                <p class="mt-2 text-xs text-slate-400">Scan with any UPI app. Not an e-invoice / IRN — collection link only.</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Notes -->
    @if($invoice->notes)
    <div class="px-8 py-6 bg-gray-50 border-t border-gray-200">
        <h4 class="text-sm font-bold text-gray-700 mb-2">Notes</h4>
        <p class="text-sm text-gray-600">{{ $invoice->notes }}</p>
    </div>
    @endif
</div>
@endsection
