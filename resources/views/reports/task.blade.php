@extends('layouts.app')

@section('header')
Task Reports
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
            <div class="text-sm font-medium text-gray-500">Total Tasks</div>
            <div class="mt-2 text-3xl font-bold text-gray-900">{{ $totalTasks }}</div>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <div class="text-sm font-medium text-gray-500">Completed</div>
            <div class="mt-2 text-3xl font-bold text-green-600">{{ $completedTasks }}</div>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <div class="text-sm font-medium text-gray-500">Completion Rate</div>
            <div class="mt-2 text-3xl font-bold text-blue-600">{{ number_format($completionRate, 1) }}%</div>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <div class="text-sm font-medium text-gray-500">Overdue</div>
            <div class="mt-2 text-3xl font-bold text-red-600">{{ $overdueTasks }}</div>
        </div>
    </div>

    <!-- Task Status Distribution -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Task Status Distribution</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Count</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($tasksByStatus as $status)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $status->status }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $status->count }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500">No data available</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Task Assignment Distribution -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Task Assignment Distribution</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assignee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Count</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($tasksByAssignee as $assignee)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $assignee->assignee->name ?? 'Unassigned' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $assignee->count }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500">No data available</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection