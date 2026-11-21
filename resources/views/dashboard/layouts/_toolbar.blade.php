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
<section class="hidden md:block">
    <p class="text-sm text-slate-600">{{ now()->format('l, j F Y') }}</p>
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $title }}</h1>
    @isset($subtitle)<p class="mt-1 text-sm text-slate-600">{{ $subtitle }}</p>@endisset
</section>

{{-- Laptop: whose work, staff and layout live in the top bar. --}}
@push('header_tools')
<div class="cd-header-tools">
    @if($f['can_see_firm'])
    <div role="group" aria-label="Whose work" class="cd-seg">
        <a href="{{ route('dashboard', $keep(['scope' => 'firm'])) }}" class="cd-seg__btn {{ $f['scope'] === 'firm' ? 'is-on' : '' }}" @if($f['scope'] === 'firm') aria-current="true" @endif>Whole firm</a>
        <a href="{{ route('dashboard', $keep(['scope' => 'mine', 'staff' => null])) }}" class="cd-seg__btn {{ $f['scope'] === 'mine' ? 'is-on' : '' }}" @if($f['scope'] === 'mine') aria-current="true" @endif>My work</a>
    </div>
    @if($f['scope'] === 'firm')
    <form method="GET" action="{{ route('dashboard') }}" class="cd-header-field">
        @foreach($keep(['staff' => null]) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
        <label for="dash-staff-top" class="cd-header-field__label">Staff</label>
        <select id="dash-staff-top" name="staff" onchange="this.form.submit()" class="cd-header-select" aria-label="Staff member">
            <option value="">All staff</option>
            @foreach($filterOptions['staff'] as $s)
            <option value="{{ $s['id'] }}" @selected($f['staff'] == $s['id'])>{{ $s['name'] }}</option>
            @endforeach
        </select>
    </form>
    @endif
    @endif
    @include('dashboard.partials.layout-switcher', ['switchId' => 'dashboard-layout-switch-top', 'compact' => true])
</div>
@endpush

@if($f['can_see_firm'] && $f['scope'] === 'firm' && (!empty($filterOptions['branches']) || ($showPeriod ?? false) || $hasFilter))
<form method="GET" action="{{ route('dashboard') }}" class="hidden md:flex flex-wrap items-center gap-x-5 gap-y-3 rounded-2xl border border-slate-200 bg-white px-4 py-3" aria-label="Filters">
    <input type="hidden" name="scope" value="firm">
    @if(($showPeriod ?? false))<input type="hidden" name="period" value="{{ $f['period'] }}">@endif
    @if($f['staff'])<input type="hidden" name="staff" value="{{ $f['staff'] }}">@endif
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

{{-- Phones: one compact row; everything else lives in the Filter panel. --}}
@php
    $activeFilters = (int) (bool) $f['branch'] + (int) (bool) ($f['scope'] === 'firm' && $f['staff']) + (int) ($f['can_see_firm'] && $f['scope'] === 'mine');
    $staffName = $f['staff'] ? collect($filterOptions['staff'] ?? [])->firstWhere('id', (int) $f['staff'])['name'] ?? null : null;
    $scopeLabel = ! $f['can_see_firm'] ? 'My work' : ($f['scope'] === 'mine' ? 'My work' : ($staffName ?: 'Whole firm'));
@endphp
<div class="md:hidden">
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="truncate text-xl font-bold tracking-tight text-slate-900">{{ $title }}</h1>
            <p class="truncate text-xs text-slate-500">{{ now()->format('D, j M') }} · {{ $scopeLabel }}</p>
        </div>
        <button type="button" onclick="document.getElementById('dash-filter-sheet').showModal()" class="relative inline-flex min-h-10 shrink-0 items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-800" aria-haspopup="dialog">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5h18M6 12h12M10 19h4"/></svg>
            Filter
            @if($activeFilters)
            <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-600 px-1 text-[11px] font-bold text-white">{{ $activeFilters }}</span>
            @endif
        </button>
    </div>

    <dialog id="dash-filter-sheet" class="dash-sheet" aria-label="Filters" onclick="if (event.target === this) this.close()">
        <div class="px-5 pb-8 pt-3">
            <div class="mx-auto mb-4 h-1.5 w-10 rounded-full bg-slate-300"></div>
            <h2 class="mb-4 text-lg font-bold text-slate-900">Show</h2>

            @if($f['can_see_firm'])
            <div role="group" aria-label="Whose work" class="mb-4 grid grid-cols-2 rounded-xl bg-slate-100 p-1">
                <a href="{{ route('dashboard', $keep(['scope' => 'firm'])) }}" class="rounded-lg py-2.5 text-center text-sm font-semibold {{ $f['scope'] === 'firm' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600' }}">Whole firm</a>
                <a href="{{ route('dashboard', $keep(['scope' => 'mine', 'staff' => null])) }}" class="rounded-lg py-2.5 text-center text-sm font-semibold {{ $f['scope'] === 'mine' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600' }}">My work</a>
            </div>
            @endif

            @if($f['can_see_firm'] && $f['scope'] === 'firm')
            <form method="GET" action="{{ route('dashboard') }}" class="space-y-3">
                <input type="hidden" name="scope" value="firm">
                @if(($showPeriod ?? false))<input type="hidden" name="period" value="{{ $f['period'] }}">@endif
                @if(!empty($filterOptions['branches']))
                <label class="block text-sm font-medium text-slate-600">Branch
                    <select name="branch" onchange="this.form.submit()" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-base text-slate-900">
                        <option value="">All branches</option>
                        @foreach($filterOptions['branches'] as $b)
                        <option value="{{ $b['id'] }}" @selected($f['branch'] == $b['id'])>{{ $b['name'] }}</option>
                        @endforeach
                    </select>
                </label>
                @endif
                <label class="block text-sm font-medium text-slate-600">Staff member
                    <select name="staff" onchange="this.form.submit()" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-base text-slate-900">
                        <option value="">Everyone</option>
                        @foreach($filterOptions['staff'] as $s)
                        <option value="{{ $s['id'] }}" @selected($f['staff'] == $s['id'])>{{ $s['name'] }}</option>
                        @endforeach
                    </select>
                </label>
            </form>
            @endif

            @if($showPeriod ?? false)
            <p class="mb-1 mt-4 text-sm font-medium text-slate-600">Chart period</p>
            <div class="grid grid-cols-3 gap-2">
                @foreach(['6m' => '6 months', 'q' => 'Quarters', 'fy' => 'Years'] as $k => $label)
                <a href="{{ route('dashboard', $keep(['period' => $k === '6m' ? null : $k])) }}" class="rounded-xl border py-2.5 text-center text-sm {{ $f['period'] === $k ? 'border-indigo-600 bg-indigo-600 font-semibold text-white' : 'border-slate-300 bg-white text-slate-700' }}">{{ $label }}</a>
                @endforeach
            </div>
            @endif

            <div class="mt-5 border-t border-slate-100 pt-4">
                @include('dashboard.partials.layout-switcher', ['switchId' => 'dashboard-layout-switch-phone'])
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3">
                @if($activeFilters)
                <a href="{{ route('dashboard', ['scope' => 'firm']) }}" class="rounded-xl border border-slate-300 py-3 text-center text-sm font-semibold text-slate-700">Clear</a>
                @else
                <span></span>
                @endif
                <button type="button" onclick="this.closest('dialog').close()" class="rounded-xl bg-slate-900 py-3 text-sm font-semibold text-white">Done</button>
            </div>
        </div>
    </dialog>
</div>
