@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full print:hidden">
    <span>Invoice #{{ $invoice->invoice_number }}</span>
    <div class="flex space-x-2">
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
                        {{ $invoice->status === 'Sent' ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ $invoice->status === 'Overdue' ? 'bg-red-100 text-red-800' : '' }}
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
            <p class="text-lg font-bold text-gray-800">{{ $invoice->client->name }}</p>
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

    <!-- Notes -->
    @if($invoice->notes)
    <div class="px-8 py-6 bg-gray-50 border-t border-gray-200">
        <h4 class="text-sm font-bold text-gray-700 mb-2">Notes</h4>
        <p class="text-sm text-gray-600">{{ $invoice->notes }}</p>
    </div>
    @endif
</div>
@endsection