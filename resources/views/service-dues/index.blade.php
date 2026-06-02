@extends('layouts.app')

@section('header')
Compliance Schedule
@endsection

@section('content')
@php
    $stats = $stats ?? ['pending' => 0, 'overdue' => 0, 'due_this_month' => 0];
    $services = $services ?? collect();
@endphp
<div class="max-w-7xl mx-auto space-y-5">
    {{-- Summary --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm px-4 py-3">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Pending</p>
            <p class="text-2xl font-bold text-amber-600">{{ number_format($stats['pending']) }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm px-4 py-3">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Overdue</p>
            <p class="text-2xl font-bold text-red-600">{{ number_format($stats['overdue']) }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm px-4 py-3">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Due this month</p>
            <p class="text-2xl font-bold text-indigo-600">{{ number_format($stats['due_this_month']) }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm px-4 py-3">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">This page</p>
            <p class="text-2xl font-bold text-slate-800">{{ $dues->count() }} <span class="text-sm font-normal text-slate-500">/ {{ $dues->total() }}</span></p>
        </div>
    </div>

    {{-- Toolbar: filters + actions --}}
    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Filters</h3>
                <p class="text-xs text-slate-500">Due dates come from <a href="{{ route('services.index') }}" class="text-indigo-600 font-medium hover:underline">Service Master</a> → Generate Dues</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if(auth()->user()?->isPartner() || auth()->user()?->isManager())
                <form action="{{ route('service-dues.generate') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                        <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Generate Dues
                    </button>
                </form>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('service-dues.index') }}" class="p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label for="status" class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                    <select id="status" name="status" class="block w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All</option>
                        @foreach(['Pending', 'Completed', 'Overdue', 'Extended'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="service_id" class="block text-xs font-semibold text-slate-600 mb-1">Service</label>
                    <select id="service_id" name="service_id" class="block w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All services</option>
                        @foreach($services as $service)
                        <option value="{{ $service->id }}" {{ request('service_id') == $service->id ? 'selected' : '' }}>{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="client_id" class="block text-xs font-semibold text-slate-600 mb-1">Client</label>
                    <select id="client_id" name="client_id" class="block w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All clients</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }} ({{ $client->client_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="month" class="block text-xs font-semibold text-slate-600 mb-1">Month</label>
                    <input type="month" name="month" id="month" value="{{ request('month') }}" class="block w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Apply</button>
                    <a href="{{ route('service-dues.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Clear</a>
                </div>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-bold uppercase tracking-wide text-slate-600 sm:pl-6">Client</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-600">Service</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-600">Due date</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-600">Status</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-600 hidden md:table-cell">Documents</th>
                        <th scope="col" class="px-3 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-600 sm:pr-6">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($dues as $due)
                    <tr class="hover:bg-slate-50/80">
                        <td class="py-3 pl-4 pr-3 text-sm sm:pl-6">
                            @if($due->clientService?->client)
                            <a href="{{ route('clients.edit', $due->clientService->client) }}" class="font-semibold text-indigo-600 hover:text-indigo-800">{{ $due->clientService->client->name }}</a>
                            <div class="text-xs text-slate-500">{{ $due->clientService->client->client_code }}</div>
                            @else
                            <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-sm text-slate-800">{{ $due->clientService?->service?->name ?? '—' }}</td>
                        <td class="px-3 py-3 text-sm whitespace-nowrap">
                            <span class="font-medium {{ $due->due_date->isPast() && $due->status !== 'Completed' ? 'text-red-600' : 'text-slate-900' }}">
                                {{ $due->due_date->format('d M Y') }}
                            </span>
                            <span class="block text-xs text-slate-500">{{ $due->due_date->diffForHumans() }}</span>
                        </td>
                        <td class="px-3 py-3 text-sm whitespace-nowrap">
                            @php
                            $statusClass = match($due->status) {
                                'Completed' => 'bg-green-50 text-green-700 ring-green-600/20',
                                'Overdue' => 'bg-red-50 text-red-700 ring-red-600/20',
                                'Extended' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                default => 'bg-amber-50 text-amber-800 ring-amber-600/20',
                            };
                            @endphp
                            <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClass }}">{{ $due->status }}</span>
                        </td>
                        <td class="px-3 py-3 text-sm hidden md:table-cell">
                            @php $docs = $due->doc_checklist ?? ['total' => 0, 'missing' => 0]; @endphp
                            @if(($docs['total'] ?? 0) > 0)
                                @if(($docs['missing'] ?? 0) > 0 && $due->status !== 'Completed' && $due->clientService?->client)
                                <a href="{{ route('clients.show', $due->clientService->client) }}?tab=work#document-checklists" class="text-xs font-semibold text-amber-700 hover:underline">{{ $docs['missing'] }}/{{ $docs['total'] }} missing</a>
                                @else
                                <span class="text-xs text-green-700">{{ $docs['received'] ?? $docs['total'] }}/{{ $docs['total'] }} ok</span>
                                @endif
                            @else
                            <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-sm text-right whitespace-nowrap sm:pr-6">
                            @if($due->status !== 'Completed')
                            <div class="inline-flex items-center gap-2 justify-end">
                                @if(auth()->user()?->isPartner() || auth()->user()?->isManager())
                                <form action="{{ route('service-dues.whatsapp', $due) }}" method="POST" class="inline" onsubmit="return confirm('Send WhatsApp to {{ $due->clientService?->client?->name ?? 'client' }}?')">
                                    @csrf
                                    <button type="submit" class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50" title="WhatsApp reminder">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                    </button>
                                </form>
                                @endif
                                <form action="{{ route('service-dues.complete', $due) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800" onclick="return confirm('Mark this due as complete?')">Complete</button>
                                </form>
                            </div>
                            @else
                            <span class="text-xs text-slate-400">Done {{ $due->completed_at?->format('d M Y') }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8">
                            <div class="text-center max-w-md mx-auto">
                                <p class="text-base font-semibold text-slate-900">No dues match these filters</p>
                                <p class="mt-2 text-sm text-slate-500">Set rules in Service Master, assign services on clients, then click <strong>Generate Dues</strong>.</p>
                                <a href="{{ route('services.index') }}" class="mt-4 inline-flex text-sm font-semibold text-indigo-600 hover:underline">Open Service Master →</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($dues->hasPages())
        <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/50">
            {{ $dues->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
