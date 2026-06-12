@php
    $mc = $missionControl ?? [];
    $strip = $mc['today_strip'] ?? [];
    $risks = $mc['risk_alerts'] ?? [];
    $insights = $mc['ai_insights'] ?? [];
    $team = $mc['team_workload'] ?? collect();
    $attention = $mc['clients_needing_attention'] ?? collect();
    $managesFirm = auth()->user()?->managesFirmModules() ?? false;
    $hasActionItems = count($risks) > 0 || count($insights) > 0;
    $hasPeopleColumn = ($team->isNotEmpty() && $managesFirm) || ($attention->isNotEmpty() && $managesFirm);
    $showDeadlines = \App\Support\ModuleGate::allowed(auth()->user(), 'service_dues')
        && ! empty($upcomingCounts ?? null);
@endphp
<section class="mission-control executive-summary space-y-3 w-full" aria-label="Executive summary" data-demo-tour="mission-control">
    <div class="mission-control__panel">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-3 mb-3">
            <div class="flex gap-3 items-start min-w-0 flex-1">
                <div class="dashboard-brand-icon shrink-0" aria-hidden="true">
                    <div class="dashboard-brand-icon__circle">
                        <span class="text-2xl leading-none">🕉️</span>
                    </div>
                </div>
                <div class="min-w-0">
                    <p class="mission-control__eyebrow">Executive Summary</p>
                    <h2 class="mission-control__heading">{{ $mc['greeting'] ?? 'Welcome' }}</h2>
                    <p class="text-xs text-[var(--premium-muted)] mt-0.5">{{ now()->format('l, d M Y') }} · {{ \App\Support\Branding::dashboardName() }}</p>
                </div>
            </div>
            @if($managesFirm && \App\Support\ModuleGate::hasFinanceModule(auth()->user()) && isset($mc['revenue']['collected_today']))
            <div class="rounded-xl border border-[var(--premium-border)] bg-[#f6f8fb] px-4 py-2 text-right shrink-0">
                <p class="text-[10px] font-bold uppercase tracking-wider text-[var(--premium-muted)]">Collected today</p>
                <p class="text-lg font-bold text-[var(--premium-navy)] tabular-nums">₹ {{ number_format($mc['revenue']['collected_today'], 0) }}</p>
            </div>
            @endif
        </div>

        <div class="mc-strip">
            @foreach($strip as $item)
            <a href="{{ $item['url'] }}" class="mc-strip-item {{ ($item['tone'] ?? '') === 'rose' ? 'mc-strip-item--alert' : '' }}">
                <p>{{ $item['label'] }}</p>
                <p>{{ $item['value'] }}</p>
            </a>
            @endforeach
        </div>
    </div>

    @if($hasActionItems || $hasPeopleColumn)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        @if($hasActionItems)
        <div class="exec-summary__card">
            <div class="flex items-center justify-between gap-2 mb-2">
                <p class="exec-summary__label">Action needed</p>
                @if(count($insights) > 0)
                <a href="{{ route('activity.index') }}" class="text-[10px] font-semibold text-indigo-600 hover:underline">The Pulse →</a>
                @endif
            </div>
            <ul class="space-y-1.5">
                @foreach($risks as $alert)
                <li>
                    <a href="{{ $alert['url'] }}" class="exec-summary__row exec-summary__row--risk">
                        <span class="truncate">{{ $alert['label'] }}</span>
                        <span class="font-black tabular-nums shrink-0">{{ $alert['count'] }}</span>
                    </a>
                </li>
                @endforeach
                @foreach(array_slice($insights, 0, 3) as $line)
                <li class="exec-summary__insight">
                    <span class="text-violet-500 shrink-0">•</span>
                    <span>{{ $line }}</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        @if($hasPeopleColumn)
        <div class="exec-summary__card" data-demo-tour="clients-attention">
            @if($team->isNotEmpty())
            <div class="flex justify-between items-center mb-2">
                <p class="exec-summary__label">Team workload</p>
                <a href="{{ route('workload.index') }}" class="text-[10px] font-semibold text-indigo-600">Planner →</a>
            </div>
            <div class="flex flex-wrap gap-1.5 mb-3">
                @foreach($team->take(5) as $member)
                <div class="exec-summary__chip {{ $member['status'] === 'overloaded' ? 'exec-summary__chip--hot' : ($member['status'] === 'idle' ? 'exec-summary__chip--cool' : '') }}">
                    <span class="truncate max-w-[5.5rem]">{{ $member['name'] }}</span>
                    <span class="font-black tabular-nums">{{ $member['open_tasks'] }}</span>
                </div>
                @endforeach
            </div>
            @endif

            @if($attention->isNotEmpty())
            <div class="flex justify-between items-center mb-2 {{ $team->isNotEmpty() ? 'pt-2 border-t border-slate-100' : '' }}">
                <p class="exec-summary__label">Clients needing attention</p>
                <a href="{{ route('clients.index') }}" class="text-[10px] font-semibold text-indigo-600">All →</a>
            </div>
            <ul class="space-y-1.5">
                @foreach($attention->take(3) as $row)
                @php $c = $row['client']; @endphp
                <li>
                    <a href="{{ route('clients.show', $c) }}" class="exec-summary__row">
                        <span class="exec-summary__score exec-summary__score--{{ $row['tone'] }}">{{ $row['score'] }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-gray-900 truncate">{{ $c->name }}</span>
                            <span class="block text-[10px] text-gray-500">{{ $row['label'] }}</span>
                        </span>
                    </a>
                </li>
                @endforeach
            </ul>
            @endif
        </div>
        @endif
    </div>
    @endif

    @if($showDeadlines)
    <div class="exec-summary__card exec-summary__card--flat">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
            <p class="exec-summary__label mb-0">Compliance deadlines</p>
            @if($canManageFirm ?? false)
            <a href="{{ route('reports.due-date') }}" class="text-[10px] font-semibold text-indigo-600">Report →</a>
            @else
            <a href="{{ route('service-dues.index') }}" class="text-[10px] font-semibold text-indigo-600">Reminders →</a>
            @endif
        </div>
        <div class="grid grid-cols-3 gap-2">
            <a href="{{ $deadline7Url ?? '#' }}" class="exec-summary__deadline exec-summary__deadline--7">
                <span class="text-[10px] font-bold uppercase tracking-wide">7 days</span>
                <span class="text-xl font-black tabular-nums">{{ $upcomingCounts['7_days'] ?? 0 }}</span>
            </a>
            <a href="{{ $deadline15Url ?? '#' }}" class="exec-summary__deadline exec-summary__deadline--15">
                <span class="text-[10px] font-bold uppercase tracking-wide">7–15 days</span>
                <span class="text-xl font-black tabular-nums">{{ ($upcomingCounts['15_days'] ?? 0) - ($upcomingCounts['7_days'] ?? 0) }}</span>
            </a>
            <a href="{{ $deadline30Url ?? '#' }}" class="exec-summary__deadline exec-summary__deadline--30">
                <span class="text-[10px] font-bold uppercase tracking-wide">15–30 days</span>
                <span class="text-xl font-black tabular-nums">{{ ($upcomingCounts['30_days'] ?? 0) - ($upcomingCounts['15_days'] ?? 0) }}</span>
            </a>
        </div>
    </div>
    @endif
</section>
