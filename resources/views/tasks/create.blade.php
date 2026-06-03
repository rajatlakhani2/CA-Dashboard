@extends('layouts.app')

@section('header', 'Create Task')

@push('head_styles')
<style>[x-cloak] { display: none !important; }</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto pb-12" x-data="taskCreateForm()" x-cloak>
    {{-- Page hero --}}
    <div class="mb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="flex items-start gap-4">
            <a href="{{ route('tasks.index') }}" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 transition-colors" aria-label="Back">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            </a>
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">Tasks</p>
                <h1 class="text-2xl sm:text-3xl font-black text-gray-900 mt-0.5">Create a new task</h1>
                <p class="text-sm text-gray-500 mt-1">Search clients &amp; staff, set priority, save in one go.</p>
            </div>
        </div>
        {{-- Step progress --}}
        <div class="flex items-center gap-2 text-xs font-semibold text-gray-500">
            <span class="flex items-center gap-1.5" :class="step >= 1 ? 'text-indigo-600' : ''"><span class="h-6 w-6 rounded-full flex items-center justify-center text-[11px]" :class="step >= 1 ? 'bg-indigo-600 text-white' : 'bg-gray-200'">1</span> Details</span>
            <span class="text-gray-300">›</span>
            <span class="flex items-center gap-1.5" :class="step >= 2 ? 'text-indigo-600' : ''"><span class="h-6 w-6 rounded-full flex items-center justify-center text-[11px]" :class="step >= 2 ? 'bg-indigo-600 text-white' : 'bg-gray-200'">2</span> People</span>
            <span class="text-gray-300">›</span>
            <span class="flex items-center gap-1.5" :class="step >= 3 ? 'text-indigo-600' : ''"><span class="h-6 w-6 rounded-full flex items-center justify-center text-[11px]" :class="step >= 3 ? 'bg-indigo-600 text-white' : 'bg-gray-200'">3</span> Schedule</span>
        </div>
    </div>

    @if ($errors->any())
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <p class="font-bold mb-1">Could not save — please check:</p>
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <form action="{{ route('tasks.store') }}" method="POST" class="lg:col-span-2 space-y-5" @submit="onSubmit">
            @csrf
            <input type="hidden" name="assign_to_me" :value="assignToMe ? '1' : '0'">
            <input type="hidden" name="assigned_to" :value="assignToMe ? '{{ auth()->id() }}' : (assigneeId || '')">

            {{-- Step 1 --}}
            <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden transition-shadow hover:shadow-md">
                <button type="button" @click="step = 1" class="w-full flex items-center gap-3 px-5 py-4 bg-gradient-to-r from-indigo-600 to-indigo-500 text-white text-left">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/20 text-sm font-black">1</span>
                    <span class="font-bold text-base">What is this task about?</span>
                </button>
                <div class="p-5 space-y-4" x-show="step === 1" x-transition>
                    <div>
                        <label for="title" class="block text-sm font-semibold text-gray-800">Task title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" id="title" x-model="title" required autofocus maxlength="255"
                            placeholder="e.g. GSTR-3B filing, Bank reconciliation, DSC renewal…"
                            class="mt-2 block w-full rounded-xl border-gray-300 text-base py-3.5 px-4 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-gray-400 text-right" x-text="title.length + ' / 255'"></p>
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-semibold text-gray-800">Notes <span class="font-normal text-gray-400">(optional)</span></label>
                        <textarea name="description" id="description" x-model="description" rows="4"
                            placeholder="Scope, documents, checklist, links…"
                            class="mt-2 block w-full rounded-xl border-gray-300 text-sm py-3 px-4 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                    <button type="button" @click="step = 2" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-bold hover:bg-gray-800">
                        Next: Client &amp; assignee →
                    </button>
                </div>
            </section>

            {{-- Step 2 --}}
            <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden transition-shadow hover:shadow-md">
                <button type="button" @click="step = 2" class="w-full flex items-center gap-3 px-5 py-4 bg-gradient-to-r from-violet-600 to-indigo-500 text-white text-left">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/20 text-sm font-black">2</span>
                    <span class="font-bold text-base">Client &amp; who will do it</span>
                </button>
                <div class="p-5 space-y-5" x-show="step === 2" x-transition>
                    @include('tasks.partials.searchable-picker', [
                        'name' => 'client_id',
                        'label' => 'Client',
                        'placeholder' => 'Search client name…',
                        'prefix' => 'client',
                        'hint' => 'Leave empty for internal / office tasks.',
                    ])

                    <div class="rounded-xl border-2 border-dashed border-indigo-200 bg-indigo-50/40 p-4">
                        <label class="flex items-center gap-3 cursor-pointer select-none">
                            <input type="checkbox" x-model="assignToMe" @change="onAssignToMeChange()"
                                class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm font-bold text-indigo-900">Assign to me ({{ auth()->user()->name }})</span>
                        </label>
                    </div>

                    <div x-show="!assignToMe" x-transition>
                        @include('tasks.partials.searchable-picker', [
                            'name' => null,
                            'label' => 'Assign to team member',
                            'placeholder' => 'Search staff name…',
                            'prefix' => 'assignee',
                            'hint' => 'Pick Unassigned if billing will be decided later.',
                        ])
                    </div>
                    <p x-show="assignToMe" class="text-sm text-indigo-800 font-medium">✓ Assigned to you</p>

                    <div class="flex gap-2">
                        <button type="button" @click="step = 1" class="px-4 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50">← Back</button>
                        <button type="button" @click="step = 3" class="flex-1 sm:flex-none px-6 py-2.5 rounded-xl bg-gray-900 text-white text-sm font-bold hover:bg-gray-800">Next: Due date →</button>
                    </div>
                </div>
            </section>

            {{-- Step 3 --}}
            <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden transition-shadow hover:shadow-md">
                <button type="button" @click="step = 3" class="w-full flex items-center gap-3 px-5 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-left">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/20 text-sm font-black">3</span>
                    <span class="font-bold text-base">When &amp; how urgent?</span>
                </button>
                <div class="p-5 space-y-5" x-show="step === 3" x-transition>
                    <div>
                        <span class="block text-sm font-semibold text-gray-800 mb-2">Quick due date</span>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="preset in duePresets" :key="preset.days">
                                <button type="button" @click="setDueDate(preset.days)"
                                    class="px-4 py-2 rounded-xl text-sm font-semibold border transition-all"
                                    :class="duePresetActive(preset.days) ? 'bg-indigo-600 text-white border-indigo-600 shadow-md' : 'bg-white text-gray-700 border-gray-200 hover:border-indigo-400'"
                                    x-text="preset.label"></button>
                            </template>
                        </div>
                        <label for="due_date" class="block text-sm text-gray-600 mt-4 mb-1">Custom date</label>
                        <input type="date" name="due_date" id="due_date" x-model="dueDate"
                            class="block w-full rounded-xl border-gray-300 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <span class="block text-sm font-semibold text-gray-800 mb-2">Priority</span>
                        <input type="hidden" name="priority" :value="priority">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <template x-for="p in priorities" :key="p.value">
                                <button type="button" @click="priority = p.value"
                                    class="rounded-xl border py-3 text-sm font-bold transition-all"
                                    :class="priority === p.value ? p.activeClass : 'border-gray-200 bg-white text-gray-600 hover:border-indigo-300'"
                                    x-text="p.label"></button>
                            </template>
                        </div>
                    </div>
                    <div class="flex flex-col-reverse sm:flex-row gap-3 pt-2">
                        <button type="button" @click="step = 2" class="px-4 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50">← Back</button>
                        <button type="submit" class="flex-1 inline-flex justify-center items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 px-8 rounded-xl shadow-lg shadow-indigo-600/30 transition-all hover:scale-[1.01]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Create task
                        </button>
                    </div>
                </div>
            </section>

            <p class="text-center text-xs text-gray-400 lg:hidden">
                After completion → <a href="{{ route('invoices.index', ['tab' => 'unbilled']) }}" class="text-indigo-600 underline">Invoices → Unbilled</a>
            </p>
        </form>

        {{-- Live preview --}}
        <aside class="lg:col-span-1">
            <div class="sticky top-24 bg-white rounded-2xl border border-gray-200 shadow-lg overflow-hidden">
                <div class="px-5 py-4 bg-slate-900 text-white">
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Live preview</p>
                    <p class="text-lg font-bold mt-1 truncate" x-text="title || 'Untitled task'"></p>
                </div>
                <dl class="px-5 py-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-500 shrink-0">Client</dt>
                        <dd class="font-medium text-gray-900 text-right truncate" x-text="clientLabel || '— Internal —'"></dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-500 shrink-0">Assignee</dt>
                        <dd class="font-medium text-right" :class="assigneeLabel ? 'text-gray-900' : 'text-amber-700'" x-text="assignToMe ? '{{ auth()->user()->name }} (you)' : (assigneeLabel || 'Unassigned')"></dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-500 shrink-0">Due</dt>
                        <dd class="font-medium text-gray-900" x-text="dueDateFormatted()"></dd>
                    </div>
                    <div class="flex justify-between gap-2 items-center">
                        <dt class="text-gray-500 shrink-0">Priority</dt>
                        <dd><span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold" :class="priorityBadgeClass()" x-text="priority"></span></dd>
                    </div>
                </dl>
                <div class="px-5 py-3 bg-indigo-50 border-t border-indigo-100 text-xs text-indigo-800">
                    When marked <strong>Completed</strong>, appears in <strong>Invoices → Unbilled</strong> (even if unassigned).
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
function taskCreateForm() {
    const clientOptions = @json($clientsForPicker);
    const assigneeOptions = @json($usersForPicker);
    const emptyClient = { id: '', name: '— No client (internal task) —' };
    const emptyAssignee = { id: '', name: '— Unassigned —' };
    const allClients = [emptyClient, ...clientOptions];
    const allAssignees = [emptyAssignee, ...assigneeOptions];

    const oldClientId = @json(old('client_id'));
    const oldAssigneeId = @json(old('assigned_to', $defaultAssignTo ?: null));

    return {
        step: 1,
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
            { value: 'High', label: 'High', activeClass: 'ring-2 ring-red-500 ring-offset-1 border-red-300 bg-red-50 text-red-800' },
            { value: 'Medium', label: 'Medium', activeClass: 'ring-2 ring-amber-500 ring-offset-1 border-amber-300 bg-amber-50 text-amber-800' },
            { value: 'Normal', label: 'Normal', activeClass: 'ring-2 ring-indigo-500 ring-offset-1 border-indigo-300 bg-indigo-50 text-indigo-800' },
            { value: 'Low', label: 'Low', activeClass: 'ring-2 ring-gray-500 ring-offset-1 border-gray-300 bg-gray-50 text-gray-700' },
        ],

        clientOptions: allClients,
        clientId: oldClientId ? String(oldClientId) : '',
        clientLabel: '',
        clientSearch: '',
        clientOpen: false,
        clientHighlightIndex: 0,

        assigneeOptions: allAssignees,
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
                this.step = 1;
                alert('Please enter a task title.');
            }
        },

        /* Client picker */
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
        clientClear() {
            this.clientSelect(emptyClient);
            this.clientSearch = '';
        },
        clientHighlightNext() {
            const list = this.clientFiltered();
            this.clientHighlightIndex = Math.min(this.clientHighlightIndex + 1, list.length - 1);
        },
        clientHighlightPrev() {
            this.clientHighlightIndex = Math.max(this.clientHighlightIndex - 1, 0);
        },
        clientSelectHighlighted() {
            const list = this.clientFiltered();
            if (list[this.clientHighlightIndex]) this.clientSelect(list[this.clientHighlightIndex]);
        },
        syncClientFromId() {
            const opt = this.clientOptions.find(o => String(o.id) === String(this.clientId));
            if (opt) {
                this.clientLabel = opt.id === '' ? '' : opt.name;
                this.clientSearch = opt.name;
            }
        },

        /* Assignee picker */
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
        assigneeClear() {
            this.assigneeSelect(emptyAssignee);
            this.assigneeSearch = '';
            this.assignToMe = false;
        },
        assigneeHighlightNext() {
            const list = this.assigneeFiltered();
            this.assigneeHighlightIndex = Math.min(this.assigneeHighlightIndex + 1, list.length - 1);
        },
        assigneeHighlightPrev() {
            this.assigneeHighlightIndex = Math.max(this.assigneeHighlightIndex - 1, 0);
        },
        assigneeSelectHighlighted() {
            const list = this.assigneeFiltered();
            if (list[this.assigneeHighlightIndex]) this.assigneeSelect(list[this.assigneeHighlightIndex]);
        },
        syncAssigneeFromId() {
            const opt = this.assigneeOptions.find(o => String(o.id) === String(this.assigneeId));
            if (opt) {
                this.assigneeLabel = opt.id === '' ? '' : opt.name;
                this.assigneeSearch = opt.name;
            }
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
        priorityBadgeClass() {
            return {
                High: 'bg-red-100 text-red-800',
                Medium: 'bg-amber-100 text-amber-800',
                Normal: 'bg-indigo-100 text-indigo-800',
                Low: 'bg-gray-100 text-gray-700',
            }[this.priority] || 'bg-gray-100 text-gray-700';
        },
    };
}
</script>
@endsection
