@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <span>Personal Renewals & Calendar</span>
    <a href="{{ route('personal-renewals.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded shadow">
        + Add New
    </a>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- List View -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Premium Tabs (Vibrant Pills) -->
        <div class="flex flex-wrap gap-2">
            @foreach(['All', 'LIC', 'Loan', 'Medical', 'Other'] as $tab)
            @php
            $isActive = (request('tab', 'All') === $tab);
            $query = request()->all();
            $query['tab'] = $tab;
            $link = route('personal-renewals.index', $query);
            @endphp
            <a href="{{ $link }}" class="px-5 py-2.5 text-base font-bold rounded-full transition-all duration-200 shadow-sm border
                {{ $isActive 
                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-200 transform scale-105' 
                    : 'bg-white text-gray-500 border-gray-200 hover:border-indigo-300 hover:text-indigo-600 hover:shadow-md' 
                }}">
                {{ $tab }}
            </a>
            @endforeach
        </div>

        <div class="bg-bg-card shadow sm:rounded-lg overflow-hidden border border-line">
            <div class="px-4 py-3 sm:px-6 border-b border-line bg-gray-50/50 flex justify-between items-center">
                <h3 class="text-sm font-semibold text-text-main uppercase tracking-wider">
                    {{ request('tab', 'Upcoming') == 'All' ? 'Upcoming' : request('tab') }}
                </h3>
                <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-2 py-0.5 rounded-full">{{ $renewals->where('status', 'Pending')->count() }}</span>
            </div>
            <ul class="divide-y divide-line max-h-[600px] overflow-y-auto">
                @forelse($renewals->where('status', 'Pending') as $renewal)
                <li class="px-4 py-3 sm:px-6 hover:bg-gray-50 transition duration-150 ease-in-out group">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $renewal->title }}</p>
                        <span class="px-2 py-0.5 inline-flex text-[10px] font-bold uppercase tracking-wide rounded-full 
                                {{ $renewal->due_date->isPast() ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' }}">
                            {{ $renewal->due_date->format('d M') }}
                        </span>
                    </div>

                    <div class="flex justify-between items-end">
                        <div>
                            <p class="text-xs text-gray-500 flex items-center">
                                {!! match($renewal->category) {
                                'LIC' => '<svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>',
                                'Loan' => '<svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>',
                                'Medical' => '<svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>',
                                default => ''
                                } !!}
                                {{ $renewal->category }}
                                @if($renewal->frequency)
                                <span class="ml-1 text-gray-400">({{ $renewal->frequency }})</span>
                                @endif
                            </p>
                            <p class="text-sm font-bold text-gray-900 mt-0.5">₹ {{ number_format($renewal->amount) }}</p>
                        </div>

                        <div class="flex space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <!-- WhatsApp -->
                            <form action="{{ route('personal-renewals.whatsapp', $renewal) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-800 p-1 rounded-full hover:bg-green-100" title="Send WhatsApp Reminder">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                    </svg>
                                </button>
                            </form>

                            <a href="{{ route('personal-renewals.edit', $renewal) }}" class="text-indigo-600 hover:text-indigo-800 p-1 rounded-full hover:bg-indigo-100" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
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
                                <button type="submit" class="text-green-600 hover:text-green-800 p-1 rounded-full hover:bg-green-100" title="Mark Paid">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </li>
                @empty
                <li class="px-4 py-8 text-center text-sm text-gray-500 italic">No pending renewals found.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <!-- Calendar View -->
    <div class="lg:col-span-2">
        <div class="bg-white shadow sm:rounded-lg p-4">
            <!-- Resize Controls -->
            <div class="flex justify-end space-x-2 mb-2">
                <button onclick="resizeCalendar(400)" class="text-xs px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded">Small</button>
                <button onclick="resizeCalendar(600)" class="text-xs px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded">Normal</button>
                <button onclick="resizeCalendar(800)" class="text-xs px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded">Large</button>
            </div>
            <div id="calendar"></div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<script>
    var calendar;

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var events = @json($events);

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listMonth'
            },
            events: events,
            height: 600,
            eventClick: function(info) {
                if (info.event.url) {
                    window.location.href = info.event.url;
                    info.jsEvent.preventDefault();
                }
            }
        });
        calendar.render();
    });

    function resizeCalendar(height) {
        if (calendar) {
            calendar.setOption('height', height);
        }
    }
</script>
@endsection