@extends('layouts.app')

@section('header', 'Clean up overdue')

@section('content')
@php
    $f = $filters;
    $query = fn (array $extra = []) => array_filter(array_merge([
        'type' => $type !== 'all' ? $type : null,
        'age' => $age ?: null,
        'staff' => $f['staff'] ?: null,
    ], $extra), fn ($v) => $v !== null && $v !== '');
    $taskCount = $rows->where('kind', 'task')->count();
    $filingCount = $rows->where('kind', 'filing')->count();
@endphp
<div class="mx-auto w-full max-w-4xl pb-40" x-data="{
        pt: [], pf: [],
        allT: @js($rows->where('kind', 'task')->pluck('id')->map(fn ($id) => (string) $id)->values()),
        allF: @js($rows->where('kind', 'filing')->pluck('id')->map(fn ($id) => (string) $id)->values()),
        get tasks() { return this.pt.length },
        get filings() { return this.pf.length },
        get count() { return this.pt.length + this.pf.length },
        toggleAll(on) { this.pt = on ? [...this.allT] : []; this.pf = on ? [...this.allF] : []; },
        moveDate: '',
    }">
    <a href="{{ route('dashboard') }}" class="mb-3 inline-flex items-center gap-1 text-sm font-semibold text-indigo-700">← Back to dashboard</a>
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Clean up overdue work</h1>
    <p class="mt-1 text-sm text-slate-600">Old tasks and filings that were finished but never closed make the dashboard look worse than it is. Tick the ones that are done and close them in one go, or move tasks to a new date. Nothing changes until you press a button.</p>

    @if(session('success'))
    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('dashboard.cleanup') }}" class="mt-5 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4">
        <div role="group" aria-label="Type" class="flex rounded-xl bg-slate-100 p-1">
            @foreach(['all' => 'All', 'tasks' => 'Tasks', 'filings' => 'Filings'] as $k => $label)
            <a href="{{ route('dashboard.cleanup', $query(['type' => $k === 'all' ? null : $k])) }}" class="rounded-lg px-3 py-2 text-sm font-semibold {{ $type === $k ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600' }}">{{ $label }}</a>
            @endforeach
        </div>
        @if($type !== 'all')<input type="hidden" name="type" value="{{ $type }}">@endif
        <label class="flex flex-col text-xs font-semibold text-slate-600">How late
            <select name="age" onchange="this.form.submit()" class="mt-1 min-h-10 rounded-lg border border-slate-300 bg-white px-2 text-sm text-slate-900">
                @foreach($ages as $days => $label)
                <option value="{{ $days }}" @selected($age === $days)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        @if($f['can_see_firm'] && !empty($filterOptions['staff']))
        <label class="flex flex-col text-xs font-semibold text-slate-600">Staff member
            <select name="staff" onchange="this.form.submit()" class="mt-1 min-h-10 rounded-lg border border-slate-300 bg-white px-2 text-sm text-slate-900">
                <option value="">Everyone</option>
                @foreach($filterOptions['staff'] as $s)
                <option value="{{ $s['id'] }}" @selected($f['staff'] == $s['id'])>{{ $s['name'] }}</option>
                @endforeach
            </select>
        </label>
        @endif
        <noscript><button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white">Apply</button></noscript>
    </form>

    <form method="POST" action="{{ route('dashboard.cleanup.apply') }}" id="cleanup-form" class="mt-4">
        @csrf
        <div class="flex items-center justify-between px-1 pb-2">
            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input type="checkbox" class="h-5 w-5 rounded border-slate-300 text-indigo-600"
                    :checked="count && count === allT.length + allF.length" @change="toggleAll($event.target.checked)">
                Select all {{ $rows->count() }}
            </label>
            <span class="text-sm text-slate-500">{{ $taskCount }} {{ \Illuminate\Support\Str::plural('task', $taskCount) }} · {{ $filingCount }} {{ \Illuminate\Support\Str::plural('filing', $filingCount) }}</span>
        </div>

        @if($capped)
        <p class="mb-2 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-900">Showing the first 500 of each. Clean these up, then reload for the rest.</p>
        @endif

        <ul class="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white">
            @forelse($rows as $r)
            @php $model = $r['kind'] === 'task' ? 'pt' : 'pf'; @endphp
            <li>
                <label class="flex cursor-pointer items-start gap-3 px-4 py-3 hover:bg-slate-50" :class="{{ $model }}.includes('{{ $r['id'] }}') ? 'bg-indigo-50/60' : ''">
                    <input type="checkbox" name="{{ $r['kind'] === 'task' ? 'tasks' : 'filings' }}[]" value="{{ $r['id'] }}" x-model="{{ $model }}"
                        class="mt-0.5 h-5 w-5 shrink-0 rounded border-slate-300 text-indigo-600" data-cleanup-row>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-2">
                            <span class="rounded-md px-2 py-0.5 text-xs font-bold {{ $r['kind'] === 'task' ? 'bg-amber-50 text-amber-800' : 'bg-indigo-50 text-indigo-800' }}">{{ $r['kind'] === 'task' ? 'Task' : 'Filing' }}</span>
                            <span class="text-xs font-semibold {{ $r['late'] > 30 ? 'text-red-700' : 'text-orange-700' }}">{{ $r['late'] }} {{ \Illuminate\Support\Str::plural('day', $r['late']) }} late · {{ $r['due']->format('d M Y') }}</span>
                        </span>
                        <span class="mt-1 block text-[15px] font-semibold text-slate-900">{{ $r['title'] }}</span>
                        <span class="block text-sm text-slate-600">{{ $r['client'] }} · {{ $r['who'] }}</span>
                    </span>
                    <a href="{{ $r['url'] }}" class="shrink-0 self-center text-sm font-semibold text-indigo-700" @click.stop>Open</a>
                </label>
            </li>
            @empty
            <li class="px-4 py-12 text-center text-slate-600">Nothing overdue here. 🎉</li>
            @endforelse
        </ul>

        {{-- Action bar: sits above the phone's bottom menu, at the bottom edge on laptops. --}}
        <div x-show="count" x-cloak x-transition
             class="fixed inset-x-0 bottom-16 z-40 px-3 lg:bottom-4 lg:left-auto lg:right-8 lg:px-0">
            <div class="mx-auto flex max-w-3xl flex-wrap items-center gap-2 rounded-2xl bg-slate-900 p-3 text-white shadow-2xl">
                <span class="mr-auto text-sm font-semibold"><span x-text="count"></span> selected</span>
                <button type="submit" name="action" value="done"
                    @click="if (!confirm('Mark ' + count + ' item(s) as done?')) $event.preventDefault()"
                    class="min-h-10 rounded-xl bg-emerald-500 px-4 text-sm font-bold text-white">✓ Mark done</button>
                <span class="flex items-center gap-2" x-show="tasks">
                    <input type="date" name="date" x-model="moveDate" min="{{ now()->toDateString() }}" class="min-h-10 w-40 rounded-xl border-0 bg-white px-2 text-sm text-slate-900" aria-label="New due date">
                    <button type="submit" name="action" value="move" :disabled="!moveDate"
                        @click="if (!confirm('Move ' + tasks + ' task(s) to ' + moveDate + '?' + (filings ? ' Filings are not moved.' : ''))) $event.preventDefault()"
                        class="min-h-10 rounded-xl bg-white px-4 text-sm font-bold text-slate-900 disabled:opacity-50">Move tasks</button>
                </span>
            </div>
        </div>
    </form>
</div>
@endsection
