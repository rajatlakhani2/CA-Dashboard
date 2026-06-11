@extends('layouts.app')

@section('header', 'Create Task')

@push('head_styles')
@include('dashboard.partials.premium-styles')
<style>
[x-cloak] { display: none !important; }
.task-create-shell { max-width: 72rem; margin: 0 auto; }
.task-preview-card {
    background: linear-gradient(145deg, #1e1b4b 0%, #312e81 45%, #4338ca 100%);
    border-radius: 1rem;
    color: #fff;
    box-shadow: 0 20px 40px -16px rgba(49, 46, 129, 0.45);
}
.task-section {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
}
.task-create-table { border-collapse: collapse; }
.task-create-table th {
    width: 7.5rem;
    min-width: 6.5rem;
    vertical-align: top;
    padding: 0.875rem 0.75rem 0.875rem 1rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 700;
    color: #374151;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    white-space: nowrap;
}
.task-create-table td {
    padding: 0.875rem 1rem;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
    min-width: 0;
}
.task-create-table tr:last-child th,
.task-create-table tr:last-child td { border-bottom: none; }
@media (min-width: 640px) {
    .task-create-table th { width: 9.5rem; padding-left: 1.25rem; }
    .task-create-table td { padding: 1rem 1.25rem; }
}
.assign-chip {
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    padding: 0.5rem 1rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #4b5563;
    background: #fff;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.assign-chip:hover { border-color: #c7d2fe; background: #eef2ff; color: #4338ca; }
.assign-chip.active { border-color: #6366f1; background: #eef2ff; color: #4338ca; box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15); }
.chip-btn {
    border-radius: 9999px;
    padding: 0.3rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #4b5563;
    transition: all 0.15s;
}
.chip-btn:hover { border-color: #c7d2fe; background: #eef2ff; color: #4338ca; }
.chip-btn.active { border-color: #6366f1; background: #eef2ff; color: #4338ca; }
.readiness-bar { height: 4px; border-radius: 9999px; background: #e5e7eb; overflow: hidden; }
.readiness-fill { height: 100%; border-radius: 9999px; background: linear-gradient(90deg, #6366f1, #818cf8); transition: width 0.25s ease; }
</style>
@endpush

@section('content')
<div class="task-create-shell px-1 pb-16" x-data="taskCreateForm()" x-cloak data-demo-tour="task-create-form">

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('tasks.index') }}" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 hover:bg-indigo-50 hover:text-indigo-700 transition-colors" aria-label="Back to tasks">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            </a>
            <div>
                <h1 class="font-display text-xl font-bold text-gray-900">Create a new task</h1>
                <p class="text-sm text-gray-500">All fields in one table — preview updates as you type.</p>
            </div>
        </div>
        <div class="hidden sm:flex items-center gap-2 text-xs text-gray-500">
            <kbd class="rounded border border-gray-200 bg-gray-50 px-1.5 py-0.5 font-mono">Tab</kbd>
            <span>move between fields</span>
        </div>
    </div>

    @if ($errors->any())
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <ul class="list-disc list-inside space-y-0.5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('tasks.store') }}" method="POST" @submit="onSubmit" class="grid xl:grid-cols-5 gap-6 items-start">
        @csrf
        <input type="hidden" name="assign_to_me" :value="assignToMe ? '1' : '0'">
        <input type="hidden" name="assigned_to" :value="assignToMe ? '{{ auth()->id() }}' : (assigneeId || '')">
        <input type="hidden" name="priority" :value="priority">

        <div class="xl:col-span-3 space-y-4 min-w-0">

            <section class="task-section overflow-hidden">
                <table class="task-create-table w-full">
                    <tbody>
                        <tr>
                            <th>Task title <span class="text-red-500">*</span></th>
                            <td>
                                <input type="text" name="title" id="title" x-model="title" required autofocus maxlength="255"
                                    placeholder="e.g. GSTR-3B filing, Bank reconciliation, DSC renewal…"
                                    class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    :class="titleError ? 'border-red-300 ring-2 ring-red-100' : ''"
                                    @input="titleError = false">
                                <div class="mt-1 flex items-center justify-between text-[11px]">
                                    <p class="text-red-600 font-medium" x-show="titleError" x-cloak>Title is required</p>
                                    <span class="ml-auto text-gray-400" :class="title.length > 220 ? 'text-amber-600 font-semibold' : ''" x-text="title.length + ' / 255'"></span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Notes</th>
                            <td>
                                <textarea name="description" id="description" x-model="description" rows="2"
                                    placeholder="Scope, documents needed, checklist, links…"
                                    class="block w-full rounded-lg border-gray-300 text-sm py-2 px-3 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th>Client</th>
                            <td class="space-y-2">
                                @if($recentClientsForPicker->isNotEmpty())
                                <div class="flex flex-wrap gap-1.5">
                                    <button type="button" @click="clientClear()"
                                        class="chip-btn"
                                        :class="!clientId ? 'active' : ''">Internal</button>
                                    @foreach($recentClientsForPicker as $client)
                                    <button type="button" @click="clientSelect({ id: {{ $client['id'] }}, name: @js($client['name']) })"
                                        class="chip-btn max-w-[11rem] truncate"
                                        :class="String(clientId) === '{{ $client['id'] }}' ? 'active' : ''"
                                        title="{{ $client['name'] }}">{{ $client['name'] }}</button>
                                    @endforeach
                                </div>
                                @endif
                                @include('tasks.partials.searchable-picker', [
                                    'name' => 'client_id',
                                    'label' => 'Client',
                                    'placeholder' => 'Type to search clients…',
                                    'prefix' => 'client',
                                    'tableCell' => true,
                                    'hint' => 'Leave empty for internal / office tasks.',
                                ])
                                <p class="text-[11px] text-gray-500" x-show="!clientId">Leave empty for internal tasks.</p>
                                <div x-show="clientId" x-transition class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 border border-emerald-200 px-2.5 py-1.5 text-xs font-semibold text-emerald-900">
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span x-text="clientLabel"></span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Assign to</th>
                            <td class="space-y-2">
                                <div class="flex flex-wrap gap-1.5">
                                    <button type="button" @click="setAssignmentMode('me')" class="assign-chip" :class="assignmentMode === 'me' ? 'active' : ''">
                                        Me
                                    </button>
                                    <button type="button" @click="setAssignmentMode('team')" class="assign-chip" :class="assignmentMode === 'team' ? 'active' : ''">
                                        Team
                                    </button>
                                    <button type="button" @click="setAssignmentMode('unassigned')" class="assign-chip" :class="assignmentMode === 'unassigned' ? 'active' : ''">
                                        Unassigned
                                    </button>
                                </div>
                                <p class="text-[11px] text-gray-500" x-text="assigneeHint()"></p>
                                <div x-show="assignmentMode === 'team'" x-transition>
                                    @if($usersForPicker->isEmpty())
                                    <p class="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                                        No team members yet. <a href="{{ route('users.index') }}" class="font-semibold underline">Add staff</a> first.
                                    </p>
                                    @else
                                    @include('tasks.partials.searchable-picker', [
                                        'name' => null,
                                        'label' => 'Team member',
                                        'placeholder' => 'Search by name or role…',
                                        'prefix' => 'assignee',
                                        'tableCell' => true,
                                        'showRole' => true,
                                    ])
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Due date</th>
                            <td class="space-y-2">
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="preset in duePresets" :key="preset.days">
                                        <button type="button" @click="setDueDate(preset.days)"
                                            class="chip-btn"
                                            :class="duePresetActive(preset.days) ? 'active' : ''"
                                            x-text="preset.label"></button>
                                    </template>
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <input type="date" name="due_date" id="due_date" x-model="dueDate"
                                        class="block w-full max-w-[11rem] rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                    <p class="text-xs font-semibold text-indigo-700" x-text="dueRelativeLabel()" x-show="dueDate"></p>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Priority</th>
                            <td>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                                    <template x-for="p in priorities" :key="p.value">
                                        <button type="button" @click="priority = p.value"
                                            class="rounded-lg border py-2 text-xs sm:text-sm font-bold transition-all flex items-center justify-center gap-1"
                                            :class="priority === p.value ? p.activeClass : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-indigo-300'">
                                            <span x-text="p.icon"></span>
                                            <span x-text="p.label"></span>
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
                <p class="text-[11px] text-gray-500">
                    Completed tasks flow to <a href="{{ route('invoices.index', ['tab' => 'unbilled']) }}" class="text-indigo-600 font-semibold hover:underline">Invoices → Unbilled</a>
                </p>
                <div class="flex gap-2">
                    <a href="{{ route('tasks.index') }}" class="px-4 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-white text-center">Cancel</a>
                    <button type="submit" data-demo-tour="task-create-submit"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold shadow-md transition-all"
                        :class="canSubmit() ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-gray-200 text-gray-500 cursor-not-allowed'"
                        :disabled="!canSubmit()">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="submitLabel()"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Live preview --}}
        <aside class="xl:col-span-2 xl:sticky xl:top-6 space-y-4 min-w-[280px]">
            <div class="task-preview-card p-5 space-y-4">
                <p class="text-[10px] font-bold uppercase tracking-widest text-indigo-200">Live preview</p>
                <div>
                    <h3 class="font-display text-lg font-bold leading-snug" x-text="title.trim() || 'Untitled task'"></h3>
                    <p class="mt-2 text-sm text-indigo-100 line-clamp-3" x-show="description.trim()" x-text="description.trim()"></p>
                    <p class="mt-2 text-sm text-indigo-200/80 italic" x-show="!description.trim()">No notes added</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold"
                        :class="priorityBadgeClass()" x-text="priority"></span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-white/15 px-2.5 py-1 text-xs font-medium text-white"
                        x-text="clientLabel || 'Internal task'"></span>
                </div>
                <dl class="grid grid-cols-2 gap-3 text-xs">
                    <div class="rounded-lg bg-white/10 px-3 py-2">
                        <dt class="text-indigo-200">Assignee</dt>
                        <dd class="mt-0.5 font-semibold" x-text="assigneePreview()"></dd>
                    </div>
                    <div class="rounded-lg bg-white/10 px-3 py-2">
                        <dt class="text-indigo-200">Due</dt>
                        <dd class="mt-0.5 font-semibold" x-text="dueDateFormatted()"></dd>
                    </div>
                </dl>
                <p class="text-[11px] text-indigo-200/90" x-text="dueRelativeLabel()" x-show="dueDate"></p>
            </div>

            <div class="task-section p-4">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-bold text-gray-700">Ready to create</p>
                    <span class="text-xs font-bold text-indigo-600" x-text="readinessPercent() + '%'"></span>
                </div>
                <div class="readiness-bar"><div class="readiness-fill" :style="'width:' + readinessPercent() + '%'"></div></div>
                <ul class="mt-3 space-y-1.5 text-xs text-gray-600">
                    <li class="flex items-center gap-2" :class="title.trim() ? 'text-emerald-700' : ''">
                        <span x-text="title.trim() ? '✓' : '○'"></span> Task title
                    </li>
                    <li class="flex items-center gap-2" :class="clientId || assignmentMode === 'unassigned' ? 'text-emerald-700' : ''">
                        <span x-text="clientId || true ? '✓' : '○'"></span> Client (optional)
                    </li>
                    <li class="flex items-center gap-2" :class="assignmentMode !== 'team' || assigneeId ? 'text-emerald-700' : ''">
                        <span x-text="assignmentMode !== 'team' || assigneeId ? '✓' : '○'"></span> Assignment
                    </li>
                    <li class="flex items-center gap-2" :class="dueDate ? 'text-emerald-700' : ''">
                        <span x-text="dueDate ? '✓' : '○'"></span> Due date
                    </li>
                </ul>
            </div>
        </aside>
    </form>
</div>

<script>
function taskCreateForm() {
    const clientOptions = @json($clientsForPicker);
    const assigneeOptions = @json($usersForPicker);
    const emptyClient = { id: '', name: '— No client (internal task) —' };
    const emptyAssignee = { id: '', name: '— Unassigned —' };
    const authUserId = '{{ auth()->id() }}';
    const authUserName = @json(auth()->user()->name);

    const oldClientId = @json(old('client_id'));
    const oldAssigneeId = @json(old('assigned_to', $defaultAssignTo ?: null));
    const oldAssignToMe = @json(old('assign_to_me', '0') === '1');

    return {
        title: @json(old('title', '')),
        description: @json(old('description', '')),
        titleError: false,
        assignToMe: oldAssignToMe,
        assignmentMode: oldAssignToMe ? 'me' : (oldAssigneeId ? 'team' : 'unassigned'),
        priority: @json(old('priority', 'Normal')),
        dueDate: @json(old('due_date', $prefillDueDate)),
        duePresets: [
            { label: 'Today', days: 0 },
            { label: 'Tomorrow', days: 1 },
            { label: 'In 7 days', days: 7 },
            { label: 'In 30 days', days: 30 },
        ],
        priorities: [
            { value: 'High', label: 'High', icon: '🔴', activeClass: 'ring-2 ring-red-400 border-red-300 bg-red-50 text-red-800' },
            { value: 'Medium', label: 'Medium', icon: '🟠', activeClass: 'ring-2 ring-amber-400 border-amber-300 bg-amber-50 text-amber-800' },
            { value: 'Normal', label: 'Normal', icon: '🔵', activeClass: 'ring-2 ring-indigo-400 border-indigo-300 bg-indigo-50 text-indigo-800' },
            { value: 'Low', label: 'Low', icon: '⚪', activeClass: 'ring-2 ring-gray-400 border-gray-300 bg-gray-50 text-gray-700' },
        ],
        clientOptions: [emptyClient, ...clientOptions],
        clientId: oldClientId ? String(oldClientId) : '',
        clientLabel: '',
        clientSearch: '',
        clientOpen: false,
        clientHighlightIndex: 0,
        assigneeOptions: [emptyAssignee, ...assigneeOptions],
        assigneeId: oldAssigneeId ? String(oldAssigneeId) : '',
        assigneeLabel: '',
        assigneeSearch: '',
        assigneeOpen: false,
        assigneeHighlightIndex: 0,

        init() {
            this.syncClientFromId();
            this.syncAssigneeFromId();
            if (this.assignToMe) {
                this.assigneeId = authUserId;
                this.assigneeLabel = authUserName;
                this.assignmentMode = 'me';
            } else if (this.assigneeId && String(this.assigneeId) !== authUserId) {
                this.assignmentMode = 'team';
            }
        },
        setAssignmentMode(mode) {
            this.assignmentMode = mode;
            if (mode === 'me') {
                this.assignToMe = true;
                this.assigneeId = authUserId;
                this.assigneeLabel = authUserName;
                this.assigneeOpen = false;
            } else if (mode === 'unassigned') {
                this.assignToMe = false;
                this.assigneeId = '';
                this.assigneeLabel = '';
                this.assigneeSearch = '';
            } else {
                this.assignToMe = false;
            }
        },
        canSubmit() {
            if (!this.title.trim()) return false;
            if (this.assignmentMode === 'team' && !this.assigneeId) return false;
            return true;
        },
        submitLabel() {
            if (!this.title.trim()) return 'Add a title to continue';
            if (this.assignmentMode === 'team' && !this.assigneeId) return 'Pick a team member';
            return 'Create task';
        },
        readinessPercent() {
            let score = 0;
            if (this.title.trim()) score += 40;
            if (this.dueDate) score += 25;
            if (this.assignmentMode !== 'team' || this.assigneeId) score += 20;
            if (this.clientId || this.assignmentMode === 'unassigned') score += 15;
            return Math.min(100, score);
        },
        assigneePreview() {
            if (this.assignmentMode === 'me') return authUserName + ' (you)';
            if (this.assignmentMode === 'unassigned') return 'Unassigned';
            return this.assigneeLabel || 'Pick a team member';
        },
        assigneeHint() {
            if (this.assignmentMode === 'me') return 'Assigned to you';
            if (this.assignmentMode === 'unassigned') return 'No assignee — you can assign later from Tasks or Workload';
            return this.assigneeLabel ? ('Assigned to ' + this.assigneeLabel) : 'Select a team member below';
        },
        priorityBadgeClass() {
            const map = {
                High: 'bg-red-500/30 text-red-100',
                Medium: 'bg-amber-500/30 text-amber-100',
                Normal: 'bg-indigo-400/30 text-indigo-100',
                Low: 'bg-white/20 text-indigo-100',
            };
            return map[this.priority] || map.Normal;
        },
        onSubmit(e) {
            if (!this.canSubmit()) {
                e.preventDefault();
                this.titleError = !this.title.trim();
                if (this.titleError) document.getElementById('title')?.focus();
            }
        },
        clientFiltered() {
            const q = this.clientSearch.toLowerCase().trim();
            const list = q
                ? this.clientOptions.filter(o => o.name.toLowerCase().includes(q))
                : this.clientOptions;
            return list.slice(0, 30);
        },
        clientOnFocus() {
            this.clientOpen = true;
            this.clientHighlightIndex = 0;
            if (this.clientId && this.clientSearch === this.clientLabel) {
                this.clientSearch = '';
            }
        },
        clientOnInput() {
            this.clientOpen = true;
            this.clientHighlightIndex = 0;
            if (this.clientId && this.clientSearch !== this.clientLabel) {
                this.clientId = '';
                this.clientLabel = '';
            }
        },
        clientToggleOpen() {
            this.clientOpen = !this.clientOpen;
            if (this.clientOpen) this.clientHighlightIndex = 0;
        },
        clientDropdownHint() {
            const q = this.clientSearch.trim();
            return q ? ('Results for “' + q + '”') : 'Recent & all clients — type to filter';
        },
        clientSelect(opt) {
            this.clientId = opt.id === '' ? '' : String(opt.id);
            this.clientLabel = opt.id === '' ? '' : opt.name;
            this.clientSearch = opt.id === '' ? '' : opt.name;
            this.clientOpen = false;
        },
        clientClear() { this.clientSelect(emptyClient); this.clientSearch = ''; },
        clientHighlightNext() {
            const list = this.clientFiltered();
            this.clientHighlightIndex = Math.min(this.clientHighlightIndex + 1, list.length - 1);
        },
        clientHighlightPrev() { this.clientHighlightIndex = Math.max(this.clientHighlightIndex - 1, 0); },
        clientSelectHighlighted() {
            const list = this.clientFiltered();
            if (list[this.clientHighlightIndex]) this.clientSelect(list[this.clientHighlightIndex]);
        },
        syncClientFromId() {
            const opt = this.clientOptions.find(o => String(o.id) === String(this.clientId));
            if (opt) { this.clientLabel = opt.id === '' ? '' : opt.name; this.clientSearch = opt.id === '' ? '' : opt.name; }
        },
        assigneeFiltered() {
            const q = this.assigneeSearch.toLowerCase().trim();
            const list = q
                ? this.assigneeOptions.filter(o => {
                    const name = (o.name || '').toLowerCase();
                    const role = (o.role || '').toLowerCase();
                    return name.includes(q) || role.includes(q);
                })
                : this.assigneeOptions.filter(o => o.id);
            return list.slice(0, 25);
        },
        assigneeOnFocus() {
            this.assigneeOpen = true;
            this.assigneeHighlightIndex = 0;
            if (this.assigneeId && this.assigneeSearch === this.assigneeLabel) {
                this.assigneeSearch = '';
            }
        },
        assigneeOnInput() {
            this.assigneeOpen = true;
            this.assigneeHighlightIndex = 0;
            if (this.assigneeId && this.assigneeSearch !== this.assigneeLabel) {
                this.assigneeId = '';
                this.assigneeLabel = '';
                this.assignToMe = false;
            }
        },
        assigneeToggleOpen() {
            this.assigneeOpen = !this.assigneeOpen;
            if (this.assigneeOpen) this.assigneeHighlightIndex = 0;
        },
        assigneeDropdownHint() {
            const q = this.assigneeSearch.trim();
            return q ? ('Results for “' + q + '”') : 'Team members — type name or role';
        },
        assigneeSelect(opt) {
            this.assigneeId = opt.id === '' ? '' : String(opt.id);
            this.assigneeLabel = opt.id === '' ? '' : opt.name;
            this.assigneeSearch = opt.id === '' ? '' : opt.name;
            this.assigneeOpen = false;
            this.assignmentMode = opt.id === '' ? 'unassigned' : 'team';
            this.assignToMe = String(opt.id) === authUserId;
            if (this.assignToMe) this.assignmentMode = 'me';
        },
        assigneeClear() {
            this.assigneeSelect(emptyAssignee);
            this.assigneeSearch = '';
            this.assignmentMode = 'unassigned';
        },
        assigneeHighlightNext() {
            const list = this.assigneeFiltered();
            this.assigneeHighlightIndex = Math.min(this.assigneeHighlightIndex + 1, list.length - 1);
        },
        assigneeHighlightPrev() { this.assigneeHighlightIndex = Math.max(this.assigneeHighlightIndex - 1, 0); },
        assigneeSelectHighlighted() {
            const list = this.assigneeFiltered();
            if (list[this.assigneeHighlightIndex]) this.assigneeSelect(list[this.assigneeHighlightIndex]);
        },
        syncAssigneeFromId() {
            const opt = this.assigneeOptions.find(o => String(o.id) === String(this.assigneeId));
            if (opt) { this.assigneeLabel = opt.id === '' ? '' : opt.name; this.assigneeSearch = opt.id === '' ? '' : opt.name; }
        },
        setDueDate(days) {
            const d = new Date();
            d.setDate(d.getDate() + days);
            this.dueDate = d.toISOString().slice(0, 10);
        },
        duePresetActive(days) {
            if (!this.dueDate) return false;
            const d = new Date();
            d.setDate(d.getDate() + days);
            return this.dueDate === d.toISOString().slice(0, 10);
        },
        dueDateFormatted() {
            if (!this.dueDate) return 'Not set';
            try {
                return new Date(this.dueDate + 'T12:00:00').toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
            } catch { return this.dueDate; }
        },
        dueRelativeLabel() {
            if (!this.dueDate) return '';
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const due = new Date(this.dueDate + 'T12:00:00');
            due.setHours(0, 0, 0, 0);
            const diff = Math.round((due - today) / 86400000);
            if (diff < 0) return `Overdue by ${Math.abs(diff)} day${Math.abs(diff) === 1 ? '' : 's'}`;
            if (diff === 0) return 'Due today';
            if (diff === 1) return 'Due tomorrow';
            return `Due in ${diff} days`;
        },
    };
}
</script>
@endsection
