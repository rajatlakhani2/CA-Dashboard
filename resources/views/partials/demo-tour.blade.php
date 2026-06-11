@php($demoTour = $demoTour ?? ['show' => false, 'isDemo' => false, 'steps' => []])
@if(!empty($demoTour['show']) || !empty($demoTour['isDemo']))
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css">
<style>
    .demo-welcome-card,
    .demo-tour-modal-card {
        background: linear-gradient(145deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
        color: #fff;
    }
    .demo-tour-modal-card {
        width: 100%;
        max-width: 28rem;
        max-height: min(88vh, 34rem);
        overflow-y: auto;
    }
    .demo-welcome-emoji { font-size: 2.25rem; line-height: 1; }
    .demo-welcome-tagline {
        font-size: 0.95rem;
        font-weight: 700;
        color: #c7d2fe;
        letter-spacing: 0.02em;
    }
    .demo-welcome-title { font-size: 1.5rem; font-weight: 900; line-height: 1.25; }
    .demo-welcome-subtitle { font-size: 0.95rem; line-height: 1.5; }
    .demo-welcome-bullets { font-size: 0.9rem; line-height: 1.45; }
    .demo-modal-emoji { font-size: 1.75rem; }
    .demo-modal-title { font-size: 1.25rem; font-weight: 900; line-height: 1.25; }
    .demo-modal-tagline { font-size: 0.95rem; font-weight: 700; color: #c7d2fe; }
    .demo-wa-bubble {
        font-size: 0.8125rem;
        line-height: 1.55;
        padding: 0.875rem 1rem;
    }
    .demo-modal-caption { font-size: 0.875rem; line-height: 1.45; }
    .driver-popover.demo-tour-popover {
        border-radius: 16px;
        max-width: 22rem;
        padding: 4px;
    }
    .driver-popover.demo-tour-popover .driver-popover-title {
        font-size: 1.1rem;
        font-weight: 900;
        line-height: 1.3;
        padding-bottom: 0.35rem;
    }
    .driver-popover.demo-tour-popover .driver-popover-description {
        font-size: 0.9rem;
        line-height: 1.5;
        color: #334155;
    }
    .driver-popover.demo-tour-popover .driver-popover-description .demo-tour-tagline {
        display: block;
        font-size: 0.95rem;
        font-weight: 800;
        color: #4338ca;
        margin-bottom: 0.5rem;
        line-height: 1.35;
    }
    .driver-popover.demo-tour-popover .driver-popover-progress-text,
    .driver-popover.demo-tour-popover .driver-popover-footer button {
        font-size: 0.95rem;
        font-weight: 700;
    }
    .demo-tour-fab {
        position: fixed;
        bottom: 5.5rem;
        right: 1.25rem;
        z-index: 220;
        font-size: 0.95rem;
    }
    @media (min-width: 1024px) {
        .demo-tour-fab { bottom: 1.25rem; }
    }
    @if(!empty($demoTour['show']))
    #demo-tour-welcome:not([data-alpine-ready]) { display: flex !important; }
    @endif
    #demo-tour-root [x-cloak] { display: none !important; }
    body.demo-tour-autoplay .driver-popover.demo-tour-popover .driver-popover-footer button.driver-popover-next-btn,
    body.demo-tour-autoplay .driver-popover.demo-tour-popover .driver-popover-footer button.driver-popover-prev-btn {
        display: none !important;
    }
    .demo-tour-cinema-bar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 204;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        padding: 0.5rem 1rem;
        background: linear-gradient(90deg, #312e81, #4338ca);
        color: #fff;
        font-size: 0.8rem;
        font-weight: 700;
        box-shadow: 0 4px 16px rgba(49, 46, 129, 0.35);
    }
    .demo-tour-cinema-bar .demo-tour-cinema-pulse {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #34d399;
        animation: demo-tour-cinema-pulse 1.2s ease-in-out infinite;
    }
    @keyframes demo-tour-cinema-pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(0.85); }
    }
    .demo-tour-countdown {
        font-size: 2.5rem;
        font-weight: 900;
        color: #fff;
        line-height: 1;
        min-width: 3rem;
        text-align: center;
    }
</style>

@if(!empty($demoTour['isDemo']))
<div class="flex items-center gap-2 mr-2 px-3 py-1.5 rounded-full bg-amber-50 border border-amber-200 text-amber-900 text-xs font-bold shrink-0">
    <span class="inline-block h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
    Demo
</div>
@endif
@endif
