@extends('layouts.app')

@section('header')
Due Date Reports
@endsection

@section('content')
<div class="space-y-6">
    <!-- Include Filters -->
    @include('reports.partials.filters')

    <!-- Tabs -->
    @include('reports.partials.tabs')

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white shadow rounded-lg p-6">
            <div class="text-sm font-medium text-gray-500">Next 7 Days</div>
            <div class="mt-2 text-3xl font-bold text-yellow-600">{{ $next7Days }}</div>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <div class="text-sm font-medium text-gray-500">Next 15 Days</div>
            <div class="mt-2 text-3xl font-bold text-orange-600">{{ $next15Days }}</div>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <div class="text-sm font-medium text-gray-500">Next 30 Days</div>
            <div class="mt-2 text-3xl font-bold text-blue-600">{{ $next30Days }}</div>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <div class="text-sm font-medium text-gray-500">Overdue Items</div>
            <div class="mt-2 text-3xl font-bold text-red-600">{{ $overdueServiceDues + $overdueTasks }}</div>
        </div>
    </div>

    <!-- Upcoming Service Dues -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Upcoming Service Dues</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($upcomingServiceDues as $due)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $due->clientService->client->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $due->clientService->service->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $due->due_date->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 py-1 text-xs rounded-full {{ $due->status === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $due->status }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No upcoming service dues</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Upcoming Tasks -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Upcoming Tasks</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Task</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($upcomingTasks as $task)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $task->title }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $task->client->name ?? 'Internal' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $task->due_date->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 py-1 text-xs rounded-full {{ $task->status === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $task->status }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No upcoming tasks</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection