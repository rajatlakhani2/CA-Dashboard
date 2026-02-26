@extends('layouts.app')

@section('header')
<div class="flex items-center space-x-2">
    <a href="{{ route('smart-documents.index') }}" class="text-text-secondary hover:text-text-main">
        Smart Archive
    </a>
    <span class="text-text-secondary">/</span>
    <span class="text-text-main font-semibold">{{ $client->name }}</span>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Drop Zone / Upload Button (Placeholder for future drag-drop) -->
    <div class="flex justify-end">
        <a href="{{ route('clients.show', $client) }}#documents" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
            <svg class="mr-2 -ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            Upload New File
        </a>
    </div>

    <!-- Files Grid -->
    <div class="bg-bg-card shadow rounded-lg border border-line p-6">
        @if($client->documents->isEmpty())
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-text-main">No documents</h3>
            <p class="mt-1 text-sm text-text-secondary">This folder is empty.</p>
        </div>
        @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
            @foreach($client->documents as $doc)
            <div class="group relative block text-center">
                <div class="relative mx-auto h-24 w-24 flex items-center justify-center rounded-lg bg-gray-50 border border-gray-200 group-hover:border-indigo-300 group-hover:bg-indigo-50 transition-all">
                    <!-- File Icon based on extension (simplified) -->
                    @php
                    $ext = pathinfo($doc->file_path, PATHINFO_EXTENSION);
                    $iconColor = match(strtolower($ext)) {
                    'pdf' => 'text-red-500',
                    'doc', 'docx' => 'text-blue-500',
                    'xls', 'xlsx', 'csv' => 'text-green-500',
                    'jpg', 'jpeg', 'png' => 'text-purple-500',
                    default => 'text-gray-400'
                    };
                    @endphp
                    <svg class="h-10 w-10 {{ $iconColor }}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                    </svg>

                    <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="absolute inset-0 z-10 focus:outline-none">
                        <span class="sr-only">View file</span>
                    </a>
                </div>
                <div class="mt-2 text-left">
                    <p class="text-sm font-medium text-text-main truncate" title="{{ $doc->document_type }}">
                        {{ $doc->document_type ?? basename($doc->file_path) }}
                    </p>
                    <p class="text-[10px] text-text-secondary">{{ strtoupper($ext) }} • {{ $doc->uploaded_at ? $doc->uploaded_at->format('M d, Y') : 'N/A' }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection