@extends('layouts.app')

@section('header', 'Folder import preview')

@section('content')
<div class="max-w-2xl mx-auto">
    <form action="{{ route('clients.import.folder.preview') }}" method="POST" class="bg-white rounded-lg shadow p-6 space-y-4">
        @csrf
        <p class="text-sm text-gray-600">Scan a local IT Return folder tree before importing clients. Server path must exist on this machine (partner-only).</p>
        <div>
            <label class="block text-sm font-medium text-gray-700">Folder path</label>
            <input type="text" name="path" value="{{ old('path', $defaultPath) }}" required placeholder="e.g. D:\Clients\IT Return" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md font-medium">Preview scan</button>
    </form>
</div>
@endsection
