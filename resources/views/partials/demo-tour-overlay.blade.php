@php
    $demoTour = $demoTour ?? ['show' => false, 'isDemo' => false, 'steps' => [], 'welcome' => []];
    $welcome = $demoTour['welcome'] ?? [];
@endphp
@if(!empty($demoTour['show']) || !empty($demoTour['isDemo']))
<div x-data="demoTourWelcome()" x-init="init()" id="demo-tour-root">
    @if(!empty($demoTour['show']))
    <div x-show="welcomeOpen" x-cloak class="fixed inset-0 z-[200]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm" @click="skip()"></div>
        <div class="fixed inset-0 z-10 flex items-center justify-center p-4 pointer-events-none">
            <div class="demo-welcome-card pointer-events-auto w-full max-w-lg rounded-2xl shadow-2xl p-8 relative overflow-hidden" @click.stop>
                <div class="absolute -top-16 -right-16 h-48 w-48 rounded-full bg-white/10"></div>
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-indigo-200">Welcome to Vouchex</p>
                <h2 class="mt-2 text-2xl font-black leading-tight">{{ $welcome['title'] ?? 'Run your business from one workspace' }}</h2>
                <p class="mt-3 text-sm text-indigo-100 leading-relaxed">
                    {{ $welcome['subtitle'] ?? 'A guided walkthrough of real workflows — from morning WhatsApp to evening wrap-up.' }}
                </p>
                <ul class="mt-5 space-y-2 text-sm text-indigo-50">
                    @foreach(($welcome['bullets'] ?? []) as $bullet)
                    <li class="flex gap-2"><span>✓</span> {{ $bullet }}</li>
                    @endforeach
                </ul>
                <div class="mt-8 flex flex-col-reverse sm:flex-row sm:justify-between gap-3">
                    <button type="button" @click="skip()" class="text-sm font-semibold text-indigo-200 hover:text-white underline underline-offset-2">
                        Skip — explore on my own
                    </button>
                    <button type="button" @click="startTour()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-bold text-indigo-900 shadow-lg hover:bg-indigo-50">
                        Start demo →
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[210]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm"></div>
        <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
            <div class="demo-welcome-card w-full max-w-md rounded-2xl shadow-2xl p-6 relative" @click.stop>
                <template x-if="activeModal === 'whatsapp-morning'">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-200">Morning · 10:00 AM</p>
                        <h3 class="mt-1 text-lg font-black">WhatsApp task reminder</h3>
                        <div class="mt-4 rounded-xl bg-[#075e54] p-3 text-left text-xs leading-relaxed text-white font-mono whitespace-pre-line shadow-inner">*Daily Reminder: Your Upcoming Tasks*
Hello Priya,
Here are your pending tasks due within the next 7 days:

1. Client proposal — Acme Corp (Due: 08 Jun)
2. Review contract — Brightline Ltd (Due: 10 Jun)
3. Follow-up call — Nova Systems (Due: 12 Jun)

Please prioritize these tasks. Have a productive day!</div>
                        <p class="mt-3 text-xs text-indigo-100">CEOs and managers receive a firm-wide summary with every team member's list.</p>
                    </div>
                </template>
                <template x-if="activeModal === 'whatsapp-evening'">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-200">Evening · 6:00 PM + 7:00 PM digest</p>
                        <h3 class="mt-1 text-lg font-black">End-of-day accountability</h3>
                        <div class="mt-4 rounded-xl bg-[#075e54] p-3 text-left text-xs leading-relaxed text-white font-mono whitespace-pre-line shadow-inner">📋 *Daily Task Digest*

Hi Priya,
• Overdue: 1
• Due today: 2
• Due tomorrow: 1

Open My Day: app.kuhu.org.in/my-day</div>
                        <p class="mt-3 text-xs text-indigo-100">Morning and evening reminder times are configurable in automation settings.</p>
                    </div>
                </template>
                <div class="mt-6 flex justify-between items-center gap-3">
                    <button type="button" @click="skip()" class="text-xs font-semibold text-indigo-200 hover:text-white underline">Skip tour</button>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] text-indigo-200" x-text="progressLabel()"></span>
                        <button type="button" @click="nextFromModal()" class="rounded-xl bg-white px-4 py-2 text-sm font-bold text-indigo-900 hover:bg-indigo-50" x-text="modalNextLabel()"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($demoTour['isDemo']))
    <button type="button" @click="restartTour()" class="demo-tour-fab inline-flex items-center gap-2 rounded-full bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg hover:bg-indigo-700">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Take a tour
    </button>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>
