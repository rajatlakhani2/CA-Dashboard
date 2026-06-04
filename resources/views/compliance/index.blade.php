@extends('layouts.app')

@section('header')
Firm Compliance 360°
@endsection

@push('head_styles')
@include('dashboard.partials.premium-styles')
@endpush

@section('content')
<div class="space-y-6 compliance-360-shell">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('compliance.index', ['status' => 'Pending']) }}" class="kpi-card kpi-amber">
            <p class="kpi-label">Pending</p>
            <p class="kpi-value">{{ $stats['pending'] ?? 0 }}</p>
            <p class="kpi-sub">Open compliance dues</p>
        </a>
        <a href="{{ route('compliance.index', ['status' => 'Overdue']) }}" class="kpi-card kpi-rose">
            <p class="kpi-label">Overdue</p>
            <p class="kpi-value">{{ $stats['overdue'] ?? 0 }}</p>
            <p class="kpi-sub">Needs immediate action</p>
        </a>
        <a href="{{ route('compliance.index', ['status' => 'Completed']) }}" class="kpi-card kpi-emerald">
            <p class="kpi-label">Completed</p>
            <p class="kpi-value">{{ $stats['completed'] ?? 0 }}</p>
            <p class="kpi-sub">Filed / done</p>
        </a>
        <a href="{{ route('reports.due-date') }}" class="kpi-card kpi-blue">
            <p class="kpi-label">Due date report</p>
            <p class="kpi-value text-xl">→</p>
            <p class="kpi-sub">Export &amp; filter by range</p>
        </a>
    </div>

    <div class="bg-bg-card shadow rounded-lg border border-line p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-main">Compliance Calendar</h3>
                <p class="mt-1 text-xs text-text-secondary">Drag pending or overdue dues to reschedule them directly from the calendar.</p>
                <p class="mt-1 text-xs text-text-secondary">When the current month has no dues, the calendar jumps to the nearest actionable due automatically.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <a href="{{ route('compliance.index') }}" class="rounded-full border px-3 py-1 {{ request('status') ? 'border-line text-text-secondary hover:border-indigo-300 hover:text-indigo-600' : 'border-indigo-600 bg-indigo-50 text-indigo-700' }}">All</a>
                @foreach(['Pending', 'Overdue', 'Completed'] as $status)
                <a href="{{ route('compliance.index', ['status' => $status]) }}" class="rounded-full border px-3 py-1 {{ request('status') === $status ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-line text-text-secondary hover:border-indigo-300 hover:text-indigo-600' }}">{{ $status }}</a>
                @endforeach
            </div>
        </div>
        <div id="calendar"></div>
    </div>
</div>
@endsection

@section('scripts')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var events = @json($events);

        function getInitialCalendarDate(calendarEvents, lockedStatus) {
            if (!calendarEvents.length) {
                return undefined;
            }

            var today = new Date();
            var todayMonth = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0');

            if (calendarEvents.some(function(event) {
                return (event.start || '').slice(0, 7) === todayMonth;
            })) {
                return undefined;
            }

            var candidates = calendarEvents.filter(function(event) {
                return event.extendedProps && event.extendedProps.status !== lockedStatus;
            });

            if (!candidates.length) {
                candidates = calendarEvents.slice();
            }

            candidates.sort(function(a, b) {
                var aDiff = Math.abs(new Date(a.start + 'T00:00:00') - today);
                var bDiff = Math.abs(new Date(b.start + 'T00:00:00') - today);
                return aDiff - bDiff;
            });

            return candidates[0] ? candidates[0].start : undefined;
        }

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            initialDate: getInitialCalendarDate(events, 'Completed'),
            editable: true,
            eventStartEditable: true,
            eventDurationEditable: false,
            eventDragMinDistance: 4,
            longPressDelay: 0,
            eventLongPressDelay: 0,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listWeek'
            },
            events: events,
            eventClick: function(info) {
                window.dispatchEvent(new CustomEvent('open-calendar-modal', {
                    detail: {
                        id: info.event.id,
                        title: info.event.extendedProps.details || info.event.title,
                        title_text: info.event.title,
                        type: info.event.extendedProps.type || 'due',
                        db_id: info.event.extendedProps.db_id,
                        client_name: info.event.extendedProps.client_name || info.event.extendedProps.client,
                        status: info.event.extendedProps.status,
                        start: info.event.startStr
                    }
                }));
            },
            eventDrop: function(info) {
                if (info.event.extendedProps.status === 'Completed') {
                    info.revert();
                    return;
                }

                if (!confirm('Reschedule compliance due to ' + info.event.startStr + '?')) {
                    info.revert();
                    return;
                }

                fetch('{{ route('calendar.update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        type: 'due',
                        id: info.event.extendedProps.db_id,
                        new_date: info.event.startStr
                    })
                }).then(r => r.json()).then(data => {
                    if (!data.success) {
                        alert(data.message || 'Failed to reschedule due date.');
                        info.revert();
                    }
                }).catch(() => {
                    info.revert();
                });
            },
            eventDidMount: function(info) {
                if (info.event.extendedProps.status !== 'Completed') {
                    info.el.style.cursor = 'move';
                    return;
                }

                info.el.style.cursor = 'not-allowed';
                info.el.style.opacity = '0.75';
            },
            height: 'auto',
            contentHeight: 600,
            themeSystem: 'standard'
        });
        calendar.render();
    });
</script>
@include('partials.calendar-event-modal')
<style>
    /* FullCalendar Overrides for Theme Compatibility */
    :root {
        --fc-border-color: var(--c-line);
        --fc-button-bg-color: var(--c-primary-600);
        --fc-button-border-color: var(--c-primary-600);
        --fc-button-hover-bg-color: var(--c-primary-500);
        --fc-button-hover-border-color: var(--c-primary-500);
        --fc-button-active-bg-color: var(--c-primary-700);
        --fc-button-active-border-color: var(--c-primary-700);
        --fc-today-bg-color: var(--c-primary-50);
        --fc-page-bg-color: var(--c-bg-card);
        --fc-neutral-bg-color: var(--c-bg-body);
        --fc-list-event-hover-bg-color: var(--c-primary-50);
    }

    .fc {
        color: var(--c-text-main);
    }

    .fc-col-header-cell-cushion,
    .fc-daygrid-day-number,
    .fc-list-day-text,
    .fc-list-day-side-text {
        color: var(--c-text-main);
        text-decoration: none !important;
    }
</style>
@endsection
