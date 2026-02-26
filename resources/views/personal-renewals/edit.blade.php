@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <span>Edit Personal Renewal</span>
    <form action="{{ route('personal-renewals.destroy', $personalRenewal) }}" method="POST" onsubmit="return confirm('Are you sure?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-sm text-red-600 hover:text-red-900">Delete</button>
    </form>
</div>
@endsection

@section('content')
<div class="max-w-lg mx-auto bg-bg-card shadow sm:rounded-lg p-6">
    <form action="{{ route('personal-renewals.update', $personalRenewal) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label for="title" class="block text-sm font-medium text-text-main">Title</label>
            <input type="text" name="title" id="title" value="{{ $personalRenewal->title }}" required class="mt-1 block w-full rounded-md border-line bg-bg-body text-text-main shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <div>
            <label for="category" class="block text-sm font-medium text-text-main">Category</label>
            <select name="category" id="category" required class="mt-1 block w-full rounded-md border-line bg-bg-body text-text-main shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @foreach(['LIC', 'Loan', 'Medical', 'Other'] as $cat)
                <option value="{{ $cat }}" {{ $personalRenewal->category == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="amount" class="block text-sm font-medium text-text-main">Amount</label>
            <input type="number" name="amount" id="amount" value="{{ $personalRenewal->amount }}" step="0.01" required class="mt-1 block w-full rounded-md border-line bg-bg-body text-text-main shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <div>
            <label for="due_date" class="block text-sm font-medium text-text-main">Due Date</label>
            <input type="date" name="due_date" id="due_date" value="{{ $personalRenewal->due_date->format('Y-m-d') }}" required class="mt-1 block w-full rounded-md border-line bg-bg-body text-text-main shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <div>
            <label for="frequency" class="block text-sm font-medium text-text-main">Frequency</label>
            <select name="frequency" id="frequency" class="mt-1 block w-full rounded-md border-line bg-bg-body text-text-main shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">One Time</option>
                @foreach(['Monthly', 'Quarterly', 'Half-Yearly', 'Yearly'] as $freq)
                <option value="{{ $freq }}" {{ $personalRenewal->frequency == $freq ? 'selected' : '' }}>{{ $freq }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-text-secondary">If specific, next due date will be auto-created when paid.</p>
        </div>

        <div>
            <label for="status" class="block text-sm font-medium text-text-main">Status</label>
            <select name="status" id="status" required class="mt-1 block w-full rounded-md border-line bg-bg-body text-text-main shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="Pending" {{ $personalRenewal->status == 'Pending' ? 'selected' : '' }}>Pending</option>
                <option value="Paid" {{ $personalRenewal->status == 'Paid' ? 'selected' : '' }}>Paid</option>
            </select>
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-text-main">Notes</label>
            <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full rounded-md border-line bg-bg-body text-text-main shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $personalRenewal->notes }}</textarea>
        </div>

        <div class="flex justify-between">
            <a href="{{ route('personal-renewals.index') }}" class="text-sm text-text-secondary hover:text-text-main py-2">Cancel</a>
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Update Renewal
            </button>
        </div>
    </form>
</div>
@endsection