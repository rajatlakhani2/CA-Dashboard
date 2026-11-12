@extends('layouts.app')

@section('header', 'Dashboard')

@php
    $scoreCls = fn ($s) => $s < 50 ? 'bg-red-50 text-red-700' : ($s < 75 ? 'bg-orange-50 text-orange-800' : 'bg-green-50 text-green-700');
    $toneCls = ['red' => 'text-red-700', 'green' => 'text-green-700', '' => 'text-slate-900'];
    $dot = ['over' => 'bg-red-600', 'soon' => 'bg-orange-500', 'ok' => 'bg-green-600', 'done' => 'bg-slate-400'];
    $selId = $selected['client']['id'] ?? null;
    $qs = fn (array $extra) => array_filter(array_merge(request()->only(['scope', 'branch', 'staff', 'q', 'sort']), $extra), fn ($v) => $v !== null && $v !== '');
@endphp

@section('content')
<div class="mx-auto flex w-full max-w-[1400px] flex-col gap-5" data-dashboard-layout="client">

    @include('dashboard.layouts._toolbar', [
        'title' => 'Clients',
        'subtitle' => 'Clients that need attention come first. Pick one to see everything about them.',
    ])

    <div class="flex flex-wrap items-start gap-5">
        {{-- Client list --}}
        <section aria-label="Clients" class="w-full overflow-hidden rounded-2xl border border-slate-200 bg-white lg:w-[360px] lg:shrink-0">
            <form method="GET" action="{{ route('dashboard') }}" class="p-4">
                @foreach(request()->only(['scope', 'branch', 'staff', 'sort']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <label for="client-search" class="sr-only">Search clients</label>
                <input id="client-search" type="search" name="q" value="{{ $search }}" placeholder="Search name, PAN, GSTIN, code"
                    class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <div class="mt-2.5 flex flex-wrap gap-1.5">
                    <a href="{{ route('dashboard', $qs(['sort' => null, 'client' => null])) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $sort === 'risk' ? 'bg-indigo-600 text-white' : 'border border-slate-300 text-slate-700' }}">Needs attention first</a>
                    <a href="{{ route('dashboard', $qs(['sort' => 'az', 'client' => null])) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $sort === 'az' ? 'bg-indigo-600 text-white' : 'border border-slate-300 text-slate-700' }}">A–Z</a>
                </div>
            </form>
            <ul class="max-h-80 overflow-y-auto border-t border-slate-100 lg:max-h-[720px]">
                @forelse($clients as $c)
                <li>
                    <a href="{{ route('dashboard', $qs(['client' => $c['id']])) }}"
                       class="flex items-center gap-3 border-b border-slate-100 px-4 py-3 {{ $selId === $c['id'] ? 'bg-indigo-50 shadow-[inset_3px_0_0_#4f46e5]' : 'hover:bg-slate-50' }}"
                       @if($selId === $c['id']) aria-current="true" @endif>
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $scoreCls($c['score']) }}">{{ $c['score'] }}</span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-900">{{ $c['name'] }}</span>
                            <span class="block truncate text-xs text-slate-600">{{ $c['flag'] }}</span>
                        </span>
                    </a>
                </li>
                @empty
                <li class="px-4 py-8 text-center text-sm text-slate-600">No clients match.</li>
                @endforelse
            </ul>
        </section>

        {{-- Client overview --}}
        <section aria-label="Client overview" class="flex min-w-0 flex-1 flex-col gap-4">
            @if(!$selected)
            <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-600">Pick a client from the list.</div>
            @else
            @php $c = $selected['client']; $h = $selected['health']; @endphp
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex min-w-0 items-center gap-4">
                    <span class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-full {{ $scoreCls($h['score']) }}" title="Health score">
                        <span class="text-lg font-bold leading-none">{{ $h['score'] }}</span>
                        <span class="text-xs font-semibold">{{ $h['label'] }}</span>
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm text-slate-600">{{ collect([$c['code'], $c['branch'], $c['manager'] ? 'handled by '.$c['manager'] : null])->filter()->implode(' · ') }}</p>
                        <h2 class="truncate text-2xl font-bold tracking-tight text-slate-900">{{ $c['name'] }}</h2>
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            @foreach($c['services'] as $s)
                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-800">{{ $s }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ $c['url'] }}" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">Open client</a>
                    @if(auth()->user()->canAccessModule('invoices'))
                    @can('create', App\Models\Invoice::class)
                    <a href="{{ route('invoices.create', ['client_id' => $c['id']]) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-800 hover:border-slate-400">New invoice</a>
                    @endcan
                    @endif
                    <a href="{{ route('tasks.create', ['client_id' => $c['id']]) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-800 hover:border-slate-400">Add task</a>
                    @can('update', \App\Models\Client::find($c['id']))
                    <form method="POST" action="{{ route('clients.auto-reminders', $c['id']) }}" class="contents">
                        @csrf @method('PATCH')
                        <input type="hidden" name="auto_reminders" value="{{ $c['auto_reminders'] ? 0 : 1 }}">
                        <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-800 hover:border-slate-400" title="Automatic payment reminders and document requests">
                            <span class="h-2.5 w-2.5 rounded-full {{ $c['auto_reminders'] ? 'bg-green-600' : 'bg-slate-400' }}"></span>
                            Auto reminders {{ $c['auto_reminders'] ? 'on' : 'off' }}
                        </button>
                    </form>
                    @endcan
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                @foreach($selected['tiles'] as $t)
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5">
                    <p class="text-xs font-medium text-slate-600">{{ $t['label'] }}</p>
                    <p class="mt-1 truncate text-xl font-bold {{ $toneCls[$t['tone']] ?? 'text-slate-900' }}">{{ $t['value'] }}</p>
                    <p class="truncate text-xs text-slate-600">{{ $t['sub'] }}</p>
                </div>
                @endforeach
            </div>

            <div class="flex flex-wrap items-start gap-4">
                <div class="min-w-0 flex-[3_1_380px] rounded-2xl border border-slate-200 bg-white p-5">
                    <h3 class="text-base font-bold text-slate-900">Upcoming &amp; overdue work</h3>
                    <p class="mb-2 mt-0.5 text-sm text-slate-600">Next 45 days, plus anything filed in the last two weeks.</p>
                    @forelse($selected['work'] as $w)
                    <div data-dash-item class="flex flex-wrap items-center gap-3 border-b border-slate-100 py-2.5">
                        <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $dot[$w['state']] }}"></span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold {{ $w['state'] === 'done' ? 'text-slate-500 line-through' : 'text-slate-900' }}">{{ $w['label'] }}</span>
                            <span class="block text-xs {{ $w['state'] === 'over' ? 'text-red-700' : 'text-slate-600' }}">{{ $w['due'] }}</span>
                        </span>
                        @if($w['complete_url'])
                        <button type="button" data-dash-action="complete" data-url="{{ $w['complete_url'] }}" data-method="POST" data-payload="{}" data-label="{{ $w['label'] }}" class="min-h-9 rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white">Mark filed</button>
                        @elseif(!empty($w['task_url']))
                        <button type="button" data-dash-action="complete" data-url="{{ $w['task_url'] }}" data-method="PATCH" data-payload='@json(['status' => \App\Models\Task::STATUS_COMPLETED])' data-label="{{ $w['label'] }}" class="min-h-9 rounded-lg bg-slate-900 px-3 text-xs font-semibold text-white">Mark done</button>
                        @endif
                        @if($w['remind_url'])
                        <form method="POST" action="{{ $w['remind_url'] }}" class="contents">@csrf<button type="submit" class="min-h-9 rounded-lg border border-slate-300 bg-white px-3 text-xs text-slate-700">WhatsApp</button></form>
                        @endif
                    </div>
                    @empty
                    <p class="text-sm text-slate-600">Nothing due in the next 45 days.</p>
                    @endforelse

                    @if(count($selected['documents']))
                    <h3 class="mt-5 text-base font-bold text-slate-900">Documents awaited</h3>
                    @foreach($selected['documents'] as $d)
                    <div data-dash-item class="flex items-center gap-3 border-b border-slate-100 py-2.5">
                        <span class="min-w-0 flex-1 text-sm"><span class="font-semibold text-slate-900">{{ $d['name'] }}</span> <span class="text-slate-600">· {{ $d['service'] }}</span></span>
                        <button type="button" data-dash-action="complete" data-url="{{ $d['toggle_url'] }}" data-method="PATCH" data-payload='{"received":1}' data-label="{{ $d['name'] }}" class="min-h-9 rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-800">Mark received</button>
                    </div>
                    @endforeach
                    @endif
                </div>

                <div class="flex min-w-0 flex-[2_1_280px] flex-col gap-4">
                    @if($canSeeMoney && count($selected['invoices']))
                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <h3 class="mb-2 text-base font-bold text-slate-900">Recent invoices</h3>
                        @foreach($selected['invoices'] as $inv)
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 py-2 text-sm">
                            <a href="{{ $inv['url'] }}" class="min-w-0 truncate text-slate-900 hover:text-indigo-700">{{ $inv['number'] }} <span class="text-slate-600">· {{ $inv['date'] }}</span></a>
                            <span class="whitespace-nowrap text-slate-700">{{ $inv['status'] === 'Paid' ? 'Paid' : $inv['balance'].' due' }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <h3 class="mb-3 text-base font-bold text-slate-900">Recent activity</h3>
                        @if(count($selected['activity']))
                        <ol class="border-l-2 border-indigo-100">
                            @foreach($selected['activity'] as $a)
                            <li class="relative pb-3.5 pl-4">
                                <span class="absolute -left-[6px] top-1 h-2.5 w-2.5 rounded-full bg-indigo-600"></span>
                                <span class="block text-sm text-slate-900">{{ $a['text'] }}</span>
                                <span class="block text-xs text-slate-600">{{ $a['at'] }}</span>
                            </li>
                            @endforeach
                        </ol>
                        @else
                        <p class="text-sm text-slate-600">No recent activity.</p>
                        @endif
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <h3 class="mb-2 text-base font-bold text-slate-900">Health breakdown</h3>
                        @foreach($h['breakdown'] as $b)
                        <div class="flex items-center justify-between gap-2 py-1.5 text-sm">
                            <span class="flex items-center gap-2 text-slate-900"><span class="h-2.5 w-2.5 rounded-full {{ ['red' => 'bg-red-600', 'amber' => 'bg-orange-500', 'green' => 'bg-green-600'][$b['status']] ?? 'bg-slate-400' }}"></span>{{ $b['label'] }}</span>
                            <span class="text-slate-600">{{ $b['detail'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </section>
    </div>
</div>

@include('dashboard.layouts._script')
@endsection
