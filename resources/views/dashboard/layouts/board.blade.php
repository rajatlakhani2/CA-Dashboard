@extends('layouts.app')

@section('header', 'Dashboard')

@php
    $columns = [
        ['key' => 'over', 'title' => 'Overdue', 'color' => 'text-red-700', 'items' => $items->filter(fn ($i) => $i['urgency'] === 'over' && $i['stage'] !== 'doing')],
        ['key' => 'week', 'title' => 'Due this week', 'color' => 'text-orange-800', 'items' => $items->filter(fn ($i) => $i['urgency'] !== 'over' && $i['stage'] === 'todo')],
        ['key' => 'waiting', 'title' => 'Waiting on client', 'color' => 'text-slate-700', 'items' => $items->filter(fn ($i) => $i['urgency'] !== 'over' && $i['stage'] === 'waiting')],
        ['key' => 'doing', 'title' => 'In progress', 'color' => 'text-teal-800', 'items' => $items->filter(fn ($i) => $i['stage'] === 'doing')],
    ];
    $tag = [
        'filing' => ['Filing', 'bg-indigo-50 text-indigo-800'],
        'payment' => ['Payment', 'bg-green-50 text-green-800'],
        'dsc' => ['DSC', 'bg-purple-50 text-purple-800'],
        'docs' => ['Docs', 'bg-slate-100 text-slate-700'],
        'task' => ['Task', 'bg-amber-50 text-amber-800'],
    ];
    $weekMax = max(1, collect($weekLoad)->max('count'));
@endphp

@section('content')
<div class="mx-auto flex w-full max-w-[1500px] flex-col gap-5" data-dashboard-layout="board">

    @include('dashboard.layouts._toolbar', [
        'title' => 'Work board',
        'subtitle' => 'Everything open for the next 7 days, sorted by urgency. Move cards along as work gets done.',
    ])

    <section aria-label="Summary" class="flex flex-wrap gap-2.5">
        @foreach($columns as $c)
        <span class="flex items-baseline gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm {{ $c['color'] }}"><strong class="text-lg">{{ $c['items']->count() }}</strong> {{ strtolower($c['title']) }}</span>
        @endforeach
    </section>

    <section aria-label="Board" class="grid grid-cols-1 items-start gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($columns as $col)
        <div class="rounded-2xl bg-slate-100 p-3">
            <div class="flex items-center justify-between px-1 pb-3">
                <h2 class="text-sm font-bold {{ $col['color'] }}">{{ $col['title'] }}</h2>
                <span class="rounded-full bg-white px-2.5 py-0.5 text-sm font-semibold text-slate-700">{{ $col['items']->count() }}</span>
            </div>
            <div class="flex max-h-[720px] flex-col gap-2 overflow-y-auto">
                @forelse($col['items'] as $item)
                <article data-dash-item class="rounded-xl border border-slate-200 bg-white p-3">
                    <div class="flex items-start justify-between gap-2">
                        <span class="flex items-center gap-1.5">
                            <span class="rounded-md px-2 py-0.5 text-[11px] font-bold {{ $tag[$item['kind']][1] }}">{{ $tag[$item['kind']][0] }}</span>
                            @if($item['who'])
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-teal-100 text-[10px] font-bold text-teal-900" title="{{ $item['who'] }}">{{ \Illuminate\Support\Str::of($item['who'])->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}</span>
                            @endif
                        </span>
                        <span class="whitespace-nowrap text-xs {{ $item['urgency'] === 'over' ? 'font-semibold text-red-700' : 'text-slate-600' }}">{{ $item['due_text'] }}</span>
                    </div>
                    <a href="{{ $item['open_url'] }}" class="mt-2 block text-sm font-semibold text-slate-900 hover:text-indigo-700">{{ $item['title'] }}</a>
                    <p class="text-[13px] text-slate-600">{{ $item['client_name'] }}</p>
                    <div class="mt-2.5 border-t border-slate-100 pt-2.5">
                        @include('dashboard.layouts._actions', ['item' => $item, 'showStart' => true])
                    </div>
                </article>
                @empty
                <p class="px-1 py-2 text-sm text-slate-600">Nothing here.</p>
                @endforelse
            </div>
        </div>
        @endforeach
    </section>

    <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="mb-3 text-base font-bold text-slate-900">Next 7 days</h2>
            <div class="grid h-36 grid-cols-7 items-end gap-2">
                @foreach($weekLoad as $d)
                <div class="flex h-full flex-col items-center justify-end gap-1">
                    <span class="text-xs font-semibold text-slate-900">{{ $d['count'] }}</span>
                    <span class="block w-3/4 rounded-t {{ $d['count'] > $weekMax * 0.66 ? 'bg-teal-700' : 'bg-teal-300' }}" style="height: {{ max(4, round($d['count'] / $weekMax * 100)) }}px"></span>
                </div>
                @endforeach
            </div>
            <div class="mt-1.5 grid grid-cols-7 gap-2 text-center text-xs text-slate-600">
                @foreach($weekLoad as $d)<span>{{ $d['label'] }}</span>@endforeach
            </div>
        </div>
        @if(count($workload))
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="mb-3 text-base font-bold text-slate-900">Who’s carrying what</h2>
            @foreach($workload as $m)
            <a href="{{ route('dashboard', ['scope' => 'firm', 'staff' => $m['id']]) }}" class="flex items-center gap-3 py-1.5 text-sm hover:text-indigo-700">
                <span class="w-32 truncate text-slate-900">{{ $m['name'] }}</span>
                <span class="block h-2.5 flex-1 overflow-hidden rounded-full bg-slate-100"><span class="block h-full rounded-full {{ $m['flag'] === 'Heavy' ? 'bg-orange-600' : 'bg-teal-600' }}" style="width: {{ $m['pct'] }}%"></span></span>
                <span class="w-24 text-right text-slate-700">{{ $m['tasks'] }}t · {{ $m['dues'] }}f</span>
            </a>
            @endforeach
            <p class="mt-2 text-xs text-slate-600">t = open tasks · f = filings due this week</p>
        </div>
        @endif
    </section>
</div>

@include('dashboard.layouts._script')
@endsection
