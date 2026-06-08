@php
    $mc = $missionControl ?? [];
    $strip = $mc['today_strip'] ?? [];
    $risks = $mc['risk_alerts'] ?? [];
    $insights = $mc['ai_insights'] ?? [];
    $team = $mc['team_workload'] ?? collect();
    $attention = $mc['clients_needing_attention'] ?? collect();
@endphp
<section class="mission-control space-y-4 w-full" aria-label="Mission control" data-demo-tour="mission-control">
    <div class="mission-control__panel">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-3 mb-4">
            <div class="flex gap-4 items-start min-w-0 flex-1">
                <div class="dashboard-brand-icon shrink-0" aria-hidden="true">
                    <div class="dashboard-brand-icon__circle">
                        <span class="text-3xl leading-none">🕉️</span>
                    </div>
                    <div class="dashboard-brand-icon__strip">
                        <span>🙏</span>
                        <span>✨</span>
                        <span>💰</span>
                    </div>
                </div>
                <div class="min-w-0">
                    <p class="mission-control__eyebrow">{{ \App\Support\Branding::dashboardName() }}</p>
                    <h2 class="mission-control__heading">{{ $mc['greeting'] ?? 'Welcome' }}</h2>
                    @if(!empty($positiveThought))
                    <p class="text-sm italic text-[var(--premium-muted)] mt-1.5 max-w-2xl">"{{ $positiveThought }}"</p>
                    @endif
                    <p class="text-xs text-[var(--premium-muted)] mt-1">Your firm at a glance — {{ now()->format('l, d M Y') }}</p>
                </div>
            </div>
            @if(auth()->user()?->managesFirmModules() && \App\Support\ModuleGate::hasFinanceModule(auth()->user()) && isset($mc['revenue']['collected_today']))
            <div class="rounded-xl border border-[var(--premium-border)] bg-[#f6f8fb] px-4 py-2.5 text-right shrink-0">
                <p class="text-[10px] font-bold uppercase tracking-wider text-[var(--premium-muted)]">Collected today</p>
                <p class="text-lg font-bold text-[var(--premium-navy)] tabular-nums">₹ {{ number_format($mc['revenue']['collected_today'], 0) }}</p>
            </div>
            @endif
        </div>

        <div class="mc-strip">
            @foreach($strip as $item)
            <a href="{{ $item['url'] }}" class="mc-strip-item">
                <p>{{ $item['label'] }}</p>
                <p>{{ $item['value'] }}</p>
            </a>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        @if(count($risks) > 0)
        <div class="xl:col-span-1 rounded-xl border border-rose-200 bg-rose-50/60 p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-rose-800 mb-2">Risk alerts</p>
            <ul class="space-y-2">
                @foreach($risks as $alert)
                <li>
                    <a href="{{ $alert['url'] }}" class="flex items-center justify-between gap-2 rounded-lg bg-white/80 px-3 py-2 text-sm hover:bg-white border border-rose-100">
                        <span class="font-medium text-rose-950">{{ $alert['label'] }}</span>
                        <span class="font-black text-rose-700">{{ $alert['count'] }}</span>
                    </a>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(count($insights) > 0)
        <div class="rounded-xl border border-violet-200 bg-violet-50/50 p-4 {{ count($risks) > 0 ? 'xl:col-span-1' : 'xl:col-span-2' }}">
            <p class="text-xs font-bold uppercase tracking-wider text-violet-800 mb-2">AI insights</p>
            <ul class="space-y-2 text-sm text-violet-950">
                @foreach($insights as $line)
                <li class="flex gap-2"><span class="text-violet-500">•</span><span>{{ $line }}</span></li>
                @endforeach
            </ul>
            <a href="{{ route('activity.index') }}" class="mt-3 inline-block text-xs font-semibold text-violet-700 hover:underline">Open The Pulse →</a>
        </div>
        @endif

        @if($team->isNotEmpty() && auth()->user()?->managesFirmModules())
        <div class="rounded-xl border border-slate-200 bg-white p-4 {{ (count($risks) > 0 && count($insights) > 0) ? 'xl:col-span-3' : 'xl:col-span-1' }}">
            <div class="flex justify-between items-center mb-2">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-600">Team workload</p>
                <a href="{{ route('workload.index') }}" class="text-xs font-semibold text-indigo-600">Planner →</a>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($team->take(6) as $member)
                <div class="flex items-center gap-2 rounded-lg border px-2.5 py-1.5 text-xs
                    {{ $member['status'] === 'overloaded' ? 'border-rose-200 bg-rose-50' : ($member['status'] === 'idle' ? 'border-emerald-200 bg-emerald-50' : 'border-gray-100 bg-gray-50') }}">
                    <span class="font-semibold text-gray-900 truncate max-w-[6rem]">{{ $member['name'] }}</span>
                    <span class="font-black tabular-nums">{{ $member['open_tasks'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    @if($attention->isNotEmpty() && auth()->user()?->managesFirmModules())
    <div class="rounded-xl border border-amber-200 bg-amber-50/40 p-4" data-demo-tour="clients-attention">
        <div class="flex justify-between items-center mb-3">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-900">Clients needing attention</p>
            <a href="{{ route('clients.index') }}" class="text-xs font-semibold text-indigo-600">All clients →</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($attention as $row)
            @php $c = $row['client']; @endphp
            <a href="{{ route('clients.show', $c) }}" class="flex items-center gap-3 rounded-xl border border-white bg-white p-3 shadow-sm hover:border-indigo-200 transition">
                <div class="h-10 w-10 rounded-lg flex items-center justify-center text-sm font-black
                    {{ $row['tone'] === 'green' ? 'bg-emerald-100 text-emerald-800' : ($row['tone'] === 'amber' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                    {{ $row['score'] }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-gray-900 truncate">{{ $c->name }}</p>
                    <p class="text-[10px] text-gray-500">{{ $row['label'] }} · Health score</p>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif
</section>
