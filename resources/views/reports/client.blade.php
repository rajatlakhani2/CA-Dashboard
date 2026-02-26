@extends('layouts.app')

@section('header')
<h2 class="font-semibold text-xl text-gray-800 leading-tight">
    Client Reports
</h2>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Include Filters -->
    @include('reports.partials.filters')

    <!-- Tabs -->
    @include('reports.partials.tabs')

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white/80 backdrop-blur-md shadow-sm border border-white/20 rounded-2xl p-6 transition-all duration-300 hover:shadow-lg group">
            <div class="text-sm font-bold text-gray-500 uppercase tracking-wider group-hover:text-indigo-600 transition-colors">Total Clients</div>
            <div class="mt-2 text-4xl font-extrabold text-gray-800">{{ $totalClients }}</div>
        </div>
        <div class="bg-white/80 backdrop-blur-md shadow-sm border border-white/20 rounded-2xl p-6 transition-all duration-300 hover:shadow-lg group">
            <div class="text-sm font-bold text-gray-500 uppercase tracking-wider group-hover:text-emerald-600 transition-colors">Active Clients</div>
            <div class="mt-2 text-4xl font-extrabold text-emerald-600">{{ $activeClients }}</div>
        </div>
        <div class="bg-white/80 backdrop-blur-md shadow-sm border border-white/20 rounded-2xl p-6 transition-all duration-300 hover:shadow-lg group">
            <div class="text-sm font-bold text-gray-500 uppercase tracking-wider group-hover:text-blue-600 transition-colors">New Clients</div>
            <div class="mt-2 text-4xl font-extrabold text-blue-600">{{ $newClients }}</div>
        </div>
    </div>

    <!-- Top Clients by Revenue -->
    <div class="bg-white/80 backdrop-blur-md shadow-sm border border-white/20 rounded-2xl p-6 transition-all duration-300 hover:shadow-lg">
        <h3 class="text-lg font-bold text-gray-800 mb-4 bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-indigo-500">Top Clients by Revenue</h3>
        <div class="overflow-x-auto rounded-xl border border-gray-100">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Client</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-transparent">
                    @forelse($topClients as $item)
                    <tr class="hover:bg-indigo-50/30 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800">
                            {{ $item->client->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">₹{{ number_format($item->total_revenue, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="px-6 py-8 text-center text-sm text-gray-500 italic">No data available</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection