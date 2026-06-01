@extends('layouts.app')

@section('header')
<div class="flex items-center gap-4">
    <a href="{{ route('staff.index') }}" class="text-gray-500 hover:text-gray-900 transition-colors">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
    </a>
    <span>{{ $employee->name }} - Performance 360°</span>
</div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Key Metrics & Reminder Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Efficiency Score -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Efficiency Score</p>
                <div class="mt-2 flex items-baseline space-x-2">
                    <span class="text-3xl font-black text-slate-800">{{ $efficiency }}%</span>
                </div>
            </div>
            <div class="h-12 w-12 rounded-full {{ $efficiency >= 80 ? 'bg-green-100 text-green-700' : ($efficiency >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }} flex items-center justify-center font-black text-lg shadow-sm">
                {{ $efficiency >= 80 ? 'A' : ($efficiency >= 50 ? 'B' : 'C') }}
            </div>
        </div>

        <!-- Total Tasks -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Workload</p>
            <p class="mt-2 text-3xl font-black text-slate-800">{{ $totalTasks }} <span class="text-sm font-bold text-gray-400">tasks</span></p>
        </div>

        <!-- Pending -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pending Tasks</p>
            <p class="mt-2 text-3xl font-black text-amber-600">{{ $pendingTasks }}</p>
        </div>

        <!-- Send Workload Summary Reminder -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col justify-center">
            @if($pendingTasks > 0 && !empty($employee->mobile))
            <form action="{{ route('staff.send-reminder', $employee) }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="summary">
                <button type="submit" class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition-all hover:scale-[1.02]">
                    <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    Send Workload Summary
                </button>
            </form>
            @elseif(empty($employee->mobile))
            <div class="text-center text-gray-400 text-xs py-3 border border-dashed border-gray-200 rounded-xl bg-gray-50/50 font-bold uppercase tracking-wider">
                No Mobile Number
            </div>
            @else
            <div class="text-center text-green-600 text-xs py-3 border border-green-100 rounded-xl bg-green-50/50 font-bold uppercase tracking-wider">
                ✓ No Pending Tasks
            </div>
            @endif
        </div>
    </div>

    <!-- Active Tasks and Management Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Active Tasks List -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-50 flex justify-between items-center bg-slate-50/20">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Active Tasks</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Tasks currently assigned to {{ $employee->name }}</p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-100">
                        {{ $activeTasks->count() }} Ongoing
                    </span>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse($activeTasks as $task)
                    <div class="p-5 hover:bg-indigo-50/10 transition-colors flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex-1 min-w-0 pr-4">
                            <div class="flex items-center justify-between mb-1">
                                <h4 class="text-sm font-bold text-gray-900 truncate">{{ $task->title }}</h4>
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-black uppercase tracking-wider {{ $task->priority == 'High' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-gray-50 text-gray-600 border border-gray-100' }}">
                                    {{ $task->priority }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-2 text-xs text-gray-500">
                                <span class="font-semibold text-gray-700">{{ $task->client->name ?? 'Internal' }}</span>
                                <span>&bull;</span>
                                <span class="{{ $task->due_date && $task->due_date->isPast() ? 'text-red-500 font-bold' : '' }}">
                                    Due {{ $task->due_date ? $task->due_date->format('d M Y') : 'No Date' }}
                                </span>
                                <span>&bull;</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700">{{ $task->status }}</span>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex items-center gap-3">
                            <a href="{{ route('tasks.edit', $task) }}" class="text-gray-500 hover:text-indigo-600 text-xs font-bold px-3 py-1.5 rounded-lg border border-gray-100 hover:border-indigo-100 transition-colors bg-white shadow-sm">
                                View Task
                            </a>
                            
                            @if(!empty($employee->mobile))
                            <form action="{{ route('staff.send-reminder', $employee) }}" method="POST">
                                @csrf
                                <input type="hidden" name="type" value="single_task">
                                <input type="hidden" name="task_id" value="{{ $task->id }}">
                                <button type="submit" class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-indigo-100 text-xs font-bold text-indigo-600 bg-indigo-50/50 hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                    <svg class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    Remind WhatsApp
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="p-8 text-center text-gray-500 text-sm font-semibold">No active tasks assigned.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar Actions & Managed Clients -->
        <div class="lg:col-span-1 space-y-6">
            
            <!-- Allot New Work -->
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-50 bg-slate-50/20">
                    <h3 class="text-base font-bold text-gray-900">Allot New Work</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Select an unassigned task to assign to this member</p>
                </div>
                <div class="p-6">
                    <form action="{{ route('staff.allot-work', $employee) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="task_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Select Task</label>
                            <select name="task_id" id="task_id" class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3 bg-white">
                                <option value="">-- Choose Unassigned Task --</option>
                                @foreach($unassignedTasks as $task)
                                <option value="{{ $task->id }}">{{ $task->title }} ({{ $task->client?->name ?? 'Internal' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-xl shadow-md shadow-indigo-500/20 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-all hover:scale-[1.02]">
                            Assign Task
                        </button>
                    </form>
                </div>
            </div>

            <!-- Managed Clients -->
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-50 bg-slate-50/20">
                    <h3 class="text-base font-bold text-gray-900">Managed Clients</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Clients assigned under this member's management</p>
                </div>
                <ul class="divide-y divide-gray-100 max-h-[350px] overflow-y-auto">
                    @forelse($employee->managedClients as $client)
                    <li class="p-4 hover:bg-indigo-50/10 flex items-center justify-between transition-colors">
                        <div class="flex items-center">
                            <span class="h-9 w-9 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-700 font-bold text-sm mr-3">
                                {{ substr($client->name, 0, 1) }}
                            </span>
                            <div>
                                <p class="text-sm font-bold text-gray-900">{{ $client->name }}</p>
                                <p class="text-[10px] text-gray-400 font-bold tracking-wider uppercase mt-0.5">{{ $client->pan_number ?? 'No PAN' }}</p>
                            </div>
                        </div>
                        <a href="{{ route('clients.show', $client) }}" class="text-gray-400 hover:text-indigo-600 p-1.5 hover:bg-slate-100 rounded-lg transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </li>
                    @empty
                    <li class="p-8 text-center text-gray-500 text-sm font-semibold">No clients under management.</li>
                    @endforelse
                </ul>
            </div>
            
        </div>
    </div>
</div>
@endsection
