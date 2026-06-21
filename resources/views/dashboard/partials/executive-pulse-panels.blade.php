@php
    $risks = $mc['risk_alerts'] ?? [];
    $insights = $mc['ai_insights'] ?? [];
    $team = $mc['team_workload'] ?? collect();
    $attention = $mc['clients_needing_attention'] ?? collect();
    $monthlyDeadlines = $mc['monthly_deadlines'] ?? [];
    $managesFirm = auth()->user()?->managesFirmModules() ?? false;
@endphp
<div class="space-y-3 exec-pulse-fill">
    @if(count($risks) > 0 || count($insights) > 0)
    <div>
        <div class="flex items-center justify-between gap-2 mb-1.5">
            <p class="exec-summary__label mb-0">Action needed</p>
            <a href="{{ route('activity.index') }}" class="text-[10px] font-semibold text-indigo-600">Pulse →</a>
        </div>
        <ul class="space-y-1">
            @foreach(array_slice($risks, 0, 3) as $alert)
            <li>
                <a href="{{ $alert['url'] }}" class="exec-summary__row exec-summary__row--risk py-1.5">
                    <span class="truncate text-xs">{{ $alert['label'] }}</span>
                    <span class="font-black tabular-nums text-xs">{{ $alert['count'] }}</span>
                </a>
            </li>
            @endforeach
            @foreach(array_slice($insights, 0, 2) as $line)
            <li class="exec-summary__insight text-xs py-0.5">
                <span class="text-violet-500">•</span><span>{{ $line }}</span>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($team->isNotEmpty() && $managesFirm)
    <div class="{{ (count($risks) > 0 || count($insights) > 0) ? 'pt-2 border-t border-slate-100' : '' }}">
        <div class="flex justify-between items-center mb-1.5">
            <p class="exec-summary__label mb-0">Team workload</p>
            <a href="{{ route('workload.index') }}" class="text-[10px] font-semibold text-indigo-600">Planner →</a>
        </div>
        <div class="flex flex-wrap gap-1">
            @foreach($team->take(6) as $member)
            <div class="exec-summary__chip exec-summary__chip--sm {{ $member['status'] === 'overloaded' ? 'exec-summary__chip--hot' : '' }}">
                <span class="truncate max-w-[5rem]">{{ $member['name'] }}</span>
                <span class="font-black">{{ $member['open_tasks'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($attention->isNotEmpty() && $managesFirm)
    <div class="pt-2 border-t border-slate-100" data-demo-tour="clients-attention">
        <p class="exec-summary__label mb-1.5">Clients needing attention</p>
        <ul class="space-y-1">
            @foreach($attention->take(4) as $row)
            @php $c = $row['client']; @endphp
            <li>
                <a href="{{ route('clients.show', $c) }}" class="exec-summary__row py-1.5">
                    <span class="exec-summary__score exec-summary__score--{{ $row['tone'] }} text-[10px]">{{ $row['score'] }}</span>
                    <span class="text-xs font-semibold truncate">{{ $c->name }}</span>
                </a>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="pt-2 border-t border-slate-100" x-data="{ openKey: null }">
        <div class="flex items-center justify-between gap-2 mb-2">
            <p class="exec-summary__label mb-0">Deadline services · next month</p>
            <a href="{{ route('service-dues.index') }}" class="text-[10px] font-semibold text-indigo-600">All dues →</a>
        </div>
        @if(count($monthlyDeadlines) > 0)
        <ul class="space-y-1.5">
            @foreach($monthlyDeadlines as $deadline)
            <li class="rounded-lg border border-slate-200 bg-white overflow-hidden">
                <button type="button"
                    @click="openKey = openKey === @js($deadline['key']) ? null : @js($deadline['key'])"
                    class="w-full text-left px-3 py-2 hover:bg-slate-50 transition">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-gray-900 truncate">{{ $deadline['service_name'] }}</p>
                            <p class="text-[10px] text-gray-500">Due {{ $deadline['due_label'] }}</p>
                        </div>
                        <span class="text-[10px] font-bold text-indigo-600 shrink-0" x-text="openKey === @js($deadline['key']) ? '▲' : '▼'"></span>
                    </div>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2 text-[10px]">
                        <span class="font-semibold text-gray-700">{{ $deadline['total'] }} clients</span>
                        <span class="text-emerald-700 font-bold">{{ $deadline['completed'] }} filed</span>
                        <span class="text-rose-700 font-bold">{{ $deadline['pending'] }} pending</span>
                    </div>
                </button>
                <div x-show="openKey === @js($deadline['key'])" x-transition class="border-t border-slate-100 bg-slate-50/80 px-3 py-2">
                    <ul class="space-y-1 max-h-32 overflow-y-auto">
                        @foreach($deadline['clients'] as $client)
                        <li>
                            <a href="{{ $client['url'] }}" class="flex items-center justify-between gap-2 text-xs rounded-md px-2 py-1 hover:bg-white">
                                <span class="font-medium text-gray-900 truncate">{{ $client['name'] }}</span>
                                <span class="shrink-0 font-bold {{ $client['status'] === 'Completed' ? 'text-emerald-700' : ($client['status'] === 'Overdue' ? 'text-rose-700' : 'text-amber-700') }}">
                                    {{ $client['status'] === 'Completed' ? 'Filed' : $client['status'] }}
                                </span>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </li>
            @endforeach
        </ul>
        @else
        <p class="text-xs text-gray-500 py-3 text-center">No statutory deadlines in the next month.</p>
        @endif
    </div>
</div>
