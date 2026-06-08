@php($demoTour = $demoTour ?? ['show' => false, 'isDemo' => false, 'steps' => []])
@if(!empty($demoTour['show']) || !empty($demoTour['isDemo']))
<div x-data="demoTourWelcome()" id="demo-tour-root">
    @if(!empty($demoTour['show']))
    <div x-show="welcomeOpen" x-cloak class="fixed inset-0 z-[200]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm" @click="skip()"></div>
        <div class="fixed inset-0 z-10 flex items-center justify-center p-4 pointer-events-none">
            <div class="demo-welcome-card pointer-events-auto w-full max-w-lg rounded-2xl shadow-2xl p-8 relative overflow-hidden" @click.stop>
                <div class="absolute -top-16 -right-16 h-48 w-48 rounded-full bg-white/10"></div>
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-indigo-200">Welcome to Vouchex</p>
                <h2 class="mt-2 text-2xl font-black leading-tight">Explore the demo dashboard</h2>
                <p class="mt-3 text-sm text-indigo-100 leading-relaxed">
                    A quick guided walkthrough of clients, tasks, compliance, billing, and settings.
                    About 2 minutes. Skip anytime.
                </p>
                <ul class="mt-5 space-y-2 text-sm text-indigo-50">
                    <li class="flex gap-2"><span>✓</span> Sample data already loaded</li>
                    <li class="flex gap-2"><span>✓</span> Spotlight on each sidebar feature</li>
                    <li class="flex gap-2"><span>✓</span> Restart tour from the button below</li>
                </ul>
                <div class="mt-8 flex flex-col-reverse sm:flex-row sm:justify-between gap-3">
                    <button type="button" @click="skip()" class="text-sm font-semibold text-indigo-200 hover:text-white underline underline-offset-2">
                        Skip tour
                    </button>
                    <button type="button" @click="startTour()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-bold text-indigo-900 shadow-lg hover:bg-indigo-50">
                        Start guided tour →
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(!empty($demoTour['isDemo']))
    <button type="button" @click="openTour()" class="demo-tour-fab inline-flex items-center gap-2 rounded-full bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg hover:bg-indigo-700">
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

    return {
        welcomeOpen: autoShow,
        openTour() {
            this.welcomeOpen = true;
        },
        skip() {
            this.welcomeOpen = false;
            fetch(dismissUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
        },
        startTour() {
            this.welcomeOpen = false;
            if (!window.driver?.js?.driver) {
                alert('Tour could not load. Check your internet connection and refresh.');
                return;
            }
            const visibleSteps = steps.filter((s) => document.querySelector(s.element));
            if (visibleSteps.length === 0) {
                alert('Tour targets not found on this page. Open the Dashboard first, then click Take a tour.');
                return;
            }
            let skipped = false;
            const driver = window.driver.js.driver({
                showProgress: true,
                progressText: '@{{current}} of @{{total}}',
                nextBtnText: 'Next →',
                prevBtnText: '← Back',
                doneBtnText: 'Finish',
                showButtons: ['next', 'previous', 'close'],
                popoverClass: 'demo-tour-popover',
                stagePadding: 6,
                overlayOpacity: 0.65,
                steps: visibleSteps.map((s) => ({
                    element: s.element,
                    popover: {
                        title: s.title,
                        description: s.description,
                        side: s.side || 'right',
                        align: 'start',
                    },
                })),
                onCloseClick: () => {
                    skipped = true;
                    driver.destroy();
                },
                onDestroyed: () => {
                    fetch(skipped ? dismissUrl : completeUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    });
                },
            });
            driver.drive();
        },
    };
}
</script>
@endif
