@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <span>Invoices</span>
    <a href="{{ route('invoices.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded shadow">
        Create Invoice
    </a>
</div>
@endsection

@section('content')
@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <span>Invoices & Billing</span>
    <a href="{{ route('invoices.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded shadow transition-colors">
        Create Invoice
    </a>
</div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Counters / Summary -->
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
            <p class="text-xs font-semibold text-gray-400 uppercase">Unbilled Work</p>
            <p class="text-2xl font-bold text-gray-800">{{ $unbilledTasks->count() }} <span class="text-sm font-normal text-gray-400">tasks</span></p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
            <p class="text-xs font-semibold text-gray-400 uppercase">Unpaid Amount</p>
            <p class="text-2xl font-bold text-indigo-600">₹ {{ number_format(\App\Models\Invoice::where('status', '!=', 'Paid')->sum('total_amount'), 2) }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
            <p class="text-xs font-semibold text-gray-400 uppercase">Collected (This Month)</p>
            <p class="text-2xl font-bold text-emerald-600">₹ {{ number_format(\App\Models\Invoice::where('status', 'Paid')->whereMonth('date', now()->month)->sum('total_amount'), 2) }}</p>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="mb-6">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('invoices.index', ['tab' => 'unbilled']) }}"
                class="px-5 py-2.5 text-base font-bold rounded-full transition-all duration-200 shadow-sm border flex items-center
                {{ $tab == 'unbilled' 
                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-200 transform scale-105' 
                    : 'bg-white text-gray-500 border-gray-200 hover:border-indigo-300 hover:text-indigo-600 hover:shadow-md' }}">
                Unbilled Work
                @if($unbilledTasks->count() > 0)
                <span class="ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium {{ $tab == 'unbilled' ? 'bg-indigo-500 text-white' : 'bg-gray-100 text-gray-900' }}">
                    {{ $unbilledTasks->count() }}
                </span>
                @endif
            </a>

            <a href="{{ route('invoices.index', ['tab' => 'raised']) }}"
                class="px-5 py-2.5 text-base font-bold rounded-full transition-all duration-200 shadow-sm border flex items-center
                {{ $tab == 'raised' 
                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-200 transform scale-105' 
                    : 'bg-white text-gray-500 border-gray-200 hover:border-indigo-300 hover:text-indigo-600 hover:shadow-md' }}">
                Raised (Unpaid)
                <span class="ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium {{ $tab == 'raised' ? 'bg-indigo-500 text-white' : 'bg-gray-100 text-gray-900' }}">
                    {{ $raisedCount ?? 0 }}
                </span>
            </a>

            <a href="{{ route('invoices.index', ['tab' => 'received']) }}"
                class="px-5 py-2.5 text-base font-bold rounded-full transition-all duration-200 shadow-sm border flex items-center
                {{ $tab == 'received' 
                    ? 'bg-emerald-600 text-white border-emerald-600 shadow-emerald-200 transform scale-105' 
                    : 'bg-white text-gray-500 border-gray-200 hover:border-emerald-300 hover:text-emerald-600 hover:shadow-md' }}">
                Received (Paid)
                <span class="ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium {{ $tab == 'received' ? 'bg-emerald-500 text-white' : 'bg-green-100 text-green-800' }}">
                    {{ $receivedCount ?? 0 }}
                </span>
            </a>
        </div>
    </div>

    <!-- CONTENT: UNBILLED -->
    @if($tab == 'unbilled')
    <div class="overflow-hidden rounded-lg bg-white shadow border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Client</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Task</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Completed Date</th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($unbilledTasks as $task)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ $task->client->name ?? 'N/A' }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $task->title }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $task->updated_at->format('d M Y') }}</td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-2">
                            <a href="{{ route('invoices.create', ['task_id' => $task->id, 'client_id' => $task->client_id]) }}" class="text-indigo-600 hover:text-indigo-900 font-bold">Raise Invoice</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">
                            No unbilled tasks pending.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- CONTENT: INVOICES (Raised / Received) -->
    @if($tab == 'raised' || $tab == 'received')

    <!-- Filters (Collapsible if needed, kept simple for now) -->
    <div class="flex justify-end mb-2">
        <form method="GET" action="{{ route('invoices.index') }}" class="flex space-x-2">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <select name="client_id" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">All Clients</option>
                @foreach($clients as $client)
                <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Invoice #</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Client</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Date</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Amount</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-indigo-600 sm:pl-6">
                            <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                            {{ $invoice->client->name }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {{ $invoice->date->format('d M Y') }}
                            <div class="text-xs text-gray-400">Due: {{ $invoice->due_date->format('d M Y') }}</div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm font-bold text-gray-900">
                            ₹ {{ number_format($invoice->total_amount, 2) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium 
                            {{ $invoice->status === 'Paid' ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20' : '' }}
                            {{ $invoice->status === 'Sent' ? 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20' : '' }}
                            {{ $invoice->status === 'Overdue' ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/10' : '' }}
                            {{ $invoice->status === 'Draft' ? 'bg-gray-50 text-gray-600 ring-1 ring-inset ring-gray-500/10' : '' }}">
                                {{ $invoice->status }}
                            </span>
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 flex justify-end space-x-2">
                            <!-- PDF Download -->
                            <a href="{{ route('invoices.download-pdf', $invoice) }}" class="text-gray-400 hover:text-gray-600" title="Download PDF">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                            </a>
                            <a href="{{ route('invoices.edit', $invoice) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-semibold text-gray-900">No invoices in this view</h3>
                            <p class="mt-1 text-sm text-text-secondary">Switch tabs or create a new invoice.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $invoices->appends(['tab' => $tab])->links() }}
        </div>
    </div>
    @endif

</div>
@endsection
@endsection