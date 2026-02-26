@extends('layouts.app')

@section('header', 'System Health & Admin Hub')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Top Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- PHP Version -->
        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-indigo-50 rounded-md p-3">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">PHP Version</dt>
                            <dd class="text-lg font-bold text-gray-900">{{ $phpVersion }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-xs text-gray-500">Laravel v{{ $laravelVersion }}</div>
            </div>
        </div>

        <!-- Environment -->
        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-50 rounded-md p-3">
                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Environment</dt>
                            <dd class="text-lg font-bold text-gray-900 capitalize">{{ $environment }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-xs text-gray-500">Debug Mode: {{ config('app.debug') ? 'On' : 'Off' }}</div>
            </div>
        </div>

        <!-- Database -->
        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-50 rounded-md p-3">
                        <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Database</dt>
                            <dd class="text-lg font-bold text-gray-900">{{ $dbStatus }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-xs text-gray-500">DB: {{ $dbName }}</div>
            </div>
        </div>

        <!-- Time -->
        <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-100">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-orange-50 rounded-md p-3">
                        <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Server Time</dt>
                            <dd class="text-sm font-bold text-gray-900">{{ now()->format('d M H:i') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-xs text-gray-500">{{ config('app.timezone') }}</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Actions Panel -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900">Admin Actions</h3>
                </div>
                <div class="p-6 space-y-4">
                    <!-- Clear Cache -->
                    <form action="{{ route('system.clear-cache') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full group relative flex items-center justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                            <svg class="mr-2 h-5 w-5 text-indigo-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Clear & Rebuild Cache
                        </button>
                        <p class="mt-2 text-xs text-gray-500 text-center">Fixes config/view issues.</p>
                    </form>

                    <hr class="border-gray-100">

                    <!-- Optimize -->
                    <form action="{{ route('system.optimize') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full group relative flex items-center justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                            <svg class="mr-2 h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Optimize System
                        </button>
                        <p class="mt-2 text-xs text-gray-500 text-center">Compiles views & routes for speed.</p>
                    </form>

                    <hr class="border-gray-100">

                    <!-- Migrate -->
                    <form action="{{ route('system.migrate') }}" method="POST" onsubmit="return confirm('Are you sure? This will update the database structure.');">
                        @csrf
                        <button type="submit" class="w-full group relative flex items-center justify-center py-3 px-4 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                            <svg class="mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                            </svg>
                            Run Migrations
                        </button>
                        <p class="mt-2 text-xs text-gray-500 text-center">Update DB schema.</p>
                    </form>
                </div>
            </div>
        </div>

        <!-- Log Viewer -->
        <div class="lg:col-span-2">
            <div class="bg-gray-900 shadow-xl rounded-xl border border-gray-700 overflow-hidden h-full flex flex-col">
                <div class="px-6 py-4 border-b border-gray-800 bg-gray-800 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-100 font-mono">
                        <span class="text-green-400">root@server:</span>~/logs/laravel.log
                    </h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-700 text-gray-300">
                        Tail: Last 50 lines
                    </span>
                </div>
                <div class="p-4 overflow-y-auto bg-gray-900 font-mono text-xs text-gray-300 flex-1 max-h-[500px]" id="log-container">
                    @forelse($logs as $log)
                    <div class="mb-1 whitespace-pre-wrap hover:bg-gray-800 p-1 rounded {{ str_contains($log, '.ERROR') ? 'text-red-400' : (str_contains($log, '.WARNING') ? 'text-yellow-400' : 'text-gray-300') }}">{{ $log }}</div>
                    @empty
                    <div class="text-gray-500 italic">No logs found or log file is empty.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection