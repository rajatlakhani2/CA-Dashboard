@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-md mx-auto bg-white dark:bg-gray-800 p-8 border border-gray-300 dark:border-gray-700 shadow-lg rounded-lg">
        <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-gray-100">Add Leave Report</h2>

        @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Oops!</strong>
            <span class="block sm:inline">Please check the form for errors.</span>
            <ul class="mt-2 list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('leaves.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label for="user_id" class="block text-gray-700 dark:text-gray-300 font-bold mb-2">Staff Member</label>
                <select name="user_id" id="user_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ auth()->id() == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="leave_date" class="block text-gray-700 dark:text-gray-300 font-bold mb-2">Leave Date</label>
                <input type="date" name="leave_date" id="leave_date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" required value="{{ old('leave_date') }}">
            </div>

            <div class="mb-4">
                <label for="informed_at" class="block text-gray-700 dark:text-gray-300 font-bold mb-2">Informed On</label>
                <input type="datetime-local" name="informed_at" id="informed_at" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" required value="{{ old('informed_at', now()->format('Y-m-d\TH:i')) }}">
            </div>

            <div class="mb-6">
                <label for="reason" class="block text-gray-700 dark:text-gray-300 font-bold mb-2">Reason</label>
                <textarea name="reason" id="reason" rows="4" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" required placeholder="Enter description/reason for leave">{{ old('reason') }}</textarea>
            </div>

            <div class="flex items-center justify-end">
                <a href="{{ route('leaves.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 mr-4">Cancel</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline transition">
                    Submit Report
                </button>
            </div>
        </form>
    </div>
</div>
@endsection