<script>
function demoTourWelcome() {
    const steps = @json($demoTour['steps'] ?? []);
    const dismissUrl = @json($demoTour['dismissUrl'] ?? '');
    const completeUrl = @json($demoTour['completeUrl'] ?? '');
    const autoShow = @json(!empty($demoTour['show']));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const ACTIVE_KEY = 'vouchex_demo_tour_active';
    const STEP_KEY = 'vouchex_demo_tour_step';
    let driverInstance = null;

    return {
        welcomeOpen: autoShow,
        modalOpen: false,
        activeModal: null,
        stepIndex: 0,

        init() {
            if (sessionStorage.getItem(ACTIVE_KEY) !== '1') return;
            const idx = parseInt(sessionStorage.getItem(STEP_KEY) || '0', 10);
            this.stepIndex = idx;
            this.welcomeOpen = false;
            setTimeout(() => this.runStep(idx), 400);
        },

        progressLabel() {
            return (this.stepIndex + 1) + ' of ' + steps.length;
        },

        modalNextLabel() {
            return this.stepIndex >= steps.length - 1 ? 'Finish' : 'Next →';
        },

        restartTour() {
            sessionStorage.setItem(ACTIVE_KEY, '1');
            sessionStorage.setItem(STEP_KEY, '0');
            this.stepIndex = 0;
            this.welcomeOpen = true;
        },

        startTour() {
            sessionStorage.setItem(ACTIVE_KEY, '1');
            sessionStorage.setItem(STEP_KEY, '0');
            this.stepIndex = 0;
            this.welcomeOpen = false;
            this.runStep(0);
        },

        skip() {
            this.welcomeOpen = false;
            this.modalOpen = false;
            this.destroyDriver();
            sessionStorage.removeItem(ACTIVE_KEY);
            sessionStorage.removeItem(STEP_KEY);
            fetch(dismissUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
        },

        finishTour(completed = true) {
            this.modalOpen = false;
            this.destroyDriver();
            sessionStorage.removeItem(ACTIVE_KEY);
            sessionStorage.removeItem(STEP_KEY);
            fetch(completed ? completeUrl : dismissUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
        },

        nextStep() {
            const next = this.stepIndex + 1;
            if (next >= steps.length) {
                this.finishTour(true);
                return;
            }
            this.stepIndex = next;
            sessionStorage.setItem(STEP_KEY, String(next));
            this.runStep(next);
        },

        nextFromModal() {
            this.modalOpen = false;
            this.nextStep();
        },

        runStep(index) {
            const step = steps[index];
            if (!step) {
                this.finishTour(true);
                return;
            }

            this.stepIndex = index;
            sessionStorage.setItem(STEP_KEY, String(index));

            if (step.type === 'modal') {
                this.destroyDriver();
                this.activeModal = step.modal;
                this.modalOpen = true;
                return;
            }

            if (step.url && !this.onPage(step.url)) {
                window.location.href = step.url;
                return;
            }

            if (step.tab) {
                const tabBtn = document.querySelector('[data-dashboard-tab="' + step.tab + '"]');
                if (tabBtn) tabBtn.click();
            }

            this.waitForElement(step.element, () => this.spotlight(step));
        },

        spotlight(step) {
            if (!window.driver?.js?.driver) {
                alert('Tour could not load. Refresh and try again.');
                return;
            }

            const el = document.querySelector(step.element);
            if (!el) {
                this.nextStep();
                return;
            }

            this.destroyDriver();
            let closed = false;

            driverInstance = window.driver.js.driver({
                showProgress: true,
                progressText: '@{{current}} of @{{total}}',
                nextBtnText: this.stepIndex >= steps.length - 1 ? 'Finish' : 'Next →',
                prevBtnText: '← Back',
                showButtons: ['next', 'close'],
                popoverClass: 'demo-tour-popover',
                stagePadding: 8,
                overlayOpacity: 0.65,
                steps: [{
                    element: step.element,
                    popover: {
                        title: step.title,
                        description: step.description,
                        side: step.side || 'bottom',
                        align: 'start',
                    },
                }],
                onNextClick: () => {
                    driverInstance?.destroy();
                    this.nextStep();
                },
                onCloseClick: () => {
                    closed = true;
                    driverInstance?.destroy();
                    this.skip();
                },
                onDestroyed: () => {
                    driverInstance = null;
                    if (closed) return;
                },
            });

            driverInstance.drive();
        },

        destroyDriver() {
            if (driverInstance) {
                driverInstance.destroy();
                driverInstance = null;
            }
        },

        onPage(url) {
            try {
                const target = new URL(url, window.location.origin);
                if (window.location.pathname !== target.pathname) return false;
                for (const [key, value] of target.searchParams.entries()) {
                    if (new URLSearchParams(window.location.search).get(key) !== value) return false;
                }
                return true;
            } catch {
                return false;
            }
        },

        waitForElement(selector, callback, attempts = 0) {
            const el = document.querySelector(selector);
            if (el) {
                callback();
                return;
            }
            if (attempts > 40) {
                this.nextStep();
                return;
            }
            setTimeout(() => this.waitForElement(selector, callback, attempts + 1), 150);
        },
    };
}
</script>
@endif
