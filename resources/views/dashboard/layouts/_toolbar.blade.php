{{-- Shared header for the Action inbox / Work board / Client first layouts. --}}
@php
    $f = $filters;
    $keep = fn (array $extra = []) => array_filter(array_merge([
        'scope' => $f['can_see_firm'] ? $f['scope'] : null,
        'branch' => $f['branch'],
        'staff' => $f['scope'] === 'firm' ? $f['staff'] : null,
        'period' => $f['period'] !== '6m' ? $f['period'] : null,
    ], $extra), fn ($v) => $v !== null && $v !== '');
    $hasFilter = $f['branch'] || ($f['scope'] === 'firm' && $f['staff']);
@endphp
<section class="flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
        <p class="text-sm text-slate-600">{{ now()->format('l, j F Y') }}</p>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $title }}</h1>
        @isset($subtitle)<p class="mt-1 text-sm text-slate-600">{{ $subtitle }}</p>@endisset
    </div>
    <div class="flex flex-wrap items-center gap-3">
        @if($f['can_see_firm'])
        <div role="group" aria-label="Whose work" class="flex rounded-xl bg-slate-200/70 p-1">
            <a href="{{ route('dashboard', $keep(['scope' => 'firm'])) }}" class="rounded-lg px-3 py-2 text-sm font-semibold {{ $f['scope'] === 'firm' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">Whole firm</a>
            <a href="{{ route('dashboard', $keep(['scope' => 'mine', 'staff' => null])) }}" class="rounded-lg px-3 py-2 text-sm font-semibold {{ $f['scope'] === 'mine' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">My work</a>
        </div>
        @endif
        @include('dashboard.partials.layout-switcher')
    </div>
</section>

@if($f['can_see_firm'] && $f['scope'] === 'firm')
<form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-center gap-x-5 gap-y-3 rounded-2xl border border-slate-200 bg-white px-4 py-3" aria-label="Filters">
    <input type="hidden" name="scope" value="firm">
    @if(($showPeriod ?? false))<input type="hidden" name="period" value="{{ $f['period'] }}">@endif
    @if(!empty($filterOptions['branches']))
    <label class="flex items-center gap-2 text-sm text-slate-600">Branch
        <select name="branch" onchange="this.form.submit()" class="min-h-10 rounded-lg border border-slate-300 bg-white px-2 text-sm text-slate-900">
            <option value="">All branches</option>
            @foreach($filterOptions['branches'] as $b)
            <option value="{{ $b['id'] }}" @selected($f['branch'] == $b['id'])>{{ $b['name'] }}</option>
            @endforeach
        </select>
    </label>
    @endif
    <label class="flex items-center gap-2 text-sm text-slate-600">Staff
        <select name="staff" onchange="this.form.submit()" class="min-h-10 rounded-lg border border-slate-300 bg-white px-2 text-sm text-slate-900">
            <option value="">Everyone</option>
            @foreach($filterOptions['staff'] as $s)
            <option value="{{ $s['id'] }}" @selected($f['staff'] == $s['id'])>{{ $s['name'] }}</option>
            @endforeach
        </select>
    </label>
    @if($showPeriod ?? false)
    <div role="group" aria-label="Chart period" class="flex items-center gap-1 text-sm text-slate-600">
        <span class="mr-1">Period</span>
        @foreach(['6m' => '6 months', 'q' => 'Quarters', 'fy' => 'Years'] as $k => $label)
        <a href="{{ route('dashboard', $keep(['period' => $k === '6m' ? null : $k])) }}" class="rounded-lg border px-3 py-2 text-sm {{ $f['period'] === $k ? 'border-indigo-600 bg-indigo-600 font-semibold text-white' : 'border-slate-300 bg-white text-slate-700 hover:border-slate-400' }}">{{ $label }}</a>
        @endforeach
    </div>
    @endif
    @if($hasFilter)
    <a href="{{ route('dashboard', ['scope' => 'firm']) }}" class="text-sm font-medium text-indigo-700 underline">Clear filters</a>
    @endif
    <noscript><button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white">Apply</button></noscript>
</form>
@endif
