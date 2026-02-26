@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <span>Compliance Report</span>
    <a href="{{ route('reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Back to Reports</a>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Filters -->
    @include('reports.partials.filters')

    <!-- Tabs -->
    @include('reports.partials.tabs')

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-6 rounded shadow border-t-4 border-indigo-500">
            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Total Statutory Dues</h3>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ $totalDues }}</div>
            <p class="text-xs text-gray-500 mt-1">In selected period</p>
        </div>
        <div class="bg-white p-6 rounded shadow border-t-4 {{ $completionRate >= 80 ? 'border-green-500' : 'border-yellow-500' }}">
            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Completion Rate</h3>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($completionRate, 1) }}%</div>
            <p class="text-xs text-gray-500 mt-1">Status: Completed</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Status Distribution -->
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Status Distribution</h3>
            </div>
            <div class="border-t border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($statusDistribution as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <a href="{{ route('service-dues.index', ['status' => $item->status]) }}" class="block w-full h-full">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $item->status == 'Completed' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $item->status == 'Overdue' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $item->status == 'Pending' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                        {{ $item->status }}
                                    </span>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                <a href="{{ route('service-dues.index', ['status' => $item->status]) }}" class="block w-full h-full font-bold hover:text-indigo-600">
                                    {{ $item->count }}
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Service-wise Breakdown -->
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Service Breakdown</h3>
            </div>
            <div class="border-t border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Due Count</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($serviceBreakdown as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <a href="{{ route('service-dues.index', ['service_id' => $item->service_id]) }}" class="block w-full h-full text-indigo-600 hover:underline">
                                    {{ $item->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                <a href="{{ route('service-dues.index', ['service_id' => $item->service_id]) }}" class="block w-full h-full font-bold hover:text-indigo-600">
                                    {{ $item->total }}
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection