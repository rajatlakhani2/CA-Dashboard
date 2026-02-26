<div
    x-data="searchModal()"
    x-init="init()"
    @keydown.window.prevent.cmd.k="openModal()"
    @keydown.window.prevent.ctrl.k="openModal()"
    @keydown.escape="closeModal()"
    class="relative z-50"
    style="display: none;"
    x-show="isOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeModal()"></div>

    <!-- Modal Panel -->
    <div class="fixed inset-0 z-10 overflow-y-auto p-4 sm:p-6 md:p-20">
        <div
            class="mx-auto max-w-2xl transform divide-y divide-gray-100 overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black ring-opacity-5 transition-all"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">
            <div class="relative">
                <!-- Search Icon -->
                <svg class="pointer-events-none absolute top-3.5 left-4 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>

                <!-- Input -->
                <input
                    x-ref="searchInput"
                    type="text"
                    class="h-12 w-full border-0 bg-transparent pl-11 pr-4 text-gray-800 placeholder-gray-400 focus:ring-0 sm:text-sm"
                    placeholder="Search clients, tasks, invoices..."
                    @input.debounce.300ms="fetchResults()"
                    x-model="query">
            </div>

            <!-- Results List -->
            <ul class="max-h-96 scroll-py-3 overflow-y-auto p-3" id="options" role="listbox" x-show="results.length > 0">
                <template x-for="(group, category) in groupedResults" :key="category">
                    <li>
                        <h2 class="bg-gray-50 px-3 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wide rounded-lg mb-2" x-text="category"></h2>
                        <ul class="text-sm text-gray-700 space-y-1 mb-2">
                            <template x-for="item in group" :key="item.url">
                                <li
                                    class="group flex cursor-pointer select-none items-center rounded-xl p-3 hover:bg-indigo-600 hover:text-white transition-colors duration-150"
                                    role="option"
                                    tabindex="-1"
                                    @click="window.location.href = item.url">
                                    <!-- Dynamic Icon based on item.icon -->
                                    <div class="flex h-8 w-8 flex-none items-center justify-center rounded-lg bg-gray-100 group-hover:bg-white/20 group-hover:text-white transition-colors">
                                        <i :class="'text-gray-500 group-hover:text-white fas fa-' + (item.icon || 'circle')"></i>
                                    </div>
                                    <div class="ml-4 flex-auto">
                                        <p class="font-medium" x-text="item.title"></p>
                                        <p class="text-xs text-gray-400 group-hover:text-indigo-200" x-show="item.subtitle" x-text="item.subtitle"></p>
                                    </div>
                                    <svg class="ml-3 h-5 w-5 flex-none text-gray-400 group-hover:text-white opacity-0 group-hover:opacity-100 transition-opacity" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </li>
                            </template>
                        </ul>
                    </li>
                </template>
            </ul>

            <!-- Empty State -->
            <div class="py-14 px-6 text-center text-sm sm:px-14" x-show="query.length > 1 && results.length === 0 && !loading">
                <svg class="mx-auto h-6 w-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <p class="mt-4 font-semibold text-gray-900">No results found</p>
                <p class="mt-2 text-gray-500">No clients, tasks, or pages found for this search term.</p>
            </div>

            <!-- Helper Text -->
            <div class="bg-gray-50 px-4 py-3 border-t border-gray-100 flex justify-between items-center text-xs text-gray-500 rounded-b-xl" x-show="!isOpen || query.length === 0">
                <span>Press <kbd class="font-sans font-semibold text-gray-900 border border-gray-200 rounded px-1">Esc</kbd> to close</span>
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
            get groupedResults() {
                return this.results.reduce((groups, item) => {
                    const category = item.category;
                    if (!groups[category]) {
                        groups[category] = [];
                    }
                    groups[category].push(item);
                    return groups;
                }, {});
            },
            openModal() {
                this.isOpen = true;
                this.$nextTick(() => {
                    this.$refs.searchInput.focus();
                });
            },
            closeModal() {
                this.isOpen = false;
                this.query = '';
                this.results = [];
            },
            init() {
                // FontAwesome fallback if not loaded
                if (!document.getElementById('fa-cdn')) {
                    const link = document.createElement('link');
                    link.id = 'fa-cdn';
                    link.rel = 'stylesheet';
                    link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css';
                    document.head.appendChild(link);
                }
            },
            fetchResults() {
                if (this.query.length < 2) {
                    this.results = [];
                    return;
                }
                this.loading = true;
                fetch(`{{ route('search.global') }}?query=${this.query}`)
                    .then(response => response.json())
                    .then(data => {
                        this.results = data;
                        this.loading = false;
                    });
            }
        }
    }
</script>