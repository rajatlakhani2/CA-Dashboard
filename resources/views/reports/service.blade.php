@extends('layouts.app')

@section('header')
<h2 class="font-semibold text-xl text-gray-800 leading-tight">
    Service Reports
</h2>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Include Filters -->
    @include('reports.partials.filters')

    <!-- Tabs -->
    @include('reports.partials.tabs')

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Service Performance -->
        <div class="bg-white/80 backdrop-blur-md shadow-sm border border-white/20 rounded-2xl p-6 transition-all duration-300 hover:shadow-lg">
            <h3 class="text-lg font-bold text-gray-800 mb-4 bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-indigo-500">Service Performance</h3>
            <div class="overflow-x-auto rounded-xl border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Service</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Count</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-transparent">
                        @forelse($servicePerformance as $service)
                        <tr class="hover:bg-indigo-50/30 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800">{{ $service->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">{{ $service->total_count }}</td>
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

        <!-- Service Revenue -->
        <div class="bg-white/80 backdrop-blur-md shadow-sm border border-white/20 rounded-2xl p-6 transition-all duration-300 hover:shadow-lg">
            <h3 class="text-lg font-bold text-gray-800 mb-4 bg-clip-text text-transparent bg-gradient-to-r from-emerald-600 to-teal-500">Top Services by Revenue</h3>
            <div class="overflow-x-auto rounded-xl border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Service</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-transparent">
                        @forelse($serviceRevenue as $service)
                        <tr class="hover:bg-emerald-50/30 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800">{{ $service->service_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">₹{{ number_format($service->total_revenue, 2) }}</td>
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
</div>
@endsection