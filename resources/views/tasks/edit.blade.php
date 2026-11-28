@extends('layouts.app')

@section('header')
<div class="flex items-center gap-3 min-w-0">
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('tasks.index') }}" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800" aria-label="Back">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
    </a>
    <span class="truncate">Edit task</span>
</div>
@endsection

@section('content')
@php
    $T = \App\Models\Task::class;
    $statusChoice = old('status', $task->status);
    $prioChoice = old('priority', $task->priorityChoice());
    $dueValue = old('due_date', $task->due_date ? $task->due_date->format('Y-m-d') : '');
    $statuses = [
        $T::STATUS_PENDING => ['To do', 'M12 8v4l2.5 2.5M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z', 'border-slate-300 bg-slate-50 text-slate-800'],
        $T::STATUS_IN_PROGRESS => ['In progress', 'M5 12h14M13 6l6 6-6 6', 'border-blue-300 bg-blue-50 text-blue-800'],
        $T::STATUS_ON_HOLD => ['Waiting', 'M10 9v6M14 9v6M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z', 'border-amber-300 bg-amber-50 text-amber-800'],
        $T::STATUS_COMPLETED => ['Done', 'M20 6 9 17l-5-5', 'border-emerald-300 bg-emerald-50 text-emerald-800'],
    ];
    if (! array_key_exists($statusChoice, $statuses)) {
        $statuses = [$statusChoice => [$statusChoice, 'M20 6 9 17l-5-5', 'border-emerald-300 bg-emerald-50 text-emerald-800']] + $statuses;
    }
    $priorities = [
        'Auto' => ['Auto', 'border-blue-300 bg-blue-50 text-blue-800'],
        'High' => ['High', 'border-red-300 bg-red-50 text-red-800'],
        'Medium' => ['Medium', 'border-amber-300 bg-amber-50 text-amber-800'],
        'Low' => ['Low', 'border-slate-300 bg-slate-100 text-slate-700'],
    ];
    $me = auth()->user();
    $meAssignable = $users->contains('id', $me->id);
@endphp

