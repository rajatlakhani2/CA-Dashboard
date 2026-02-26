@extends('layouts.app')

@section('header')
<div class="flex items-center gap-4">
    <a href="{{ route('tasks.index') }}" class="text-text-secondary hover:text-text-main">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
    </a>
    <span>Create New Task</span>
</div>
@endsection

@section('content')
<div class="bg-bg-card shadow rounded-lg max-w-3xl mx-auto">
    <form action="{{ route('tasks.store') }}" method="POST" class="p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <!-- Title -->
            <div class="sm:col-span-2">
                <label for="title" class="block text-sm font-medium text-text-main">Task Title *</label>
                <input type="text" name="title" id="title" required class="mt-1 block w-full rounded-md border-line shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            </div>

            <!-- Client -->
            <div>
                <label for="client_id" class="block text-sm font-medium text-text-main">Client</label>
                <select name="client_id" id="client_id" class="mt-1 block w-full rounded-md border-line shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    <option value="">-- No Client --</option>
                    @foreach($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Assigned To -->
            <div>
                <label for="assigned_to" class="block text-sm font-medium text-text-main">Assign To</label>
                <select name="assigned_to" id="assigned_to" class="mt-1 block w-full rounded-md border-line shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    <option value="">-- Unassigned --</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Priority -->
            <div>
                <label for="priority" class="block text-sm font-medium text-text-main">Priority</label>
                <select name="priority" id="priority" class="mt-1 block w-full rounded-md border-line shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Normal" selected>Normal</option>
                    <option value="Low">Low</option>
                </select>
            </div>

            <!-- Due Date -->
            <div>
                <label for="due_date" class="block text-sm font-medium text-text-main">Due Date</label>
                <input type="date" name="due_date" id="due_date" value="{{ $prefillDueDate ?? '' }}" class="mt-1 block w-full rounded-md border-line shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            </div>

            <!-- Description -->
            <div class="sm:col-span-2">
                <label for="description" class="block text-sm font-medium text-text-main">Description</label>
                <textarea name="description" id="description" rows="4" class="mt-1 block w-full rounded-md border-line shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
            </div>
        </div>

        <div class="flex justify-end pt-4">
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 px-4 rounded shadow">
                Create Task
            </button>
        </div>
    </form>
</div>
@endsection