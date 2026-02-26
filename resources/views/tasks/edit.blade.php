@extends('layouts.app')

@section('header')
<div class="flex items-center gap-4">
    <a href="{{ route('tasks.index') }}" class="text-gray-400 hover:text-gray-500">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
    </a>
    <span>Edit Task</span>

    <div class="ml-auto">
        <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('Delete this task?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Delete Task</button>
        </form>
    </div>
</div>
@endsection

@section('content')
<div class="bg-white shadow rounded-lg max-w-3xl mx-auto">
    <form action="{{ route('tasks.update', $task) }}" method="POST" class="p-6 space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <!-- Title -->
            <div class="sm:col-span-2">
                <label for="title" class="block text-sm font-medium text-gray-700">Task Title *</label>
                <input type="text" name="title" id="title" value="{{ old('title', $task->title) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            <!-- Client -->
            <div>
                <label for="client_id" class="block text-sm font-medium text-gray-700">Client</label>
                <select name="client_id" id="client_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">-- No Client --</option>
                    @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ $task->client_id == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Assigned To -->
            <div>
                <label for="assigned_to" class="block text-sm font-medium text-gray-700">Assign To</label>
                <select name="assigned_to" id="assigned_to" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">-- Unassigned --</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ $task->assigned_to == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Priority -->
            <div>
                <label for="priority" class="block text-sm font-medium text-gray-700">Priority</label>
                <select name="priority" id="priority" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @foreach(['High', 'Medium', 'Normal', 'Low'] as $p)
                    <option value="{{ $p }}" {{ $task->priority == $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @foreach(['Pending', 'In Progress', 'On Hold', 'Completed'] as $s)
                    <option value="{{ $s }}" {{ $task->status == $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Due Date -->
            <div>
                <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                <input type="date" name="due_date" id="due_date" value="{{ old('due_date', $task->due_date ? $task->due_date->format('Y-m-d') : '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            <!-- Description -->
            <div class="sm:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('description', $task->description) }}</textarea>
            </div>
        </div>

        <div class="flex justify-end pt-4">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow">
                Update Task
            </button>
        </div>
    </form>
</div>
@endsection