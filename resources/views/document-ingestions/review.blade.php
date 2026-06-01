@extends('layouts.app')

@section('header', 'Review document')

@section('content')
<div class="max-w-2xl space-y-6">
    <div class="bg-white rounded-xl border p-4 text-sm">
        <p><strong>Client:</strong> {{ $documentIngestion->client?->name }}</p>
        <p><strong>File:</strong> {{ $documentIngestion->original_filename }}</p>
        <p><strong>Source:</strong> {{ $documentIngestion->source }}</p>
        <a href="{{ route('document-ingestions.download', $documentIngestion) }}" class="text-indigo-600 text-xs font-semibold mt-2 inline-block">Download file</a>
    </div>

    @php $guess = $documentIngestion->extracted_fields ?? []; @endphp

    <form method="POST" action="{{ route('document-ingestions.confirm', $documentIngestion) }}" class="bg-white rounded-xl border p-5 space-y-4">
        @csrf
        <p class="text-xs text-amber-700 bg-amber-50 rounded-lg px-3 py-2">OCR API not required — edit guessed fields, then confirm. Full OCR can be added later via Azure Document Intelligence.</p>

        <div>
            <label class="block text-xs font-medium text-slate-600">Document type</label>
            <input type="text" name="document_type" value="{{ old('document_type', $documentIngestion->document_type ?? $guess['document_type'] ?? '') }}" class="mt-1 w-full rounded-md border-slate-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600">PAN (if found)</label>
            <input type="text" name="pan" value="{{ old('pan', $guess['pan'] ?? '') }}" class="mt-1 w-full rounded-md border-slate-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600">Amount (₹)</label>
            <input type="number" step="0.01" name="amount" value="{{ old('amount', $guess['amount'] ?? '') }}" class="mt-1 w-full rounded-md border-slate-300 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600">Suggested due date</label>
            <input type="date" name="due_date" value="{{ old('due_date') }}" class="mt-1 w-full rounded-md border-slate-300 text-sm">
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="create_task" value="1" id="create_task" checked class="rounded border-slate-300 text-indigo-600">
            <label for="create_task" class="text-sm text-slate-700">Create follow-up task</label>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600">Task title</label>
            <input type="text" name="task_title" class="mt-1 w-full rounded-md border-slate-300 text-sm" placeholder="Process uploaded document">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600">Review notes</label>
            <textarea name="review_notes" rows="2" class="mt-1 w-full rounded-md border-slate-300 text-sm"></textarea>
        </div>
        <button type="submit" class="w-full py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg">Confirm &amp; save</button>
    </form>

    <form method="POST" action="{{ route('document-ingestions.reject', $documentIngestion) }}" class="bg-white rounded-xl border p-4">
        @csrf
        <textarea name="review_notes" rows="2" class="w-full rounded-md border-slate-300 text-sm mb-2" placeholder="Rejection reason"></textarea>
        <button type="submit" class="text-sm text-red-600 font-semibold">Reject document</button>
    </form>
</div>
@endsection
