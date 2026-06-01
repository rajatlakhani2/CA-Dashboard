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
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-text-main">Documents</th>
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
                        @if($due->clientService?->client)
                        <a href="{{ route('clients.edit', $due->clientService->client) }}">{{ $due->clientService->client->name }}</a>
                        <div class="text-gray-500 text-xs">{{ $due->clientService->client->client_code }}</div>
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-text-main">
                        {{ $due->clientService?->service?->name ?? '—' }}
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
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        @php $docs = $due->doc_checklist ?? ['total' => 0, 'missing' => 0]; @endphp
                        @if(($docs['total'] ?? 0) > 0)
                            @if(($docs['missing'] ?? 0) > 0 && $due->status !== 'Completed')
                            @if($due->clientService?->client)
                            <a href="{{ route('clients.show', $due->clientService->client) }}?tab=work#document-checklists"
                                class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-600/20 hover:bg-amber-100"
                                title="Open client document checklist">
                                {{ $docs['missing'] }}/{{ $docs['total'] }} missing →
                            </a>
                            @else
                            <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-600/20">
                                {{ $docs['missing'] }}/{{ $docs['total'] }} missing
                            </span>
                            @endif
                            @else
                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                {{ $docs['received'] ?? $docs['total'] }}/{{ $docs['total'] }} received
                            </span>
                            @endif
                        @else
                        <span class="text-text-secondary text-xs">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary">
                        @if($due->completed_at)
                        <div>Completed: {{ $due->completed_at->format('d M Y') }}</div>
                        @endif
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 flex justify-end items-center space-x-3">
                        @if($due->status !== 'Completed')
                        <!-- WhatsApp -->
                        <form action="{{ route('service-dues.whatsapp', $due) }}" method="POST" class="inline" onsubmit="return confirm('Send WhatsApp reminder to {{ $due->clientService?->client?->name ?? 'client' }}?')">
                            @csrf
                            <button type="submit" class="text-green-600 hover:text-green-800" title="Send WhatsApp Reminder">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                </svg>
                            </button>
                        </form>
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
                    <td colspan="6" class="p-0 border-0">
                        @include('partials.empty-state', [
                            'title' => 'No scheduled dues found',
                            'description' => 'Try adjusting your filters or generate dues from Service Master.',
                            'icon' => 'inbox',
                        ])
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<div class="mt-4">
    {!! $dues->withQueryString()->links() !!}
</div>
</div>
@endsection