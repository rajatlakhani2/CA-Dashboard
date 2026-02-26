@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <span>{{ $title }}</span>
</div>
@endsection

@section('content')
<div class="bg-white overflow-hidden shadow rounded-lg border border-gray-200 p-12 text-center">
    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
    </svg>
    <h3 class="mt-2 text-lg font-medium text-gray-900">Work in Progress</h3>
    <p class="mt-1 text-sm text-gray-500">This report is currently under development. Check back soon!</p>
    <div class="mt-6">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Go to Dashboard
        </a>
    </div>
</div>
@endsection