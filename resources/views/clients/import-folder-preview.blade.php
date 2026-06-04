@extends('layouts.app')

@section('header', 'Folder import preview')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <p class="text-sm text-gray-600">Path: <code>{{ $path }}</code> — {{ $preview['total_folders'] ?? 0 }} folders scanned.</p>
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <p class="text-xs font-bold text-gray-500 uppercase">New</p>
            <p class="text-2xl font-bold">{{ count($preview['create']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <p class="text-xs font-bold text-gray-500 uppercase">Update</p>
            <p class="text-2xl font-bold">{{ count($preview['update']) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-gray-400">
            <p class="text-xs font-bold text-gray-500 uppercase">Skipped</p>
            <p class="text-2xl font-bold">{{ count($preview['skip']) }}</p>
        </div>
    </div>

    @if(count($preview['create']) + count($preview['update']) > 0)
    <form action="{{ route('clients.import.folder.run') }}" method="POST" class="bg-white rounded-lg shadow p-6 space-y-4">
        @csrf
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="assign_service" value="1" class="rounded border-gray-300 text-indigo-600">
            Assign default ITR service to new clients
        </label>
        <div class="flex gap-3">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700"
                onclick="return confirm('Import {{ count($preview['create']) }} new and update {{ count($preview['update']) }} clients?')">
                Confirm import
            </button>
            <a href="{{ route('clients.import.folder') }}" class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700">Cancel</a>
        </div>
    </form>
    @else
    <p class="text-sm text-gray-500">Nothing to import. Adjust the folder path or scan again.</p>
    <a href="{{ route('clients.import.folder') }}" class="text-indigo-600 text-sm font-medium">← Back</a>
    @endif
</div>
@endsection
