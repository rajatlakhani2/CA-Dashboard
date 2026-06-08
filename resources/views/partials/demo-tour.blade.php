@php($demoTour = $demoTour ?? ['show' => false, 'isDemo' => false, 'steps' => []])
@if(!empty($demoTour['show']) || !empty($demoTour['isDemo']))
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css">
<style>
    .demo-welcome-card {
        background: linear-gradient(145deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
        color: #fff;
    }
    .demo-welcome-emoji { font-size: 2.5rem; line-height: 1; }
    .demo-welcome-tagline {
        font-size: 1.05rem;
        font-weight: 700;
        color: #c7d2fe;
        letter-spacing: 0.02em;
    }
    .demo-welcome-title { font-size: 1.75rem; font-weight: 900; line-height: 1.2; }
    .demo-welcome-subtitle { font-size: 1.05rem; line-height: 1.55; }
    .demo-welcome-bullets { font-size: 1rem; line-height: 1.5; }
    .demo-modal-emoji { font-size: 2rem; }
    .demo-modal-title { font-size: 1.35rem; font-weight: 900; }
    .demo-modal-tagline { font-size: 1.05rem; font-weight: 700; color: #c7d2fe; }
    .driver-popover.demo-tour-popover {
        border-radius: 16px;
        max-width: 420px;
        padding: 4px;
    }
    .driver-popover.demo-tour-popover .driver-popover-title {
        font-size: 1.25rem;
        font-weight: 900;
        line-height: 1.3;
        padding-bottom: 0.35rem;
    }
    .driver-popover.demo-tour-popover .driver-popover-description {
        font-size: 1.05rem;
        line-height: 1.55;
        color: #334155;
    }
    .driver-popover.demo-tour-popover .driver-popover-description .demo-tour-tagline {
        display: block;
        font-size: 1.1rem;
        font-weight: 800;
        color: #4338ca;
        margin-bottom: 0.65rem;
        line-height: 1.35;
    }
    .driver-popover.demo-tour-popover .driver-popover-progress-text,
    .driver-popover.demo-tour-popover .driver-popover-footer button {
        font-size: 0.95rem;
        font-weight: 700;
    }
    .demo-tour-fab {
        position: fixed;
        bottom: 1.25rem;
        right: 1.25rem;
        z-index: 55;
        font-size: 0.95rem;
    }
    #demo-tour-root [x-cloak] { display: none !important; }
</style>

@if(!empty($demoTour['isDemo']))
<div class="flex items-center gap-2 mr-2 px-3 py-1.5 rounded-full bg-amber-50 border border-amber-200 text-amber-900 text-xs font-bold shrink-0">
    <span class="inline-block h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
    Demo
</div>
@endif
@endif
