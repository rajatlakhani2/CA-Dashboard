@extends('layouts.app')

@section('header', 'Priority Board')

@php
    $tabTone = [
        'High' => ['dot' => 'bg-rose-500', 'active' => 'bg-rose-600 text-white shadow-rose-600/30', 'soft' => 'text-rose-700'],
        'Medium' => ['dot' => 'bg-amber-500', 'active' => 'bg-amber-500 text-white shadow-amber-500/30', 'soft' => 'text-amber-700'],
        'Low' => ['dot' => 'bg-slate-400', 'active' => 'bg-slate-600 text-white shadow-slate-600/30', 'soft' => 'text-slate-600'],
    ];
    $bucketTone = [
        'overdue' => 'bg-rose-50 text-rose-700 border-rose-200',
        'today' => 'bg-amber-50 text-amber-800 border-amber-200',
        'week' => 'bg-blue-50 text-blue-800 border-blue-200',
        'later' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'none' => 'bg-gray-50 text-gray-600 border-gray-200',
    ];
@endphp

@section('content')
<div class="space-y-4 max-w-6xl mx-auto" x-data="{ tab: @js($active) }">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Priority Board</h2>
            <p class="text-sm text-gray-500">Open tasks by priority, then by deadline.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('tasks.index') }}" class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:border-indigo-300 shadow-sm">All tasks</a>
            @can('create', App\Models\Task::class)
            <a href="{{ route('tasks.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700">
                <span class="text-base leading-none">+</span> Create Task
            </a>
            @endcan
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('tasks.priority') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <input type="hidden" name="p" :value="tab">
        @unless(auth()->user()?->isArticle())
        <select name="assigned_to" onchange="this.form.submit()" class="rounded-xl border-gray-200 bg-white py-2.5 text-sm shadow-sm">
            <option value="">All assignees</option>
            @foreach($users as $u)
            <option value="{{ $u->id }}" @selected(request('assigned_to') == $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        @endunless
        <select name="client_id" onchange="this.form.submit()" class="rounded-xl border-gray-200 bg-white py-2.5 text-sm shadow-sm">
            <option value="">All clients</option>
            @foreach($clients as $c)
            <option value="{{ $c->id }}" @selected(request('client_id') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
        @if(request()->hasAny(['assigned_to', 'client_id']))
        <a href="{{ route('tasks.priority') }}" class="self-center text-xs text-gray-500 underline">Clear filters</a>
        @endif
    </form>

    {{-- Priority tabs --}}
    <div class="grid grid-cols-3 gap-2" role="tablist">
        @foreach($board as $priority => $data)
        <button type="button" role="tab" @click="tab = @js($priority)"
            :class="tab === @js($priority) ? '{{ $tabTone[$priority]['active'] }} shadow-lg' : 'bg-white text-gray-700 border border-gray-200 hover:border-indigo-300'"
            class="rounded-2xl px-3 py-3 text-left transition-all">
            <div class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full {{ $tabTone[$priority]['dot'] }}"></span>
                <span class="text-sm font-bold">{{ $priority }}</span>
            </div>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-extrabold tabular-nums">{{ $data['total'] }}</span>
                @if($data['overdue'] > 0)
                <span class="text-[11px] font-bold opacity-90">{{ $data['overdue'] }} overdue</span>
                @endif
            </div>
        </button>
        @endforeach
    </div>

    {{-- Deadline-wise sub-tables --}}
    @foreach($board as $priority => $data)
    <div x-show="tab === @js($priority)" x-cloak class="space-y-4">
        @if($data['total'] === 0)
        <div class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">
            No open {{ strtolower($priority) }} priority tasks.
        </div>
        @endif

        @foreach($buckets as $key => $label)
        @php $rows = $data['groups'][$key]; @endphp
        @continue($rows->isEmpty())
        <section class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <header class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100">
                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $bucketTone[$key] }}">{{ $label }}</span>
                <span class="text-xs font-semibold text-gray-500">{{ $rows->count() }} {{ \Illuminate\Support\Str::plural('task', $rows->count()) }}</span>
            </header>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-4 py-2 text-left">Task</th>
                            <th class="px-4 py-2 text-left">Client</th>
                            <th class="px-4 py-2 text-left hidden sm:table-cell">Assignee</th>
                            <th class="px-4 py-2 text-left hidden sm:table-cell">Status</th>
                            <th class="px-4 py-2 text-right">Deadline</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($rows as $task)
                        <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('tasks.edit', $task) }}'">
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $task->title }}</td>
                            <td class="px-4 py-3 text-gray-700">
                                @if($task->client)
                                <a href="{{ route('clients.show', $task->client) }}" onclick="event.stopPropagation()" class="hover:text-indigo-700 hover:underline">{{ $task->client->name }}</a>
                                @else
                                <span class="text-gray-400">Internal</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700 hidden sm:table-cell">{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                            <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ $task->status }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap font-bold {{ $key === 'overdue' ? 'text-rose-600' : 'text-gray-700' }}">
                                @if($task->due_date)
                                {{ $task->due_date->format('d M Y') }}
                                @if($key === 'overdue')
                                <span class="block text-[11px] font-semibold">{{ (int) $task->due_date->copy()->startOfDay()->diffInDays(now()->startOfDay()) }}d late</span>
                                @endif
                                @else
                                —
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        @endforeach
    </div>
    @endforeach
</div>
@endsection
