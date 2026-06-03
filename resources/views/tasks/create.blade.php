@extends('layouts.app')

@section('header')
<div class="flex items-center gap-4">
    <a href="{{ route('tasks.index') }}" class="text-gray-400 hover:text-gray-600" aria-label="Back to tasks">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
    </a>
    <div>
        <span class="block text-xl font-bold text-gray-900">New Task</span>
        <span class="text-sm text-gray-500 font-normal">Add work for a client or internal follow-up</span>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-2xl mx-auto space-y-4 pb-8">
    @if ($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <p class="font-semibold mb-1">Please fix the following:</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="rounded-xl border border-indigo-100 bg-indigo-50/80 px-4 py-3 text-sm text-indigo-900">
        <strong>Tip:</strong> After the work is done, set status to <strong>Completed</strong>. It will appear under
        <a href="{{ route('invoices.index', ['tab' => 'unbilled']) }}" class="underline font-semibold">Invoices → Unbilled</a>
        (even if nobody is assigned).
    </div>

    <form action="{{ route('tasks.store') }}" method="POST" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden" x-data="{ assignToMe: {{ old('assign_to_me', '0') === '1' ? 'true' : 'false' }} }">
        @csrf

        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/80">
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">What needs to be done?</h3>
        </div>
        <div class="p-6 space-y-5">
            <div>
                <label for="title" class="block text-sm font-semibold text-gray-800">Task title <span class="text-red-500">*</span></label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" required autofocus
                    placeholder="e.g. Prepare Q4 GST return for ABC Pvt Ltd"
                    class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm text-base py-3 px-4 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="description" class="block text-sm font-semibold text-gray-800">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                <textarea name="description" id="description" rows="3" placeholder="Scope, documents needed, or links…"
                    class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm text-sm py-3 px-4 focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
            </div>
        </div>

        <div class="px-6 py-4 border-b border-t border-gray-100 bg-gray-50/80">
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Who is it for?</h3>
        </div>
        <div class="p-6 space-y-5">
            <div>
                <label for="client_id" class="block text-sm font-semibold text-gray-800">Client</label>
                <select name="client_id" id="client_id"
                    class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">— No client (internal task) —</option>
                    @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="flex items-center gap-2 cursor-pointer mb-2">
                    <input type="checkbox" name="assign_to_me" value="1" x-model="assignToMe"
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        @change="if (assignToMe) { $refs.assignSelect.value = '{{ auth()->id() }}'; } else { $refs.assignSelect.value = ''; }">
                    <span class="text-sm font-semibold text-gray-800">Assign to me ({{ auth()->user()->name }})</span>
                </label>
                <label for="assigned_to" class="block text-sm text-gray-600">Or choose someone else</label>
                <select name="assigned_to" id="assigned_to" x-ref="assignSelect"
                    class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    @change="assignToMe = false">
                    <option value="">— Leave unassigned —</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ (string) old('assigned_to', $defaultAssignTo) === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-xs text-gray-500">Unassigned tasks still show in Unbilled once marked Completed.</p>
            </div>
        </div>

        <div class="px-6 py-4 border-b border-t border-gray-100 bg-gray-50/80">
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">When &amp; priority</h3>
        </div>
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label for="due_date" class="block text-sm font-semibold text-gray-800">Due date</label>
                <input type="date" name="due_date" id="due_date" value="{{ old('due_date', $prefillDueDate) }}"
                    class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm py-3 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="priority" class="block text-sm font-semibold text-gray-800">Priority</label>
                <select name="priority" id="priority"
                    class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach(['High' => 'High — urgent', 'Medium' => 'Medium', 'Normal' => 'Normal', 'Low' => 'Low'] as $value => $label)
                    <option value="{{ $value }}" {{ old('priority', 'Normal') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="px-6 py-5 bg-gray-50 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:justify-between sm:items-center gap-3">
            <a href="{{ route('tasks.index') }}" class="text-center text-sm font-semibold text-gray-600 hover:text-gray-900 py-2">Cancel</a>
            <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-xl shadow-md transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Create task
            </button>
        </div>
    </form>
</div>
@endsection
