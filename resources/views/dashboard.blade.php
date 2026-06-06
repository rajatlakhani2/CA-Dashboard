@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <div>
        <h2 class="font-bold text-lg text-gray-900 tracking-wide">{{ ($workspace['name'] ?? null) ? $workspace['name'] : 'Command Centre' }}</h2>
        <p class="text-xs text-gray-500 mt-0.5">Multi-user workspace · {{ $workspace['seat_used'] ?? '—' }} team members</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        @can('create', App\Models\Client::class)
        <a href="{{ route('clients.create') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:border-indigo-200 hover:bg-indigo-50 transition">
            <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            <span>Client</span>
        </a>
        @endcan
        <a href="{{ route('tasks.create') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:border-violet-200 hover:bg-violet-50 transition">
            <svg class="h-4 w-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span>Task</span>
        </a>
        @can('create', App\Models\Invoice::class)
        <a href="{{ route('invoices.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-md shadow-indigo-600/25 hover:bg-indigo-700 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Invoice</span>
        </a>
        @endcan
    </div>
</div>
@endsection

@push('head_styles')
@include('dashboard.partials.premium-styles')
<style>
    /* ===== VouchEx colour palette (layout unchanged) ===== */
    body { background: var(--premium-bg, #f8fafc) !important; }
    .min-h-full.bg-bg-body { background: transparent !important; }
    main.flex-1 { background: transparent !important; }

    #glass-bg { display: none; }

    header.bg-white\/80 {
        background: rgba(255, 255, 255, 0.78) !important;
        border-bottom: 1px solid rgba(226, 232, 240, 0.85) !important;
        backdrop-filter: blur(14px) saturate(1.15) !important;
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.8);
    }
    header h2 { color: #0f172a !important; background: none !important; -webkit-text-fill-color: #0f172a !important; }

    .glass-card {
        background: rgba(255, 255, 255, 0.72);
        border: 1px solid rgba(226, 232, 240, 0.85);
        border-radius: 14px;
        transition: all 0.28s ease;
        backdrop-filter: blur(10px) saturate(1.1);
        box-shadow: 0 1px 0 rgba(255, 255, 255, 0.9) inset, 0 8px 24px -8px rgba(15, 23, 42, 0.1);
    }
    .glass-card:hover { transform: translateY(-2px); box-shadow: 0 14px 32px rgba(15, 23, 42, 0.1); border-color: #bfdbfe; }

    .kpi-card {
        background: rgba(255, 255, 255, 0.62);
        border: 1px solid rgba(226, 232, 240, 0.85);
        border-radius: 14px;
        padding: 1.25rem 1.35rem;
        cursor: pointer;
        transition: all 0.28s ease;
        text-decoration: none;
        display: block;
        backdrop-filter: blur(12px) saturate(1.1);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }
    .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 14px 32px rgba(15, 23, 42, 0.1); border-color: #bfdbfe; }
    .kpi-card .kpi-label { color: #475569; font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; }
    .kpi-card .kpi-value { color: #0f172a; font-size:2.5rem; font-weight:800; line-height:1; margin: 0.5rem 0 0.25rem; }
    .kpi-card .kpi-sub { color: #64748b; font-size:0.75rem; }

    .kpi-blue { border-top: 3px solid #2563eb !important; }
    .kpi-violet { border-top: 3px solid #7c3aed !important; }
    .kpi-amber { border-top: 3px solid #f59e0b !important; }
    .kpi-rose { border-top: 3px solid #ef4444 !important; }
    .kpi-emerald { border-top: 3px solid #10b981 !important; }

    .glass-tabs { display: flex; flex-wrap: wrap; gap: 0.5rem; padding: 0.35rem; background: rgba(255,255,255,0.85); border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 1px 2px rgba(15,23,42,0.04); backdrop-filter: blur(8px); }
    .glass-tab { color: #64748b; border: none; border-radius: 10px; padding: 0.6rem 1rem; font-size:0.8125rem; font-weight:600; cursor:pointer; transition: all 0.2s; white-space:nowrap; background: transparent; }
    .glass-tab.active { color: #fff; background: linear-gradient(135deg, #2563eb, #0d9488); box-shadow: 0 6px 16px rgba(37, 99, 235, 0.25); }
    .glass-tab:hover:not(.active) { color: #0f172a; background: #f1f5f9; }

    .dashboard-tab-panel.hidden { display: none !important; }

    @media (max-width: 639px) {
        .glass-tabs { overflow-x: auto; flex-wrap: nowrap; -webkit-overflow-scrolling: touch; }
        .glass-tab { flex-shrink: 0; }
    }

    .glass-section-title { color: #4b5563; font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:1rem; }
    .glass-list-item { padding: 0.75rem 0; border-bottom: 1px solid #f3f4f6; }
    .glass-list-item:last-child { border-bottom: none; }

    /* ===== Minimal month grid calendar (dot indicators) ===== */
    #dashboardCalendar.cal-grid-minimal { background: #f3f4f6; border-radius: 16px; padding: 12px; }
    #dashboardCalendar .fc { color: #374151; }
    #dashboardCalendar .fc-theme-standard .fc-scrollgrid { border: none !important; }
    #dashboardCalendar .fc-theme-standard td,
    #dashboardCalendar .fc-theme-standard th { border: none !important; }
    #dashboardCalendar .fc-col-header { background: #f3f4f6; }
    #dashboardCalendar .fc-col-header-cell {
        background: #f3f4f6 !important;
        padding: 8px 4px 12px !important;
    }
    #dashboardCalendar .fc-col-header-cell-cushion {
        color: #6b7280 !important;
        font-size: 0.8rem !important;
        font-weight: 600 !important;
        text-transform: capitalize;
    }
    #dashboardCalendar .fc-daygrid-body { background: #f3f4f6; }
    #dashboardCalendar .fc-daygrid-day {
        background: transparent !important;
        padding: 4px !important;
    }
    #dashboardCalendar .fc-daygrid-day-frame {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        min-height: 92px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transition: box-shadow 0.15s, border-color 0.15s;
    }
    #dashboardCalendar .fc-daygrid-day:hover .fc-daygrid-day-frame {
        border-color: #c7d2fe;
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.08);
    }
    #dashboardCalendar .fc-day-today .fc-daygrid-day-frame {
        border-color: #a5b4fc !important;
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15);
    }
    #dashboardCalendar .fc-daygrid-day-top {
        flex-direction: row !important;
        justify-content: flex-start !important;
        padding: 8px 10px 0 !important;
    }
    #dashboardCalendar .fc-daygrid-day-number {
        color: #374151 !important;
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        float: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    #dashboardCalendar .fc-day-other .fc-daygrid-day-frame { background: #fafafa; }
    #dashboardCalendar .fc-day-other .fc-daygrid-day-number { color: #9ca3af !important; }
    #dashboardCalendar .fc-daygrid-day-events {
        margin: auto 0 0 0 !important;
        min-height: 28px;
        display: flex !important;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 6px 8px 10px !important;
    }
    #dashboardCalendar .fc-daygrid-event-harness { margin: 0 !important; }
    #dashboardCalendar .fc-daygrid-event {
        background: transparent !important;
        border: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    #dashboardCalendar .cal-event-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        display: block;
        flex-shrink: 0;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(0,0,0,0.12);
    }
    #dashboardCalendar .fc-daygrid-more-link {
        font-size: 0.65rem !important;
        color: #6366f1 !important;
        font-weight: 700;
        background: #eef2ff;
        border-radius: 6px;
        padding: 2px 6px;
        margin: 0 !important;
    }
    #dashboardCalendar .fc .fc-toolbar { margin-bottom: 1rem !important; gap: 0.5rem; flex-wrap: wrap; }
    #dashboardCalendar .fc .fc-toolbar-title {
        color: #111827 !important;
        font-size: 1.125rem !important;
        font-weight: 700 !important;
    }
    #dashboardCalendar .fc .fc-button-primary {
        background: #ffffff !important;
        border: 1px solid #e5e7eb !important;
        color: #374151 !important;
        border-radius: 10px !important;
        font-size: 0.8rem !important;
        font-weight: 600 !important;
        padding: 0.4rem 0.85rem !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    #dashboardCalendar .fc .fc-button-primary:hover { background: #f9fafb !important; }
    #dashboardCalendar .fc .fc-button-primary:not(:disabled).fc-button-active {
        background: #eef2ff !important;
        border-color: #c7d2fe !important;
        color: #4f46e5 !important;
    }
    #dashboardCalendar .fc-list { border-radius: 12px; overflow: hidden; }
    #dashboardCalendar .fc-popover {
        background: #ffffff !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 12px !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .deadline-pill { border-radius:12px; padding:1rem 1.25rem; display:flex; justify-content:space-between; align-items:center; transition:all 0.2s; text-decoration:none; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .deadline-pill:hover { transform:translateX(3px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.08); }
    .deadline-pill-7 { background: #fef2f2; border-color: #fecaca; }
    .deadline-pill-15 { background: #fffbeb; border-color: #fde68a; }
    .deadline-pill-30 { background: #fefce8; border-color: #fef08a; }
    .dash-empty { border: 2px dashed #e5e7eb; border-radius: 16px; padding: 2rem 1.5rem; text-align: center; background: #fafafa; }

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

{{-- dashboard-tabs-v2: vanilla JS tabs (no Alpine dependency) --}}
@php $dashboardActiveTab = $initialDashboardTab ?? 'overview'; @endphp
<div
    id="dashboard-tab-root"
    data-initial-tab="{{ $dashboardActiveTab }}"
    data-firm-tab="{{ ($showFirmOverviewTab ?? false) ? '1' : '0' }}"
    class="w-full min-w-0 max-w-none space-y-6 dashboard-shell"
>

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

    @include('dashboard.partials.onboarding-banner')

    @include('dashboard.partials.mission-control')

    <div class="flex flex-wrap items-center justify-end gap-3 -mb-2">
        <p class="text-[10px] text-gray-400" title="If this does not say tabs-v2, the server still has an old dashboard file">
            Build: {{ $dashboardBuildId ?? 'unknown' }}
        </p>
        <button type="button" data-dashboard-report-issue class="text-[10px] font-semibold text-indigo-600 hover:text-indigo-800 underline">
            Report technical issue
        </button>
    </div>

    {{-- ===== TAB NAVIGATION ===== --}}
    <div class="glass-tabs" role="tablist">
        <button type="button" role="tab" data-dashboard-tab="overview" class="glass-tab {{ $dashboardActiveTab === 'overview' ? 'active' : '' }}">Overview</button>
        <button type="button" role="tab" data-dashboard-tab="calendar" class="glass-tab {{ $dashboardActiveTab === 'calendar' ? 'active' : '' }}">Schedule</button>
        <button type="button" role="tab" data-dashboard-tab="workload" class="glass-tab {{ $dashboardActiveTab === 'workload' ? 'active' : '' }}">Workload</button>
        @if($canManageFirm)
        <button type="button" role="tab" data-dashboard-tab="financials" class="glass-tab {{ $dashboardActiveTab === 'financials' ? 'active' : '' }}">Financials</button>
        @endif
        @if($showFirmOverviewTab ?? false)
        <button type="button" role="tab" data-dashboard-tab="firm" class="glass-tab {{ $dashboardActiveTab === 'firm' ? 'active' : '' }}">Firm overview</button>
        @endif
    </div>

    {{-- ===== OVERVIEW TAB ===== --}}
    <div data-dashboard-panel="overview" class="dashboard-tab-panel {{ $dashboardActiveTab !== 'overview' ? 'hidden' : '' }}">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Pending Tasks --}}
            <div class="lg:col-span-2 glass-card p-6 h-full">
                <div class="flex flex-wrap justify-between items-center gap-2 mb-5">
                    <div>
                        <p class="glass-section-title mb-0">Today's priority queue</p>
                        <p class="text-xs text-gray-500 mt-0.5">Your open tasks, soonest due first</p>
                    </div>
                    <a href="{{ route('tasks.index') }}" class="text-indigo-600 text-xs font-semibold hover:text-indigo-800">View all →</a>
                </div>
                <ul class="space-y-2">
                    @forelse($myPendingTasks->take(8) as $task)
                    <li class="rounded-xl border border-gray-100 bg-gray-50/80 px-4 py-3 hover:border-indigo-200 hover:bg-indigo-50/50 transition">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div class="h-2.5 w-2.5 rounded-full flex-shrink-0 {{ $task->due_date->isPast() ? 'bg-rose-500' : 'bg-indigo-500' }}"></div>
                                <div class="min-w-0">
                                    <div class="text-gray-900 text-sm font-semibold truncate">{{ $task->client?->name ?? 'Internal' }}</div>
                                    <a href="{{ route('tasks.edit', $task) }}" class="text-gray-600 text-xs hover:text-indigo-600 truncate block">{{ $task->title }}</a>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                @if(isset($task->client->primary_contact_phone) && $task->client->primary_contact_phone)
                                @php
                                $phone = preg_replace('/[^0-9]/', '', $task->client->primary_contact_phone);
                                if(strlen($phone) == 10) $phone = '91' . $phone;
                                $msg = "Hi " . ($task->client?->name ?? 'Client') . ", a gentle reminder regarding '" . $task->title . "' which is due on " . $task->due_date->format('d M') . ".";
                                $waLink = "https://wa.me/" . $phone . "?text=" . urlencode($msg);
                                @endphp
                                <a href="{{ $waLink }}" target="_blank" class="text-emerald-600 hover:text-emerald-700" title="WhatsApp reminder">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 2C6.502 2 2 6.516 2 12.067c0 1.83.487 3.633 1.414 5.23L2.007 22l4.897-1.28c1.55.845 3.302 1.29 5.127 1.29h.005c5.53 0 10.031-4.515 10.031-10.067C22.063 6.52 17.561 2 12.031 2z"/></svg>
                                </a>
                                @endif
                                <span class="text-xs px-2.5 py-1 rounded-full font-semibold {{ $task->due_date->isPast() ? 'bg-rose-100 text-rose-800' : 'bg-white text-gray-600 border border-gray-200' }}">
                                    {{ $task->due_date->format('d M') }}
                                </span>
                            </div>
                        </div>
                    </li>
                    @empty
                    <li class="dash-empty">
                        <p class="text-3xl mb-2">✓</p>
                        <p class="text-gray-700 text-sm font-semibold">All caught up</p>
                        <p class="text-gray-500 text-xs mt-1 mb-4">No pending tasks on your queue.</p>
                        <a href="{{ route('tasks.create') }}" class="inline-flex items-center gap-1 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">+ Create a task</a>
                    </li>
                    @endforelse
                </ul>
            </div>

            {{-- Upcoming Deadlines --}}
            @php
                $upcomingOverview = $alerts->take(4);
            @endphp
            <div class="glass-card p-6 flex flex-col">
                <div class="flex justify-between items-center mb-4">
                    <p class="glass-section-title mb-0">Upcoming overview</p>
                    @if($canManageFirm)
                    <a href="{{ route('reports.due-date') }}" class="text-indigo-600 text-xs font-semibold hover:text-indigo-800">Report →</a>
                    @else
                    <a href="{{ route('service-dues.index') }}" class="text-indigo-600 text-xs font-semibold hover:text-indigo-800">Reminders →</a>
                    @endif
                </div>
                <div class="space-y-2 mb-6">
                    @forelse($upcomingOverview as $alert)
                    <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $alert->clientService?->client?->name ?? 'Internal' }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $alert->clientService?->service?->name ?? 'Service due' }}</p>
                            </div>
                            <span class="text-xs font-bold text-gray-700 whitespace-nowrap bg-gray-100 px-2 py-1 rounded-lg">{{ \Carbon\Carbon::parse($alert->due_date)->format('d M') }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="dash-empty py-8">
                        <p class="text-sm font-medium text-gray-600">No urgent dues</p>
                        <p class="text-xs text-gray-500 mt-1">Compliance queue is clear for now.</p>
                    </div>
                    @endforelse
                </div>
                <p class="glass-section-title">Upcoming deadlines</p>
                <div class="space-y-3 flex-1">
                    <a href="{{ $deadline7Url }}" class="deadline-pill deadline-pill-7">
                        <div>
                            <div class="text-rose-800 font-bold text-sm">Next 7 days</div>
                            <div class="text-rose-600/80 text-xs mt-0.5">Critical window</div>
                        </div>
                        <div class="text-rose-700 text-3xl font-black">{{ $upcomingCounts['7_days'] }}</div>
                    </a>
                    <a href="{{ $deadline15Url }}" class="deadline-pill deadline-pill-15">
                        <div>
                            <div class="text-amber-800 font-bold text-sm">7 – 15 days</div>
                            <div class="text-amber-700/80 text-xs mt-0.5">Plan ahead</div>
                        </div>
                        <div class="text-amber-700 text-3xl font-black">{{ $upcomingCounts['15_days'] - $upcomingCounts['7_days'] }}</div>
                    </a>
                    <a href="{{ $deadline30Url }}" class="deadline-pill deadline-pill-30">
                        <div>
                            <div class="text-yellow-800 font-bold text-sm">15 – 30 days</div>
                            <div class="text-yellow-700/80 text-xs mt-0.5">On the horizon</div>
                        </div>
                        <div class="text-yellow-700 text-3xl font-black">{{ $upcomingCounts['30_days'] - $upcomingCounts['15_days'] }}</div>
                    </a>
                </div>

                @if($highRiskClients->count() > 0)
                <div class="mt-6 pt-5 border-t border-gray-100">
                    <p class="glass-section-title">High-risk clients</p>
                    <ul class="space-y-2">
                        @foreach($highRiskClients->take(4) as $client)
                        <li class="flex justify-between items-center rounded-lg bg-rose-50 px-3 py-2 border border-rose-100">
                            <span class="text-gray-800 text-sm font-medium truncate">{{ $client->name }}</span>
                            <a href="{{ route('clients.show', $client) }}" class="text-indigo-600 text-xs font-semibold ml-2 flex-shrink-0">View</a>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== SCHEDULE / CALENDAR TAB ===== --}}
    <div id="schedule" data-dashboard-panel="calendar" class="dashboard-tab-panel {{ $dashboardActiveTab !== 'calendar' ? 'hidden' : '' }}">
        <div class="glass-card p-6" style="min-height: 600px;">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <p class="glass-section-title mb-0">📅 Schedule & Deadlines</p>
                    <p class="mt-1 text-xs text-gray-500">Colored dots = tasks &amp; dues. Click a dot for details, drag to reschedule, or click a day to add a task.</p>
                </div>
                <div class="flex gap-3 text-xs text-gray-500 flex-wrap justify-end">
                    <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-blue-400"></span> Tasks</span>
                    <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-violet-400"></span> Dues</span>
                    <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-emerald-400"></span> Done</span>
                    <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-rose-400"></span> Overdue</span>
                </div>
            </div>
            @include('dashboard.partials.calendar-filters')
            <div id="dashboardCalendar" class="cal-grid-minimal" style="min-height: 580px;"></div>
        </div>
    </div>

    {{-- ===== WORKLOAD TAB ===== --}}
    <div data-dashboard-panel="workload" class="dashboard-tab-panel {{ $dashboardActiveTab !== 'workload' ? 'hidden' : '' }}">
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
                                    <p class="text-gray-500 text-xs mt-0.5">{{ $task->client?->name ?? 'Internal' }}</p>
                                </div>
                                <span class="text-gray-400 text-xs">{{ $task->due_date->format('M d') }}</span>
                            </div>
                        </li>
                        @empty
                        <li class="dash-empty py-6 text-sm text-gray-500">No pending tasks.</li>
                        @endforelse
                    </ul>
                </div>

                {{-- Deadline breakdown --}}
                <div class="glass-card p-6">
                    <p class="glass-section-title">Upcoming Deadlines Breakdown</p>
                    <div class="space-y-3">
                        <a href="{{ $deadline7Url }}" class="deadline-pill deadline-pill-7">
                            <span class="text-rose-800 font-semibold text-sm">Next 7 days</span>
                            <span class="text-rose-700 text-2xl font-black">{{ $upcomingCounts['7_days'] }}</span>
                        </a>
                        <a href="{{ $deadline15Url }}" class="deadline-pill deadline-pill-15">
                            <span class="text-amber-800 font-semibold text-sm">7 – 15 days</span>
                            <span class="text-amber-700 text-2xl font-black">{{ $upcomingCounts['15_days'] - $upcomingCounts['7_days'] }}</span>
                        </a>
                        <a href="{{ $deadline30Url }}" class="deadline-pill deadline-pill-30">
                            <span class="text-yellow-800 font-semibold text-sm">15 – 30 days</span>
                            <span class="text-yellow-700 text-2xl font-black">{{ $upcomingCounts['30_days'] - $upcomingCounts['15_days'] }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($canManageFirm)
    {{-- ===== FINANCIALS TAB ===== --}}
    <div data-dashboard-panel="financials" class="dashboard-tab-panel {{ $dashboardActiveTab !== 'financials' ? 'hidden' : '' }}">
        @include('dashboard.partials.revenue-command-center')
        <div class="space-y-6 mt-6">
            {{-- Recent Clients --}}
            <div class="glass-card p-6">
                <div class="flex justify-between items-center mb-4">
                    <p class="glass-section-title mb-0">Recently Updated Clients</p>
                    <a href="{{ route('clients.index') }}" class="text-indigo-600 text-xs font-semibold hover:text-indigo-800">View all →</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($recentClients as $client)
                    <a href="{{ route('clients.edit', $client) }}" class="flex items-center gap-3 p-3 rounded-xl bg-white hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition">
                        <div class="h-9 w-9 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-700 font-bold text-sm flex-shrink-0">
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

    @if($showFirmOverviewTab ?? false)
    <div data-dashboard-panel="firm" class="dashboard-tab-panel {{ $dashboardActiveTab !== 'firm' ? 'hidden' : '' }}">
        @include('dashboard.partials.firm-overview', ['firmOverview' => $firmOverview])
    </div>
    @endif

    @include('partials.welcome-modal')

    @include('dashboard.partials.tabs-script')
    @includeIf('dashboard.partials.error-reporter', ['dashboardBuildId' => $dashboardBuildId ?? 'unknown'])
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
        fixedWeekCount: false,
        showNonCurrentDates: true,
        firstDay: 1,
        dayMaxEvents: 6,
        moreLinkClick: 'popover',
        editable: true,
        eventStartEditable: true,
        eventDurationEditable: false,
        eventDragMinDistance: 6,
        longPressDelay: 0,
        eventLongPressDelay: 0,
        droppable: true,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listWeek'
        },
        buttonText: { today: 'Today', month: 'Month', week: 'List' },
        events: events,
        eventContent: function(arg) {
            if (arg.view.type !== 'dayGridMonth') {
                return null;
            }
            var clientName = arg.event.extendedProps.client_name || '';
            var details = arg.event.extendedProps.details || arg.event.title;
            var dot = document.createElement('span');
            dot.className = 'cal-event-dot';
            dot.style.backgroundColor = arg.event.backgroundColor || arg.event.borderColor || '#6366f1';
            dot.setAttribute('title', clientName + ': ' + details);
            dot.setAttribute('aria-label', details);
            return { domNodes: [dot] };
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
