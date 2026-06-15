@php

    $managesFirm = auth()->user()?->managesFirmModules() ?? false;

    $hasFirmTab = $showFirmOverviewTab ?? false;

@endphp

@if($managesFirm || $hasFirmTab)

<div class="exec-widget__inner executive-firm-section" data-demo-tour="executive-firm">

    @if($hasFirmTab && ($firmOverview ?? null))

    @include('dashboard.partials.firm-overview', ['firmOverview' => $firmOverview])

    @else

    <p class="text-xs text-gray-500 text-center py-4">Partner firm metrics appear here when available.</p>

    @endif

</div>

@endif

