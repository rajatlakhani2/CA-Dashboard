@php
    $rev = $missionControl['revenue'] ?? [];
    $canSeeFirmFinance = auth()->user()?->managesFirmModules();
    $masked = '₹ •••••';
@endphp
<div class="space-y-6">
    @unless($canSeeFirmFinance)
    <p class="text-xs text-slate-500 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">Financial figures are visible to partners and managers only. Tap Executive Summary finance widgets after sign-in for your allowed view.</p>
    @endunless
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div class="glass-card p-6 md:col-span-2">
            <p class="glass-section-title">Revenue this month</p>
            <div class="flex flex-wrap items-end justify-between gap-4 mt-2">
                <div>
                    <p class="text-xs text-gray-500">Target</p>
                    <p class="text-lg font-bold text-gray-700">{{ $canSeeFirmFinance ? ($rev['target_formatted'] ?? '—') : $masked }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500">Achieved (invoiced)</p>
                    <p class="text-2xl sm:text-3xl font-black text-indigo-700">{{ $canSeeFirmFinance ? ($rev['achieved_formatted'] ?? '₹ 0') : $masked }}</p>
                </div>
            </div>
            @if($canSeeFirmFinance && $rev['progress_percent'] !== null)
            <div class="mt-4">
                <div class="flex justify-between text-xs font-semibold text-gray-600 mb-1">
                    <span>Progress</span>
                    <span>{{ $rev['progress_percent'] }}%</span>
                </div>
                <div class="h-3 rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500 transition-all duration-700"
                         style="width: {{ min(100, $rev['progress_percent']) }}%"></div>
                </div>
            </div>
            @elseif($canSeeFirmFinance)
            <p class="mt-3 text-xs text-gray-500">Set <code class="bg-gray-100 px-1 rounded">monthly_revenue_target</code> in Settings to track progress.</p>
            @endif
        </div>
        <div class="glass-card p-6">
            <p class="glass-section-title">Collection efficiency</p>
            <p class="text-4xl font-black text-emerald-600 mt-2">{{ $canSeeFirmFinance ? ($rev['collection_efficiency'] ?? 0).'%' : '—' }}</p>
            <p class="text-xs text-gray-500 mt-1">Collected vs invoiced this month</p>
            @if($canSeeFirmFinance)
            <div class="mt-3 h-2 rounded-full bg-gray-100">
                <div class="h-full rounded-full bg-emerald-500" style="width: {{ min(100, $rev['collection_efficiency'] ?? 0) }}%"></div>
            </div>
            @endif
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="glass-card p-5 kpi-emerald">
            <p class="glass-section-title">Outstanding</p>
            <p class="text-2xl font-black text-emerald-700">{{ $canSeeFirmFinance ? ($rev['outstanding_formatted'] ?? $summary['outstanding_fees'] ?? '₹ 0') : $masked }}</p>
            <a href="{{ route('invoices.index') }}" class="text-xs font-semibold text-indigo-600 mt-2 inline-block">Invoices →</a>
        </div>
        <div class="glass-card p-5 kpi-violet">
            <p class="glass-section-title">Collected MTD</p>
            <p class="text-2xl font-black text-violet-700">{{ $canSeeFirmFinance ? '₹ '.number_format($rev['collected_mtd'] ?? 0, 0) : $masked }}</p>
            <a href="{{ route('payments.index') }}" class="text-xs font-semibold text-indigo-600 mt-2 inline-block">Payments →</a>
        </div>
        <div class="glass-card p-5 kpi-rose">
            <p class="glass-section-title">Overdue collections</p>
            <p class="text-2xl font-black text-rose-700">{{ $canSeeFirmFinance ? ($summary['overdue_collections'] ?? '₹ 0') : $masked }}</p>
            <a href="{{ route('collections.index') }}" class="text-xs font-semibold text-indigo-600 mt-2 inline-block">Collections →</a>
        </div>
    </div>
</div>
