@extends('layouts.app')

@section('header')
Client Profitability Report
@endsection

@section('content')
<div class="space-y-6">
    @include('reports.partials.filters')
    @include('reports.partials.tabs')

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white shadow rounded-lg p-5">
            <p class="text-xs font-bold text-slate-500 uppercase">Revenue (period)</p>
            <p class="text-2xl font-black text-slate-900 mt-1">₹ {{ number_format($totals['revenue'], 0) }}</p>
        </div>
        <div class="bg-white shadow rounded-lg p-5">
            <p class="text-xs font-bold text-slate-500 uppercase">Collected</p>
            <p class="text-2xl font-black text-emerald-600 mt-1">₹ {{ number_format($totals['collected'], 0) }}</p>
        </div>
        <div class="bg-white shadow rounded-lg p-5">
            <p class="text-xs font-bold text-slate-500 uppercase">Outstanding (now)</p>
            <p class="text-2xl font-black text-red-600 mt-1">₹ {{ number_format($totals['outstanding'], 0) }}</p>
        </div>
        <div class="bg-white shadow rounded-lg p-5">
            <p class="text-xs font-bold text-slate-500 uppercase">Low-margin clients</p>
            <p class="text-2xl font-black text-amber-600 mt-1">{{ $totals['low_margin_count'] }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ $totals['hours'] }}h logged on client tasks</p>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full text-sm divide-y divide-slate-100">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">Client</th>
                    <th class="px-4 py-3 text-right">Revenue</th>
                    <th class="px-4 py-3 text-right">Collected</th>
                    <th class="px-4 py-3 text-right">Outstanding</th>
                    <th class="px-4 py-3 text-right">Hours</th>
                    <th class="px-4 py-3 text-right">Realization</th>
                    <th class="px-4 py-3 text-right">₹ / hour</th>
                    <th class="px-4 py-3 text-center">Flag</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $row)
                <tr class="hover:bg-slate-50 {{ $row->low_margin ? 'bg-amber-50/50' : '' }}">
                    <td class="px-4 py-3">
                        <a href="{{ route('clients.show', $row->client) }}" class="font-semibold text-indigo-600 hover:underline">{{ $row->client->name }}</a>
                        @if($row->client->category)
                        <span class="text-xs text-slate-400">Cat {{ $row->client->category }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">₹ {{ number_format($row->revenue, 0) }}</td>
                    <td class="px-4 py-3 text-right text-emerald-700">₹ {{ number_format($row->collected, 0) }}</td>
                    <td class="px-4 py-3 text-right text-red-600">₹ {{ number_format($row->outstanding, 0) }}</td>
                    <td class="px-4 py-3 text-right">{{ $row->hours }}h</td>
                    <td class="px-4 py-3 text-right">{{ $row->realization_rate }}%</td>
                    <td class="px-4 py-3 text-right">
                        {{ $row->revenue_per_hour ? '₹ ' . number_format($row->revenue_per_hour, 0) : '—' }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($row->low_margin)
                        <span class="text-xs font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full">Review</span>
                        @else
                        <span class="text-slate-300">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-slate-500">No billable client activity in this period.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="text-xs text-slate-500">Low-margin flag: high hours with low ₹/hour, or weak collection with large outstanding. Firm expenses are not allocated per client.</p>
</div>
@endsection
