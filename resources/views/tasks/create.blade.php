@extends('layouts.app')

@section('header', 'New Task')

@push('head_styles')
<style>[x-cloak] { display: none !important; }</style>
@endpush

@section('content')
<div class="flex justify-center px-4 py-6 pb-16" x-data="taskCreateForm()" x-cloak>
    <div class="w-full max-w-xl">
        <a href="{{ route('tasks.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-indigo-600 mb-4">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Back to tasks
        </a>

        @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('tasks.store') }}" method="POST" @submit="onSubmit"
            class="bg-white rounded-2xl border border-gray-200/80 shadow-lg shadow-gray-200/50 overflow-hidden">
            @csrf
            <input type="hidden" name="assign_to_me" :value="assignToMe ? '1' : '0'">
            <input type="hidden" name="assigned_to" :value="assignToMe ? '{{ auth()->id() }}' : (assigneeId || '')">
            <input type="hidden" name="priority" :value="priority">

            {{-- Summary strip --}}
            <div class="px-5 py-3 bg-gradient-to-r from-slate-800 to-indigo-900 text-white text-xs">
                <p class="font-bold truncate text-sm text-white mb-1.5" x-text="title || 'New task'"></p>
                <div class="flex flex-wrap gap-x-3 gap-y-1 text-slate-300">
                    <span x-text="'Client: ' + (clientLabel || 'Internal')"></span>
                    <span>·</span>
                    <span x-text="assignToMe ? 'You' : (assigneeLabel || 'Unassigned')"></span>
                    <span>·</span>
                    <span x-text="dueDateFormatted()"></span>
                    <span>·</span>
                    <span class="font-semibold text-white" x-text="priority"></span>
                </div>
            </div>

            <div class="p-5 space-y-4">
                <div>
                    <label for="title" class="text-xs font-semibold text-gray-700">What to do <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" x-model="title" required autofocus maxlength="255"
                        placeholder="e.g. GSTR-3B, bank reconciliation, DSC renewal…"
                        class="mt-1 block w-full rounded-lg border-gray-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @include('tasks.partials.searchable-picker', [
                        'name' => 'client_id',
                        'label' => 'Client',
                        'placeholder' => 'Search client…',
                        'prefix' => 'client',
                        'compact' => true,
                    ])
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 cursor-pointer rounded-lg border border-indigo-100 bg-indigo-50/50 px-3 py-2">
                            <input type="checkbox" x-model="assignToMe" @change="onAssignToMeChange()"
                                class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500">
                            <span class="text-xs font-semibold text-indigo-900">Assign to me</span>
                        </label>
                        <div x-show="!assignToMe">
                            @include('tasks.partials.searchable-picker', [
                                'name' => null,
                                'label' => 'Or assign to',
                                'placeholder' => 'Search staff…',
                                'prefix' => 'assignee',
                                'compact' => true,
                            ])
                        </div>
                    </div>
                </div>

                <div>
                    <span class="text-xs font-semibold text-gray-700">Due date</span>
                    <div class="mt-1 flex flex-wrap gap-1.5">
                        <template x-for="preset in duePresets" :key="preset.days">
                            <button type="button" @click="setDueDate(preset.days)"
                                class="px-2.5 py-1 rounded-md text-xs font-semibold border transition-colors"
                                :class="duePresetActive(preset.days) ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-gray-50 text-gray-700 border-gray-200 hover:border-indigo-300'"
                                x-text="preset.label"></button>
                        </template>
                    </div>
                    <input type="date" name="due_date" id="due_date" x-model="dueDate"
                        class="mt-2 block w-full rounded-lg border-gray-300 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <span class="text-xs font-semibold text-gray-700">Priority</span>
                    <div class="mt-1 grid grid-cols-4 gap-1.5">
                        <template x-for="p in priorities" :key="p.value">
                            <button type="button" @click="priority = p.value"
                                class="rounded-lg border py-2 text-xs font-bold transition-all"
                                :class="priority === p.value ? p.activeClass : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-indigo-200'"
                                x-text="p.label"></button>
                        </template>
                    </div>
                </div>

                <div>
                    <label for="description" class="text-xs font-semibold text-gray-700">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea name="description" id="description" x-model="description" rows="2"
                        placeholder="Documents, scope, links…"
                        class="mt-1 block w-full rounded-lg border-gray-300 text-sm py-2 px-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
            </div>

            <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-[11px] text-gray-500 text-center sm:text-left">
                    Completed tasks → <a href="{{ route('invoices.index', ['tab' => 'unbilled']) }}" class="text-indigo-600 underline font-medium">Unbilled</a>
                </p>
                <div class="flex gap-2 w-full sm:w-auto">
                    <a href="{{ route('tasks.index') }}" class="flex-1 sm:flex-none text-center px-4 py-2.5 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-white">Cancel</a>
                    <button type="submit" class="flex-1 sm:flex-none inline-flex justify-center items-center gap-1.5 px-6 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow-md">
                        Save task
                    </button>
                </div>
            </div>
        </form>
    </div>

    <p class="fixed bottom-3 right-3 z-10 rounded-md bg-slate-800 text-white text-[10px] font-mono px-2 py-0.5 pointer-events-none">Task UI v3</p>
</div>

<script>
function taskCreateForm() {
    const clientOptions = @json($clientsForPicker);
    const assigneeOptions = @json($usersForPicker);
    const emptyClient = { id: '', name: 'No client (internal)' };
    const emptyAssignee = { id: '', name: 'Unassigned' };

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
            { label: '+1 day', days: 1 },
            { label: '+7 days', days: 7 },
            { label: '+30 days', days: 30 },
        ],
        priorities: [
            { value: 'High', label: 'High', activeClass: 'bg-red-100 text-red-800 border-red-300 ring-1 ring-red-400' },
            { value: 'Medium', label: 'Med', activeClass: 'bg-amber-100 text-amber-800 border-amber-300 ring-1 ring-amber-400' },
            { value: 'Normal', label: 'Normal', activeClass: 'bg-indigo-100 text-indigo-800 border-indigo-300 ring-1 ring-indigo-400' },
            { value: 'Low', label: 'Low', activeClass: 'bg-gray-100 text-gray-700 border-gray-300 ring-1 ring-gray-400' },
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
                alert('Please enter what needs to be done.');
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
            if (!this.dueDate) return 'No date';
            try {
                return new Date(this.dueDate + 'T12:00:00').toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
            } catch { return this.dueDate; }
        },
    };
}
</script>
@endsection
