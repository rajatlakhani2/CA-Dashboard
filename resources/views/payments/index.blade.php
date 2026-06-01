@extends('layouts.app')

@section('header', 'Payment History')

@section('content')
<div class="space-y-6">
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Received (Month)</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">₹{{ number_format($totalReceived, 2) }}</p>
                </div>
                <div class="p-3 bg-green-50 rounded-xl">
                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-green-600 font-bold flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                    Healthy Cashflow
                </span>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Payments Tracked</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $payments->total() }}</p>
                </div>
                <div class="p-3 bg-indigo-50 rounded-xl">
                    <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-indigo-600 rounded-2xl shadow-lg p-6 border border-indigo-500 flex items-center justify-center">
            <a href="{{ route('payments.create') }}" class="text-white font-bold text-lg flex items-center group">
                <div class="bg-white/20 p-2 rounded-lg mr-3 group-hover:bg-white/30 transition-colors">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                Record New Payment
            </a>
        </div>
    </div>

    <!-- Filters & List -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-50 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/50">
            <h3 class="font-bold text-gray-800">Recent Transactions</h3>
            <form action="{{ route('payments.index') }}" method="GET" class="flex flex-wrap gap-2">
                <select name="mode" class="rounded-lg border-gray-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Modes</option>
                    <option value="Cash" {{ request('mode') == 'Cash' ? 'selected' : '' }}>Cash</option>
                    <option value="Bank Transfer" {{ request('mode') == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="UPI" {{ request('mode') == 'UPI' ? 'selected' : '' }}>UPI</option>
                    <option value="Cheque" {{ request('mode') == 'Cheque' ? 'selected' : '' }}>Cheque</option>
                </select>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="rounded-lg border-gray-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="rounded-lg border-gray-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700 transition-colors">Apply</button>
                <a href="{{ route('payments.index') }}" class="bg-white border border-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-50 transition-colors text-center">Clear</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-6 py-4">Receipt No</th>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Client / Invoice</th>
                        <th class="px-6 py-4">Amount</th>
                        <th class="px-6 py-4">Mode</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($payments as $payment)
                    <tr class="hover:bg-indigo-50/30 transition-colors group">
                        <td class="px-6 py-4">
                            <span class="font-mono font-bold text-gray-900">{{ $payment->receipt_number }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $payment->payment_date->format('d M, Y') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-gray-900">{{ $payment->invoice?->client?->name ?? 'N/A' }}</div>
                            <div class="text-xs text-gray-500">Inv: #{{ $payment->invoice->invoice_number }}</div>
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-900">
                            ₹{{ number_format($payment->amount, 2) }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ 
                                $payment->payment_mode == 'Cash' ? 'bg-orange-100 text-orange-700' : 
                                ($payment->payment_mode == 'Bank Transfer' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700')
                            }}">
                                {{ $payment->payment_mode }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('payments.receipt', $payment) }}" class="inline-flex items-center px-3 py-1 bg-white border border-indigo-200 text-indigo-600 rounded-md text-xs font-bold hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                Receipt
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-4">
                            @include('partials.empty-state', [
                                'title' => 'No payments recorded',
                                'description' => 'Log receipts against invoices to track collections and outstanding balances.',
                                'icon' => 'payment',
                                'actionLabel' => 'Record payment',
                                'actionUrl' => route('payments.create'),
                            ])
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-gray-50/50">
            {!! $payments->links() !!}
        </div>
    </div>
</div>
@endsection