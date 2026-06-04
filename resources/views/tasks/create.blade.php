@extends('layouts.app')

@section('header', 'Create Task')

@push('head_styles')
<style>
[x-cloak] { display: none !important; }
.task-form-table th {
    width: 9.5rem;
    vertical-align: top;
    padding: 0.75rem 1rem 0.75rem 1.25rem;
    text-align: left;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #374151;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}
.task-form-table td {
    padding: 0.75rem 1.25rem 0.75rem 1rem;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
}
.task-form-table tr:last-child th,
.task-form-table tr:last-child td { border-bottom: 0; }
@media (max-width: 640px) {
    .task-form-table, .task-form-table tbody, .task-form-table tr, .task-form-table th, .task-form-table td {
        display: block;
        width: 100%;
    }
    .task-form-table th {
        border-bottom: 0;
        padding-bottom: 0.25rem;
        background: transparent;
    }
    .task-form-table td { padding-top: 0; }
}
</style>
@endpush

@section('content')
<div class="flex justify-center px-4 py-5 pb-14" x-data="taskCreateForm()" x-cloak>
    <div class="w-full max-w-2xl">
        <div class="mb-4 flex items-center gap-3">
            <a href="{{ route('tasks.index') }}" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-indigo-50 hover:text-indigo-700" aria-label="Back">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            </a>
            <div>
                <h1 class="text-lg font-bold text-gray-900">Create a new task</h1>
                <p class="text-xs text-gray-500">Fill the table below — everything on one screen.</p>
            </div>
        </div>

        @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
            <ul class="list-disc list-inside">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
        @endif

        <form action="{{ route('tasks.store') }}" method="POST" @submit="onSubmit"
            class="bg-white rounded-xl border border-gray-200 shadow-md overflow-hidden">
            @csrf
            <input type="hidden" name="assign_to_me" :value="assignToMe ? '1' : '0'">
            <input type="hidden" name="assigned_to" :value="assignToMe ? '{{ auth()->id() }}' : (assigneeId || '')">
            <input type="hidden" name="priority" :value="priority">

            <table class="task-form-table w-full text-sm">
                <tbody>
                    <tr class="bg-indigo-50/40">
                        <th scope="row">Preview</th>
                        <td class="text-xs text-gray-700 space-y-0.5">
                            <p><span class="font-semibold text-gray-900" x-text="title || 'Untitled task'"></span></p>
                            <p>
                                <span x-text="clientLabel || '— Internal —'"></span>
                                · <span x-text="assignToMe ? '{{ auth()->user()->name }} (you)' : (assigneeLabel || 'Unassigned')"></span>
                                · <span x-text="dueDateFormatted()"></span>
                                · <span class="font-semibold" x-text="priority"></span>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Task title <span class="text-red-500">*</span></th>
                        <td>
                            <input type="text" name="title" id="title" x-model="title" required autofocus maxlength="255"
                                placeholder="e.g. GSTR-3B filing, Bank reconciliation, DSC renewal…"
                                class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="mt-0.5 text-right text-[11px] text-gray-400" x-text="title.length + ' / 255'"></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Notes</th>
                        <td>
                            <textarea name="description" id="description" x-model="description" rows="3"
                                placeholder="Scope, documents, checklist, links…"
                                class="block w-full rounded-lg border-gray-300 text-sm py-2 px-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            <p class="mt-0.5 text-[11px] text-gray-400">Optional</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Client</th>
                        <td>
                            @include('tasks.partials.searchable-picker', [
                                'name' => 'client_id',
                                'label' => 'Client',
                                'placeholder' => 'Search client name…',
                                'prefix' => 'client',
                                'tableCell' => true,
                                'hint' => 'Leave empty for internal / office tasks.',
                            ])
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Assignment</th>
                        <td class="space-y-3">
                            <label class="inline-flex items-center gap-2 cursor-pointer rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2">
                                <input type="checkbox" x-model="assignToMe" @change="onAssignToMeChange()"
                                    class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-semibold text-indigo-900">Assign to me ({{ auth()->user()->name }})</span>
                            </label>
                            <div x-show="!assignToMe" x-transition>
                                @include('tasks.partials.searchable-picker', [
                                    'name' => null,
                                    'label' => 'Team member',
                                    'placeholder' => 'Search staff name…',
                                    'prefix' => 'assignee',
                                    'tableCell' => true,
                                    'hint' => 'Pick Unassigned if billing will be decided later.',
                                ])
                            </div>
                            <p x-show="assignToMe" class="text-sm font-medium text-indigo-800">✓ Assigned to you</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Due date</th>
                        <td>
                            <div class="flex flex-wrap gap-1.5 mb-2">
                                <template x-for="preset in duePresets" :key="preset.days">
                                    <button type="button" @click="setDueDate(preset.days)"
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors"
                                        :class="duePresetActive(preset.days) ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-200 hover:border-indigo-400'"
                                        x-text="preset.label"></button>
                                </template>
                            </div>
                            <label for="due_date" class="text-xs text-gray-500">Custom date</label>
                            <input type="date" name="due_date" id="due_date" x-model="dueDate"
                                class="mt-1 block w-full max-w-xs rounded-lg border-gray-300 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Priority</th>
                        <td>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 max-w-md">
                                <template x-for="p in priorities" :key="p.value">
                                    <button type="button" @click="priority = p.value"
                                        class="rounded-lg border py-2 text-sm font-bold transition-all"
                                        :class="priority === p.value ? p.activeClass : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-indigo-300'"
                                        x-text="p.label"></button>
                                </template>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-4 bg-gray-50 border-t border-gray-100">
                <p class="text-[11px] text-gray-500">
                    When marked <strong>Completed</strong> → <a href="{{ route('invoices.index', ['tab' => 'unbilled']) }}" class="text-indigo-600 underline">Invoices → Unbilled</a>
                </p>
                <div class="flex gap-2">
                    <a href="{{ route('tasks.index') }}" class="px-4 py-2.5 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-white text-center">Cancel</a>
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 px-6 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow-md">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Create task
                    </button>
                </div>
            </div>
        </form>
    </div>

    <p class="fixed bottom-3 right-3 z-10 rounded-md bg-slate-800 text-white text-[10px] font-mono px-2 py-0.5 pointer-events-none">Task UI v4 · table</p>
</div>

<script>
function taskCreateForm() {
    const clientOptions = @json($clientsForPicker);
    const assigneeOptions = @json($usersForPicker);
    const emptyClient = { id: '', name: '— No client (internal task) —' };
    const emptyAssignee = { id: '', name: '— Unassigned —' };

    const oldClientId = @json(old('client_id'));
    const oldAssigneeId = @json(old('assigned_to', $defaultAssignTo ?: null));

    return {
        title: @json(old('title', '')),
        description: @json(old('description', '')),
        assignToMe: @json(old('assign_to_me', '0') === '1'),
        priority: @json(old('priority', 'Normal')),
        dueDate: @json(old('due_date', $prefillDueDate)),
        duePresets: [
            { label: 'Today', days: 0 },
            { label: 'Tomorrow', days: 1 },
            { label: 'In 7 days', days: 7 },
            { label: 'In 30 days', days: 30 },
        ],
        priorities: [
            { value: 'High', label: 'High', activeClass: 'ring-2 ring-red-400 border-red-300 bg-red-50 text-red-800' },
            { value: 'Medium', label: 'Medium', activeClass: 'ring-2 ring-amber-400 border-amber-300 bg-amber-50 text-amber-800' },
            { value: 'Normal', label: 'Normal', activeClass: 'ring-2 ring-indigo-400 border-indigo-300 bg-indigo-50 text-indigo-800' },
            { value: 'Low', label: 'Low', activeClass: 'ring-2 ring-gray-400 border-gray-300 bg-gray-50 text-gray-700' },
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
                this.assigneeId = '{{ auth()->id() }}';
                this.assigneeLabel = '{{ auth()->user()->name }}';
            }
        },
        onAssignToMeChange() {
            if (this.assignToMe) {
                this.assigneeId = '{{ auth()->id() }}';
                this.assigneeLabel = '{{ auth()->user()->name }}';
                this.assigneeOpen = false;
            } else {
                this.assigneeId = '';
                this.assigneeLabel = '';
                this.assigneeSearch = '';
            }
        },
        onSubmit(e) {
            if (!this.title.trim()) {
                e.preventDefault();
                document.getElementById('title')?.focus();
                alert('Please enter a task title.');
            }
        },
        clientFiltered() {
            const q = this.clientSearch.toLowerCase().trim();
            if (!q) return this.clientOptions;
            return this.clientOptions.filter(o => o.name.toLowerCase().includes(q));
        },
        clientSelect(opt) {
            this.clientId = opt.id === '' ? '' : String(opt.id);
            this.clientLabel = opt.id === '' ? '' : opt.name;
            this.clientSearch = opt.name;
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
            if (opt) { this.clientLabel = opt.id === '' ? '' : opt.name; this.clientSearch = opt.name; }
        },
        assigneeFiltered() {
            const q = this.assigneeSearch.toLowerCase().trim();
            if (!q) return this.assigneeOptions;
            return this.assigneeOptions.filter(o => o.name.toLowerCase().includes(q));
        },
        assigneeSelect(opt) {
            this.assigneeId = opt.id === '' ? '' : String(opt.id);
            this.assigneeLabel = opt.id === '' ? '' : opt.name;
            this.assigneeSearch = opt.name;
            this.assigneeOpen = false;
            this.assignToMe = String(opt.id) === '{{ auth()->id() }}';
        },
        assigneeClear() { this.assigneeSelect(emptyAssignee); this.assigneeSearch = ''; this.assignToMe = false; },
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
            if (opt) { this.assigneeLabel = opt.id === '' ? '' : opt.name; this.assigneeSearch = opt.name; }
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
            if (!this.dueDate) return '— Not set —';
            try {
                return new Date(this.dueDate + 'T12:00:00').toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
            } catch { return this.dueDate; }
        },
    };
}
</script>
@endsection
