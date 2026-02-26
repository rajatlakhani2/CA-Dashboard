@extends('layouts.app')

@section('header')
Compliance Schedule
@endsection

@section('content')
<div class="space-y-6">
    <!-- Filters -->
    <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
        <form method="GET" action="{{ route('service-dues.index') }}">
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <!-- Status -->
                <div class="sm:col-span-2">
                    <label for="status" class="block text-sm font-medium text-text-secondary">Status</label>
                    <select id="status" name="status" class="mt-1 block w-full rounded-md border-line py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Statuses</option>
                        @foreach(['Pending', 'Completed', 'Overdue'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Client -->
                <div class="sm:col-span-2">
                    <label for="client_id" class="block text-sm font-medium text-text-secondary">Client</label>
                    <select id="client_id" name="client_id" class="mt-1 block w-full rounded-md border-line py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Clients</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Month -->
                <div class="sm:col-span-2">
                    <label for="month" class="block text-sm font-medium text-text-secondary">Month</label>
                    <input type="month" name="month" id="month" value="{{ request('month') }}" class="mt-1 block w-full rounded-md border-line shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <a href="{{ route('service-dues.index') }}" class="inline-flex items-center rounded-md border border-line bg-bg-card px-4 py-2 text-sm font-medium text-text-secondary shadow-sm hover:bg-gray-50">Clear</a>
                <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Filter</button>
            </div>
    </div>
    </form>
</div>
</form>
</div>

<!-- Actions -->
<div class="flex justify-end">
    <form action="{{ route('service-dues.generate') }}" method="POST">
        @csrf
        <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Generate Dues
        </button>
    </form>
</div>

<!-- Data Table -->
<div class="overflow-hidden rounded-lg bg-bg-card shadow">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-bg-body">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-text-main sm:pl-6">Client</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-text-main">Service</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-text-main">Due Date</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-text-main">Status</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-text-main">Details</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line bg-bg-card">
                @forelse($dues as $due)
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-indigo-600 sm:pl-6">
                        <a href="{{ route('clients.edit', $due->clientService->client) }}">{{ $due->clientService->client->name }}</a>
                        <div class="text-gray-500 text-xs">{{ $due->clientService->client->client_code }}</div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-text-main">
                        {{ $due->clientService->service->name }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        <div class="font-medium {{ $due->due_date->isPast() && $due->status !== 'Completed' ? 'text-red-600' : 'text-text-main' }}">
                            {{ $due->due_date->format('d M Y') }}
                        </div>
                        <div class="text-xs text-text-secondary">{{ $due->due_date->diffForHumans() }}</div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium 
                                {{ $due->status === 'Completed' ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20' : '' }}
                                {{ $due->status === 'Pending' ? 'bg-yellow-50 text-yellow-800 ring-1 ring-inset ring-yellow-600/20' : '' }}
                                {{ $due->status === 'Overdue' ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/10' : '' }}">
                            {{ $due->status }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary">
                        @if($due->completed_at)
                        <div>Completed: {{ $due->completed_at->format('d M Y') }}</div>
                        @endif
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        @if($due->status !== 'Completed')
                        <form action="{{ route('service-dues.complete', $due) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-indigo-600 hover:text-indigo-900" onclick="return confirm('Are you sure you want to mark this as complete?')">Mark Complete</button>
                        </form>
                        @else
                        <span class="text-gray-400 cursor-not-allowed">Completed</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-semibold text-text-main">No scheduled dues found</h3>
                        <p class="mt-1 text-sm text-text-secondary">Try adjusting your filters.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<div class="mt-4">
    {{ $dues->withQueryString()->links() }}
</div>
</div>
@endsection