@php
    $rev = $missionControl['revenue'] ?? [];
    $showFinance = \App\Support\ModuleGate::hasFinanceModule(auth()->user());
    $financeLabels = [
        'target' => $rev['target_formatted'] ?? '—',
        'achieved' => $rev['achieved_formatted'] ?? '₹ 0',
        'efficiency' => ($rev['collection_efficiency'] ?? 0).'%',
        'outstanding' => $rev['outstanding_formatted'] ?? '₹ 0',
        'collected_mtd' => '₹ '.number_format($rev['collected_mtd'] ?? 0, 0),
        'overdue' => $summary['overdue_collections'] ?? '₹ 0',
        'collected_today' => '₹ '.number_format($rev['collected_today'] ?? 0, 0),
    ];
@endphp
@php $hideHeader = $hideHeader ?? false; @endphp
@if($showFinance)
<div class="{{ $hideHeader ? 'exec-widget__inner executive-finance' : 'exec-summary__card exec-summary__card--compact executive-finance' }}" data-demo-tour="executive-finance">
    @if(! $hideHeader)
    <div class="flex items-center justify-between gap-2 mb-3">
        <p class="exec-summary__label mb-0">Finance</p>
        <p class="text-[10px] text-gray-500">Tap a card to reveal figures</p>
    </div>
    @endif
    <div class="exec-finance-grid">
        <div class="exec-finance-card exec-finance-card--wide" x-data="{ revealed: false }" @click="revealed = true" :class="revealed && 'exec-finance-card--revealed'">
            <p class="exec-finance-card__label">Revenue this month</p>
            <div class="flex flex-wrap justify-between gap-3 mt-1">
                <div>
                    <p class="text-[10px] text-gray-500">Target</p>
                    <p class="exec-finance-card__value" x-text="revealed ? @js($financeLabels['target']) : 'xxx'"></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-gray-500">Achieved</p>
                    <p class="exec-finance-card__value exec-finance-card__value--lg" x-text="revealed ? @js($financeLabels['achieved']) : 'xxx'"></p>
                </div>
            </div>
            @if(($rev['progress_percent'] ?? null) !== null)
            <p class="text-[10px] text-gray-500 mt-2" x-show="revealed">{{ $rev['progress_percent'] }}% of target</p>
            @endif
        </div>

        <div class="exec-finance-card" x-data="{ revealed: false }" @click="revealed = true" :class="revealed && 'exec-finance-card--revealed'">
            <p class="exec-finance-card__label">Collection efficiency</p>
            <p class="exec-finance-card__value exec-finance-card__value--lg text-emerald-700" x-text="revealed ? @js($financeLabels['efficiency']) : 'xxx'"></p>
        </div>

        <div class="exec-finance-card" x-data="{ revealed: false }" @click="revealed = true" :class="revealed && 'exec-finance-card--revealed'">
            <p class="exec-finance-card__label">Outstanding</p>
            <p class="exec-finance-card__value exec-finance-card__value--lg" x-text="revealed ? @js($financeLabels['outstanding']) : 'xxx'"></p>
        </div>

        <div class="exec-finance-card" x-data="{ revealed: false }" @click="revealed = true" :class="revealed && 'exec-finance-card--revealed'">
            <p class="exec-finance-card__label">Collected MTD</p>
            <p class="exec-finance-card__value exec-finance-card__value--lg" x-text="revealed ? @js($financeLabels['collected_mtd']) : 'xxx'"></p>
        </div>

        <div class="exec-finance-card" x-data="{ revealed: false }" @click="revealed = true" :class="revealed && 'exec-finance-card--revealed'">
            <p class="exec-finance-card__label">Overdue collections</p>
            <p class="exec-finance-card__value exec-finance-card__value--lg text-rose-700" x-text="revealed ? @js($financeLabels['overdue']) : 'xxx'"></p>
        </div>

        <div class="exec-finance-card" x-data="{ revealed: false }" @click="revealed = true" :class="revealed && 'exec-finance-card--revealed'">
            <p class="exec-finance-card__label">Collected today</p>
            <p class="exec-finance-card__value exec-finance-card__value--lg" x-text="revealed ? @js($financeLabels['collected_today']) : 'xxx'"></p>
        </div>
    </div>
</div>
@endif
