@extends('layouts.app')

@section('header', 'New Task')

@section('content')
<div class="max-w-3xl mx-auto pb-10" x-data="taskCreateForm()">
    {{-- Top bar --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('tasks.index') }}" class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-800 shadow-sm" aria-label="Back to tasks">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
        </a>
        <div class="min-w-0 flex-1">
            <h1 class="text-2xl font-bold text-gray-900">Create a task</h1>
            <p class="text-sm text-gray-500">Fill in the basics — you can edit or assign later.</p>
        </div>
    </div>

    @if ($errors->any())
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <p class="font-semibold mb-1">Please fix:</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('tasks.store') }}" method="POST" class="space-y-5">
        @csrf

        {{-- Step 1 --}}
        <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 bg-gradient-to-r from-indigo-50 to-white border-b border-gray-100">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-white text-sm font-bold">1</span>
                <h2 class="text-base font-bold text-gray-900">What needs to be done?</h2>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label for="title" class="block text-sm font-semibold text-gray-800">Task title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required autofocus
                        placeholder="e.g. File GSTR-3B for March, Review bank statements…"
                        class="mt-2 block w-full rounded-xl border-gray-300 text-base py-3 px-4 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="description" class="block text-sm font-semibold text-gray-800">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea name="description" id="description" rows="3" placeholder="Documents needed, deadline notes, links…"
                        class="mt-2 block w-full rounded-xl border-gray-300 text-sm py-3 px-4 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                </div>
            </div>
        </section>

        {{-- Step 2 --}}
        <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 bg-gradient-to-r from-indigo-50 to-white border-b border-gray-100">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-white text-sm font-bold">2</span>
                <h2 class="text-base font-bold text-gray-900">Client &amp; assignment</h2>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label for="client_search" class="block text-sm font-semibold text-gray-800">Client</label>
                    <input type="search" id="client_search" x-model="clientSearch" placeholder="Type to filter clients…"
                        class="mt-2 mb-2 block w-full rounded-xl border-gray-300 text-sm py-2.5 px-4 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <select name="client_id" id="client_id" size="1"
                        class="block w-full rounded-xl border-gray-300 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">— No client (internal / office task) —</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}" data-name="{{ strtolower($client->name) }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="assign_to_me" value="1" x-model="assignToMe"
                            @change="toggleAssignToMe()"
                            class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm font-semibold text-gray-800">Assign to me — {{ auth()->user()->name }}</span>
                    </label>
                    <div>
                        <label for="assigned_to" class="block text-sm text-gray-600 mb-1">Or assign to / leave unassigned</label>
                        <select name="assigned_to" id="assigned_to" @change="assignToMe = false"
                            class="block w-full rounded-xl border-gray-300 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— Unassigned (shows in Unbilled when completed) —</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (string) old('assigned_to', $defaultAssignTo) === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </section>

        {{-- Step 3 --}}
        <section class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-5 py-4 bg-gradient-to-r from-indigo-50 to-white border-b border-gray-100">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-white text-sm font-bold">3</span>
                <h2 class="text-base font-bold text-gray-900">Due date &amp; priority</h2>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <span class="block text-sm font-semibold text-gray-800 mb-2">Quick due date</span>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="setDueDate(0)" class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-200 bg-white hover:border-indigo-400 hover:text-indigo-700">Today</button>
                        <button type="button" @click="setDueDate(1)" class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-200 bg-white hover:border-indigo-400 hover:text-indigo-700">Tomorrow</button>
                        <button type="button" @click="setDueDate(7)" class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-200 bg-white hover:border-indigo-400 hover:text-indigo-700">In 7 days</button>
                        <button type="button" @click="setDueDate(30)" class="px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-200 bg-white hover:border-indigo-400 hover:text-indigo-700">In 30 days</button>
                    </div>
                    <label for="due_date" class="block text-sm text-gray-600 mt-3">Or pick a date</label>
                    <input type="date" name="due_date" id="due_date" x-ref="dueDate" value="{{ old('due_date', $prefillDueDate) }}"
                        class="mt-1 block w-full rounded-xl border-gray-300 py-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <span class="block text-sm font-semibold text-gray-800 mb-2">Priority</span>
                    <input type="hidden" name="priority" id="priority" :value="priority">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach(['High' => ['label' => 'High', 'class' => 'border-red-300 bg-red-50 text-red-800'], 'Medium' => ['label' => 'Medium', 'class' => 'border-amber-300 bg-amber-50 text-amber-800'], 'Normal' => ['label' => 'Normal', 'class' => 'border-indigo-300 bg-indigo-50 text-indigo-800'], 'Low' => ['label' => 'Low', 'class' => 'border-gray-300 bg-gray-50 text-gray-700']] as $value => $meta)
                        <button type="button" @click="priority = '{{ $value }}'"
                            :class="priority === '{{ $value }}' ? 'ring-2 ring-indigo-600 ring-offset-1 {{ $meta['class'] }}' : 'border-gray-200 bg-white text-gray-600 hover:border-indigo-300'"
                            class="rounded-xl border py-3 text-sm font-bold transition-all">
                            {{ $meta['label'] }}
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
            <a href="{{ route('tasks.index') }}" class="text-center text-sm font-semibold text-gray-600 hover:text-gray-900 py-2">Cancel</a>
            <button type="submit" class="inline-flex justify-center items-center gap-2 w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 px-10 rounded-xl shadow-lg shadow-indigo-600/25 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                Save task
            </button>
        </div>

        <p class="text-center text-xs text-gray-400">
            When done, set status to <strong>Completed</strong> → appears in
            <a href="{{ route('invoices.index', ['tab' => 'unbilled']) }}" class="text-indigo-600 underline">Invoices → Unbilled</a>
        </p>
    </form>
</div>

<script>
    function taskCreateForm() {
        return {
            assignToMe: {{ old('assign_to_me', '0') === '1' ? 'true' : 'false' }},
            priority: '{{ old('priority', 'Normal') }}',
            clientSearch: '',
            init() {
                this.$watch('clientSearch', (q) => this.filterClients(q));
                if (this.assignToMe) {
                    document.getElementById('assigned_to').value = '{{ auth()->id() }}';
                }
            },
            toggleAssignToMe() {
                const sel = document.getElementById('assigned_to');
                sel.value = this.assignToMe ? '{{ auth()->id() }}' : '';
            },
            setDueDate(days) {
                const d = new Date();
                d.setDate(d.getDate() + days);
                this.$refs.dueDate.value = d.toISOString().slice(0, 10);
            },
            filterClients(query) {
                const q = (query || '').toLowerCase().trim();
                document.querySelectorAll('#client_id option').forEach((opt) => {
                    if (!opt.value) {
                        opt.hidden = false;
                        return;
                    }
                    const name = opt.getAttribute('data-name') || '';
                    opt.hidden = q !== '' && !name.includes(q);
                });
            },
        };
    }
</script>
@endsection
