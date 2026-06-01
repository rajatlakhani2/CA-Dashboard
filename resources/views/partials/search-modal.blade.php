<div
    x-data="searchModal()"
    x-init="init()"
    @keydown.window.prevent.cmd.k="openModal()"
    @keydown.window.prevent.ctrl.k="openModal()"
    @keydown.escape.window="closeModal()"
    class="relative z-[60]"
    style="display: none;"
    x-show="isOpen"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeModal()"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto p-4 sm:p-6 md:p-20" @keydown.arrow-down.prevent="moveActive(1)" @keydown.arrow-up.prevent="moveActive(-1)" @keydown.enter.prevent="goToActive()">
        <div
            class="mx-auto max-w-2xl transform overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10 transition-all"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.stop>
            <div class="relative border-b border-slate-100">
                <svg class="pointer-events-none absolute top-3.5 left-4 h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
                <input
                    x-ref="searchInput"
                    type="text"
                    class="h-12 w-full border-0 bg-transparent pl-11 pr-4 text-slate-900 placeholder-slate-400 focus:ring-0 sm:text-sm"
                    placeholder="Search clients, tasks, invoices…"
                    autocomplete="off"
                    @input.debounce.250ms="fetchResults()"
                    x-model="query">
                <div x-show="loading" class="absolute right-4 top-3.5">
                    <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>
            </div>

            <div class="max-h-[min(24rem,50vh)] overflow-y-auto p-2" x-show="flatResults.length > 0">
                <template x-for="(group, category) in groupedResults" :key="category">
                    <div class="mb-2">
                        <p class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400" x-text="category"></p>
                        <template x-for="item in group" :key="item.url">
                            <button
                                type="button"
                                class="w-full flex items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition-colors"
                                :class="isActive(item) ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-100'"
                                @click="navigate(item.url)"
                                @mouseenter="setActiveByUrl(item.url)">
                                <span class="flex h-8 w-8 flex-none items-center justify-center rounded-lg text-xs font-bold"
                                    :class="isActive(item) ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'"
                                    x-text="(item.title || '?').charAt(0).toUpperCase()"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-medium truncate" x-text="item.title"></span>
                                    <span class="block text-xs truncate" :class="isActive(item) ? 'text-indigo-100' : 'text-slate-400'" x-show="item.subtitle" x-text="item.subtitle"></span>
                                </span>
                                <kbd class="hidden sm:inline text-[10px] opacity-60" x-show="isActive(item)">↵</kbd>
                            </button>
                        </template>
                    </div>
                </template>
            </div>

            <div class="py-12 px-6 text-center text-sm" x-show="query.length >= 2 && flatResults.length === 0 && !loading">
                <p class="font-semibold text-slate-900">No results</p>
                <p class="mt-1 text-slate-500">Try a client name, PAN, invoice number, or task title.</p>
            </div>

            <div class="py-10 px-6 text-center text-sm text-slate-500" x-show="query.length < 2 && !loading">
                <p class="font-medium text-slate-700">Quick jump</p>
                <p class="mt-2 text-xs">Type at least 2 characters — clients, tasks, invoices, and pages.</p>
            </div>

            <div class="bg-slate-50 px-4 py-2.5 border-t border-slate-100 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-slate-500">
                <span><kbd class="rounded border border-slate-200 bg-white px-1 font-mono">↑</kbd> <kbd class="rounded border border-slate-200 bg-white px-1 font-mono">↓</kbd> navigate</span>
                <span><kbd class="rounded border border-slate-200 bg-white px-1 font-mono">↵</kbd> open</span>
                <span><kbd class="rounded border border-slate-200 bg-white px-1 font-mono">esc</kbd> close</span>
            </div>
        </div>
    </div>
</div>

<script>
    function searchModal() {
        return {
            isOpen: false,
            query: '',
            results: [],
            loading: false,
            activeIndex: 0,
            get flatResults() {
                return this.results;
            },
            get groupedResults() {
                return this.results.reduce((groups, item) => {
                    const category = item.category || 'Results';
                    if (!groups[category]) {
                        groups[category] = [];
                    }
                    groups[category].push(item);
                    return groups;
                }, {});
            },
            openModal() {
                this.isOpen = true;
                this.activeIndex = 0;
                this.$nextTick(() => this.$refs.searchInput?.focus());
            },
            closeModal() {
                if (!this.isOpen) {
                    return;
                }
                this.isOpen = false;
                this.query = '';
                this.results = [];
                this.activeIndex = 0;
            },
            init() {},
            fetchResults() {
                if (this.query.length < 2) {
                    this.results = [];
                    this.activeIndex = 0;
                    return;
                }
                this.loading = true;
                fetch(`{{ route('search.global') }}?query=${encodeURIComponent(this.query)}`)
                    .then((response) => response.json())
                    .then((data) => {
                        this.results = data;
                        this.activeIndex = data.length ? 0 : -1;
                        this.loading = false;
                    })
                    .catch(() => {
                        this.loading = false;
                    });
            },
            isActive(item) {
                const idx = this.flatResults.findIndex((r) => r.url === item.url);
                return idx === this.activeIndex;
            },
            setActiveByUrl(url) {
                const idx = this.flatResults.findIndex((r) => r.url === url);
                if (idx >= 0) {
                    this.activeIndex = idx;
                }
            },
            moveActive(delta) {
                if (!this.flatResults.length) {
                    return;
                }
                const next = this.activeIndex + delta;
                if (next < 0) {
                    this.activeIndex = this.flatResults.length - 1;
                } else if (next >= this.flatResults.length) {
                    this.activeIndex = 0;
                } else {
                    this.activeIndex = next;
                }
            },
            goToActive() {
                const item = this.flatResults[this.activeIndex];
                if (item?.url) {
                    this.navigate(item.url);
                }
            },
            navigate(url) {
                window.location.href = url;
            },
        };
    }
</script>
