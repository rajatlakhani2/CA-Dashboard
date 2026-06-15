@php
    $embedded = $embedded ?? false;
    $resizable = $resizable ?? false;
    $hideHeader = $hideHeader ?? false;
@endphp
<section id="dashboard-schedule" class="{{ $embedded ? 'exec-calendar-card' : 'glass-card p-5 mt-2' }}" data-demo-tour="schedule-calendar-wrap">
    @if($hideHeader)
    <div class="flex gap-2 text-[10px] text-gray-500 flex-wrap mb-2">
        <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-blue-400"></span> Tasks</span>
        <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-violet-400"></span> Dues</span>
        <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-rose-400"></span> Overdue</span>
    </div>
    @else
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-3">
        <div>
            <p class="glass-section-title mb-0">📅 Schedule & Deadlines</p>
            <p class="mt-0.5 text-xs text-gray-500">Client &amp; task names on the calendar. Click for details, drag to reschedule.</p>
        </div>
        <div class="flex gap-2 text-[10px] text-gray-500 flex-wrap">
            <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-blue-400"></span> Tasks</span>
            <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-violet-400"></span> Dues</span>
            <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-rose-400"></span> Overdue</span>
        </div>
    </div>
    @endif
    @include('dashboard.partials.calendar-filters')
    <div id="dashboardCalendar" class="cal-grid-labels {{ $resizable ? 'cal-grid-resizable' : '' }}" data-demo-tour="schedule-calendar" style="min-height: {{ $resizable ? '100%' : (($embedded ?? false) ? '380px' : '480px') }};{{ $resizable ? ' height: 100%;' : '' }}"></div>
</section>
