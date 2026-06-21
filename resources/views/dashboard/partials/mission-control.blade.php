@php
    $mc = $missionControl ?? [];
    $kpis = $mc['executive_kpis'] ?? ($mc['today_strip'] ?? []);
    $showMyDay = auth()->user()?->canAccessModule('tasks') ?? false;
    $showDuesTomorrow = auth()->user()?->canAccessModule('service_dues') ?? false;
    $showTomorrow = $showMyDay || $showDuesTomorrow;
    $showExecFirm = ($managesFirm ?? auth()->user()?->managesFirmModules()) || ($showFirmOverviewTab ?? false);
    $managesFirm = auth()->user()?->managesFirmModules() ?? false;
    $dueTomorrowTasks = $dueTomorrowTasks ?? collect();
    $dueTomorrowDues = $dueTomorrowDues ?? collect();
    $dueTomorrowTotal = $dueTomorrowTasks->count() + $dueTomorrowDues->count();
@endphp
<section class="mission-control executive-summary executive-summary--customizable w-full" aria-label="Executive summary" data-demo-tour="mission-control">
    <div class="mission-control__panel executive-summary__header mb-3">
        <div class="min-w-0 flex-1">
            <p class="mission-control__eyebrow">Executive Summary</p>
            <h2 class="mission-control__heading text-xl sm:text-2xl">{{ $mc['greeting'] ?? 'Welcome' }}</h2>
            <p class="text-[11px] text-[var(--premium-muted)]">{{ now()->format('l, d M Y') }} · {{ \App\Support\Branding::dashboardName() }}</p>
        </div>
        <p class="executive-summary__layout-hint text-[10px] text-gray-500 shrink-0 hidden sm:block">Drag · ▼ collapse · drag ⤡ corner to resize</p>
    </div>

    <div id="executive-summary-sortable" class="executive-summary__sortable" data-allowed-widgets="@json(array_keys($allowedExecutiveWidgets ?? []))">
        @if($showMyDay)
        <x-executive-widget id="exec-my-day" title="☀️ My Day" :subtitle="auth()->user()->name . ' · ' . now()->format('l, d M Y')">
            @include('tasks.partials.my-day-panel', [
                'tasksToday' => $myDayTasksToday ?? collect(),
                'tasksUpcoming' => $myDayTasksUpcoming ?? collect(),
                'compact' => true,
                'hideUpcoming' => true,
                'hideChrome' => true,
            ])
        </x-executive-widget>
        @endif

        @if($showTomorrow)
        <x-executive-widget id="exec-due-tomorrow" :title="'Due tomorrow (' . $dueTomorrowTotal . ')'" subtitle="Tasks and compliance dues due next working day">
            @include('dashboard.partials.due-tomorrow-panel', [
                'dueTomorrowTasks' => $dueTomorrowTasks,
                'dueTomorrowDues' => $dueTomorrowDues,
                'hideHeader' => true,
            ])
        </x-executive-widget>
        @endif

        <x-executive-widget id="exec-kpis" title="At a glance" subtitle="Key counts across tasks, compliance, billing, and clients">
            <div class="exec-kpi-grid">
                @foreach($kpis as $item)
                @if(!empty($item['url']))
                <a href="{{ $item['url'] }}" class="exec-kpi-card exec-kpi-card--{{ $item['tone'] ?? 'slate' }}">
                    <span class="exec-kpi-card__label">{{ $item['label'] }}</span>
                    <span class="exec-kpi-card__value">{{ $item['value'] }}</span>
                </a>
                @else
                <div class="exec-kpi-card exec-kpi-card--{{ $item['tone'] ?? 'slate' }}">
                    <span class="exec-kpi-card__label">{{ $item['label'] }}</span>
                    <span class="exec-kpi-card__value">{{ $item['value'] }}</span>
                </div>
                @endif
                @endforeach
            </div>
        </x-executive-widget>

        <x-executive-widget id="exec-calendar" title="📅 Schedule & Deadlines" subtitle="Drag events to reschedule · resize with corner or edges">
            <div class="executive-summary__calendar exec-calendar-fill min-w-0">
                @include('dashboard.partials.schedule-calendar', ['embedded' => true, 'resizable' => true, 'hideHeader' => true])
            </div>
        </x-executive-widget>

        <x-executive-widget id="exec-pulse" title="Firm pulse" subtitle="Action needed, workload, clients, and upcoming deadlines">
            @include('dashboard.partials.executive-pulse-panels', ['mc' => $mc])
        </x-executive-widget>

        @if(\App\Support\ModuleGate::hasFinanceModule(auth()->user()))
        <x-executive-widget id="exec-finance" title="Finance" subtitle="Tap a card to reveal figures">
            @include('dashboard.partials.executive-finance', ['hideHeader' => true])
        </x-executive-widget>
        @endif

        @if($showExecFirm)
        <x-executive-widget id="exec-firm" title="Firm overview" subtitle="Alerts, workload, compliance rollups & partner metrics" :defaultCollapsed="true">
            @include('dashboard.partials.executive-firm-section')
        </x-executive-widget>
        @endif

        @if(! $showMyDay && ! $showTomorrow)
        <x-executive-widget id="exec-empty-hint" title="Your day" subtitle="Enable Tasks or Service Dues in settings">
            <p class="text-xs text-gray-500 text-center py-6">Enable Tasks or Service Dues to see your day here.</p>
        </x-executive-widget>
        @endif
    </div>
</section>
