@php($demoTour = $demoTour ?? ['show' => false, 'isDemo' => false, 'steps' => []])
@if(!empty($demoTour['show']) || !empty($demoTour['isDemo']))
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css">
<style>
    .demo-welcome-card {
        background: linear-gradient(145deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
        color: #fff;
    }
    .driver-popover.demo-tour-popover { border-radius: 14px; max-width: 340px; }
    .driver-popover.demo-tour-popover .driver-popover-title { font-size: 1rem; font-weight: 800; }
    .driver-popover.demo-tour-popover .driver-popover-description { font-size: 0.875rem; line-height: 1.45; }
    .demo-tour-fab {
        position: fixed;
        bottom: 1.25rem;
        right: 1.25rem;
        z-index: 55;
    }
</style>

@if(!empty($demoTour['isDemo']))
<div class="flex items-center gap-2 mr-2 px-3 py-1.5 rounded-full bg-amber-50 border border-amber-200 text-amber-900 text-[11px] font-bold shrink-0">
    <span class="inline-block h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
    Demo
</div>
@endif
@endif
