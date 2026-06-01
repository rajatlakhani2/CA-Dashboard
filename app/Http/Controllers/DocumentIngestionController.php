<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\DocumentIngestion;
use App\Models\Task;
use App\Services\Intelligence\DocumentFieldGuesser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentIngestionController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->managesFirmModules(), 403);

        $status = $request->input('status', DocumentIngestion::STATUS_PENDING);

        $ingestions = DocumentIngestion::query()
            ->with(['client', 'uploader'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20);

        $pendingCount = DocumentIngestion::where('status', DocumentIngestion::STATUS_PENDING)->count();

        return view('document-ingestions.index', compact('ingestions', 'status', 'pendingCount'));
    }

    public function store(Request $request, Client $client)
    {
        $this->authorize('view', $client);
        abort_unless(auth()->user()?->managesFirmModules() || auth()->user()?->canAccessModule('smart_documents'), 403);

        $request->validate([
            'document' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
            'document_type' => 'nullable|string|max:100',
        ]);

        $file = $request->file('document');
        $path = $file->store('document-ingestions/' . $client->id, 'local');
        $guessed = app(DocumentFieldGuesser::class)->fromFilename($file->getClientOriginalName());

        DocumentIngestion::create([
            'client_id' => $client->id,
            'uploaded_by' => auth()->id(),
            'source' => DocumentIngestion::SOURCE_FIRM,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getMimeType(),
            'status' => DocumentIngestion::STATUS_PENDING,
            'document_type' => $request->input('document_type') ?: ($guessed['document_type'] ?? null),
            'extracted_fields' => $guessed,
        ]);

        return redirect()
            ->route('document-ingestions.index')
            ->with('success', 'Document queued for review.');
    }

    public function review(DocumentIngestion $documentIngestion)
    {
        abort_unless(auth()->user()?->managesFirmModules(), 403);
        $documentIngestion->load(['client', 'uploader']);

        return view('document-ingestions.review', compact('documentIngestion'));
    }

    public function confirm(Request $request, DocumentIngestion $documentIngestion)
    {
        abort_unless(auth()->user()?->managesFirmModules(), 403);

        $validated = $request->validate([
            'document_type' => 'nullable|string|max:100',
            'pan' => 'nullable|string|max:20',
            'amount' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'create_task' => 'nullable|boolean',
            'task_title' => 'nullable|string|max:255',
            'review_notes' => 'nullable|string|max:2000',
        ]);

        $confirmed = array_filter([
            'document_type' => $validated['document_type'] ?? $documentIngestion->document_type,
            'pan' => $validated['pan'] ?? null,
            'amount' => $validated['amount'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
        ]);

        $taskId = null;
        if ($request->boolean('create_task')) {
            $title = $validated['task_title']
                ?: ('Review document: ' . ($confirmed['document_type'] ?? $documentIngestion->original_filename));
            $task = Task::create([
                'client_id' => $documentIngestion->client_id,
                'title' => $title,
                'status' => Task::STATUS_PENDING,
                'priority' => 'Medium',
                'due_date' => $validated['due_date'] ?? now()->addDays(7),
                'assigned_to' => auth()->id(),
                'created_by' => auth()->id(),
            ]);
            $taskId = $task->id;
        }

        $documentIngestion->update([
            'status' => DocumentIngestion::STATUS_CONFIRMED,
            'document_type' => $confirmed['document_type'] ?? null,
            'confirmed_fields' => $confirmed,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'created_task_id' => $taskId,
            'review_notes' => $validated['review_notes'] ?? null,
        ]);

        return redirect()->route('document-ingestions.index')->with('success', 'Document confirmed.');
    }

    public function reject(Request $request, DocumentIngestion $documentIngestion)
    {
        abort_unless(auth()->user()?->managesFirmModules(), 403);

        $request->validate(['review_notes' => 'nullable|string|max:2000']);

        $documentIngestion->update([
            'status' => DocumentIngestion::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes'),
        ]);

        return redirect()->route('document-ingestions.index')->with('warning', 'Document rejected.');
    }

    public function download(DocumentIngestion $documentIngestion)
    {
        abort_unless(auth()->user()?->managesFirmModules(), 403);

        if (! Storage::disk('local')->exists($documentIngestion->stored_path)) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $documentIngestion->stored_path,
            $documentIngestion->original_filename
        );
    }
}
