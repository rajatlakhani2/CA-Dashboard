
@extends('layouts.app')

@section('header')
@php
    $canManageInvoices = auth()->user()?->managesFirmModules();
@endphp
<div class="flex justify-between items-center w-full">
    <span>{{ auth()->user()?->isAssociate() ? 'My Client Invoices' : 'Invoices & Billing' }}</span>
    @can('create', App\Models\Invoice::class)
    <a href="{{ route('invoices.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded shadow transition-colors">
        Create Invoice
    </a>
    @endcan
</div>
@endsection

@section('content')
@php
    $canManageInvoices = auth()->user()?->managesFirmModules();
@endphp
<div class="space-y-6 max-w-full overflow-x-hidden">

    @if($canManageInvoices)
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
    @endif

    <!-- Tab Navigation -->
    <div class="mb-6 overflow-x-auto">
        <div class="flex flex-wrap gap-2 min-w-0">
            @if($canManageInvoices)
            <a href="{{ route('invoices.index', ['tab' => 'unbilled']) }}"
                class="px-5 py-2.5 text-base font-bold rounded-full transition-all duration-200 shadow-sm border flex items-center shrink-0
                {{ $tab == 'unbilled' 
                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-200' 
                    : 'bg-white text-gray-500 border-gray-200 hover:border-indigo-300 hover:text-indigo-600 hover:shadow-md' }}">
                Unbilled Work
                @if($unbilledTasks->count() > 0)
                <span class="ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium {{ $tab == 'unbilled' ? 'bg-indigo-500 text-white' : 'bg-gray-100 text-gray-900' }}">
                    {{ $unbilledTasks->count() }}
                </span>
                @endif
            </a>
            @endif

            <a href="{{ route('invoices.index', ['tab' => 'raised']) }}"
                class="px-5 py-2.5 text-base font-bold rounded-full transition-all duration-200 shadow-sm border flex items-center shrink-0
                {{ $tab == 'raised' 
                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-200' 
                    : 'bg-white text-gray-500 border-gray-200 hover:border-indigo-300 hover:text-indigo-600 hover:shadow-md' }}">
                Raised (Unpaid)
                <span class="ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium {{ $tab == 'raised' ? 'bg-indigo-500 text-white' : 'bg-gray-100 text-gray-900' }}">
                    {{ $raisedCount ?? 0 }}
                </span>
            </a>

            <a href="{{ route('invoices.index', ['tab' => 'received']) }}"
                class="px-5 py-2.5 text-base font-bold rounded-full transition-all duration-200 shadow-sm border flex items-center shrink-0
                {{ $tab == 'received' 
                    ? 'bg-emerald-600 text-white border-emerald-600 shadow-emerald-200' 
                    : 'bg-white text-gray-500 border-gray-200 hover:border-emerald-300 hover:text-emerald-600 hover:shadow-md' }}">
                Received (Paid)
                <span class="ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium {{ $tab == 'received' ? 'bg-emerald-500 text-white' : 'bg-green-100 text-green-800' }}">
                    {{ $receivedCount ?? 0 }}
                </span>
            </a>
        </div>
    </div>

    <!-- CONTENT: UNBILLED -->
    @if($canManageInvoices && $tab == 'unbilled')
    <p class="text-sm text-gray-600 mb-3">Shows completed tasks that are not yet invoiced or marked FOC — including <strong>unassigned</strong> tasks.</p>
    <div class="overflow-hidden rounded-lg bg-white shadow border border-gray-200" data-demo-tour="unbilled-queue">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Client</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Task</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Completed Date</th>
                        @if(auth()->user()?->hasRole('partner', 'manager'))
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Assigned To</th>
                        @endif
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
                        @if(auth()->user()?->hasRole('partner', 'manager'))
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            @if($task->assignee)
                                {{ $task->assignee->name }}
                            @else
                                <span class="text-amber-700 font-medium">Unassigned</span>
                                @if($task->creator)
                                <span class="text-gray-400 text-xs block">by {{ $task->creator->name }}</span>
                                @endif
                            @endif
                        </td>
                        @endif
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-2">
                            <a href="{{ route('invoices.create', ['task_id' => $task->id, 'client_id' => $task->client_id]) }}" class="text-indigo-600 hover:text-indigo-900 font-bold">Raise Invoice</a>
                            @can('markFoc', $task)
                            <form action="{{ route('tasks.mark-foc', $task) }}" method="POST" class="inline" onsubmit="return confirm('Mark as Free of Cost? This task will leave the Unbilled list.');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-emerald-700 hover:text-emerald-900 font-bold">Mark FOC</button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()?->hasRole('partner', 'manager') ? 5 : 4 }}" class="px-6 py-12 text-center text-sm text-gray-500">
                            <p>No unbilled tasks here.</p>
                            <p class="mt-2 text-xs text-gray-400">Task must be <strong>Completed</strong> and not marked FOC. Use <strong>Tasks</strong> list → status <strong>Completed</strong> (unassigned tasks appear here too).</p>
                            <p class="mt-1 text-xs text-gray-400">Service dues use <a href="{{ route('billing.index') }}" class="text-indigo-600 underline">Billing Queue</a>, not this tab.</p>
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
                            {{ $invoice->client?->name ?? '— Client missing —' }}
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
                            {{ $invoice->status === 'Overdue' ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/10' : '' }}
                            {{ $invoice->status === 'Partially Paid' ? 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-600/20' : '' }}
                            {{ $invoice->status === 'Draft' ? 'bg-gray-50 text-gray-600 ring-1 ring-inset ring-gray-500/10' : '' }}">
                                {{ $invoice->status }}
                            </span>
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 flex justify-end items-center space-x-2">
                            @if($invoice->status !== 'Paid')
                            <!-- WhatsApp -->
                            <form action="{{ route('invoices.whatsapp', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Send WhatsApp reminder to {{ $invoice->client?->name ?? 'client' }}?')">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-800" title="Send WhatsApp Reminder">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                    </svg>
                                </button>
                            </form>
                            @endif
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
                        <td colspan="6" class="p-0 border-0">
                            @include('partials.empty-state', [
                                'title' => 'No invoices in this view',
                                'description' => 'Switch tabs or create a new invoice.',
                                'icon' => 'inbox',
                                'actionLabel' => 'Create invoice',
                                'actionUrl' => route('invoices.create'),
                            ])
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-gray-200 sm:px-6">
            {!! $invoices->appends(['tab' => $tab])->links() !!}
        </div>
    </div>
    @endif

</div>
@endsection