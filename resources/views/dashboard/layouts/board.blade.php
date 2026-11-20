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
    $firstTab = collect($columns)->first(fn ($c) => $c['items']->isNotEmpty())['key'] ?? 'over';
    $tabTone = ['over' => 'text-red-700', 'week' => 'text-orange-800', 'waiting' => 'text-slate-700', 'doing' => 'text-teal-800'];
    // Phones: overdue cards grouped by age, oldest group folded away.
    $overGroups = function ($items) {
        $g = [
            'recent' => ['Last 7 days', $items->filter(fn ($i) => ($i['days_over'] ?? 0) <= 7)],
            'month' => ['8–30 days late', $items->filter(fn ($i) => ($i['days_over'] ?? 0) > 7 && ($i['days_over'] ?? 0) <= 30)],
            'older' => ['Over a month late', $items->filter(fn ($i) => ($i['days_over'] ?? 0) > 30)],
        ];
        return collect($g)->filter(fn ($x) => $x[1]->isNotEmpty());
    };
    $canReassign = auth()->user()?->isPartner() || auth()->user()?->isManager();
    $reassignStaff = ($canReassign && $filters['can_see_firm']) ? ($filterOptions['staff'] ?? []) : [];
@endphp

@section('content')
<div class="mx-auto flex w-full max-w-[1500px] flex-col gap-4 md:gap-5" data-dashboard-layout="board" x-data="{ tab: '{{ $firstTab }}' }">

    @include('dashboard.layouts._toolbar', [
        'title' => 'Work board',
        'subtitle' => 'Everything open for the next 7 days, sorted by urgency. Move cards along as work gets done.',
    ])

    <section aria-label="Summary" class="hidden md:flex flex-wrap gap-2.5">
        @foreach($columns as $c)
        <span class="flex items-baseline gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm {{ $c['color'] }}"><strong class="text-lg">{{ $c['items']->count() }}</strong> {{ strtolower($c['title']) }}</span>
        @endforeach
    </section>

    <nav aria-label="Board columns" class="md:hidden -mx-4 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none]" data-phone-tabs>
        @foreach($columns as $c)
        <button type="button" @click="tab = '{{ $c['key'] }}'; $el.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' })"
            :class="tab === '{{ $c['key'] }}' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white {{ $tabTone[$c['key']] }}'"
            class="flex shrink-0 items-baseline gap-1.5 rounded-full border px-4 py-2 text-sm font-medium transition" :aria-pressed="tab === '{{ $c['key'] }}'">
            <strong class="text-base" data-tab-count="{{ $c['key'] }}">{{ $c['items']->count() }}</strong>{{ strtolower($c['title']) }}
        </button>
        @endforeach
    </nav>

    <div class="md:hidden" x-data="{ hint: (()=>{ try { return !localStorage.getItem('board-swipe-hint') } catch(e) { return true } })() }" x-show="hint" x-cloak>
        <div class="flex items-start gap-3 rounded-2xl bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
            <span class="flex-1"><strong>Tip:</strong> swipe a card right to mark it done, left to move it to tomorrow. Tap a card for more. Pull down to refresh.</span>
            <button type="button" class="font-semibold text-indigo-700" @click="hint = false; try { localStorage.setItem('board-swipe-hint', '1') } catch(e) {}">Got it</button>
        </div>
    </div>

    <div data-drop-col="done" class="hidden md:flex min-h-16 items-center justify-center rounded-2xl border-2 border-dashed border-teal-300 bg-teal-50/60 px-4 text-sm font-medium text-teal-900 transition">
        Drag a card here to mark it done · drag tasks between columns to change their status
    </div>

    <section aria-label="Board" class="grid grid-cols-1 items-start gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($columns as $col)
        <div class="rounded-2xl bg-slate-100 p-3 max-md:bg-transparent max-md:p-0" :class="tab === '{{ $col['key'] }}' ? '' : 'max-md:hidden'">
            <div class="flex items-center justify-between px-1 pb-3 max-md:hidden">
                <h2 class="text-sm font-bold {{ $col['color'] }}">{{ $col['title'] }}</h2>
                <span data-col-count class="rounded-full bg-white px-2.5 py-0.5 text-sm font-semibold text-slate-700">{{ $col['items']->count() }}</span>
            </div>
            @if($col['key'] === 'over' && $canReassign && $col['items']->count() >= 5)
            <a href="{{ route('dashboard.cleanup') }}" class="mb-2 flex items-center justify-between rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-sm font-semibold text-red-800 hover:bg-red-100 max-md:py-3">
                <span>Old items piling up? Clean up overdue</span><span aria-hidden="true">→</span>
            </a>
            @endif
            <div data-drop-col="{{ $col['key'] }}" class="flex min-h-24 md:max-h-[720px] flex-col gap-2 md:overflow-y-auto rounded-xl transition">
                @if($col['key'] === 'over' && $col['items']->isNotEmpty())
                    @php $groups = $overGroups($col['items']); @endphp
                    @foreach($groups as $gKey => [$gLabel, $gItems])
                    <div class="flex shrink-0 flex-col gap-2" x-data="{ open: {{ ($gKey !== 'older' || $groups->count() === 1) ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open" class="md:hidden flex items-center justify-between px-1 pt-1 text-xs font-bold uppercase tracking-wide text-slate-500" :aria-expanded="open">
                            <span>{{ $gLabel }} · <span data-group-count>{{ $gItems->count() }}</span></span>
                            <svg class="h-4 w-4 transition" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div class="flex flex-col gap-2" :class="open ? '' : 'max-md:hidden'">
                            @foreach($gItems as $item)
                                @include('dashboard.layouts._board-card', ['item' => $item, 'tag' => $tag, 'reassignStaff' => $reassignStaff])
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                @else
                    @foreach($col['items'] as $item)
                        @include('dashboard.layouts._board-card', ['item' => $item, 'tag' => $tag, 'reassignStaff' => $reassignStaff])
                    @endforeach
                @endif
                <p data-empty class="px-1 py-2 text-sm text-slate-600 max-md:rounded-2xl max-md:bg-white max-md:py-8 max-md:text-center" @if($col['items']->isNotEmpty()) hidden @endif>Nothing here.</p>
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

@include('dashboard.layouts._payment-dialog')
@include('dashboard.layouts._script')
@include('dashboard.layouts._phone-board')
@endsection
