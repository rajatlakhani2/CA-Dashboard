@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <div>
        <h2 class="font-bold text-lg text-gray-900 tracking-wide">Personal Renewals</h2>
        <p class="text-xs text-gray-500 mt-0.5">LIC, loans, medical policies & family due dates</p>
    </div>
    <a href="{{ route('personal-renewals.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-md shadow-indigo-600/25 hover:bg-indigo-700 transition">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add renewal
    </a>
</div>
@endsection

@push('head_styles')
@include('dashboard.partials.premium-styles')
<style>
    body { background: var(--premium-bg, #e8ecf1) !important; }
    .renewals-shell .kpi-card { cursor: default; }
    .renewals-shell .glass-tabs { margin-bottom: 0; }
    .renewals-shell .glass-tab { text-decoration: none; display: inline-block; }
    .renewals-shell .renewal-list-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.04); }
    .renewals-shell .renewal-row { border-left: 4px solid transparent; transition: all 0.2s; }
    .renewals-shell .renewal-row:hover { border-left-color: var(--vx-accent-blue); background: var(--vx-accent-soft); }
    .renewals-shell .renewal-row.overdue { border-left-color: #ef4444; }
    .renewals-shell .renewal-row.renewal-draggable { cursor: grab; }
    .renewals-shell .renewal-row.renewal-draggable:active { cursor: grabbing; }
    .renewals-shell #calendar { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 1rem; }
    .renewals-shell #calendar .fc-daygrid-day { cursor: pointer; }
    .renewals-shell #calendar .fc-daygrid-day:hover { background: rgba(99, 102, 241, 0.06); }
    .renewals-shell #calendar .fc-event { cursor: grab; border-radius: 6px; font-size: 0.7rem; font-weight: 600; }
    .renewals-shell #calendar .fc-event:active { cursor: grabbing; }
    .renewals-shell #calendar .fc-event.fc-event-locked { cursor: not-allowed; opacity: 0.75; }
</style>
@endpush

@section('content')
@php
    $pending = $renewals->where('status', 'Pending');
    $overdueCount = $pending->filter(fn ($r) => $r->due_date->isPast())->count();
    $dueThisMonth = $pending->filter(fn ($r) => $r->due_date->isSameMonth(now()))->count();
    $totalPendingAmount = $pending->sum('amount');
@endphp

@if(session('success'))
<div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
    {{ session('success') }}
</div>
@endif

<div class="renewals-shell w-full space-y-6">
    <div class="rounded-2xl p-5 sm:p-6 shadow-lg" style="background: linear-gradient(135deg, var(--premium-navy) 0%, var(--premium-navy-soft) 50%, var(--vx-accent-blue) 100%); color: #fff; box-shadow: 0 10px 30px -8px var(--vx-nav-active-shadow);">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest opacity-80">Personal reminders</p>
                <h2 class="text-2xl sm:text-3xl font-bold mt-1">Stay ahead of renewals</h2>
                <p class="text-sm mt-2 opacity-90">LIC, loans, medical policies & family due dates in one calm view.</p>
            </div>
            <div class="text-right">
                <p class="text-[10px] font-bold uppercase tracking-widest opacity-80">Due this month</p>
                <p class="text-3xl font-extrabold tabular-nums mt-1">{{ $dueThisMonth }}</p>
                @if($overdueCount > 0)
                <p class="text-xs text-rose-200 mt-1 font-semibold">{{ $overdueCount }} overdue</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="kpi-card kpi-violet">
            <div class="flex items-center justify-between mb-3">
                <p class="kpi-label">Pending</p>
                <div class="h-9 w-9 rounded-xl bg-violet-50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <p class="kpi-value">{{ $pending->count() }}</p>
            <p class="kpi-sub">Active renewals</p>
        </div>
        <div class="kpi-card kpi-rose">
            <div class="flex items-center justify-between mb-3">
                <p class="kpi-label">Overdue</p>
                <div class="h-9 w-9 rounded-xl bg-rose-50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="kpi-value">{{ $overdueCount }}</p>
            <p class="kpi-sub">Needs attention</p>
        </div>
        <div class="kpi-card kpi-amber">
            <div class="flex items-center justify-between mb-3">
                <p class="kpi-label">Due this month</p>
                <div class="h-9 w-9 rounded-xl bg-amber-50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="kpi-value">{{ $dueThisMonth }}</p>
            <p class="kpi-sub">{{ now()->format('F Y') }}</p>
        </div>
        <div class="kpi-card kpi-emerald">
            <div class="flex items-center justify-between mb-3">
                <p class="kpi-label">Pending amount</p>
                <div class="h-9 w-9 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="kpi-value text-2xl">₹ {{ number_format($totalPendingAmount) }}</p>
            <p class="kpi-sub">Estimated outflow</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-4">
            <div class="glass-tabs flex-wrap">
                @foreach(['All', 'LIC', 'Loan', 'Medical', 'Other'] as $tab)
                @php
                $isActive = (request('tab', 'All') === $tab);
                $query = request()->all();
                $query['tab'] = $tab;
                $link = route('personal-renewals.index', $query);
                @endphp
                <a href="{{ $link }}" class="glass-tab {{ $isActive ? 'active' : '' }}">{{ $tab }}</a>
                @endforeach
            </div>

            <div class="renewal-list-card">
                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50/80 flex justify-between items-center">
                    <p class="glass-section-title mb-0">{{ request('tab', 'All') === 'All' ? 'Upcoming' : request('tab') }}</p>
                    <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-2.5 py-0.5 rounded-full">{{ $pending->count() }}</span>
                </div>
                <ul id="renewal-drag-list" class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
                    @forelse($pending as $renewal)
                    <li
                        class="renewal-row px-4 py-4 sm:px-5 group {{ $renewal->due_date->isPast() ? 'overdue' : '' }} {{ $renewal->status === 'Pending' ? 'renewal-draggable' : '' }}"
                        @if($renewal->status === 'Pending')
                        data-event-id="renewal_{{ $renewal->id }}"
                        data-renewal-id="{{ $renewal->id }}"
                        data-title="{{ $renewal->title }} ({{ $renewal->amount }})"
                        data-status="{{ $renewal->status }}"
                        data-color="{{ $renewal->status === 'Paid' ? '#22c55e' : '#ef4444' }}"
                        @endif
                    >
                        <div class="flex items-center justify-between mb-1 gap-2">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $renewal->title }}</p>
                            <span class="renewal-date-badge text-xs font-bold px-2 py-0.5 rounded-lg whitespace-nowrap {{ $renewal->due_date->isPast() ? 'bg-rose-100 text-rose-800' : 'bg-emerald-50 text-emerald-800' }}">
                                {{ $renewal->due_date->format('d M') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-end gap-2">
                            <div>
                                <p class="text-xs text-gray-500">{{ $renewal->category }}@if($renewal->frequency) · {{ $renewal->frequency }}@endif</p>
                                <p class="text-sm font-bold text-gray-900 mt-0.5">₹ {{ number_format($renewal->amount) }}</p>
                            </div>
                            <div class="flex gap-1 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                <form action="{{ route('personal-renewals.whatsapp', $renewal) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-emerald-600 hover:bg-emerald-50 p-1.5 rounded-lg" title="WhatsApp reminder">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                    </button>
                                </form>
                                <a href="{{ route('personal-renewals.edit', $renewal) }}" class="text-indigo-600 hover:bg-indigo-50 p-1.5 rounded-lg" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </a>
                                <form action="{{ route('personal-renewals.update', $renewal) }}" method="POST" class="inline" onsubmit="return confirm('Mark as paid?')">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="title" value="{{ $renewal->title }}">
                                    <input type="hidden" name="category" value="{{ $renewal->category }}">
                                    <input type="hidden" name="due_date" value="{{ $renewal->due_date->format('Y-m-d') }}">
                                    <input type="hidden" name="amount" value="{{ $renewal->amount }}">
                                    <input type="hidden" name="frequency" value="{{ $renewal->frequency }}">
                                    <input type="hidden" name="status" value="Paid">
                                    <button type="submit" class="text-emerald-600 hover:bg-emerald-50 p-1.5 rounded-lg" title="Mark paid">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </li>
                    @empty
                    <li class="px-4 py-10 text-center text-sm text-gray-500">No pending renewals in this view.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="glass-card p-4 sm:p-5">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="glass-section-title mb-0">Calendar</p>
                        <p class="text-xs text-gray-500 mt-0.5">Drag pending items to reschedule · paid items stay locked</p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="resizeCalendar(400)" class="text-xs px-2.5 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 font-medium text-gray-700">Small</button>
                        <button type="button" onclick="resizeCalendar(600)" class="text-xs px-2.5 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 font-medium text-gray-700">Normal</button>
                        <button type="button" onclick="resizeCalendar(800)" class="text-xs px-2.5 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 font-medium text-gray-700">Large</button>
                    </div>
                </div>
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script>
    var calendar;

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var events = @json($events);
        var lockedStatus = 'Paid';

        function getInitialCalendarDate(calendarEvents) {
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

        function formatBadgeDate(dateStr) {
            var parts = dateStr.split('-');
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return parseInt(parts[2], 10) + ' ' + months[parseInt(parts[1], 10) - 1];
        }

        function updateListDateBadge(renewalId, dateStr) {
            var row = document.querySelector('[data-renewal-id="' + renewalId + '"]');
            if (!row) {
                return;
            }

            var badge = row.querySelector('.renewal-date-badge');
            if (badge) {
                badge.textContent = formatBadgeDate(dateStr);
            }
        }

        function persistRenewalDate(dbId, newDate, onFail) {
            return fetch('{{ route('calendar.update') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    type: 'renewal',
                    id: dbId,
                    new_date: newDate
                })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (!data.success) {
                    if (typeof onFail === 'function') {
                        onFail();
                    }
                    alert(data.message || 'Failed to reschedule renewal.');
                    return false;
                }

                updateListDateBadge(dbId, newDate);
                return true;
            }).catch(function() {
                if (typeof onFail === 'function') {
                    onFail();
                }
                return false;
            });
        }

        function openRenewalModal(info) {
            window.dispatchEvent(new CustomEvent('open-calendar-modal', {
                detail: {
                    id: info.event.id,
                    title: info.event.extendedProps.details || info.event.title,
                    title_text: info.event.extendedProps.title_text || info.event.title,
                    type: info.event.extendedProps.type || 'renewal',
                    db_id: info.event.extendedProps.db_id,
                    client_name: info.event.extendedProps.client_name,
                    status: info.event.extendedProps.status,
                    start: info.event.startStr
                }
            }));
        }

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            initialDate: getInitialCalendarDate(events),
            firstDay: 1,
            editable: true,
            eventStartEditable: true,
            eventDurationEditable: false,
            eventDragMinDistance: 4,
            longPressDelay: 0,
            eventLongPressDelay: 0,
            droppable: true,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listMonth'
            },
            events: events,
            height: 600,
            eventAllow: function(dropInfo, draggedEvent) {
                return draggedEvent.extendedProps.status !== lockedStatus;
            },
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                openRenewalModal(info);
            },
            eventDrop: function(info) {
                if (info.event.extendedProps.status === lockedStatus) {
                    info.revert();
                    return;
                }

                if (!confirm('Reschedule renewal to ' + info.event.startStr + '?')) {
                    info.revert();
                    return;
                }

                persistRenewalDate(info.event.extendedProps.db_id, info.event.startStr, function() {
                    info.revert();
                });
            },
            eventReceive: function(info) {
                var dbId = info.event.extendedProps.db_id;
                var newDate = info.event.startStr;

                if (!dbId || info.event.extendedProps.status === lockedStatus) {
                    info.revert();
                    return;
                }

                if (!confirm('Reschedule renewal to ' + newDate + '?')) {
                    info.revert();
                    return;
                }

                persistRenewalDate(dbId, newDate, function() {
                    info.revert();
                });
            },
            dateClick: function(info) {
                var modal = document.getElementById('renewalDateClickModal');
                var dateText = document.getElementById('renewalSelectedDateText');
                var addLink = document.getElementById('renewalAddOnDateLink');

                if (!modal || !dateText || !addLink) {
                    return;
                }

                dateText.textContent = info.dateStr;
                addLink.href = '{{ route('personal-renewals.create') }}?due_date=' + info.dateStr;
                modal.classList.remove('hidden');
            },
            eventDidMount: function(info) {
                if (info.event.extendedProps.status === lockedStatus) {
                    info.el.classList.add('fc-event-locked');
                }
            }
        });
        calendar.render();

        var listEl = document.getElementById('renewal-drag-list');
        if (listEl && typeof FullCalendar.Draggable !== 'undefined') {
            new FullCalendar.Draggable(listEl, {
                itemSelector: '.renewal-draggable',
                eventData: function(eventEl) {
                    return {
                        id: eventEl.getAttribute('data-event-id'),
                        title: eventEl.getAttribute('data-title'),
                        duration: { days: 1 },
                        backgroundColor: eventEl.getAttribute('data-color') || '#ef4444',
                        borderColor: eventEl.getAttribute('data-color') || '#ef4444',
                        textColor: '#ffffff',
                        extendedProps: {
                            type: 'renewal',
                            db_id: parseInt(eventEl.getAttribute('data-renewal-id'), 10),
                            status: eventEl.getAttribute('data-status'),
                            client_name: 'Personal Renewal',
                            details: eventEl.getAttribute('data-title'),
                            title_text: eventEl.getAttribute('data-title')
                        }
                    };
                }
            });
        }
    });

    function resizeCalendar(height) {
        if (calendar) {
            calendar.setOption('height', height);
        }
    }

    function closeRenewalDateModal() {
        var modal = document.getElementById('renewalDateClickModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }
</script>
@include('partials.calendar-event-modal')

<div id="renewalDateClickModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-gray-900/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 transform transition-all">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Add renewal</h3>
        <p class="text-sm text-gray-500 mb-6">Create a personal renewal on <span id="renewalSelectedDateText" class="font-semibold text-indigo-600"></span>.</p>
        <a id="renewalAddOnDateLink" href="#" class="flex items-center gap-3 w-full p-3 rounded-xl border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition">
            <div class="h-10 w-10 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600 text-lg">📅</div>
            <div class="text-left">
                <div class="text-sm font-bold text-gray-900">Add renewal</div>
                <div class="text-xs text-gray-500">LIC, loan, medical or other</div>
            </div>
        </a>
        <button type="button" onclick="closeRenewalDateModal()" class="mt-6 w-full py-2.5 rounded-xl border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 transition">Cancel</button>
    </div>
</div>
@endsection
