@extends('layouts.app')

@section('header', 'Dashboard')

@php
    $svcMoney = app(\App\Services\DashboardLayoutDataService::class);
    $kindMeta = [
        'filing' => ['Filing', 'bg-blue-50 text-blue-800'],
        'payment' => ['Payment', 'bg-green-50 text-green-800'],
        'dsc' => ['DSC', 'bg-purple-50 text-purple-800'],
        'docs' => ['Documents', 'bg-slate-100 text-slate-700'],
        'task' => ['Task', 'bg-amber-50 text-amber-800'],
    ];
    $urgencyCls = ['over' => 'bg-red-50 text-red-700', 'soon' => 'bg-orange-50 text-orange-800', 'ok' => 'bg-slate-100 text-slate-700'];
    $tabCounts = $items->countBy('kind');
    $revMax = max(1, collect($revenue)->max('billed') ?? 1, collect($revenue)->max('collected') ?? 1);
    $ageMax = max(1, collect($ageing)->max('amount') ?? 1);
    $ageTotal = collect($ageing)->sum('amount');
@endphp

@section('content')
<div class="mx-auto flex w-full max-w-[1400px] flex-col gap-5" data-dashboard-layout="inbox">

    @include('dashboard.layouts._toolbar', [
        'title' => 'Action inbox',
        'subtitle' => $items->where('urgency', 'over')->count().' overdue · '.$items->count().' items need attention in the next 7 days',
        'showPeriod' => $canSeeMoney,
    ])

    {{-- Headline numbers --}}
    <section aria-label="Key numbers" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpis as $k)
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4">
            <p class="text-sm font-medium text-slate-600">{{ $k['label'] }}</p>
            <p class="mt-1 text-3xl font-bold tracking-tight {{ $k['tone'] === 'red' ? 'text-red-700' : 'text-slate-900' }}">{{ $k['value'] }}</p>
            <p class="text-sm text-slate-600">{{ $k['sub'] }}</p>
        </div>
        @endforeach
    </section>

    <section class="flex flex-wrap items-start gap-5">
        {{-- Inbox --}}
        <div class="min-w-0 flex-[999_1_560px] rounded-2xl border border-slate-200 bg-white" x-data="{ tab: 'all' }">
            <div class="flex flex-wrap items-baseline justify-between gap-2 px-5 pt-5">
                <h2 class="text-lg font-bold text-slate-900">Today’s action inbox</h2>
                <span class="text-sm text-slate-600">Overdue first, then by due date</span>
            </div>
            <div role="tablist" aria-label="Item type" class="flex flex-wrap gap-1.5 border-b border-slate-200 px-5 py-3">
                <button type="button" role="tab" @click="tab='all'" :aria-selected="tab==='all'" :class="tab==='all' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-700 border-slate-300'" class="min-h-9 rounded-lg border px-3 text-sm font-medium">All <span class="ml-1 opacity-80">{{ $items->count() }}</span></button>
                @foreach($kindMeta as $k => [$label])
                @if(($tabCounts[$k] ?? 0) > 0)
                <button type="button" role="tab" @click="tab='{{ $k }}'" :aria-selected="tab==='{{ $k }}'" :class="tab==='{{ $k }}' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-700 border-slate-300'" class="min-h-9 rounded-lg border px-3 text-sm font-medium">{{ $label }} <span class="ml-1 opacity-80">{{ $tabCounts[$k] }}</span></button>
                @endif
                @endforeach
            </div>

            @if($items->isEmpty())
            <div class="px-5 py-12 text-center">
                <p class="text-base font-semibold text-slate-900">All clear</p>
                <p class="mt-1 text-sm text-slate-600">Nothing overdue or due in the next 7 days for this view.</p>
            </div>
            @else
            <ul class="max-h-[640px] divide-y divide-slate-100 overflow-y-auto">
                @foreach($items as $item)
                <li data-dash-item x-show="tab==='all' || tab==='{{ $item['kind'] }}'" class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3">
                    <span class="w-24 shrink-0 rounded-md py-1 text-center text-xs font-semibold {{ $kindMeta[$item['kind']][1] }}">{{ $kindMeta[$item['kind']][0] }}</span>
                    <div class="min-w-0 flex-[1_1_240px]">
                        <a href="{{ $item['open_url'] }}" class="block truncate text-[15px] font-semibold text-slate-900 hover:text-indigo-700">{{ $item['title'] }}</a>
                        <p class="truncate text-sm text-slate-600">{{ $item['client_name'] }}@if($item['who']) · {{ $item['who'] }}@endif</p>
                    </div>
                    <span class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $urgencyCls[$item['urgency']] }}">{{ $item['due_text'] }}</span>
                    @include('dashboard.layouts._actions', ['item' => $item])
                </li>
                @endforeach
            </ul>
            @endif
        </div>

        {{-- Compliance calendar --}}
        <div class="min-w-0 flex-[1_1_340px] rounded-2xl border border-slate-200 bg-white p-5"
             x-data="{ sel: {{ collect($calendar['days'])->firstWhere('is_today', true)['day'] ?? 1 }}, days: @js($calendar['days']) }">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-lg font-bold text-slate-900">Compliance calendar</h2>
                <div class="flex items-center gap-1 text-sm">
                    <a href="{{ request()->fullUrlWithQuery(['month' => $calendar['prev']]) }}" class="rounded-md px-2 py-1 text-slate-700 hover:bg-slate-100" aria-label="Previous month">‹</a>
                    <span class="font-medium text-slate-700">{{ $calendar['label'] }}</span>
                    <a href="{{ request()->fullUrlWithQuery(['month' => $calendar['next']]) }}" class="rounded-md px-2 py-1 text-slate-700 hover:bg-slate-100" aria-label="Next month">›</a>
                </div>
            </div>
            <p class="mb-3 mt-1 text-xs text-slate-600">Darker = more filings due. Click a day.</p>
            <div class="mb-1 grid grid-cols-7 gap-1 text-center text-xs text-slate-600">
                @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d)<span>{{ $d }}</span>@endforeach
            </div>
            <div class="grid grid-cols-7 gap-1">
                @for($i = 0; $i < $calendar['lead_blanks']; $i++)<span></span>@endfor
                @foreach($calendar['days'] as $d)
                @php
                    $n = $d['count'];
                    $cls = $n === 0 ? 'bg-slate-50 text-slate-600' : ($n <= 5 ? 'bg-blue-100 text-blue-950' : ($n <= 15 ? 'bg-blue-300 text-slate-950' : ($n <= 30 ? 'bg-blue-600 text-white' : 'bg-blue-900 text-white')));
                @endphp
                <button type="button" @click="sel={{ $d['day'] }}"
                    :class="sel==={{ $d['day'] }} ? 'ring-2 ring-amber-500 ring-offset-1' : ''"
                    class="flex min-h-12 flex-col items-center justify-center rounded-lg {{ $cls }} {{ $d['is_today'] ? 'outline outline-2 outline-dashed outline-slate-900' : '' }}"
                    aria-label="{{ $d['day'] }} {{ $calendar['label'] }}: {{ $n }} filings due">
                    <span class="text-[13px] font-semibold">{{ $d['day'] }}</span>
                    @if($n)<span class="text-[11px]">{{ $n }}</span>@endif
                </button>
                @endforeach
            </div>
            <div class="mt-4 border-t border-slate-200 pt-3">
                <p class="mb-2 text-sm font-semibold text-slate-900" x-text="sel + ' {{ $calendar['label'] }}'">{{ collect($calendar['days'])->firstWhere('is_today', true)['day'] ?? '' }} {{ $calendar['label'] }}</p>
                <template x-for="it in (days.find(d => d.day === sel)?.items || [])" :key="it.name">
                    <div class="flex justify-between gap-3 border-b border-slate-100 py-2 text-sm">
                        <span x-text="it.name" class="text-slate-900"></span>
                        <span class="whitespace-nowrap text-slate-600"><span x-text="it.done"></span> of <span x-text="it.total"></span> filed</span>
                    </div>
                </template>
                <p x-show="!(days.find(d => d.day === sel)?.items || []).length" class="text-sm text-slate-600">No filings due on this day.</p>
                <a href="{{ $calendar['url'] }}" class="mt-3 inline-block text-sm font-medium text-indigo-700 underline">Open service dues</a>
            </div>
        </div>
    </section>

    @if($canSeeMoney && count($revenue))
    <section class="flex flex-wrap items-stretch gap-5">
        {{-- Billed vs collected --}}
        <div class="min-w-0 flex-[999_1_560px] rounded-2xl border border-slate-200 bg-white p-5" x-data="{ sel: {{ count($revenue) - 1 }}, rev: @js($revenue) }">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-lg font-bold text-slate-900">Billed vs collected</h2>
                <div class="flex gap-4 text-sm text-slate-600">
                    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-blue-900"></span>Billed</span>
                    <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm bg-blue-400"></span>Collected</span>
                </div>
            </div>
            <p class="mt-1 text-sm text-slate-600">Click a bar for details.</p>
            <div class="mt-4 flex h-56 items-end gap-2 overflow-x-auto border-b border-slate-300">
                @foreach($revenue as $i => $r)
                <button type="button" @click="sel={{ $i }}" :class="sel==={{ $i }} ? 'bg-indigo-50' : ''"
                    class="flex h-full min-w-11 flex-1 items-end justify-center gap-1 rounded-t-lg pt-2"
                    aria-label="{{ $r['label'] }}: billed {{ $svcMoney->money($r['billed']) }}, collected {{ $svcMoney->money($r['collected']) }}">
                    <span class="block w-4 rounded-t bg-blue-900" style="height: {{ max(2, round($r['billed'] / $revMax * 200)) }}px"></span>
                    <span class="block w-4 rounded-t bg-blue-400" style="height: {{ max(2, round($r['collected'] / $revMax * 200)) }}px"></span>
                </button>
                @endforeach
            </div>
            <div class="mt-1.5 flex gap-2">
                @foreach($revenue as $r)<span class="min-w-11 flex-1 text-center text-xs text-slate-600">{{ $r['label'] }}</span>@endforeach
            </div>
            @php $revJs = collect($revenue)->map(fn ($r) => $r + ['billed_fmt' => $svcMoney->money($r['billed']), 'collected_fmt' => $svcMoney->money($r['collected']), 'rate' => $r['billed'] > 0 ? round($r['collected'] / $r['billed'] * 100) : null])->values(); @endphp
            <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 rounded-xl bg-slate-50 px-4 py-3 text-sm" x-data="{ r: @js($revJs) }">
                <strong x-text="r[sel].label"></strong>
                <span>Billed <span x-text="r[sel].billed_fmt"></span></span>
                <span>Collected <span x-text="r[sel].collected_fmt"></span></span>
                <span x-show="r[sel].rate !== null">Collection rate <span x-text="r[sel].rate"></span>%</span>
                <a :href="r[sel].url" class="ml-auto font-medium text-indigo-700 underline">Open invoices</a>
            </div>
        </div>

        {{-- Receivables ageing --}}
        <div class="min-w-0 flex-[1_1_340px] rounded-2xl border border-slate-200 bg-white p-5" x-data="{ sel: {{ collect($ageing)->search(fn ($a) => $a['amount'] > 0) !== false ? collect($ageing)->search(fn ($a) => $a['amount'] > 0) : 0 }}, age: @js(collect($ageing)->map(fn ($a) => ['label' => $a['label'], 'clients' => collect($a['clients'])->map(fn ($c) => $c + ['fmt' => $svcMoney->money($c['amount'])])->all()])) }">
            <h2 class="text-lg font-bold text-slate-900">Receivables by age</h2>
            <p class="mb-4 mt-1 text-sm text-slate-600">{{ $svcMoney->money($ageTotal) }} outstanding. Click a group.</p>
            <div class="flex flex-col gap-2">
                @foreach($ageing as $i => $a)
                <button type="button" @click="sel={{ $i }}" :class="sel==={{ $i }} ? 'border-indigo-300 bg-indigo-50' : 'border-transparent'" class="flex min-h-11 w-full items-center gap-3 rounded-lg border px-2 text-left">
                    <span class="w-24 text-sm text-slate-700">{{ $a['label'] }}</span>
                    <span class="block h-3.5 flex-1 overflow-hidden rounded-full bg-slate-100"><span class="block h-full rounded-full {{ ['bg-blue-300','bg-blue-500','bg-orange-600','bg-red-700'][$i] }}" style="width: {{ round($a['amount'] / $ageMax * 100) }}%"></span></span>
                    <span class="w-20 text-right text-sm font-semibold text-slate-900">{{ $svcMoney->money($a['amount']) }}</span>
                </button>
                @endforeach
            </div>
            <div class="mt-4 border-t border-slate-200 pt-3">
                <p class="mb-1 text-sm font-semibold text-slate-900"><span x-text="age[sel].label"></span> · top clients</p>
                <template x-for="c in age[sel].clients" :key="c.url">
                    <div class="flex items-center justify-between py-1.5 text-sm">
                        <a :href="c.url" x-text="c.name" class="text-slate-900 hover:text-indigo-700"></a>
                        <span x-text="c.fmt" class="text-slate-600"></span>
                    </div>
                </template>
                <p x-show="!age[sel].clients.length" class="text-sm text-slate-600">Nothing in this group.</p>
                <a href="{{ route('collections.index') }}" class="mt-2 inline-block text-sm font-medium text-indigo-700 underline">Open collections</a>
            </div>
        </div>
    </section>
    @endif

    <section class="grid grid-cols-1 gap-5 lg:grid-cols-2 2xl:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-bold text-slate-900">Filing status this month</h2>
            <p class="mb-4 mt-1 text-sm text-slate-600">Filed out of total due in {{ now()->format('F') }}.</p>
            @forelse($filingStatus as $c)
            <a href="{{ route('service-dues.index') }}" class="mb-3 block text-slate-900 hover:text-indigo-700">
                <span class="mb-1.5 flex justify-between text-sm"><strong>{{ $c['name'] }}</strong><span class="text-slate-600">{{ $c['filed'] }} of {{ $c['total'] }} filed</span></span>
                <span class="block h-2.5 overflow-hidden rounded-full bg-slate-100"><span class="block h-full rounded-full bg-indigo-600" style="width: {{ $c['total'] ? round($c['filed'] / $c['total'] * 100) : 0 }}%"></span></span>
            </a>
            @empty
            <p class="text-sm text-slate-600">No filings due this month.</p>
            @endforelse
        </div>

        @if(count($workload))
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-bold text-slate-900">Team workload</h2>
            <p class="mb-4 mt-1 text-sm text-slate-600">Open tasks plus filings due this week. Click a person to filter.</p>
            <div class="flex flex-col gap-2">
                @foreach($workload as $m)
                <a href="{{ route('dashboard', ['scope' => 'firm', 'staff' => $m['id']]) }}" class="block rounded-xl border px-3 py-2.5 {{ $filters['staff'] == $m['id'] ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200 hover:border-slate-300' }}">
                    <span class="flex items-center justify-between gap-2">
                        <span class="text-sm"><strong class="text-slate-900">{{ $m['name'] }}</strong> <span class="text-slate-600">{{ $m['role'] }}</span></span>
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $m['flag'] === 'Heavy' ? 'bg-orange-50 text-orange-800' : ($m['flag'] === 'Free' ? 'bg-green-50 text-green-800' : 'bg-slate-100 text-slate-700') }}">{{ $m['flag'] }}</span>
                    </span>
                    <span class="mt-2 block h-2 overflow-hidden rounded-full bg-slate-100"><span class="block h-full rounded-full {{ $m['flag'] === 'Heavy' ? 'bg-orange-600' : 'bg-indigo-600' }}" style="width: {{ $m['pct'] }}%"></span></span>
                    <span class="mt-1 block text-xs text-slate-600">{{ $m['tasks'] }} tasks · {{ $m['dues'] }} filings this week</span>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        @if($canSeeMoney || ($filters['can_see_firm'] ?? false))
        @php $attention = app(\App\Services\DashboardLayoutDataService::class)->clientList(auth()->user(), $filters, 'risk', null, 6)->filter(fn ($c) => $c['score'] < 100); @endphp
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-bold text-slate-900">Clients needing attention</h2>
            <p class="mb-3 mt-1 text-sm text-slate-600">Scored on overdue filings, overdue payments and DSC expiry.</p>
            @forelse($attention as $c)
            <a href="{{ route('clients.show', $c['id']) }}" class="flex items-center gap-3 border-b border-slate-100 py-2.5 text-slate-900 hover:text-indigo-700">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $c['score'] < 50 ? 'bg-red-50 text-red-700' : ($c['score'] < 75 ? 'bg-orange-50 text-orange-800' : 'bg-green-50 text-green-700') }}">{{ $c['score'] }}</span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold">{{ $c['name'] }}</span>
                    <span class="block truncate text-xs text-slate-600">{{ $c['flag'] }}</span>
                </span>
            </a>
            @empty
            <p class="text-sm text-slate-600">Every client is on track.</p>
            @endforelse
        </div>
        @endif
    </section>
</div>

@include('dashboard.layouts._script')
@endsection
