@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <span>Reports & Analytics</span>
    <div class="flex items-center space-x-2">
        <button onclick="window.print()" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded shadow transition">
            <svg class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print
        </button>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Filters Card -->
    <div class="bg-white shadow rounded-lg p-6">
        <form method="GET" action="{{ route('reports.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Date Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                    <select name="date_range" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="this_month" {{ request('date_range') == 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ request('date_range') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                        <option value="this_quarter" {{ request('date_range') == 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
                        <option value="this_year" {{ request('date_range') == 'this_year' ? 'selected' : '' }}>This Year</option>
                        <option value="custom" {{ request('date_range') == 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>

                <!-- Start Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" name="start_date" value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- End Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" name="end_date" value="{{ request('end_date', now()->endOfMonth()->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <!-- Client Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                    <select name="client_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Clients</option>
                        @php
                        $clientQuery = \App\Models\Client::query();
                        $currentUser = auth()->user();
                        if ($currentUser?->isManager() && $currentUser->branch_id) {
                            $clientQuery->where(function ($query) use ($currentUser) {
                                $query->whereNull('branch_id')
                                    ->orWhere('branch_id', $currentUser->branch_id);
                            });
                        }
                        @endphp
                        @foreach($clientQuery->orderBy('name')->get() as $client)
                        <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex justify-end space-x-2">
                <a href="{{ route('reports.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Clear</a>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Apply Filters</button>
            </div>
        </form>
    </div>

    <!-- Tabbed Navigation -->
    <div class="bg-white shadow rounded-lg">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <a href="{{ route('reports.financial') }}" class="{{ request()->routeIs('reports.financial') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Financial
                </a>
                <a href="{{ route('reports.compliance') }}" class="{{ request()->routeIs('reports.compliance') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Compliance
                </a>
                <a href="{{ route('reports.service') }}" class="{{ request()->routeIs('reports.service') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Services
                </a>
                <a href="{{ route('reports.client') }}" class="{{ request()->routeIs('reports.client') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Clients
                </a>
                <a href="{{ route('reports.task') }}" class="{{ request()->routeIs('reports.task') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Tasks
                </a>
                <a href="{{ route('reports.due-date') }}" class="{{ request()->routeIs('reports.due-date') ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Due Dates
                </a>
            </nav>
        </div>

        <!-- Welcome Message -->
        <div class="p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">Welcome to Reports & Analytics</h3>
            <p class="mt-2 text-sm text-gray-500">Select a report type from the tabs above to view detailed analytics and insights.</p>
            <div class="mt-6 grid grid-cols-2 md:grid-cols-3 gap-4 max-w-3xl mx-auto">
                <a href="{{ route('reports.financial') }}" class="p-4 border border-gray-200 rounded-lg hover:border-indigo-500 hover:shadow transition">
                    <div class="text-2xl mb-2">💰</div>
                    <div class="font-medium text-sm">Financial</div>
                    <div class="text-xs text-gray-500">Revenue & Payments</div>
                </a>
                <a href="{{ route('reports.compliance') }}" class="p-4 border border-gray-200 rounded-lg hover:border-indigo-500 hover:shadow transition">
                    <div class="text-2xl mb-2">✅</div>
                    <div class="font-medium text-sm">Compliance</div>
                    <div class="text-xs text-gray-500">Service Dues</div>
                </a>
                <a href="{{ route('reports.service') }}" class="p-4 border border-gray-200 rounded-lg hover:border-indigo-500 hover:shadow transition">
                    <div class="text-2xl mb-2">🔧</div>
                    <div class="font-medium text-sm">Services</div>
                    <div class="text-xs text-gray-500">Performance</div>
                </a>
                <a href="{{ route('reports.client') }}" class="p-4 border border-gray-200 rounded-lg hover:border-indigo-500 hover:shadow transition">
                    <div class="text-2xl mb-2">👥</div>
                    <div class="font-medium text-sm">Clients</div>
                    <div class="text-xs text-gray-500">Activity & Value</div>
                </a>
                <a href="{{ route('reports.task') }}" class="p-4 border border-gray-200 rounded-lg hover:border-indigo-500 hover:shadow transition">
                    <div class="text-2xl mb-2">📋</div>
                    <div class="font-medium text-sm">Tasks</div>
                    <div class="text-xs text-gray-500">Status & Workload</div>
                </a>
                <a href="{{ route('reports.due-date') }}" class="p-4 border border-gray-200 rounded-lg hover:border-indigo-500 hover:shadow transition">
                    <div class="text-2xl mb-2">📅</div>
                    <div class="font-medium text-sm">Due Dates</div>
                    <div class="text-xs text-gray-500">Upcoming & Overdue</div>
                </a>
                <a href="{{ route('reports.staff-productivity') }}" class="p-4 border border-gray-200 rounded-lg hover:border-indigo-500 hover:shadow transition">
                    <div class="text-2xl mb-2">👤</div>
                    <div class="font-medium text-sm">Staff Productivity</div>
                    <div class="text-xs text-gray-500">Tasks & time</div>
                </a>
                <a href="{{ route('reports.client-profitability') }}" class="p-4 border border-gray-200 rounded-lg hover:border-indigo-500 hover:shadow transition">
                    <div class="text-2xl mb-2">📈</div>
                    <div class="font-medium text-sm">Profitability</div>
                    <div class="text-xs text-gray-500">Revenue vs effort</div>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
