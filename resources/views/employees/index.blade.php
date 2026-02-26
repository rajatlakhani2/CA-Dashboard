@extends('layouts.app')

@section('header', 'Team 360°')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Team Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($employees as $employee)
        <a href="{{ route('employees.show', $employee) }}" class="group relative block bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 hover:border-indigo-100">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-indigo-600 shadow-lg">
                        <span class="text-xl font-bold leading-none text-white">{{ substr($employee->name, 0, 1) }}</span>
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 truncate transition-colors">
                        {{ $employee->name }}
                    </p>
                    <p class="text-sm text-gray-500 truncate">
                        {{ $employee->email }}
                    </p>
                </div>
                <div>
                    <svg class="h-5 w-5 text-gray-400 group-hover:text-indigo-500 transform group-hover:translate-x-1 transition-all" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-4 border-t border-gray-50 pt-4">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Active Tasks</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ $employee->tasks_count }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Clients Mgd.</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ $employee->managed_clients_count }}</p>
                </div>
            </div>
        </a>
        @endforeach
    </div>

</div>
@endsection