@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Dashboard
    </h2>
    <!-- Quick Actions (Top Right) -->
    <div class="flex space-x-2">
        <a href="{{ route('clients.create') }}" class="flex items-center space-x-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-2 rounded shadow transition">
            <svg style="width: 16px; height: 16px;" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span>Client</span>
        </a>
        <a href="{{ route('tasks.create') }}" class="flex items-center space-x-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-2 rounded shadow transition">
            <svg style="width: 16px; height: 16px;" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span>Task</span>
        </a>
        <a href="{{ route('invoices.create') }}" class="flex items-center space-x-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-2 rounded shadow transition">
            <svg style="width: 16px; height: 16px;" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span>Invoice</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="{ activeTab: 'overview' }" class="w-full">
    <!-- DEVOTIONAL HEADER -->
    <div class="mb-6">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 justify-items-center">
            <!-- 1. Jay Minalbhavni Maa -->
            <div class="flex flex-col items-center group cursor-pointer transition-transform hover:scale-105">
                <div class="h-20 w-20 rounded-full bg-gradient-to-br from-red-100 to-orange-100 border-2 border-orange-200 flex items-center justify-center shadow-sm overflow-hidden mb-2">
                    <!-- Placeholder Image / Icon -->
                    <span class="text-2xl">🕉️</span>
                    <!-- <img src="path/to/image.jpg" alt="Minalbhavni Maa" class="h-full w-full object-cover"> -->
                </div>
                <!-- <p class="text-xs font-bold text-gray-700 text-center uppercase tracking-wide"></p> -->
            </div>

            <!-- 2. Jay Tuljabhavni Maa -->
            <div class="flex flex-col items-center group cursor-pointer transition-transform hover:scale-105">
                <div class="h-20 w-20 rounded-full bg-gradient-to-br from-orange-100 to-amber-100 border-2 border-orange-200 flex items-center justify-center shadow-sm overflow-hidden mb-2">
                    <span class="text-2xl">🕉️</span>
                </div>
                <!-- <p class="text-xs font-bold text-gray-700 text-center uppercase tracking-wide"></p> -->
            </div>

            <!-- 3. Jay Suprura Dada -->
            <div class="flex flex-col items-center group cursor-pointer transition-transform hover:scale-105">
                <div class="h-20 w-20 rounded-full bg-gradient-to-br from-amber-100 to-yellow-100 border-2 border-amber-200 flex items-center justify-center shadow-sm overflow-hidden mb-2">
                    <span class="text-2xl">🙏</span>
                </div>
                <!-- <p class="text-xs font-bold text-gray-700 text-center uppercase tracking-wide"></p> -->
            </div>

            <!-- 4. Jay Saviraseth -->
            <div class="flex flex-col items-center group cursor-pointer transition-transform hover:scale-105">
                <div class="h-20 w-20 rounded-full bg-gradient-to-br from-yellow-100 to-lime-100 border-2 border-yellow-200 flex items-center justify-center shadow-sm overflow-hidden mb-2">
                    <span class="text-2xl">✨</span>
                </div>
                <!-- <p class="text-xs font-bold text-gray-700 text-center uppercase tracking-wide"></p> -->
            </div>

            <!-- 5. Jay Kuber -->
            <div class="flex flex-col items-center group cursor-pointer transition-transform hover:scale-105">
                <div class="h-20 w-20 rounded-full bg-gradient-to-br from-emerald-100 to-teal-100 border-2 border-emerald-200 flex items-center justify-center shadow-sm overflow-hidden mb-2">
                    <span class="text-2xl">💰</span>
                </div>
                <!-- <p class="text-xs font-bold text-gray-700 text-center uppercase tracking-wide"></p> -->
            </div>
        </div>
    </div>

    <!-- TABS NAVIGATION -->
    <div class="mb-6 flex space-x-4 border-b border-gray-200">
        <button @click="activeTab = 'overview'" :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'overview', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'overview' }" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
            Overview
        </button>
        <button @click="activeTab = 'clients'" :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'clients', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'clients' }" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
            Clients View
        </button>
        <button @click="activeTab = 'workload'" :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'workload', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'workload' }" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
            Workload & Tasks
        </button>
        <button @click="activeTab = 'financials'" :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'financials', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'financials' }" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
            Financials (Beta)
        </button>
    </div>

    <!-- 1. OVERVIEW TAB (GridStack) -->
    <div x-show="activeTab === 'overview'" x-transition.opacity class="w-full">
        <div class="grid-stack transition-opacity duration-500 ease-in-out" style="opacity: 0;">
            <!-- Total Clients -->
            <div class="grid-stack-item" gs-id="kpi_total_clients" gs-w="3" gs-h="2" gs-x="0" gs-y="0">
                <div class="grid-stack-item-content">
                    <a href="{{ route('clients.index') }}" draggable="false" class="h-full block relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-white shadow-lg group">
                        <div class="absolute top-0 right-0 -mr-8 -mt-8 h-32 w-32 rounded-full bg-white/10 opacity-50 blur-xl transition-all group-hover:bg-white/20"></div>
                        <div class="flex items-center justify-between relative z-10 h-full">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-blue-100">Total Clients</p>
                                <p class="mt-2 text-3xl font-bold">{{ $summary['total_clients'] }}</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- New Clients -->
            <div class="grid-stack-item" gs-id="kpi_new_clients" gs-w="3" gs-h="2" gs-x="3" gs-y="0">
                <div class="grid-stack-item-content">
                    <a href="{{ route('clients.index') }}" draggable="false" class="h-full block relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 p-6 text-white shadow-lg group">
                        <div class="absolute top-0 right-0 -mr-8 -mt-8 h-32 w-32 rounded-full bg-white/10 opacity-50 blur-xl transition-all group-hover:bg-white/20"></div>
                        <div class="flex items-center justify-between relative z-10 h-full">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-emerald-100">New (Month)</p>
                                <p class="mt-2 text-3xl font-bold">{{ $summary['new_clients_this_month'] }}</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Pending Tasks KPI -->
            <div class="grid-stack-item" gs-id="kpi_pending_tasks" gs-w="3" gs-h="2" gs-x="6" gs-y="0">
                <div class="grid-stack-item-content">
                    <a href="{{ route('tasks.index') }}" draggable="false" class="h-full block relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 p-6 text-white shadow-lg group">
                        <div class="absolute top-0 right-0 -mr-8 -mt-8 h-32 w-32 rounded-full bg-white/10 opacity-50 blur-xl transition-all group-hover:bg-white/20"></div>
                        <div class="flex items-center justify-between relative z-10 h-full">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-amber-100">My Tasks</p>
                                <p class="mt-2 text-3xl font-bold">{{ \App\Models\Task::where('assigned_to', auth()->id())->whereNotIn('status', ['Completed', 'Done', 'Closed'])->count() }}</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Service Dues KPI -->
            <div class="grid-stack-item" gs-id="kpi_service_dues" gs-w="3" gs-h="2" gs-x="9" gs-y="0">
                <div class="grid-stack-item-content">
                    <a href="{{ route('service-dues.index') }}" draggable="false" class="h-full block relative overflow-hidden rounded-2xl bg-gradient-to-br from-rose-500 to-rose-600 p-6 text-white shadow-lg group">
                        <div class="absolute top-0 right-0 -mr-8 -mt-8 h-32 w-32 rounded-full bg-white/10 opacity-50 blur-xl transition-all group-hover:bg-white/20"></div>
                        <div class="flex items-center justify-between relative z-10 h-full">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-rose-100">Due (Month)</p>
                                <p class="mt-2 text-3xl font-bold">{{ $summary['services_due_month'] }}</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Calendar (Full Width) -->
            <div class="grid-stack-item" gs-id="widget_calendar" gs-w="12" gs-h="6" gs-x="0" gs-y="2">
                <div class="grid-stack-item-content bg-white/80 backdrop-blur-md p-6 rounded-2xl shadow-sm border border-white/20 h-full flex flex-col transition-transform duration-300 hover:shadow-lg">
                    <div class="flex justify-between items-center mb-4 drag-handle cursor-move">
                        <h3 class="text-lg font-bold text-gray-800 flex items-center bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-indigo-500">
                            <svg class="h-5 w-5 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Schedule & Deadlines
                        </h3>
                    </div>
                    <div id="dashboardCalendar" class="flex-1" style="min-height: 0;"></div>
                </div>
            </div>

            <!-- Upcoming Breakdown (Combined) -->
            <div class="grid-stack-item" gs-id="widget_upcoming" gs-w="6" gs-h="4" gs-x="0" gs-y="8">
                <div class="grid-stack-item-content bg-white/80 backdrop-blur-md p-6 rounded-2xl shadow-sm border border-white/20 h-full flex flex-col overflow-y-auto transition-transform duration-300 hover:shadow-lg">
                    <div class="drag-handle cursor-move mb-4">
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider bg-clip-text text-transparent bg-gradient-to-r from-gray-700 to-gray-500">Upcoming Overview</h3>
                    </div>
                    <div class="space-y-4">
                        <!-- Next 7 Days (Clickable) -->
                        <a href="{{ route('reports.due-date', ['start_date' => \Carbon\Carbon::now()->format('Y-m-d'), 'end_date' => \Carbon\Carbon::now()->addDays(7)->format('Y-m-d')]) }}" class="group block cursor-pointer">
                            <div class="bg-red-50 hover:bg-red-100 active:scale-95 transition-all duration-150 p-4 rounded-xl border border-red-100 flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-red-200 text-red-700 flex items-center justify-center mr-3 font-bold shadow-sm">!</div>
                                    <span class="text-sm font-bold text-red-700 group-hover:text-red-900">Next 7 Days</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-2xl font-bold text-red-800 mr-2">{{ $upcomingCounts['7_days'] }}</span>
                                    <svg class="w-4 h-4 text-red-400 group-hover:text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <!-- 7-15 Days (Clickable) -->
                        <a href="{{ route('reports.due-date', ['start_date' => \Carbon\Carbon::now()->addDays(7)->format('Y-m-d'), 'end_date' => \Carbon\Carbon::now()->addDays(15)->format('Y-m-d')]) }}" class="group block cursor-pointer">
                            <div class="bg-orange-50 hover:bg-orange-100 active:scale-95 transition-all duration-150 p-4 rounded-xl border border-orange-100 flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-orange-200 text-orange-700 flex items-center justify-center mr-3 font-bold shadow-sm">7</div>
                                    <span class="text-sm font-bold text-orange-700 group-hover:text-orange-900">7-15 Days</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-2xl font-bold text-orange-800 mr-2">{{ $upcomingCounts['15_days'] - $upcomingCounts['7_days'] }}</span>
                                    <svg class="w-4 h-4 text-orange-400 group-hover:text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <!-- 15-30 Days (Clickable) -->
                        <a href="{{ route('reports.due-date', ['start_date' => \Carbon\Carbon::now()->addDays(15)->format('Y-m-d'), 'end_date' => \Carbon\Carbon::now()->addDays(30)->format('Y-m-d')]) }}" class="group block cursor-pointer">
                            <div class="bg-yellow-50 hover:bg-yellow-100 active:scale-95 transition-all duration-150 p-4 rounded-xl border border-yellow-100 flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-yellow-200 text-yellow-700 flex items-center justify-center mr-3 font-bold shadow-sm">30</div>
                                    <span class="text-sm font-bold text-yellow-700 group-hover:text-yellow-900">15-30 Days</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-2xl font-bold text-yellow-800 mr-2">{{ $upcomingCounts['30_days'] - $upcomingCounts['15_days'] }}</span>
                                    <svg class="w-4 h-4 text-yellow-400 group-hover:text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Pending Tasks List -->
            <div class="grid-stack-item" gs-id="widget_pending_list" gs-w="6" gs-h="4" gs-x="6" gs-y="7">
                <div class="grid-stack-item-content bg-white/80 backdrop-blur-md p-4 rounded-2xl shadow-sm border border-white/20 h-full flex flex-col transition-transform duration-300 hover:shadow-lg">
                    <div class="flex justify-between items-center mb-2 border-b border-gray-100 pb-2 drag-handle cursor-move">
                        <h3 class="text-sm font-bold text-gray-700 uppercase bg-clip-text text-transparent bg-gradient-to-r from-gray-700 to-gray-500">Pending Tasks</h3>
                        <a href="{{ route('tasks.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline">View All</a>
                    </div>
                    <ul class="space-y-2 overflow-y-auto flex-1 px-1">
                        @forelse($myPendingTasks as $task)
                        <li class="border-b border-gray-50 last:border-0 pb-2 last:pb-0 hover:bg-white/50 p-2 rounded-lg transition-colors group/item">
                            <div class="flex flex-col">
                                <!-- Client Name (Bold, Top) with WhatsApp -->
                                <div class="flex justify-between items-center">
                                    <div class="font-bold text-xs text-gray-800 truncate" title="{{ $task->client->name ?? 'Internal Task' }}">
                                        {{ $task->client->name ?? 'Internal Task' }}
                                    </div>
                                    @if(isset($task->client->primary_contact_phone) && $task->client->primary_contact_phone)
                                    @php
                                    $phone = preg_replace('/[^0-9]/', '', $task->client->primary_contact_phone);
                                    // Add country code if missing (assuming India +91 for now if 10 digits)
                                    if(strlen($phone) == 10) $phone = '91' . $phone;

                                    $msg = "Hi " . ($task->client->name ?? 'Client') . ", a gentle reminder regarding '" . $task->title . "' which is due on " . $task->due_date->format('d M') . ". Please share the details.";
                                    $waLink = "https://wa.me/" . $phone . "?text=" . urlencode($msg);
                                    @endphp
                                    <a href="{{ $waLink }}" target="_blank" class="text-green-500 hover:text-green-600 hover:bg-green-50 p-1 rounded-full transition-colors" title="Send WhatsApp Reminder">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M12.031 2C6.502 2 2 6.516 2 12.067c0 1.83.487 3.633 1.414 5.23L2.007 22l4.897-1.28c1.55.845 3.302 1.29 5.127 1.29h.005c5.53 0 10.031-4.515 10.031-10.067C22.063 6.52 17.561 2 12.031 2zM12.054 20.25c-1.636 0-3.236-.437-4.636-1.27l-.333-.197-3.46 .906.924-3.373-.217-.345a8.271 8.271 0 01-1.238-4.382c0-4.568 3.715-8.283 8.286-8.283 4.57 0 8.283 3.715 8.283 8.283 0 4.568-3.693 8.283-8.264 8.283zm4.538-6.195c-.248-.124-1.47-.726-1.697-.808-.228-.083-.395-.125-.56.124-.166.248-.642.808-.787.973-.146.166-.29.187-.538.062-.248-.124-1.047-.386-1.996-1.232-.733-.654-1.228-1.462-1.372-1.71-.143-.248-.016-.382.108-.506.113-.112.248-.29.373-.435.124-.145.166-.248.248-.413.083-.166.042-.31-.02-.435-.062-.124-.56-1.348-.767-1.846-.201-.486-.406-.42-.56-.428-.145-.008-.31-.008-.475-.008-.166 0-.435.062-.662.31-.228.248-.87 0.85-0.87 2.072 0 1.223.89 2.404 1.014 2.57.124.165 1.752 2.677 4.246 3.753 1.493.645 2.06.7 2.82.684.85-.018 1.62-.65 1.848-1.277.228-.627.228-1.164.16-1.277-.068-.112-.248-.176-.496-.3z" />
                                        </svg>
                                    </a>
                                    @endif
                                </div>
                                <div class="flex justify-between items-center mt-1">
                                    <!-- Task Title (Normal, Detail) -->
                                    <a href="{{ route('tasks.edit', $task) }}" class="text-sm text-gray-600 hover:text-indigo-600 truncate block flex-1 mr-2 font-medium">
                                        {{ $task->title }}
                                    </a>
                                    <!-- Due Date Pill -->
                                    <span class="text-xs px-2 py-0.5 rounded-full font-bold {{ $task->due_date->isPast() ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600' }} whitespace-nowrap shadow-sm">
                                        {{ $task->due_date->format('d M') }}
                                    </span>
                                </div>
                            </div>
                        </li>
                        @empty
                        <li class="text-sm text-gray-400 italic text-center py-4">No pending tasks.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. CLIENTS VIEW -->
    <div x-show="activeTab === 'clients'" x-transition.opacity class="w-full space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Client Cards Repeater -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Clients</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $summary['total_clients'] }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-emerald-100 text-emerald-600 mr-4">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">New Clients (Month)</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $summary['new_clients_this_month'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- High Risk Clients -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-50 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">High Priority / High Risk Clients</h3>
                    <span class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded-md font-semibold">Action Required</span>
                </div>
                <div class="p-6">
                    @if($highRiskClients->count() > 0)
                    <ul class="divide-y divide-gray-100">
                        @foreach($highRiskClients as $client)
                        <li class="py-3 flex justify-between items-center">
                            <span class="font-medium text-gray-800">{{ $client->name }}</span>
                            <a href="{{ route('clients.show', $client) }}" class="text-sm text-indigo-600 hover:underline">View</a>
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <p class="text-gray-500 text-sm">No high risk clients identified.</p>
                    @endif
                </div>
            </div>

            <!-- Recent Clients -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-50 bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-800">Recently Updated Clients</h3>
                </div>
                <div class="p-6">
                    <ul class="divide-y divide-gray-100">
                        @foreach($recentClients as $client)
                        <li class="py-3 flex justify-between items-center">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 mr-3 font-bold text-xs">
                                    {{ substr($client->name, 0, 2) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">{{ $client->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $client->updated_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <a href="{{ route('clients.edit', $client) }}" class="text-xs border border-gray-200 px-2 py-1 rounded text-gray-600 hover:bg-gray-50">Edit</a>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. WORKLOAD VIEW -->
    <div x-show="activeTab === 'workload'" x-transition.opacity class="w-full space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <h4 class="text-gray-500 text-sm font-medium uppercase">Pending Tasks</h4>
                <p class="text-4xl font-bold text-gray-900 mt-2">{{ \App\Models\Task::where('assigned_to', auth()->id())->whereNotIn('status', ['Completed', 'Done', 'Closed'])->count() }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <h4 class="text-gray-500 text-sm font-medium uppercase">Services Due Now</h4>
                <p class="text-4xl font-bold text-rose-600 mt-2">{{ $summary['services_due_month'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <h4 class="text-gray-500 text-sm font-medium uppercase">Completion Rate</h4>
                <div class="flex items-end mt-2">
                    <p class="text-4xl font-bold text-emerald-600">
                        @php
                        $total = $complianceStats['Pending'] + $complianceStats['Completed'] + $complianceStats['Overdue'];
                        $rate = $total > 0 ? round(($complianceStats['Completed'] / $total) * 100) : 0;
                        @endphp
                        {{ $rate }}%
                    </p>
                    <p class="text-gray-400 text-sm ml-2 mb-1">this month</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Pending Tasks List (Detailed) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-50">
                    <h3 class="text-lg font-bold text-gray-800">My Task Queue</h3>
                </div>
                <ul class="divide-y divide-gray-100">
                    @forelse($myPendingTasks as $task)
                    <li class="p-4 hover:bg-gray-50 transition-colors">
                        <div class="flex justify-between items-start">
                            <div class="flex-1 mr-2">
                                <div class="flex justify-between items-center mb-1">
                                    <p class="font-bold text-gray-800">{{ $task->title }}</p>
                                    @if(isset($task->client->primary_contact_phone) && $task->client->primary_contact_phone)
                                    @php
                                    $phone = preg_replace('/[^0-9]/', '', $task->client->primary_contact_phone);
                                    if(strlen($phone) == 10) $phone = '91' . $phone;
                                    $msg = "Hi " . ($task->client->name ?? 'Client') . ", a gentle reminder regarding '" . $task->title . "' which is due on " . $task->due_date->format('d M') . ". Please share the details.";
                                    $waLink = "https://wa.me/" . $phone . "?text=" . urlencode($msg);
                                    @endphp
                                    <a href="{{ $waLink }}" target="_blank" class="text-green-500 hover:text-green-600 hover:bg-green-50 p-1 rounded-full transition-colors" title="Send WhatsApp Reminder">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M12.031 2C6.502 2 2 6.516 2 12.067c0 1.83.487 3.633 1.414 5.23L2.007 22l4.897-1.28c1.55.845 3.302 1.29 5.127 1.29h.005c5.53 0 10.031-4.515 10.031-10.067C22.063 6.52 17.561 2 12.031 2zM12.054 20.25c-1.636 0-3.236-.437-4.636-1.27l-.333-.197-3.46 .906.924-3.373-.217-.345a8.271 8.271 0 01-1.238-4.382c0-4.568 3.715-8.283 8.286-8.283 4.57 0 8.283 3.715 8.283 8.283 0 4.568-3.693 8.283-8.264 8.283zm4.538-6.195c-.248-.124-1.47-.726-1.697-.808-.228-.083-.395-.125-.56.124-.166.248-.642.808-.787.973-.146.166-.29.187-.538.062-.248-.124-1.047-.386-1.996-1.232-.733-.654-1.228-1.462-1.372-1.71-.143-.248-.016-.382.108-.506.113-.112.248-.29.373-.435.124-.145.166-.248.248-.413.083-.166.042-.31-.02-.435-.062-.124-.56-1.348-.767-1.846-.201-.486-.406-.42-.56-.428-.145-.008-.31-.008-.475-.008-.166 0-.435.062-.662.31-.228.248-.87 0.85-0.87 2.072 0 1.223.89 2.404 1.014 2.57.124.165 1.752 2.677 4.246 3.753 1.493.645 2.06.7 2.82.684.85-.018 1.62-.65 1.848-1.277.228-.627.228-1.164.16-1.277-.068-.112-.248-.176-.496-.3z" />
                                        </svg>
                                    </a>
                                    @endif
                                </div>
                                <p class="text-xs text-indigo-600 font-medium">{{ $task->client->name ?? 'Internal' }}</p>
                            </div>
                            <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full whitespace-nowrap">
                                {{ $task->due_date->format('M d') }}
                            </span>
                        </div>
                    </li>
                    @empty
                    <li class="p-6 text-center text-gray-500">No pending tasks.</li>
                    @endforelse
                </ul>
            </div>

            <!-- Service Dues Breakdown (Also Clickable) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Upcoming Deadlines Breakdown</h3>
                <div class="space-y-4">
                    <a href="{{ route('reports.due-date', ['start_date' => \Carbon\Carbon::now()->format('Y-m-d'), 'end_date' => \Carbon\Carbon::now()->addDays(7)->format('Y-m-d')]) }}" class="flex items-center justify-between p-4 bg-red-50 rounded-xl border border-red-100 hover:bg-red-100 transition-colors">
                        <span class="text-red-800 font-medium">Next 7 Days</span>
                        <span class="text-2xl font-bold text-red-700">{{ $upcomingCounts['7_days'] }}</span>
                    </a>

                    <a href="{{ route('reports.due-date', ['start_date' => \Carbon\Carbon::now()->addDays(7)->format('Y-m-d'), 'end_date' => \Carbon\Carbon::now()->addDays(15)->format('Y-m-d')]) }}" class="flex items-center justify-between p-4 bg-orange-50 rounded-xl border border-orange-100 hover:bg-orange-100 transition-colors">
                        <span class="text-orange-800 font-medium">7 - 15 Days</span>
                        <span class="text-2xl font-bold text-orange-700">{{ $upcomingCounts['15_days'] - $upcomingCounts['7_days'] }}</span>
                    </a>

                    <a href="{{ route('reports.due-date', ['start_date' => \Carbon\Carbon::now()->addDays(15)->format('Y-m-d'), 'end_date' => \Carbon\Carbon::now()->addDays(30)->format('Y-m-d')]) }}" class="flex items-center justify-between p-4 bg-yellow-50 rounded-xl border border-yellow-100 hover:bg-yellow-100 transition-colors">
                        <span class="text-yellow-800 font-medium">15 - 30 Days</span>
                        <span class="text-2xl font-bold text-yellow-700">{{ $upcomingCounts['30_days'] - $upcomingCounts['15_days'] }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. FINANCIALS VIEW (Placeholders) -->
    <div x-show="activeTab === 'financials'" x-transition.opacity class="w-full">
        <div class="bg-gradient-to-r from-gray-800 to-gray-900 rounded-2xl p-8 text-center text-white shadow-xl">
            <h3 class="text-2xl font-bold mb-2">Financial Insights</h3>
            <p class="text-gray-400">Coming soon. Track invoices, payments, and outstanding dues directly from your dashboard.</p>
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                <div class="bg-white/10 backdrop-blur rounded-xl p-6 border border-white/10">
                    <p class="text-gray-300 text-sm">Outstanding Fees</p>
                    <p class="text-2xl font-bold mt-2">₹ 0</p>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-xl p-6 border border-white/10">
                    <p class="text-gray-300 text-sm">Collections (Month)</p>
                    <p class="text-2xl font-bold mt-2">₹ 0</p>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-xl p-6 border border-white/10">
                    <p class="text-gray-300 text-sm">Overdue Invoices</p>
                    <p class="text-2xl font-bold mt-2">0</p>
                </div>
            </div>
        </div>
    </div>

    @include('partials.welcome-modal')
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<!-- GridStack JS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/gridstack.js/10.0.1/gridstack.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/gridstack.js/10.0.1/gridstack-all.js"></script>

<style>
    .grid-stack-item-content {
        background: transparent;
    }

    .grid-stack-item-content>.bg-white {
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize GridStack
        var grid = GridStack.init({
            cellHeight: 100,
            margin: 10,
            handleClass: 'drag-handle', // Restrict dragging to the header handle
            alwaysShowResizeHandle: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
            disableOneColumnMode: true,
            float: true,
            acceptWidgets: true,
            dragIn: '.grid-stack-item', // Allow internal dragging
            animate: false // Disable animation to prevent "flying" widgets on load
        });

        // Load saved layout
        let savedLayout = localStorage.getItem('dashboard_layout');
        if (savedLayout) {
            grid.load(JSON.parse(savedLayout));
        }

        // Show grid after a safety delay to ensure layout is settled
        setTimeout(() => {
            document.querySelector('.grid-stack').style.opacity = '1';
        }, 300);

        // Save layout on change
        grid.on('change', function(event, items) {
            let serializedData = grid.save();
            localStorage.setItem('dashboard_layout', JSON.stringify(serializedData));
        });

        // Handle Resize - Force Calendar/Charts to redraw
        grid.on('resizestop', function(event, el) {
            setTimeout(() => {
                if (window.calendar) {
                    window.calendar.updateSize();
                }
                window.dispatchEvent(new Event('resize'));
            }, 50);
        });

        // 1. FullCalendar Initialization
        var calendarEl = document.getElementById('dashboardCalendar');
        window.calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: '100%', // Adapt to container
            handleWindowResize: true, // Auto-resize on window resize
            eventDisplay: 'block',
            // --- UPDATED CONFIGURATION FOR CLEANER LOOK ---
            dayMaxEvents: 2, // Collapses events into "+X more" popover
            editable: true, // Keep Drag & Drop
            droppable: true,

            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listWeek'
            },
            events: @json($calendarEvents),

            // Use Standard Colors if possible, but we already set BG colors in controller. 
            // We just ensure text is white here.
            eventTextColor: '#ffffff',

            // Custom Event Rendering
            eventContent: function(arg) {
                let clientName = arg.event.extendedProps.client_name || '';
                let details = arg.event.extendedProps.details || arg.event.title;

                let contentEl = document.createElement('div');
                // Added 'truncate' and fixed width handling for clean look
                contentEl.className = 'px-1.5 py-1 text-xs leading-tight overflow-hidden w-full';
                contentEl.setAttribute('title', clientName + ': ' + details); // Tooltip

                // Force single line or limited lines to avoid "dragging name" look
                contentEl.innerHTML = `
                    <div class="font-bold truncate">${clientName}</div>
                    <div class="truncate opacity-90 text-[10px]">${details}</div>
                `;

                return {
                    domNodes: [contentEl]
                };
            },

            // Handle Event Drop (Date Change)
            eventDrop: function(info) {
                var newDate = info.event.startStr;
                var eventId = info.event.id;
                var type = info.event.extendedProps.type;
                var dbId = info.event.extendedProps.db_id;

                if (!confirm("Reschedule " + type + " to " + newDate + "?")) {
                    info.revert();
                    return;
                }

                // AJAX Call to update date (Logic to be implemented in Backend)
                fetch('/calendar/update-date', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            type: type,
                            id: dbId,
                            new_date: newDate
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Success toast or quiet
                            console.log('Date updated');
                        } else {
                            alert('Failed to update date');
                            info.revert();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        info.revert();
                    });
            },

            // Handle Event Click (View Details)
            eventClick: function(info) {
                // Dispatch Alpine event
                window.dispatchEvent(new CustomEvent('open-calendar-modal', {
                    detail: {
                        id: info.event.id,
                        title: info.event.extendedProps.details || info.event.title,
                        type: info.event.extendedProps.type,
                        db_id: info.event.extendedProps.db_id,
                        client_name: info.event.extendedProps.client_name,
                        start: info.event.startStr
                    }
                }));
            },

            // Handle Date Click (Create Task)
            dateClick: function(info) {
                // Optional: Open quick create modal in future
            }
        });
        window.calendar.render();

    });
</script>

@include('partials.calendar-event-modal')

<style>
    /* Beautiful Calendar Styling */
    .fc {
        font-family: 'Inter', sans-serif;
    }

    /* Ensure widgets handle resize gracefully */
    .grid-stack-item-content {
        height: 100% !important;
        overflow: hidden;
        /* Prevent spillover */
    }

    .fc .fc-toolbar-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1f2937;
    }

    /* Responsive font for smaller widgets */
    @media (max-width: 768px) {
        .fc .fc-toolbar-title {
            font-size: 1rem;
        }
    }

    .fc .fc-button-primary {
        background-color: white;
        border: 1px solid #e5e7eb;
        color: #374151;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }

    .fc .fc-button-primary:hover {
        background-color: #f9fafb;
        border-color: #d1d5db;
        color: #111827;
    }

    .fc .fc-button-primary:not(:disabled).fc-button-active,
    .fc .fc-button-primary:not(:disabled):active {
        background-color: #4f46e5;
        border-color: transparent;
        color: white;
    }

    /* Event Styling Override for consistency */
    .fc-event {
        border: none !important;
        border-radius: 4px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .fc-theme-standard td,
    .fc-theme-standard th {
        border-color: #f3f4f6;
    }

    .fc-col-header-cell-cushion {
        padding: 8px;
        color: #6b7280;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .fc-daygrid-day-number {
        color: #4b5563;
        font-weight: 500;
        padding: 8px;
    }

    .fc-day-today {
        background-color: #fcfaff !important;
    }

    /* Popover styling for +X more */
    .fc-popover {
        border-radius: 8px !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        border: 1px solid #f3f4f6 !important;
        z-index: 50 !important;
    }
</style>
@endsection