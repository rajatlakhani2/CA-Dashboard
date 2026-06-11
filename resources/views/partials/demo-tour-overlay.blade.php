@php
    $demoTour = $demoTour ?? ['show' => false, 'isDemo' => false, 'steps' => [], 'welcome' => []];
    $welcome = $demoTour['welcome'] ?? [];
    $demoStaffName = $demoTour['staffName'] ?? 'Neha Kapoor';
    $waDates = $demoTour['waTaskDates'] ?? [];
    $autoPlay = $demoTour['autoPlay'] ?? true;
@endphp
@if(!empty($demoTour['show']) || !empty($demoTour['isDemo']))
<div x-data="demoTourWelcome()" x-init="init()" id="demo-tour-root">
    <div x-show="cinemaActive" x-cloak class="demo-tour-cinema-bar" role="status">
        <span class="demo-tour-cinema-pulse"></span>
        <span>Demo playing automatically</span>
        <span class="text-indigo-200" x-text="progressLabel()"></span>
        <button type="button" @click="skip()" class="ml-2 text-xs font-semibold text-indigo-200 hover:text-white underline underline-offset-2">Skip</button>
    </div>

    @if(!empty($demoTour['show']))
    <div id="demo-tour-welcome" x-show="welcomeOpen" class="fixed inset-0 z-[200]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm" @click="skip()"></div>
        <div class="fixed inset-0 z-10 flex items-center justify-center p-4 pointer-events-none">
            <div class="demo-welcome-card demo-tour-modal-card pointer-events-auto rounded-2xl shadow-2xl p-6 sm:p-7 relative overflow-hidden" @click.stop>
                <div class="absolute -top-16 -right-16 h-48 w-48 rounded-full bg-white/10"></div>
                <p class="demo-welcome-emoji">{{ $welcome['emoji'] ?? '✨' }}</p>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-200 mt-2">Welcome to Vouchex</p>
                @if(!empty($welcome['tagline']))
                <p class="demo-welcome-tagline mt-2">{{ $welcome['tagline'] }}</p>
                @endif
                <h2 class="demo-welcome-title mt-2">{{ $welcome['title'] ?? 'Run your business from one workspace' }}</h2>
                <p class="demo-welcome-subtitle mt-3 text-indigo-100">
                    {{ $welcome['subtitle'] ?? 'A guided walkthrough of real workflows — from morning WhatsApp to evening wrap-up.' }}
                </p>
                <ul class="demo-welcome-bullets mt-4 space-y-2 text-indigo-50">
                    @foreach(($welcome['bullets'] ?? []) as $bullet)
                    <li class="flex gap-2 items-start">{{ $bullet }}</li>
                    @endforeach
                </ul>
                <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-between sm:items-center gap-3">
                    <button type="button" @click="skip()" class="text-sm font-semibold text-indigo-200 hover:text-white underline underline-offset-2">
                        Skip — explore on my own
                    </button>
                    @if($autoPlay)
                    <div class="flex items-center gap-3">
                        <p class="text-sm font-semibold text-indigo-100">Starting in</p>
                        <span class="demo-tour-countdown" x-text="countdown"></span>
                    </div>
                    @else
                    <button type="button" @click="startTour()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-indigo-900 shadow-lg hover:bg-indigo-50">
                        Start demo →
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[210]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm"></div>
        <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
            <div class="demo-tour-modal-card rounded-2xl shadow-2xl p-6 relative" @click.stop>
                <p class="demo-modal-emoji" x-text="modalEmoji()"></p>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-200 mt-2" x-text="modalEyebrow()"></p>
                <p class="demo-modal-tagline mt-1" x-text="modalTagline()"></p>
                <h3 class="demo-modal-title mt-1" x-text="modalTitle()"></h3>
                <template x-if="activeModal === 'whatsapp-morning'">
                    <div class="mt-3">
                        <div class="demo-wa-bubble rounded-xl bg-[#075e54] text-left text-white font-mono whitespace-pre-line shadow-inner">*Daily Reminder: Your Upcoming Tasks*
Hello {{ $demoStaffName }},
Here are your pending tasks due within the next 7 days:

1. Client proposal — Acme Corp (Due: {{ $waDates['proposal'] ?? '—' }})
2. Review contract — Brightline Ltd (Due: {{ $waDates['contract'] ?? '—' }})
3. Follow-up call — Nova Systems (Due: {{ $waDates['followup'] ?? '—' }})

