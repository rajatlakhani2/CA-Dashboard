@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <span>Financial Report</span>
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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded shadow border-t-4 border-blue-500">
            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Total Invoiced</h3>
            <div class="mt-2 text-3xl font-bold text-gray-900">₹ {{ number_format($totalInvoiced, 2) }}</div>
        </div>
        <div class="bg-white p-6 rounded shadow border-t-4 border-green-500">
            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Total Collected</h3>
            <div class="mt-2 text-3xl font-bold text-gray-900">₹ {{ number_format($totalCollected, 2) }}</div>
        </div>
        <div class="bg-white p-6 rounded shadow border-t-4 border-red-500">
            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Outstanding</h3>
            <div class="mt-2 text-3xl font-bold text-gray-900">₹ {{ number_format($totalOutstanding, 2) }}</div>
        </div>
    </div>

    <!-- Monthly Revenue Table -->
    <div class="bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Monthly Revenue</h3>
        </div>
        <div class="border-t border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($monthlyRevenue as $item)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item->month }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">₹ {{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Clients Table -->
    <div class="bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Top Clients by Revenue</h3>
        </div>
        <div class="border-t border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Invoiced</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($clientRevenue as $item)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item->client->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">₹ {{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection