<div
    x-data="calendarEventModal()"
    x-show="isOpen"
    @open-calendar-modal.window="openModal($event.detail)"
    @keydown.escape.window="closeModal()"
    class="relative z-[60]"
    style="display: none;"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true">
    <!-- Backdrop -->
    <div
        x-show="isOpen"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm"
        @click="closeModal()"></div>

    <!-- Modal Panel -->
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div
                x-show="isOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-100">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full sm:mx-0 sm:h-10 sm:w-10"
                            :class="iconBgClass">
                            <svg x-show="event.type === 'task'" class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <svg x-show="event.type === 'due'" class="h-6 w-6 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg x-show="event.type === 'renewal'" class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title" x-text="headerTitle"></h3>
                            <div class="mt-2 space-y-3">
                                <p class="text-sm text-gray-500" x-text="bodyTitle"></p>

                                <div class="flex items-center text-xs text-gray-400 bg-gray-50 p-2 rounded-lg">
                                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="font-medium">Due Date:</span>
                                    <span class="ml-1" x-text="event.start"></span>
                                </div>
                                <template x-if="event.status">
                                    <div class="flex items-center text-xs text-gray-400 bg-gray-50 p-2 rounded-lg">
                                        <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="font-medium">Status:</span>
                                        <span class="ml-1" x-text="event.status"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-gray-100">
                    <a
                        :href="viewUrl"
                        class="inline-flex w-full justify-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto transition-colors">
                        View Full Details
                    </a>
                    <button
                        type="button"
                        class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto transition-colors"
                        @click="closeModal()">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function calendarEventModal() {
        return {
            isOpen: false,
            event: {},
            get iconBgClass() {
                if (this.event.type === 'task') return 'bg-amber-100';
                if (this.event.type === 'renewal') return 'bg-emerald-100';
                return 'bg-rose-100';
            },
            get headerTitle() {
                return this.event.client_name || 'Event Details';
            },
            get bodyTitle() {
                return this.event.title_text || this.event.title || '';
            },
            get viewUrl() {
                if (!this.event.db_id) return '#';
                if (this.event.type === 'task') {
                    return `/tasks/${this.event.db_id}/edit`;
                }
                if (this.event.type === 'renewal') {
                    return `/personal-renewals/${this.event.db_id}/edit`;
                }
                return `/service-dues?highlight=${this.event.db_id}`;
            },
            openModal(data) {
                this.event = data;
                this.isOpen = true;
            },
            closeModal() {
                this.isOpen = false;
                this.event = {};
            }
        }
    }
</script>
