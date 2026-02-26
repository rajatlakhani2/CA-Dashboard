@extends('layouts.app')

@section('header')
Firm Compliance 360°
@endsection

@section('content')
<div class="space-y-6">
    <div class="bg-bg-card shadow rounded-lg border border-line p-6">
        <div id="calendar"></div>
    </div>
</div>
@endsection

@section('scripts')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listWeek'
            },
            events: @json($events),
            eventClick: function(info) {
                alert('Client: ' + info.event.extendedProps.client + '\nStatus: ' + info.event.extendedProps.status);
            },
            height: 'auto',
            contentHeight: 600,
            themeSystem: 'standard'
        });
        calendar.render();
    });
</script>
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