@extends('layouts.app')

@section('header', 'Document review queue')

@section('content')
<div class="space-y-4">
    <p class="text-sm text-slate-600">{{ $pendingCount }} pending · Confirm extracted fields before creating work items.</p>

    <div class="flex gap-2 text-xs">
        <a href="{{ route('document-ingestions.index', ['status' => 'pending_review']) }}" class="px-3 py-1 rounded-lg {{ $status === 'pending_review' ? 'bg-indigo-600 text-white' : 'bg-white border' }}">Pending</a>
        <a href="{{ route('document-ingestions.index', ['status' => 'confirmed']) }}" class="px-3 py-1 rounded-lg {{ $status === 'confirmed' ? 'bg-indigo-600 text-white' : 'bg-white border' }}">Confirmed</a>
        <a href="{{ route('document-ingestions.index', ['status' => 'rejected']) }}" class="px-3 py-1 rounded-lg {{ $status === 'rejected' ? 'bg-indigo-600 text-white' : 'bg-white border' }}">Rejected</a>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">Client</th>
                    <th class="px-4 py-3 text-left">File</th>
                    <th class="px-4 py-3 text-left">Source</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($ingestions as $row)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $row->client?->name }}</td>
                    <td class="px-4 py-3">{{ $row->original_filename }}</td>
                    <td class="px-4 py-3 capitalize">{{ $row->source }}</td>
                    <td class="px-4 py-3">{{ $row->status }}</td>
                    <td class="px-4 py-3 text-right space-x-2">
                        <a href="{{ route('document-ingestions.download', $row) }}" class="text-indigo-600 text-xs font-semibold">Download</a>
                        @if($row->status === 'pending_review')
                        <a href="{{ route('document-ingestions.review', $row) }}" class="text-indigo-600 text-xs font-semibold">Review →</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No documents in this queue.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $ingestions->withQueryString()->links() }}
</div>
@endsection