<form action="{{ route('tasks.update', $task) }}" method="POST" id="task-edit-form" class="te mx-auto max-w-3xl"
    x-data="{
        title: {{ \Illuminate\Support\Js::from(old('title', $task->title)) }},
        status: {{ \Illuminate\Support\Js::from($statusChoice) }},
        priority: {{ \Illuminate\Support\Js::from($prioChoice) }},
        due: {{ \Illuminate\Support\Js::from($dueValue) }},
        assignee: {{ \Illuminate\Support\Js::from((string) old('assigned_to', $task->assigned_to)) }},
        dirty: false,
        iso(d) { const z = n => String(n).padStart(2, '0'); return d.getFullYear() + '-' + z(d.getMonth() + 1) + '-' + z(d.getDate()); },
        setDue(days) { const d = new Date(); d.setDate(d.getDate() + days); this.due = this.iso(d); this.dirty = true; },
        days() { if (!this.due) return null; const t = new Date(); t.setHours(0,0,0,0); return Math.round((new Date(this.due + 'T00:00:00') - t) / 86400000); },
        dueText() { const n = this.days(); if (n === null) return 'No due date'; if (n < 0) return 'Overdue by ' + (-n) + (n === -1 ? ' day' : ' days'); if (n === 0) return 'Due today'; if (n === 1) return 'Due tomorrow'; return 'Due in ' + n + ' days'; },
        autoPriority() { const n = this.days(); if (n === null) return 'Low'; return n <= 3 ? 'High' : (n <= 6 ? 'Medium' : 'Low'); },
        effective() { return this.priority === 'Auto' ? this.autoPriority() : this.priority; },
        hint() {
            if (this.priority !== 'Auto') return 'Fixed at ' + this.priority + '. It stays the same whatever the due date.';
            if (!this.due) return 'Auto: Low for now. Add a due date, or pick High, Medium or Low yourself.';
            return 'Auto: ' + this.autoPriority() + ' now. Medium from 6 days before the due date, High from 3 days before.';
        },
    }"
    @input="dirty = true" @change="dirty = true">
    @csrf
    @method('PUT')

    @if ($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
    @endif

    {{-- Summary --}}
    <div class="te-summary max-sm:hidden mb-4 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
            <span class="te-chip rounded-md border px-2 py-0.5"
                :class="{ 'border-red-200 bg-red-50 text-red-700': effective()==='High', 'border-amber-200 bg-amber-50 text-amber-800': effective()==='Medium', 'border-slate-200 bg-slate-50 text-slate-600': effective()==='Low' }"
                x-text="effective() + ' priority' + (priority === 'Auto' ? ' · auto' : '')">{{ $task->priority }} priority</span>
            <span class="te-chip rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 text-slate-700" x-text="dueText()">{{ $task->due_date ? $task->due_date->format('d M Y') : 'No due date' }}</span>
            @if($task->is_billed)
            <span class="te-chip rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-emerald-800">Billed / FOC</span>
            @elseif(in_array($task->status, $T::TERMINAL_STATUSES, true))
            <span class="te-chip rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-amber-800">Waiting to be billed</span>
            @endif
        </div>
        <p class="mt-2 text-lg font-bold leading-snug text-slate-900 break-words" x-text="title || 'Untitled task'">{{ $task->title }}</p>
        <p class="text-sm text-slate-600">{{ $task->client?->name ?? 'Internal task' }}@if($task->creator) · added by {{ $task->creator->name }}@endif</p>
    </div>

    <div class="te-stack space-y-4">
        {{-- What --}}
        <section class="te-card rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
            <label for="title" class="te-label">Task title <span class="text-red-600">*</span></label>
            <input type="text" name="title" id="title" x-model="title" value="{{ old('title', $task->title) }}" required maxlength="255" class="te-input" autocomplete="off">

            <label for="description" class="te-label mt-4">Notes</label>
            <textarea name="description" id="description" rows="3" class="te-input te-textarea" @focus="$el.classList.add('is-open')" placeholder="Scope, documents needed, checklist…">{{ old('description', $task->description) }}</textarea>
        </section>

        {{-- Status --}}
        <section class="te-card rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
            <p class="te-label" id="status-label">Status</p>
            <div class="grid grid-cols-4 gap-2" role="radiogroup" aria-labelledby="status-label">
                @foreach($statuses as $value => [$label, $icon, $on])
                <label class="te-option te-option--status" :class="status === @js($value) ? '{{ $on }} is-on' : ''">
                    <input type="radio" name="status" value="{{ $value }}" x-model="status" class="sr-only" @checked($statusChoice === $value)>
                    <svg class="te-ico h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                    <span>{{ $label }}</span>
                </label>
                @endforeach
            </div>
        </section>

        {{-- When + priority --}}
        <section class="te-card rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
            <div class="flex items-center justify-between gap-3">
                <label for="due_date" class="te-label mb-0">Due date</label>
                <span class="text-sm font-semibold" :class="days() !== null && days() < 0 ? 'text-red-700' : 'text-slate-600'" x-text="dueText()"></span>
            </div>
            <div class="te-pills mt-2 flex flex-wrap gap-2">
                <button type="button" class="te-pill" @click="setDue(0)">Today</button>
                <button type="button" class="te-pill" @click="setDue(1)">Tomorrow</button>
                <button type="button" class="te-pill" @click="setDue(7)">In 7 days</button>
                <button type="button" class="te-pill" @click="setDue(30)">In 30 days</button>
                <button type="button" class="te-pill" x-show="due" @click="due = ''; dirty = true">No date</button>
            </div>
            <input type="date" name="due_date" id="due_date" x-model="due" value="{{ $dueValue }}" class="te-input te-date mt-3 sm:max-w-xs">

            <div class="te-prio-head mt-5 flex items-center justify-between gap-2"><p class="te-label mb-0" id="priority-label">Priority</p><span class="sm:hidden text-xs font-bold" :class="{ 'text-red-700': effective()==='High', 'text-amber-700': effective()==='Medium', 'text-slate-600': effective()==='Low' }" x-text="'Now: ' + effective()"></span></div>
            <div class="grid grid-cols-4 gap-2" role="radiogroup" aria-labelledby="priority-label">
                @foreach($priorities as $value => [$label, $on])
                <label class="te-option justify-center" :class="priority === @js($value) ? '{{ $on }} is-on' : ''">
                    <input type="radio" name="priority" value="{{ $value }}" x-model="priority" class="sr-only" @checked($prioChoice === $value)>
                    @if($value === 'Auto')<svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>@endif
                    <span>{{ $label }}</span>
                </label>
                @endforeach
            </div>
            <p class="te-hint mt-2 text-sm text-slate-600" x-text="hint()"></p>
        </section>

        {{-- Who --}}
        <section class="te-card rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <label for="client_id" class="te-label">Client</label>
                    <select name="client_id" id="client_id" class="te-input">
                        <option value="">Internal (no client)</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected((string) old('client_id', $task->client_id) === (string) $client->id)>{{ $client->name }}</option>
                        @endforeach
                        @if($task->client && ! $clients->contains('id', $task->client_id))
                        <option value="{{ $task->client_id }}" selected>{{ $task->client->name }} (inactive)</option>
                        @endif
                    </select>
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <label for="assigned_to" class="te-label">Assigned to</label>
                        @if($meAssignable)
                        <button type="button" class="te-me text-sm font-semibold text-blue-700 hover:underline" x-show="assignee !== @js((string) $me->id)" @click="assignee = @js((string) $me->id); dirty = true">Assign to me</button>
                        @endif
                    </div>
                    <select name="assigned_to" id="assigned_to" x-model="assignee" class="te-input">
                        <option value="">Unassigned</option>
                        @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) old('assigned_to', $task->assigned_to) === (string) $user->id)>{{ $user->name }}{{ $user->id === $me->id ? ' (me)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>
    </div>

    {{-- Save bar: stays in view above the phone's bottom menu --}}
    <div class="te-savebar">
        <a href="{{ route('tasks.index') }}" class="te-btn te-btn--ghost">Cancel</a>
        <button type="submit" class="te-btn te-btn--primary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            <span x-text="dirty ? 'Save changes' : 'Save'">Save changes</span>
        </button>
    </div>
</form>

{{-- Other actions: separate forms (kept outside the edit form so each button does only its own job) --}}
@php $canFoc = auth()->user()->can('markFoc', $task) && ! $task->is_billed && in_array($task->status, $T::TERMINAL_STATUSES, true); @endphp
@if($canFoc || auth()->user()->can('delete', $task))
<div class="mx-auto mt-6 max-w-3xl rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
    <p class="te-label">More actions</p>
    <div class="flex flex-wrap items-center gap-2">
        @if($canFoc)
        <form action="{{ route('tasks.mark-foc', $task) }}" method="POST" onsubmit="return confirm('Mark this task as free of cost? It will not appear in Unbilled.');">
            @csrf
            @method('PATCH')
            <button type="submit" class="te-btn te-btn--ghost">Mark as free of cost</button>
        </form>
        @endif
        @include('tasks.partials.delete-button', ['task' => $task])
    </div>
    @if($canFoc)
    <p class="mt-2 text-xs text-slate-600">Completed work shows in Invoices → Unbilled until it is invoiced or marked free of cost.</p>
    @endif
</div>
@endif
<style>
    .te-label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 700; color: var(--cd-text-2, #475569); }
    .te-input { display: block; width: 100%; min-height: 48px; border-radius: 12px; border: 1px solid var(--cd-border-strong, #cbd5e1); background: var(--cd-surface, #fff); color: var(--cd-text, #0f172a); padding: 10px 14px; font-size: 16px; line-height: 1.4; }
    .te-input:focus { outline: none; border-color: var(--cd-primary, #2563eb); box-shadow: 0 0 0 3px rgba(37, 99, 235, .18); }
    .te-textarea { min-height: 96px; resize: vertical; }
    select.te-input { appearance: none; padding-right: 40px; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; }
    .te-option { display: flex; align-items: center; gap: 8px; min-height: 48px; padding: 0 12px; border-radius: 12px; border: 1.5px solid var(--cd-border, #e2e8f0); background: var(--cd-surface, #fff); color: var(--cd-text-2, #334155); font-size: 14px; font-weight: 600; cursor: pointer; user-select: none; transition: border-color .12s ease, background-color .12s ease; }
    .te-option:hover { border-color: var(--cd-border-strong, #cbd5e1); }
    .te-option.is-on { font-weight: 700; border-width: 2px; box-shadow: 0 1px 2px rgba(15, 23, 42, .08); }
    .te-option.is-on.border-slate-300 { border-color: #64748b; }
    .te-option.is-on.border-blue-300 { border-color: #2563eb; }
    .te-option.is-on.border-amber-300 { border-color: #d97706; }
    .te-option.is-on.border-emerald-300 { border-color: #059669; }
    .te-option.is-on.border-red-300 { border-color: #dc2626; }
    .te-option:has(input:focus-visible) { box-shadow: 0 0 0 3px rgba(37, 99, 235, .3); }
    .te-pill { min-height: 36px; padding: 0 14px; border-radius: 999px; border: 1px solid var(--cd-border, #e2e8f0); background: var(--cd-surface-2, #f8fafc); color: var(--cd-text-2, #334155); font-size: 13px; font-weight: 600; }
    .te-pill:hover { border-color: var(--cd-primary, #2563eb); color: var(--cd-primary, #2563eb); }
    .te-savebar { position: sticky; bottom: 0; z-index: 30; display: flex; gap: 10px; justify-content: flex-end; margin-top: 16px; padding: 12px 0; background: linear-gradient(to top, var(--cd-bg, #f8fafc) 70%, transparent); }
    .te-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 48px; padding: 0 20px; border-radius: 12px; font-size: 15px; font-weight: 700; border: 1px solid transparent; text-decoration: none; cursor: pointer; }
    .te-btn--primary { background: var(--cd-primary, #2563eb); color: #fff; }
    .te-btn--primary:hover { background: var(--cd-primary-hover, #1d4ed8); }
    .te-btn--ghost { background: var(--cd-surface, #fff); border-color: var(--cd-border-strong, #cbd5e1); color: var(--cd-text, #0f172a); }
    .te-btn--ghost:hover { background: var(--cd-surface-2, #f1f5f9); }
    /* Phones: the whole form fits in one screen (no scrolling) */
    @media (max-width: 639px) {
        .te-stack { margin: 0; border: 1px solid var(--cd-border, #e2e8f0); border-radius: 16px; background: var(--cd-surface, #fff); overflow: hidden; }
        .te-stack > :not([hidden]) ~ :not([hidden]) { margin-top: 0 !important; }
        .te-stack > .te-card { border: 0; border-radius: 0; padding: 10px 12px; background: transparent; }
        .te-stack > .te-card + .te-card { border-top: 1px solid var(--cd-border, #e2e8f0); }
        .te-label { margin-bottom: 4px; font-size: 12px; }
        .te-card .te-label.mt-4 { margin-top: 8px; }
        .te-input { min-height: 42px; padding: 8px 12px; border-radius: 10px; font-size: 16px; }
        .te-textarea { min-height: 42px; height: 42px; resize: none; }
        .te-textarea.is-open { height: 96px; }
        .te-option { min-height: 40px; padding: 0 6px; gap: 4px; border-radius: 10px; font-size: 13px; justify-content: center; }
        .te-option--status .te-ico { display: none; }
        .te-option--status { font-size: 12.5px; white-space: nowrap; letter-spacing: -0.01em; }
        .te-pills { flex-wrap: nowrap; overflow-x: auto; gap: 6px; margin-top: 6px; scrollbar-width: none; }
        .te-pills::-webkit-scrollbar { display: none; }
        .te-pill { min-height: 32px; padding: 0 11px; font-size: 12.5px; white-space: nowrap; flex-shrink: 0; }
        .te-date { margin-top: 8px; }
        .te-prio-head { margin-top: 10px; }
        .te-hint { margin-top: 4px; font-size: 12px; line-height: 1.35; }
        .te-me { font-size: 12px; }
        .te { padding-bottom: 74px; }
    }
    @media (max-width: 1023px) {
        /* fixed just above the bottom menu (the page's outer box doesn't scroll, so sticky can't be used) */
        .te { padding-bottom: 84px; }
        .te-savebar { position: fixed; left: 0; right: 0; bottom: calc(64px + env(safe-area-inset-bottom, 0px)); z-index: 45; margin: 0; padding: 10px 16px; background: var(--cd-surface, #fff); border-top: 1px solid var(--cd-border, #e2e8f0); box-shadow: 0 -6px 18px rgba(15, 23, 42, .08); }
        .te-savebar .te-btn { flex: 1; }
        .te-savebar .te-btn--ghost { flex: 0 0 auto; }
    }
    [data-theme="dark"] .te-option.is-on.bg-slate-50, [data-theme="dark"] .te-option.is-on.bg-slate-100 { background: var(--cd-surface-2) !important; color: var(--cd-text) !important; border-color: #8b949e !important; }
    [data-theme="dark"] .te-option.is-on.bg-blue-50 { background: rgba(56, 139, 253, .15) !important; color: #79b8ff !important; border-color: #388bfd !important; }
    [data-theme="dark"] .te-option.is-on.bg-red-50 { background: rgba(248, 81, 73, .15) !important; color: #ff9b95 !important; border-color: #f85149 !important; }
    [data-theme="dark"] .te-option.is-on.bg-amber-50 { background: rgba(210, 153, 34, .15) !important; color: #e3b341 !important; border-color: #d29922 !important; }
    [data-theme="dark"] .te-option.is-on.bg-emerald-50 { background: rgba(63, 185, 80, .15) !important; color: #56d364 !important; border-color: #3fb950 !important; }
    [data-theme="dark"] .te-chip.bg-red-50 { background: rgba(248, 81, 73, .15) !important; color: #ff9b95 !important; border-color: rgba(248, 81, 73, .4) !important; }
    [data-theme="dark"] .te-chip.bg-amber-50 { background: rgba(210, 153, 34, .15) !important; color: #e3b341 !important; border-color: rgba(210, 153, 34, .4) !important; }
    [data-theme="dark"] .te-chip.bg-emerald-50 { background: rgba(63, 185, 80, .15) !important; color: #56d364 !important; border-color: rgba(63, 185, 80, .4) !important; }
</style>
@endsection