Please prioritize these tasks. Have a productive day!</div>
                        <p class="demo-modal-caption mt-3 text-indigo-100">CEOs and managers receive a firm-wide summary with every team member's list.</p>
                    </div>
                </template>
                <template x-if="activeModal === 'whatsapp-evening'">
                    <div class="mt-3">
                        <div class="demo-wa-bubble rounded-xl bg-[#075e54] text-left text-white font-mono whitespace-pre-line shadow-inner">📋 *Daily Task Digest*

Hi {{ $demoStaffName }},
• Overdue: 1
• Due today: 2
• Due tomorrow: 1

Open My Day: app.kuhu.org.in/my-day</div>
                        <p class="demo-modal-caption mt-3 text-indigo-100">Morning and evening reminder times are configurable in automation settings.</p>
                    </div>
                </template>
                <div class="mt-5 flex justify-between items-center gap-3">
                    <button type="button" @click="skip()" class="text-sm font-semibold text-indigo-200 hover:text-white underline">Skip tour</button>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-indigo-200" x-text="progressLabel()"></span>
                        @if($autoPlay)
                        <span class="text-xs font-semibold text-emerald-200 animate-pulse">Playing…</span>
                        @else
                        <button type="button" @click="nextFromModal()" class="rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-indigo-900 hover:bg-indigo-50" x-text="modalNextLabel()"></button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="loadingOpen" x-cloak class="fixed bottom-24 left-1/2 -translate-x-1/2 z-[205] rounded-full bg-slate-900/90 px-4 py-2 text-sm font-semibold text-white shadow-lg">
        Loading demo screen…
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
    const AUTO_PLAY = @json($autoPlay);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const ACTIVE_KEY = 'vouchex_demo_tour_active';
    const STEP_KEY = 'vouchex_demo_tour_step';
    let driverInstance = null;
    let autoAdvanceTimer = null;
    let countdownInterval = null;

    return {
        welcomeOpen: autoShow,
        modalOpen: false,
        loadingOpen: false,
        activeModal: null,
        stepIndex: 0,
        countdown: 3,
        cinemaActive: false,

        init() {
            const welcomeEl = document.getElementById('demo-tour-welcome');
            if (welcomeEl) welcomeEl.setAttribute('data-alpine-ready', '1');

            if (AUTO_PLAY) {
                document.body.classList.add('demo-tour-autoplay');
            }

            const freshStart = @json(session('demo_tour_fresh_start'));
            if (freshStart) {
                this.clearTourStorage();
            }

            const resumeFromFlash = @json(session('demo_tour_resume_step'));
            if (resumeFromFlash !== null && resumeFromFlash !== '') {
                sessionStorage.setItem(ACTIVE_KEY, '1');
                sessionStorage.setItem(STEP_KEY, String(resumeFromFlash));
            }

            if (sessionStorage.getItem(ACTIVE_KEY) === '1') {
                const idx = parseInt(sessionStorage.getItem(STEP_KEY) || '0', 10);
                this.stepIndex = idx;
                this.welcomeOpen = false;
                this.cinemaActive = true;
                setTimeout(() => this.runStep(idx), 850);
                this.scheduleResumeRecovery();
                return;
            }

            if (autoShow && AUTO_PLAY) {
                this.startWelcomeCountdown();
            }
        },

        clearTourStorage() {
            sessionStorage.removeItem(ACTIVE_KEY);
            sessionStorage.removeItem(STEP_KEY);
            window.DemoTourPlay?.clearAllPlayDone?.();
        },

        scheduleResumeRecovery() {
            this.clearAutoAdvance();
            autoAdvanceTimer = setTimeout(() => {
                if (!this.welcomeOpen && !this.modalOpen && !driverInstance && !this.loadingOpen) {
                    this.clearTourStorage();
                    this.welcomeOpen = autoShow;
                    this.cinemaActive = false;
                    if (autoShow && AUTO_PLAY) {
                        this.startWelcomeCountdown();
                    } else if (window.DemoTourPlay?.toast) {
                        window.DemoTourPlay.toast('Demo paused — tap “Take a tour” to restart.');
                    }
                }
            }, 12000);
        },

        startWelcomeCountdown() {
            this.countdown = 3;
            if (countdownInterval) clearInterval(countdownInterval);
            countdownInterval = setInterval(() => {
                this.countdown -= 1;
                if (this.countdown <= 0) {
                    clearInterval(countdownInterval);
                    countdownInterval = null;
                    this.startTour();
                }
            }, 1000);
        },

        clearAutoAdvance() {
            if (autoAdvanceTimer) {
                clearTimeout(autoAdvanceTimer);
                autoAdvanceTimer = null;
            }
        },

        scheduleAutoAdvance(fn, ms) {
            this.clearAutoAdvance();
            if (!AUTO_PLAY) return;
            autoAdvanceTimer = setTimeout(fn, ms);
        },

        dwellMs(step) {
            if (step.dwellMs) return step.dwellMs;
            if (step.play) return 7500;
            if (step.type === 'modal') return 7000;
            return 5200;
        },

        progressLabel() {
            return (this.stepIndex + 1) + ' of ' + steps.length;
        },

        modalNextLabel() {
            return this.stepIndex >= steps.length - 1 ? 'Finish' : 'Next →';
        },
        currentStep() {
            return steps[this.stepIndex] || {};
        },
        modalEmoji() {
            return this.currentStep().emoji || '📱';
        },
        modalTitle() {
            return this.currentStep().title || 'Demo step';
        },
        modalTagline() {
            return this.currentStep().tagline || '';
        },
        modalEyebrow() {
            if (this.activeModal === 'whatsapp-evening') return 'Evening · 6:00 PM + 7:00 PM digest';
            return 'Morning · 10:00 AM';
        },
        stepTitle(step) {
            const emoji = step.emoji ? step.emoji + ' ' : '';
            return emoji + (step.title || '');
        },
        stepDescription(step) {
            const tagline = step.tagline
                ? '<span class="demo-tour-tagline">' + step.tagline + '</span>'
                : '';
            return tagline + (step.description || '');
        },

        restartTour() {
            this.clearAutoAdvance();
            if (countdownInterval) clearInterval(countdownInterval);
            this.clearTourStorage();
            this.stepIndex = 0;
            this.cinemaActive = false;
            this.welcomeOpen = true;
            if (AUTO_PLAY) {
                this.startWelcomeCountdown();
            }
        },

        startTour() {
            if (countdownInterval) clearInterval(countdownInterval);
            this.clearTourStorage();
            sessionStorage.setItem(ACTIVE_KEY, '1');
            sessionStorage.setItem(STEP_KEY, '0');
            this.stepIndex = 0;
            this.welcomeOpen = false;
            this.cinemaActive = true;
            this.runStep(0);
        },

        skip() {
            this.clearAutoAdvance();
            if (countdownInterval) clearInterval(countdownInterval);
            this.welcomeOpen = false;
            this.modalOpen = false;
            this.loadingOpen = false;
            this.cinemaActive = false;
            this.destroyDriver();
            sessionStorage.removeItem(ACTIVE_KEY);
            sessionStorage.removeItem(STEP_KEY);
            fetch(dismissUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
        },

        finishTour(completed = true) {
            this.clearAutoAdvance();
            this.modalOpen = false;
            this.loadingOpen = false;
            this.cinemaActive = false;
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

        scrollToSelector(selector) {
            if (!selector) return;
            const el = document.querySelector(selector);
            if (el) {
                el.scrollIntoView({ block: 'start', behavior: 'smooth' });
            }
        },

        activateDashboardTab(tab) {
            if (typeof window.showDashboardTab === 'function') {
                window.showDashboardTab(tab);
                return;
            }
            const tabBtn = document.querySelector('[data-dashboard-tab="' + tab + '"]');
            if (tabBtn) tabBtn.click();
        },

        tabDelay(tab) {
            return tab === 'calendar' ? 800 : 420;
        },

        runStep(index) {
            const step = steps[index];
            if (!step) {
                this.finishTour(true);
                return;
            }

            this.stepIndex = index;
            sessionStorage.setItem(STEP_KEY, String(index));
            this.cinemaActive = true;

            if (step.type === 'modal') {
                this.destroyDriver();
                this.loadingOpen = false;
                this.activeModal = step.modal;
                this.modalOpen = true;
                this.scheduleAutoAdvance(() => this.nextFromModal(), this.dwellMs(step));
                return;
            }

            if (step.url && !this.onPage(step.url)) {
                sessionStorage.setItem(ACTIVE_KEY, '1');
                sessionStorage.setItem(STEP_KEY, String(index));
                window.location.href = step.url;
                return;
            }

            const beginSpotlight = () => {
                if (step.scrollTo) {
                    this.scrollToSelector(step.scrollTo);
                }
                const waitSelector = step.waitFor || step.element;
                const maxAttempts = step.waitAttempts || (step.tab === 'calendar' ? 90 : 65);
                this.loadingOpen = true;

                this.waitForElement(
                    waitSelector,
                    (el) => {
                        this.runPlayThenSpotlight(step, index, el);
                    },
                    0,
                    maxAttempts,
                    () => {
                        this.loadingOpen = false;
                        const fallback = document.querySelector(step.element);
                        if (fallback) {
                            this.runPlayThenSpotlight(step, index, fallback);
                            return;
                        }
                        setTimeout(() => {
                            this.waitForElement(step.element, () => this.runPlayThenSpotlight(step, index), 0, 40);
                        }, 800);
                    }
                );
            };

            if (step.tab) {
                this.activateDashboardTab(step.tab);
                setTimeout(beginSpotlight, this.tabDelay(step.tab));
            } else {
                beginSpotlight();
            }
        },

        async runPlayThenSpotlight(step, index, el) {
            this.loadingOpen = false;
            let spotlightStep = Object.assign({}, step);

            if (step.play && window.DemoTourPlay) {
                const playResult = await window.DemoTourPlay.run(step.play, index, { stepIndex: index });
                if (playResult.reload) {
                    return;
                }
                const afterEl = playResult.spotlight || step.spotlightAfterPlay;
                if (afterEl) {
                    spotlightStep = Object.assign({}, step, { element: afterEl });
                }
            }

            const target = document.querySelector(spotlightStep.element) || el;
            if (target && target.scrollIntoView) {
                target.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
            setTimeout(() => this.spotlight(spotlightStep), 300);
        },

        spotlight(step) {
            if (!window.driver?.js?.driver) {
                alert('Tour could not load. Refresh and try again.');
                return;
            }

            const el = document.querySelector(step.element);
            if (!el) {
                this.loadingOpen = true;
                this.waitForElement(step.element, () => this.spotlight(step), 0, 30);
                return;
            }

            this.destroyDriver();
            let closed = false;

            driverInstance = window.driver.js.driver({
                showProgress: true,
                progressText: '@{{current}} of @{{total}}',
                nextBtnText: this.stepIndex >= steps.length - 1 ? 'Finish' : 'Next →',
                prevBtnText: '← Back',
                showButtons: AUTO_PLAY ? ['close'] : ['next', 'close'],
                popoverClass: 'demo-tour-popover',
                stagePadding: 8,
                overlayOpacity: 0.65,
                steps: [{
                    element: step.element,
                    popover: {
                        title: this.stepTitle(step),
                        description: this.stepDescription(step),
                        side: step.side || 'bottom',
                        align: 'start',
                    },
                }],
                onPopoverRender: (popover) => {
                    const desc = popover.description;
                    if (desc && step.tagline) {
                        desc.innerHTML = this.stepDescription(step);
                    }
                    if (AUTO_PLAY) {
                        const footer = popover.footer;
                        if (footer && !footer.querySelector('.demo-tour-auto-hint')) {
                            const hint = document.createElement('span');
                            hint.className = 'demo-tour-auto-hint text-xs font-semibold text-indigo-500';
                            hint.textContent = 'Advancing automatically…';
                            footer.appendChild(hint);
                        }
                    }
                },
                onNextClick: () => {
                    this.clearAutoAdvance();
                    driverInstance?.destroy();
                    this.nextStep();
                },
                onCloseClick: () => {
                    closed = true;
                    this.clearAutoAdvance();
                    driverInstance?.destroy();
                    this.skip();
                },
                onDestroyed: () => {
                    driverInstance = null;
                    if (closed) return;
                },
            });

            driverInstance.drive();

            this.scheduleAutoAdvance(() => {
                this.destroyDriver();
                this.nextStep();
            }, this.dwellMs(step));
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

        waitForElement(selector, callback, attempts = 0, maxAttempts = 65, onTimeout = null) {
            const el = document.querySelector(selector);
            if (el) {
                callback(el);
                return;
            }
            if (attempts >= maxAttempts) {
                if (onTimeout) onTimeout();
                else callback(null);
                return;
            }
            setTimeout(() => this.waitForElement(selector, callback, attempts + 1, maxAttempts, onTimeout), 150);
        },
    };
}
</script>
@endif
