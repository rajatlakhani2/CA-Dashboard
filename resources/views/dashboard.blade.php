@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <div>
        <h2 class="font-bold text-lg text-gray-900 tracking-wide">Command Centre</h2>
        <p class="text-xs text-gray-500 mt-0.5">{{ now()->format('l, d F Y') }}</p>
    </div>
    <div class="flex space-x-2">
        @can('create', App\Models\Client::class)
        <a href="{{ route('clients.create') }}" class="flex items-center space-x-1 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 text-xs px-3 py-2 rounded-lg shadow-sm transition">
            <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Client</span>
        </a>
        @endcan
        <a href="{{ route('tasks.create') }}" class="flex items-center space-x-1 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 text-xs px-3 py-2 rounded-lg shadow-sm transition">
            <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Task</span>
        </a>
        @can('create', App\Models\Invoice::class)
        <a href="{{ route('invoices.create') }}" class="flex items-center space-x-1 bg-indigo-600 hover:bg-indigo-700 border border-transparent text-white text-xs px-3 py-2 rounded-lg shadow-sm transition">
            <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Invoice</span>
        </a>
        @endcan
    </div>
</div>
@endsection

@push('head_styles')
<style>
    /* ===== LIGHT THEME DASHBOARD OVERRIDE ===== */
    body { background: #f8fafc !important; }
    .min-h-full.bg-bg-body { background: transparent !important; }
    main.flex-1 { background: transparent !important; }

    #glass-bg { display: none; }

    header.bg-white\/80 {
        background: rgba(255,255,255,0.95) !important;
        border-bottom: 1px solid rgba(0,0,0,0.05) !important;
        backdrop-filter: blur(10px) !important;
    }
    header h2 { color: #111827 !important; background: none !important; -webkit-text-fill-color: #111827 !important; }

    .glass-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .glass-card:hover { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border-color: #d1d5db; }

    .kpi-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 1.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: block;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border-color: #d1d5db; }
    .kpi-card .kpi-label { color: #6b7280; font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; }
    .kpi-card .kpi-value { color: #111827; font-size:2.5rem; font-weight:800; line-height:1; margin: 0.5rem 0 0.25rem; }
    .kpi-card .kpi-sub { color: #9ca3af; font-size:0.75rem; }

    .kpi-blue { border-top: 3px solid #3b82f6 !important; }
    .kpi-violet { border-top: 3px solid #8b5cf6 !important; }
    .kpi-amber { border-top: 3px solid #f59e0b !important; }
    .kpi-rose { border-top: 3px solid #ef4444 !important; }
    .kpi-emerald { border-top: 3px solid #10b981 !important; }

    .glass-tabs { border-bottom: 1px solid #e5e7eb; }
    .glass-tab { color: #6b7280; border-bottom: 2px solid transparent; padding: 0.75rem 0.5rem; font-size:0.875rem; font-weight:600; cursor:pointer; transition: all 0.2s; white-space:nowrap; }
    .glass-tab.active { color: #4f46e5; border-bottom-color: #4f46e5; }
    .glass-tab:hover:not(.active) { color: #374151; border-bottom-color: #d1d5db; }

    .glass-section-title { color: #4b5563; font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:1rem; }
    .glass-list-item { padding: 0.75rem 0; border-bottom: 1px solid #f3f4f6; }
    .glass-list-item:last-child { border-bottom: none; }

    #dashboardCalendar .fc { color: #374151; }
    .fc-theme-standard td, .fc-theme-standard th { border-color: #e5e7eb !important; }
    .fc .fc-toolbar-title { color: #111827 !important; font-size: 1.1rem; font-weight: 700; }
    .fc .fc-col-header-cell-cushion { color: #4b5563 !important; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; }
    .fc .fc-daygrid-day-number { color: #6b7280 !important; font-size:0.8rem; }
    .fc-day-today .fc-daygrid-day-number { color: #4f46e5 !important; font-weight:800; }
    .fc-day-today { background: #eef2ff !important; }
    .fc .fc-button-primary { background: #ffffff !important; border: 1px solid #d1d5db !important; color: #374151 !important; border-radius:8px !important; font-size:0.75rem !important; padding: 0.35rem 0.75rem !important; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .fc .fc-button-primary:hover { background: #f9fafb !important; color: #111827 !important; }
    .fc .fc-button-primary:not(:disabled).fc-button-active { background: #eef2ff !important; border-color: #c7d2fe !important; color: #4f46e5 !important; }
    .fc-event { border-radius: 6px !important; border: none !important; font-size:0.7rem !important; margin-top: 2px !important; }
    .fc-popover { background: #ffffff !important; border: 1px solid #e5e7eb !important; border-radius:12px !important; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    .fc-popover-header { background: #f9fafb !important; color: #111827 !important; border-radius:12px 12px 0 0 !important; border-bottom: 1px solid #e5e7eb; }
    .fc-popover-body { background: transparent !important; }

    .deadline-pill { border-radius:12px; padding:1rem 1.25rem; display:flex; justify-content:space-between; align-items:center; transition:all 0.2s; text-decoration:none; background: #ffffff; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .deadline-pill:hover { transform:translateX(4px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }

    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius:10px; }
    ::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
</style>
@endpush

@section('content')
@php
    $canManageFirm = auth()->user()?->managesFirmModules();
    $deadline7Url = $canManageFirm
        ? route('reports.due-date', ['start_date' => now()->format('Y-m-d'), 'end_date' => now()->addDays(7)->format('Y-m-d')])
        : route('service-dues.index');
    $deadline15Url = $canManageFirm
        ? route('reports.due-date', ['start_date' => now()->addDays(7)->format('Y-m-d'), 'end_date' => now()->addDays(15)->format('Y-m-d')])
        : route('service-dues.index');
    $deadline30Url = $canManageFirm
        ? route('reports.due-date', ['start_date' => now()->addDays(15)->format('Y-m-d'), 'end_date' => now()->addDays(30)->format('Y-m-d')])
        : route('service-dues.index');
@endphp
{{-- Gradient background layer --}}
<div id="glass-bg"></div>

<div x-data="{ activeTab: 'overview' }" class="w-full space-y-6">

    @if(($pendingClientApprovals ?? 0) > 0)
    <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 flex flex-wrap items-center justify-between gap-3 shadow-sm">
        <div>
            <p class="text-sm font-bold text-amber-900">{{ $pendingClientApprovals }} client{{ $pendingClientApprovals === 1 ? '' : 's' }} awaiting approval</p>
            <p class="text-xs text-amber-800">Article submissions need your review on the Clients page.</p>
        </div>
        <a href="{{ route('clients.index') }}" class="inline-flex items-center rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500">
            Review now
        </a>
    </div>
    @endif

    {{-- ===== WELCOME HERO ===== --}}
    <div class="glass-card p-6 flex items-center justify-between">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <div class="flex gap-2">
                    <span class="text-2xl">🕉️</span>
                    <span class="text-2xl">🙏</span>
                    <span class="text-2xl">✨</span>
                    <span class="text-2xl">💰</span>
                </div>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">
                Welcome back, <span class="text-violet-300">{{ auth()->user()->name }}</span>
            </h1>
            <p class="text-gray-500 text-sm mt-1 italic">"{{ $positiveThought }}"</p>
        </div>
        <div class="hidden md:flex flex-col items-end text-right">
            <div class="text-gray-400 text-xs uppercase tracking-widest">Today</div>
            <div class="text-gray-900 text-xl font-bold">{{ now()->format('d M Y') }}</div>
            <div class="text-violet-300/70 text-sm">{{ now()->format('l') }}</div>
        </div>
    </div>

    {{-- ===== KPI TILES ===== --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <a href="{{ route('clients.index') }}" class="kpi-card kpi-blue">
            <div class="flex items-center justify-between mb-3">
                <p class="kpi-label">Total Clients</p>
                <div class="h-9 w-9 rounded-xl bg-blue-500/20 flex items-center justify-center">
                    <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
            <p class="kpi-value">{{ $summary['total_clients'] }}</p>
            <p class="kpi-sub">+{{ $summary['new_clients_this_month'] }} this month</p>
        </a>

        <a href="{{ route('tasks.index') }}" class="kpi-card kpi-amber">
            <div class="flex items-center justify-between mb-3">
                <p class="kpi-label">My Tasks</p>
                <div class="h-9 w-9 rounded-xl bg-amber-500/20 flex items-center justify-center">
                    <svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
            </div>
            <p class="kpi-value">{{ \App\Models\Task::where('assigned_to', auth()->id())->whereNotIn('status', ['Completed','Done','Closed'])->count() }}</p>
            <p class="kpi-sub">Pending action</p>
        </a>

        <a href="{{ route('service-dues.index') }}" class="kpi-card kpi-rose">
            <div class="flex items-center justify-between mb-3">
                <p class="kpi-label">Due This Month</p>
                <div class="h-9 w-9 rounded-xl bg-rose-500/20 flex items-center justify-center">
                    <svg class="h-5 w-5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="kpi-value">{{ $summary['services_due_month'] }}</p>
            <p class="kpi-sub">Service deadlines</p>
        </a>

        @if(auth()->user()?->hasRole('partner', 'manager'))
        <a href="{{ route('invoices.index') }}" class="kpi-card kpi-emerald">
            <div class="flex items-center justify-between mb-3">
                <p class="kpi-label">Outstanding</p>
                <div class="h-9 w-9 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                    <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="kpi-value text-2xl">{{ $summary['outstanding_fees'] }}</p>
            <p class="kpi-sub">Fees receivable</p>
        </a>

        <a href="{{ route('billing.index') }}" class="kpi-card kpi-violet">
            <div class="flex items-center justify-between mb-3">
                <p class="kpi-label">Unbilled Work</p>
                <div class="h-9 w-9 rounded-xl bg-violet-500/20 flex items-center justify-center">
                    <svg class="h-5 w-5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <p class="kpi-value">{{ $summary['unbilled_items'] }}</p>
            <p class="kpi-sub">Ready to invoice</p>
        </a>
        @endif
    </div>

    {{-- ===== TAB NAVIGATION ===== --}}
    <div class="glass-tabs flex space-x-6">
        <button @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'active' : ''" class="glass-tab">
            📊 Overview
        </button>
        <button @click="activeTab = 'calendar'; setTimeout(() => { window.dispatchEvent(new Event('resize')); if(window.calendar) { window.calendar.updateSize(); window.calendar.render(); } }, 350)" :class="activeTab === 'calendar' ? 'active' : ''" class="glass-tab">
            📅 Schedule
        </button>
        <button @click="activeTab = 'workload'" :class="activeTab === 'workload' ? 'active' : ''" class="glass-tab">
            ⚡ Workload
        </button>
        @if($canManageFirm)
        <button @click="activeTab = 'financials'" :class="activeTab === 'financials' ? 'active' : ''" class="glass-tab">
            💰 Financials
        </button>
        @endif
    </div>

    {{-- ===== OVERVIEW TAB ===== --}}
    <div x-show="activeTab === 'overview'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Pending Tasks --}}
            <div class="lg:col-span-2 glass-card p-6 h-full">
                <div class="flex justify-between items-center mb-4">
                    <p class="glass-section-title mb-0">🎯 Today's Priority Queue</p>
                    <a href="{{ route('tasks.index') }}" class="text-violet-400 text-xs font-semibold hover:text-violet-300 transition">View All →</a>
                </div>
                <ul class="space-y-1">
                    @forelse($myPendingTasks->take(8) as $task)
                    <li class="glass-list-item">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div class="h-2 w-2 rounded-full flex-shrink-0 {{ $task->due_date->isPast() ? 'bg-rose-400' : 'bg-violet-400' }}"></div>
                                <div class="min-w-0">
                                    <div class="text-gray-900 text-sm font-semibold truncate">{{ $task->client?->name ?? 'Internal Task' }}</div>
                                    <a href="{{ route('tasks.edit', $task) }}" class="text-gray-500 text-xs hover:text-violet-300 truncate block transition">{{ $task->title }}</a>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 ml-3 flex-shrink-0">
                                @if(isset($task->client->primary_contact_phone) && $task->client->primary_contact_phone)
                                @php
                                $phone = preg_replace('/[^0-9]/', '', $task->client->primary_contact_phone);
                                if(strlen($phone) == 10) $phone = '91' . $phone;
                                $msg = "Hi " . ($task->client?->name ?? 'Client') . ", a gentle reminder regarding '" . $task->title . "' which is due on " . $task->due_date->format('d M') . ".";
                                $waLink = "https://wa.me/" . $phone . "?text=" . urlencode($msg);
                                @endphp
                                <a href="{{ $waLink }}" target="_blank" class="text-green-400/70 hover:text-green-400 transition">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 2C6.502 2 2 6.516 2 12.067c0 1.83.487 3.633 1.414 5.23L2.007 22l4.897-1.28c1.55.845 3.302 1.29 5.127 1.29h.005c5.53 0 10.031-4.515 10.031-10.067C22.063 6.52 17.561 2 12.031 2z"/></svg>
                                </a>
                                @endif
                                <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $task->due_date->isPast() ? 'bg-rose-500/20 text-rose-300' : 'bg-gray-50 text-gray-500' }}">
                                    {{ $task->due_date->format('d M') }}
                                </span>
                            </div>
                        </div>
                    </li>
                    @empty
                    <li class="py-8 text-center">
                        <div class="text-4xl mb-2">✅</div>
                        <p class="text-gray-400 text-sm">All caught up! No pending tasks.</p>
                    </li>
                    @endforelse
                </ul>
            </div>

            {{-- Upcoming Deadlines --}}
            @php
                $upcomingOverview = $alerts->take(4);
            @endphp
            <div class="glass-card p-6">
                <div class="flex justify-between items-center mb-4">
                    <p class="glass-section-title mb-0">Upcoming Overview</p>
                    @if($canManageFirm)
                    <a href="{{ route('reports.due-date') }}" class="text-violet-400 text-xs font-semibold hover:text-violet-300 transition">Open Report →</a>
                    @else
                    <a href="{{ route('service-dues.index') }}" class="text-violet-400 text-xs font-semibold hover:text-violet-300 transition">View Reminders →</a>
                    @endif
                </div>
                <div class="space-y-3 mb-6">
                    @forelse($upcomingOverview as $alert)
                    <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $alert->clientService?->client?->name ?? 'Internal' }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $alert->clientService?->service?->name ?? 'Service Due' }}</p>
                            </div>
                            <span class="text-xs font-semibold text-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($alert->due_date)->format('Y-m-d') }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="rounded-xl border border-dashed border-gray-200 px-4 py-6 text-center text-sm text-gray-400">
                        No urgent dues right now.
                    </div>
                    @endforelse
                </div>
                <p class="glass-section-title">⏰ Upcoming Deadlines</p>
                <div class="space-y-3">
                    <a href="{{ $deadline7Url }}"
                       class="deadline-pill bg-rose-500/10 border border-rose-500/20 hover:bg-rose-500/20">
                        <div>
                            <div class="text-rose-300 font-bold text-sm">Next 7 Days</div>
                            <div class="text-gray-400 text-xs mt-0.5">Critical window</div>
                        </div>
                        <div class="text-rose-300 text-3xl font-black">{{ $upcomingCounts['7_days'] }}</div>
                    </a>

                    <a href="{{ $deadline15Url }}"
                       class="deadline-pill bg-amber-500/10 border border-amber-500/20 hover:bg-amber-500/20">
                        <div>
                            <div class="text-amber-300 font-bold text-sm">7 – 15 Days</div>
                            <div class="text-gray-400 text-xs mt-0.5">Plan ahead</div>
                        </div>
                        <div class="text-amber-300 text-3xl font-black">{{ $upcomingCounts['15_days'] - $upcomingCounts['7_days'] }}</div>
                    </a>

                    <a href="{{ $deadline30Url }}"
                       class="deadline-pill bg-yellow-500/10 border border-yellow-500/20 hover:bg-yellow-500/20">
                        <div>
                            <div class="text-yellow-300 font-bold text-sm">15 – 30 Days</div>
                            <div class="text-gray-400 text-xs mt-0.5">On the horizon</div>
                        </div>
                        <div class="text-yellow-300 text-3xl font-black">{{ $upcomingCounts['30_days'] - $upcomingCounts['15_days'] }}</div>
                    </a>
                </div>

                {{-- High Risk Clients --}}
                @if($highRiskClients->count() > 0)
                <div class="mt-5">
                    <p class="glass-section-title">🔴 High Risk Clients</p>
                    <ul class="space-y-2">
                        @foreach($highRiskClients->take(4) as $client)
                        <li class="flex justify-between items-center">
                            <span class="text-gray-700 text-sm font-medium truncate">{{ $client->name }}</span>
                            <a href="{{ route('clients.show', $client) }}" class="text-violet-400 text-xs hover:text-violet-300 ml-2 flex-shrink-0">View →</a>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== SCHEDULE / CALENDAR TAB ===== --}}
    <div x-show="activeTab === 'calendar'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" style="display:none;">
        <div class="glass-card p-6" style="min-height: 600px;">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <p class="glass-section-title mb-0">📅 Schedule & Deadlines</p>
                    <p class="mt-1 text-xs text-gray-500">Drag tasks and pending dues to reschedule them. The calendar opens on the nearest live month when the current month is empty.</p>
                </div>
                <div class="flex gap-3 text-xs text-gray-500 flex-wrap justify-end">
                    <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-blue-400"></span> Tasks</span>
                    <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-violet-400"></span> Dues</span>
                    <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-emerald-400"></span> Done</span>
                    <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-rose-400"></span> Overdue</span>
                </div>
            </div>
            @include('dashboard.partials.calendar-filters')
            <div id="dashboardCalendar" style="min-height: 550px;"></div>
        </div>
    </div>

    {{-- ===== WORKLOAD TAB ===== --}}
    <div x-show="activeTab === 'workload'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" style="display:none;">
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="glass-card p-6">
                    <p class="glass-section-title">Pending Tasks</p>
                    <p class="text-4xl font-black text-gray-900">{{ \App\Models\Task::where('assigned_to', auth()->id())->whereNotIn('status',['Completed','Done','Closed'])->count() }}</p>
                    <p class="text-gray-500 text-xs mt-1">assigned to me</p>
                </div>
                <div class="glass-card p-6">
                    <p class="glass-section-title">Services Due Now</p>
                    <p class="text-4xl font-black text-rose-300">{{ $summary['services_due_month'] }}</p>
                    <p class="text-gray-500 text-xs mt-1">this month</p>
                </div>
                <div class="glass-card p-6">
                    <p class="glass-section-title">Completion Rate</p>
                    @php
                        $total = $complianceStats['Pending'] + $complianceStats['Completed'] + $complianceStats['Overdue'];
                        $rate = $total > 0 ? round(($complianceStats['Completed'] / $total) * 100) : 0;
                    @endphp
                    <p class="text-4xl font-black text-emerald-300">{{ $rate }}%</p>
                    <p class="text-gray-500 text-xs mt-1">this month</p>
                    <div class="mt-3 h-1.5 bg-gray-50 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 rounded-full transition-all duration-1000" style="width: {{ $rate }}%"></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Task Queue --}}
                <div class="glass-card p-6">
                    <p class="glass-section-title">My Task Queue</p>
                    <ul>
                        @forelse($myPendingTasks as $task)
                        <li class="glass-list-item">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-gray-900 font-semibold text-sm">{{ $task->title }}</p>
                                    <p class="text-violet-300/70 text-xs mt-0.5">{{ $task->client?->name ?? 'Internal' }}</p>
                                </div>
                                <span class="text-gray-400 text-xs">{{ $task->due_date->format('M d') }}</span>
                            </div>
                        </li>
                        @empty
                        <li class="py-6 text-center text-gray-400 text-sm">No pending tasks.</li>
                        @endforelse
                    </ul>
                </div>

                {{-- Deadline breakdown --}}
                <div class="glass-card p-6">
                    <p class="glass-section-title">Upcoming Deadlines Breakdown</p>
                    <div class="space-y-3">
                        <a href="{{ $deadline7Url }}" class="deadline-pill bg-rose-500/10 border border-rose-500/20 hover:bg-rose-500/20">
                            <span class="text-rose-300 font-semibold text-sm">Next 7 Days</span>
                            <span class="text-rose-300 text-2xl font-black">{{ $upcomingCounts['7_days'] }}</span>
                        </a>
                        <a href="{{ $deadline15Url }}" class="deadline-pill bg-amber-500/10 border border-amber-500/20 hover:bg-amber-500/20">
                            <span class="text-amber-300 font-semibold text-sm">7 – 15 Days</span>
                            <span class="text-amber-300 text-2xl font-black">{{ $upcomingCounts['15_days'] - $upcomingCounts['7_days'] }}</span>
                        </a>
                        <a href="{{ $deadline30Url }}" class="deadline-pill bg-yellow-500/10 border border-yellow-500/20 hover:bg-yellow-500/20">
                            <span class="text-yellow-300 font-semibold text-sm">15 – 30 Days</span>
                            <span class="text-yellow-300 text-2xl font-black">{{ $upcomingCounts['30_days'] - $upcomingCounts['15_days'] }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($canManageFirm)
    {{-- ===== FINANCIALS TAB ===== --}}
    <div x-show="activeTab === 'financials'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" style="display:none;">
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="glass-card p-6 kpi-emerald">
                    <p class="glass-section-title">Outstanding Fees</p>
                    <p class="text-3xl font-black text-emerald-300">{{ $summary['outstanding_fees'] }}</p>
                    <p class="text-gray-400 text-xs mt-1">Across all open invoices</p>
                </div>
                <div class="glass-card p-6 kpi-violet">
                    <p class="glass-section-title">Overdue Collections</p>
                    <p class="text-3xl font-black text-violet-300">{{ $summary['overdue_collections'] }}</p>
                    <p class="text-gray-400 text-xs mt-1">Overdue invoices only</p>
                </div>
                <div class="glass-card p-6 kpi-blue">
                    <p class="glass-section-title">Collected This Month</p>
                    <p class="text-3xl font-black text-blue-300">{{ $summary['collections_this_month'] }}</p>
                    <p class="text-gray-400 text-xs mt-1">Since 1st {{ now()->format('M') }}</p>
                </div>
            </div>

            {{-- Recent Clients --}}
            <div class="glass-card p-6">
                <div class="flex justify-between items-center mb-4">
                    <p class="glass-section-title mb-0">Recently Updated Clients</p>
                    <a href="{{ route('clients.index') }}" class="text-violet-400 text-xs hover:text-violet-300">View All →</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($recentClients as $client)
                    <a href="{{ route('clients.edit', $client) }}" class="flex items-center gap-3 p-3 rounded-xl bg-white hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition">
                        <div class="h-9 w-9 rounded-xl bg-violet-500/20 flex items-center justify-center text-violet-300 font-bold text-sm flex-shrink-0">
                            {{ substr($client->name, 0, 2) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-gray-900 text-sm font-semibold truncate">{{ $client->name }}</p>
                            <p class="text-gray-400 text-xs">{{ $client->updated_at->diffForHumans() }}</p>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    @include('partials.welcome-modal')
</div>
@endsection

@section('scripts')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<script>
function calendarFilterBar() {
    var initial = @json($calendarFilters->toQueryArray());
    return {
        showTasks: initial.show_tasks !== '0',
        showDues: initial.show_dues !== '0',
        dueStatus: initial.due_status || 'active',
        serviceId: initial.service_id ? String(initial.service_id) : '',
        assignedTo: initial.assigned_to ? String(initial.assigned_to) : '',
        branchId: initial.branch_id ? String(initial.branch_id) : '',
        category: initial.category || '',
        queryParams() {
            var p = new URLSearchParams();
            p.set('show_tasks', this.showTasks ? '1' : '0');
            p.set('show_dues', this.showDues ? '1' : '0');
            p.set('due_status', this.dueStatus);
            if (this.serviceId) p.set('service_id', this.serviceId);
            if (this.assignedTo) p.set('assigned_to', this.assignedTo);
            if (this.branchId) p.set('branch_id', this.branchId);
            if (this.category) p.set('category', this.category);
            return p.toString();
        },
        apply() {
            if (!window.calendar) return;
            fetch('{{ route('calendar.events') }}?' + this.queryParams(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                window.calendar.removeAllEvents();
                window.calendar.addEventSource(data.events || []);
            });
        },
        reset() {
            this.showTasks = true;
            this.showDues = true;
            this.dueStatus = 'active';
            this.serviceId = '';
            this.assignedTo = '';
            this.branchId = '';
            this.category = '';
            this.apply();
        }
    };
}

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('dashboardCalendar');
    if(!calendarEl) return;
    var events = @json($calendarEvents);

    function getInitialCalendarDate(calendarEvents) {
        if (!calendarEvents.length) {
            return undefined;
        }

        var today = new Date();
        var todayMonth = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0');

        if (calendarEvents.some(function(event) {
            return (event.start || '').slice(0, 7) === todayMonth;
        })) {
            return undefined;
        }

        var candidates = calendarEvents.slice().sort(function(a, b) {
            var aDiff = Math.abs(new Date(a.start + 'T00:00:00') - today);
            var bDiff = Math.abs(new Date(b.start + 'T00:00:00') - today);
            return aDiff - bDiff;
        });

        return candidates[0] ? candidates[0].start : undefined;
    }

    window.calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        initialDate: getInitialCalendarDate(events),
        height: 'auto',
        handleWindowResize: true,
        eventDisplay: 'block',
        dayMaxEvents: 3,
        editable: true,
        eventStartEditable: true,
        eventDurationEditable: false,
        eventDragMinDistance: 4,
        longPressDelay: 0,
        eventLongPressDelay: 0,
        droppable: true,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listWeek'
        },
        events: events,
        eventTextColor: '#ffffff',
        eventContent: function(arg) {
            if (arg.view.type === 'listWeek') {
                 // Fast fallback: let standard rendering apply for list view
                 return null;
            }
            let clientName = arg.event.extendedProps.client_name || '';
            let details = arg.event.extendedProps.details || arg.event.title;
            let el = document.createElement('div');
            el.className = 'px-1.5 py-0.5 text-xs leading-tight overflow-hidden w-full';
            el.setAttribute('title', clientName + ': ' + details);
            el.innerHTML = `<div class="font-bold truncate">${clientName}</div><div class="truncate opacity-75 text-[10px]">${details}</div>`;
            return { domNodes: [el] };
        },
        eventDrop: function(info) {
            var newDate = info.event.startStr;
            var type = info.event.extendedProps.type;
            var dbId = info.event.extendedProps.db_id;
            if (!confirm("Reschedule to " + newDate + "?")) { info.revert(); return; }
            fetch('/calendar/update-date', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({type: type, id: dbId, new_date: newDate})
            }).then(r => r.json()).then(data => {
                if (!data.success) { alert('Failed to update date'); info.revert(); }
            }).catch(() => { info.revert(); });
        },
        eventClick: function(info) {
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
        eventDidMount: function(info) {
            if (info.event.extendedProps.type !== 'done') {
                info.el.style.cursor = 'move';
            }
        },
        dateClick: function(info) {
            document.getElementById('selectedDateText').innerText = info.dateStr;
            document.getElementById('btnAddTask').href = "{{ route('tasks.create') }}?due_date=" + info.dateStr;
            document.getElementById('dateClickModal').classList.remove('hidden');
        }
    });
    window.calendar.render();
});
</script>

@include('partials.calendar-event-modal')

<!-- Date Click Action Modal -->
<div id="dateClickModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-gray-900/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 transform transition-all">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Add to Schedule</h3>
        <p class="text-sm text-gray-500 mb-6">What would you like to add on <span id="selectedDateText" class="font-semibold text-violet-600"></span>?</p>
        
        <div class="space-y-3">
            <a id="btnAddTask" href="#" class="flex items-center gap-3 w-full p-3 rounded-xl border border-gray-200 hover:border-violet-300 hover:bg-violet-50 transition">
                <div class="h-10 w-10 rounded-lg bg-violet-100 flex items-center justify-center text-violet-600 text-lg">📋</div>
                <div class="text-left">
                    <div class="text-sm font-bold text-gray-900">Add Task</div>
                    <div class="text-xs text-gray-500">Assign work or deadline</div>
                </div>
            </a>
            
            <a href="{{ route('clients.create') }}" class="flex items-center gap-3 w-full p-3 rounded-xl border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition">
                <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600 text-lg">🏢</div>
                <div class="text-left">
                    <div class="text-sm font-bold text-gray-900">Add Client</div>
                    <div class="text-xs text-gray-500">Register new client</div>
                </div>
            </a>
            
            <a href="{{ route('expenses.create') }}" class="flex items-center gap-3 w-full p-3 rounded-xl border border-gray-200 hover:border-emerald-300 hover:bg-emerald-50 transition">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600 text-lg">💸</div>
                <div class="text-left">
                    <div class="text-sm font-bold text-gray-900">Add Expense</div>
                    <div class="text-xs text-gray-500">Record a payment out</div>
                </div>
            </a>
        </div>
        
        <button type="button" onclick="document.getElementById('dateClickModal').classList.add('hidden')" class="mt-6 w-full py-2.5 rounded-xl border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 transition">Cancel</button>
    </div>
</div>

@endsection
