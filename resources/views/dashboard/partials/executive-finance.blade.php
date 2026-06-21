@php
    $showFinance = \App\Support\ModuleGate::hasFinanceModule(auth()->user());
    $hideHeader = $hideHeader ?? false;
@endphp
@if($showFinance)
<div class="{{ $hideHeader ? 'exec-widget__inner executive-finance' : 'exec-summary__card exec-summary__card--compact executive-finance' }}" data-demo-tour="executive-finance">
    @if(! $hideHeader)
    <div class="flex items-center justify-between gap-2 mb-3">
        <p class="exec-summary__label mb-0">Finance</p>
        <p class="text-[10px] text-gray-500">Tap a card to reveal figures</p>
    </div>
    @endif
    <div
        class="exec-finance-grid"
        x-data="executiveFinanceMask()"
        data-finance-snapshot-url="{{ route('dashboard.finance-snapshot') }}"
    >
        <div
            class="exec-finance-card exec-finance-card--wide"
            role="button"
            tabindex="0"
            @click="reveal('revenue')"
            @keydown.enter.prevent="reveal('revenue')"
            @keydown.space.prevent="reveal('revenue')"
            :class="isRevealed('revenue') && 'exec-finance-card--revealed'"
            :aria-label="isRevealed('revenue') ? 'Revenue this month revealed' : 'Revenue this month — tap to reveal'"
        >
            <p class="exec-finance-card__label">Revenue this month</p>
            <div class="flex flex-wrap justify-between gap-3 mt-1">
                <div>
                    <p class="text-[10px] text-gray-500">Target</p>
                    <p class="exec-finance-card__value" x-cloak x-text="display('revenue', 'target')">xxx</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-gray-500">Achieved</p>
                    <p class="exec-finance-card__value exec-finance-card__value--lg" x-cloak x-text="display('revenue', 'achieved')">xxx</p>
                </div>
            </div>
            <p class="text-[10px] text-gray-500 mt-2" x-show="isRevealed('revenue') && progressLabel()" x-cloak x-text="progressLabel()"></p>
        </div>

        <div
            class="exec-finance-card"
            role="button"
            tabindex="0"
            @click="reveal('efficiency')"
            @keydown.enter.prevent="reveal('efficiency')"
            @keydown.space.prevent="reveal('efficiency')"
            :class="isRevealed('efficiency') && 'exec-finance-card--revealed'"
        >
            <p class="exec-finance-card__label">Collection efficiency</p>
            <p class="exec-finance-card__value exec-finance-card__value--lg text-emerald-700" x-cloak x-text="display('efficiency', 'efficiency')">xxx</p>
        </div>

        <div
            class="exec-finance-card"
            role="button"
            tabindex="0"
            @click="reveal('outstanding')"
            @keydown.enter.prevent="reveal('outstanding')"
            @keydown.space.prevent="reveal('outstanding')"
            :class="isRevealed('outstanding') && 'exec-finance-card--revealed'"
        >
            <p class="exec-finance-card__label">Outstanding</p>
            <p class="exec-finance-card__value exec-finance-card__value--lg" x-cloak x-text="display('outstanding', 'outstanding')">xxx</p>
        </div>

        <div
            class="exec-finance-card"
            role="button"
            tabindex="0"
            @click="reveal('collected_mtd')"
            @keydown.enter.prevent="reveal('collected_mtd')"
            @keydown.space.prevent="reveal('collected_mtd')"
            :class="isRevealed('collected_mtd') && 'exec-finance-card--revealed'"
        >
            <p class="exec-finance-card__label">Collected MTD</p>
            <p class="exec-finance-card__value exec-finance-card__value--lg" x-cloak x-text="display('collected_mtd', 'collected_mtd')">xxx</p>
        </div>

        <div
            class="exec-finance-card"
            role="button"
            tabindex="0"
            @click="reveal('overdue')"
            @keydown.enter.prevent="reveal('overdue')"
            @keydown.space.prevent="reveal('overdue')"
            :class="isRevealed('overdue') && 'exec-finance-card--revealed'"
        >
            <p class="exec-finance-card__label">Overdue collections</p>
            <p class="exec-finance-card__value exec-finance-card__value--lg text-rose-700" x-cloak x-text="display('overdue', 'overdue')">xxx</p>
        </div>

        <div
            class="exec-finance-card"
            role="button"
            tabindex="0"
            @click="reveal('collected_today')"
            @keydown.enter.prevent="reveal('collected_today')"
            @keydown.space.prevent="reveal('collected_today')"
            :class="isRevealed('collected_today') && 'exec-finance-card--revealed'"
        >
            <p class="exec-finance-card__label">Collected today</p>
            <p class="exec-finance-card__value exec-finance-card__value--lg" x-cloak x-text="display('collected_today', 'collected_today')">xxx</p>
        </div>
    </div>
</div>
<script>
function executiveFinanceMask() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    return {
        figures: null,
        loading: false,
        loadError: false,
        revealed: {},

        snapshotUrl() {
            return this.$root.dataset.financeSnapshotUrl || '';
        },

        isRevealed(card) {
            return !!this.revealed[card];
        },

        display(card, field) {
            if (!this.isRevealed(card) || !this.figures) {
                return 'xxx';
            }

            return this.figures[field] ?? '—';
        },

        progressLabel() {
            if (!this.figures || this.figures.progress_percent === null || this.figures.progress_percent === undefined) {
                return '';
            }

            return this.figures.progress_percent + '% of target';
        },

        async ensureFigures() {
            if (this.figures || this.loading) {
                return this.figures;
            }

            this.loading = true;
            this.loadError = false;

            try {
                const res = await fetch(this.snapshotUrl(), {
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    credentials: 'same-origin',
                });

                if (!res.ok) {
                    throw new Error('snapshot failed');
                }

                this.figures = await res.json();
            } catch {
                this.loadError = true;
            } finally {
                this.loading = false;
            }

            return this.figures;
        },

        async reveal(card) {
            await this.ensureFigures();
            this.revealed[card] = true;
        },

        reset() {
            this.figures = null;
            this.loading = false;
            this.loadError = false;
            this.revealed = {};
        },
    };
}
</script>
@endif
