@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <span>Recycle Bin</span>
    <!-- Maybe a "Empty Bin" button later -->
</div>
@endsection

@section('content')
<div class="bg-bg-card shadow sm:rounded-lg border border-line">
    <div class="px-4 py-5 sm:p-6">
        @if($allItems->isEmpty())
        <div class="text-center text-text-secondary py-10">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-text-main">Recycle Bin is Empty</h3>
            <p class="mt-1 text-sm text-text-secondary">Items you delete will show up here.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="bg-bg-body">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-text-main sm:pl-6">Type</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-text-main">Name / Title</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-text-main">Deleted Date</th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line bg-bg-card">
                    @foreach($allItems->sortByDesc('deleted_at') as $item)
                    <tr>
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-text-main sm:pl-6">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium 
                                {{ $item->type === 'Client' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $item->type === 'Task' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $item->type === 'Invoice' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $item->type === 'Service Due' ? 'bg-purple-100 text-purple-800' : '' }}">
                                {{ $item->type }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary">
                            {{ $item->display_name }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary">
                            {{ $item->deleted_at->format('d M Y H:i') }}
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <form action="{{ route('recycle-bin.restore', ['type' => $item->type, 'id' => $item->id]) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-indigo-600 hover:text-indigo-900 mr-4">Restore</button>
                            </form>
                            <form action="{{ route('recycle-bin.force-delete', ['type' => $item->type, 'id' => $item->id]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete this item? This action cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete Permanently</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection