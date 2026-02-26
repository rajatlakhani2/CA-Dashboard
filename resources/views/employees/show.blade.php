@extends('layouts.app')

@section('header', $employee->name . ' - Performance 360°')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Efficiency Score -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Efficiency Score</p>
                <div class="mt-2 flex items-baseline space-x-2">
                    <span class="text-3xl font-bold text-gray-900">{{ $efficiency }}%</span>
                </div>
            </div>
            <div class="h-12 w-12 rounded-full {{ $efficiency >= 80 ? 'bg-green-100 text-green-600' : ($efficiency >= 50 ? 'bg-yellow-100 text-yellow-600' : 'bg-red-100 text-red-600') }} flex items-center justify-center font-bold text-lg">
                {{ $efficiency >= 80 ? 'A' : ($efficiency >= 50 ? 'B' : 'C') }}
            </div>
        </div>

        <!-- Total Tasks -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Workload</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ $totalTasks }} <span class="text-sm font-normal text-gray-400">tasks</span></p>
        </div>

        <!-- Pending -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Pending</p>
            <p class="mt-2 text-3xl font-bold text-amber-600">{{ $pendingTasks }}</p>
        </div>

        <!-- Completed -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Completed</p>
            <p class="mt-2 text-3xl font-bold text-green-600">{{ $completedTasks }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Active Tasks List -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900">Active Tasks</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                        {{ $activeTasks->count() }} Ongoing
                    </span>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse($activeTasks as $task)
                    <div class="p-4 hover:bg-gray-50 transition-colors flex items-center justify-between">
                        <div class="flex-1 min-w-0 pr-4">
                            <div class="flex items-center justify-between mb-1">
                                <h4 class="text-sm font-bold text-gray-900 truncate">{{ $task->title }}</h4>
                                <span class="text-xs px-2 py-0.5 rounded-md font-medium {{ $task->priority == 'High' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $task->priority }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 truncate">
                                {{ $task->client->name ?? 'Internal' }} &bull; Due {{ $task->due_date ? $task->due_date->format('M d') : 'No Date' }}
                            </p>
                        </div>
                        <div>
                            <a href="{{ route('tasks.edit', $task) }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">View</a>
                        </div>
                    </div>
                    @empty
                    <div class="p-6 text-center text-gray-500 text-sm">No active tasks assigned.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Managed Clients -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-900">Managed Clients</h3>
                </div>
                <ul class="divide-y divide-gray-100 max-h-[500px] overflow-y-auto">
                    @forelse($employee->managedClients as $client)
                    <li class="p-4 hover:bg-gray-50 flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 font-bold text-xs mr-3">
                                {{ substr($client->name, 0, 1) }}
                            </span>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $client->name }}</p>
                                <p class="text-xs text-gray-500">{{ $client->pan_number }}</p>
                            </div>
                        </div>
                        <a href="{{ route('clients.show', $client) }}" class="text-gray-400 hover:text-indigo-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </li>
                    @empty
                    <li class="p-6 text-center text-gray-500 text-sm">No clients under management.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection