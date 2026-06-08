@php
    $fo = $firmOverview ?? [];
@endphp
@php $showFinance = $fo['hasFinance'] ?? false; @endphp
<div class="space-y-6 w-full min-w-0">
    @if($showFinance)
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 w-full">
        <div class="glass-card p-5 border-l-4 border-[var(--premium-navy-muted)]">
            <p class="kpi-label">MTD Invoiced</p>
            <p class="kpi-value text-2xl">₹ {{ number_format($fo['mtdInvoiced'] ?? 0, 0) }}</p>
        </div>
        <div class="glass-card p-5 border-l-4 border-emerald-600">
            <p class="kpi-label">MTD Collected</p>
            <p class="kpi-value text-2xl text-emerald-800">₹ {{ number_format($fo['mtdCollected'] ?? 0, 0) }}</p>
        </div>
        <div class="glass-card p-5 border-l-4 border-amber-500">
            <p class="kpi-label">Outstanding</p>
            <p class="kpi-value text-2xl text-amber-800">₹ {{ number_format(max(0, $fo['outstanding'] ?? 0), 0) }}</p>
        </div>
        <div class="glass-card p-5 border-l-4 border-rose-500">
            <p class="kpi-label">Unbilled queue</p>
            <p class="kpi-value text-2xl">{{ $fo['unbilledQueue'] ?? 0 }}</p>
        </div>
    </div>
    @endif

    <div class="glass-card p-6 w-full min-w-0">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h3 class="mission-control__heading text-lg">Firm alerts</h3>
            <form method="POST" action="{{ route('firm-alerts.scan') }}">
                @csrf
                <button type="submit" class="text-xs font-semibold text-[var(--premium-navy)] hover:underline">Run scan now</button>
            </form>
        </div>
        <ul class="space-y-3 text-sm max-h-64 overflow-y-auto">
            @forelse(($fo['firmAlerts'] ?? collect()) as $alert)
            <li class="flex gap-3 items-start border-b border-[var(--premium-border)] pb-3 last:border-0">
                <span class="flex-shrink-0 mt-0.5 h-2 w-2 rounded-full {{ $alert->severity === 'critical' ? 'bg-red-500' : ($alert->severity === 'warning' ? 'bg-amber-500' : 'bg-slate-400') }}"></span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-[var(--premium-text)]">{{ $alert->title }}</p>
                    <p class="text-[var(--premium-muted)] text-xs mt-0.5">{{ $alert->message }}</p>
                    @if($alert->client)
                    <a href="{{ route('clients.show', $alert->client) }}" class="text-xs font-medium text-[var(--premium-navy)] hover:underline">View client →</a>
                    @endif
                </div>
                <form method="POST" action="{{ route('firm-alerts.dismiss', $alert) }}" class="flex-shrink-0">
                    @csrf
                    <button type="submit" class="text-xs text-[var(--premium-muted)] hover:text-[var(--premium-text)]">Dismiss</button>
                </form>
            </li>
            @empty
            <li class="text-[var(--premium-muted)]">No open alerts. Run a scan or wait for the daily job.</li>
            @endforelse
        </ul>
    </div>

    @if(($fo['atRiskCompliance'] ?? collect())->isNotEmpty())
    <div class="glass-card p-6 border border-amber-100 w-full min-w-0">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-[var(--premium-text)]">At-risk compliance (next 14 days)</h3>
            <a href="{{ route('service-dues.index') }}" class="text-xs font-semibold text-[var(--premium-navy)]">Reminders →</a>
        </div>
        <ul class="space-y-2 text-sm">
            @foreach($fo['atRiskCompliance'] as $risk)
            <li class="flex justify-between gap-4 flex-wrap">
                <span>
                    <a href="{{ route('clients.show', $risk->client) }}" class="font-medium text-[var(--premium-navy)] hover:underline">{{ $risk->client?->name }}</a>
                    <span class="text-[var(--premium-muted)]"> · {{ $risk->service?->name }}</span>
                </span>
                <span class="flex-shrink-0">
                    @include('partials.status-badge', [
                        'status' => ucfirst($risk->level),
                        'type' => $risk->level === 'high' ? 'danger' : 'warning',
                    ])
                    <span class="ml-1 text-xs text-[var(--premium-muted)]">{{ $risk->score }}</span>
                </span>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 w-full">
        <div class="glass-card p-6 min-w-0">
            <h3 class="font-bold text-[var(--premium-text)] mb-4">Risk indicators</h3>
            <ul class="space-y-2 text-sm">
                <li class="flex justify-between gap-2"><span>Overdue compliance dues</span><strong class="text-red-600">{{ $fo['overdueCompliance'] ?? 0 }}</strong></li>
                @if($showFinance)
                <li class="flex justify-between gap-2"><span>Overdue invoices</span><strong class="text-red-600">{{ $fo['overdueInvoices'] ?? 0 }}</strong></li>
                @endif
                <li class="flex justify-between gap-2"><span>Open tasks</span><strong>{{ $fo['openTasks'] ?? 0 }}</strong></li>
                <li class="flex justify-between gap-2"><span>Overdue tasks</span><strong class="text-amber-600">{{ $fo['overdueTasks'] ?? 0 }}</strong></li>
            </ul>
            @if($showFinance)
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('billing.index') }}" class="text-sm font-medium text-[var(--premium-navy)]">Billing queue →</a>
                <a href="{{ route('reports.financial') }}" class="text-sm font-medium text-[var(--premium-navy)]">Reports →</a>
            </div>
            @endif
        </div>

        <div class="glass-card p-6 min-w-0">
            <h3 class="font-bold text-[var(--premium-text)] mb-4">Staff workload (open tasks)</h3>
            <ul class="space-y-2 text-sm">
                @forelse(($fo['staffLoad'] ?? collect()) as $member)
                <li class="flex justify-between gap-2">
                    <span class="truncate">{{ $member->name }} <span class="text-[var(--premium-muted)]">({{ $member->role }})</span></span>
                    <strong class="tabular-nums">{{ $member->open_tasks_count }}</strong>
                </li>
                @empty
                <li class="text-[var(--premium-muted)]">No staff with open tasks.</li>
                @endforelse
            </ul>
        </div>
    </div>

    @if(($fo['branchRevenue'] ?? collect())->isNotEmpty())
    <div class="glass-card p-6 w-full min-w-0 overflow-x-auto">
        <h3 class="font-bold text-[var(--premium-text)] mb-4">Branch revenue (MTD)</h3>
        <table class="min-w-full text-sm">
            <thead><tr class="text-left text-[var(--premium-muted)]"><th class="pb-2">Branch</th><th class="pb-2 text-right">Amount</th></tr></thead>
            <tbody>
                @foreach($fo['branchRevenue'] as $row)
                <tr class="border-t border-[var(--premium-border)]">
                    <td class="py-2">{{ $row->branch?->name ?? 'Unassigned' }}</td>
                    <td class="py-2 text-right font-medium tabular-nums">₹ {{ number_format($row->total, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
