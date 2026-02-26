@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <div class="flex items-center space-x-4">
        <h1 class="text-2xl font-bold text-gray-900">{{ $client->name }}</h1>
        <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
            {{ $client->client_code }}
        </span>
        <span class="inline-flex items-center rounded-md 
            {{ $client->status === 'Active' ? 'bg-green-50 text-green-700 ring-green-600/20' : '' }}
            {{ $client->status === 'On-Hold' ? 'bg-yellow-50 text-yellow-800 ring-yellow-600/20' : '' }}
            {{ $client->status === 'Closed' ? 'bg-red-50 text-red-700 ring-red-600/20' : '' }}
            px-2 py-1 text-xs font-medium ring-1 ring-inset">
            {{ $client->status }}
        </span>
    </div>
    <div class="flex space-x-3">
        <a href="{{ route('invoices.create', ['client_id' => $client->id]) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-md text-sm font-semibold shadow-sm">
            + New Invoice
        </a>
        <a href="{{ route('clients.edit', $client) }}" class="bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 px-3 py-2 rounded-md text-sm font-semibold shadow-sm">
            Edit Profile
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Top Row: Financial Summary -->
    <dl class="grid grid-cols-1 gap-5 sm:grid-cols-4">
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Total Billed (YTD)</dt>
            <dd class="mt-1 text-2xl font-semibold tracking-tight text-gray-900">₹ {{ number_format($totalBilled, 2) }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Collected</dt>
            <dd class="mt-1 text-2xl font-semibold tracking-tight text-green-600">₹ {{ number_format($totalCollected, 2) }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Outstanding</dt>
            <dd class="mt-1 text-2xl font-semibold tracking-tight text-red-600">₹ {{ number_format($totalOutstanding, 2) }}</dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Active Tasks</dt>
            <dd class="mt-1 text-2xl font-semibold tracking-tight text-indigo-600">{{ $activeTasks->count() }}</dd>
        </div>
    </dl>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        <!-- Left Column: Operations (Tasks & Compliance) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Pending Compliance -->
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Pending Compliance</h3>
                    <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full">{{ $serviceDues->count() }} Due</span>
                </div>
                <ul role="list" class="divide-y divide-gray-200">
                    @forelse($serviceDues as $due)
                    <li class="px-4 py-4 sm:px-6 hover:bg-gray-50 transition duration-150 ease-in-out">
                        <div class="flex items-center justify-between">
                            <p class="truncate text-sm font-medium text-indigo-600">{{ $due->clientService->service->name ?? 'Service' }}</p>
                            <div class="ml-2 flex flex-shrink-0">
                                <p class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800">
                                    Due: {{ $due->due_date->format('d M') }}
                                </p>
                            </div>
                        </div>
                    </li>
                    @empty
                    <li class="px-4 py-4 text-sm text-gray-500 text-center">No pending dues. Good job!</li>
                    @endforelse
                </ul>
            </div>

            <!-- Active Tasks -->
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Active Work items</h3>
                </div>
                <ul role="list" class="divide-y divide-gray-200">
                    @forelse($activeTasks as $task)
                    <li class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <p class="text-sm font-medium text-gray-900">{{ $task->title }}</p>
                                <p class="text-xs text-gray-500 mt-1">Assigned to: {{ $task->assignee->name ?? 'Unassigned' }}</p>
                            </div>
                            <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                {{ $task->status }}
                            </span>
                        </div>
                    </li>
                    @empty
                    <li class="px-4 py-4 text-sm text-gray-500 text-center">No active tasks.</li>
                    @endforelse
                </ul>
            </div>

        </div>

        <!-- Right Column: Profile & Finance -->
        <div class="space-y-6">

            <!-- Contact Card -->
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Profile</h3>
                </div>
                <div class="px-4 py-4 text-sm">
                    <div class="mt-2 text-gray-900 font-medium">Primary Contact</div>
                    <div class="text-gray-500">{{ $client->primary_contact_name ?? 'N/A' }}</div>

                    <div class="mt-3 text-gray-900 font-medium">Email</div>
                    <div class="text-gray-500">{{ $client->primary_contact_email ?? 'N/A' }}</div>

                    <div class="mt-3 text-gray-900 font-medium">Phone</div>
                    <div class="text-gray-500">{{ $client->primary_contact_phone ?? 'N/A' }}</div>

                    <div class="mt-3 text-gray-900 font-medium">PAN / GSTIN</div>
                    <div class="text-gray-500">{{ $client->pan }} / {{ $client->gstin }}</div>

                    <div class="mt-3 text-gray-900 font-medium">Opted Services</div>
                    <div class="flex flex-wrap gap-1 mt-1">
                        @foreach($client->optedServices as $service)
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                            {{ $service->name }}
                        </span>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Recent Invoices -->
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-4 border-b border-gray-200 bg-gray-50 flex justify-between">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Recent Invoices</h3>
                    <a href="{{ route('invoices.index', ['client_id' => $client->id]) }}" class="text-sm text-indigo-600 hover:text-indigo-500">View All</a>
                </div>
                <ul role="list" class="divide-y divide-gray-200">
                    @foreach($client->invoices as $invoice)
                    <li class="px-4 py-3">
                        <div class="flex justify-between items-center">
                            <div class="text-sm">
                                <a href="{{ route('invoices.show', $invoice) }}" class="font-medium text-indigo-600 hover:underline">
                                    {{ $invoice->invoice_number }}
                                </a>
                                <p class="text-xs text-gray-500">{{ $invoice->date->format('d M') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900">₹ {{ number_format($invoice->total_amount) }}</p>
                                <p class="text-xs {{ $invoice->status == 'Paid' ? 'text-green-600' : ($invoice->status == 'Overdue' ? 'text-red-600' : 'text-yellow-600') }}">
                                    {{ $invoice->status }}
                                </p>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>

        </div>
    </div>
</div>
@endsection