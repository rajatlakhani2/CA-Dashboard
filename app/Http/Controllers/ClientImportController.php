<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\ClientImportApplier;
use App\Services\ClientImportPreviewService;
use App\Services\NileshFolderImporter;
use App\Services\NileshFolderImportService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientImportController extends Controller
{
    public function previewExcel(Request $request, ClientImportPreviewService $previewService)
    {
        $this->authorize('import', Client::class);

        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:10240']);

        $branchId = auth()->user()?->isManager() ? auth()->user()->branch_id : null;
        $storedPath = $request->file('file')->store('client-imports');
        $preview = $previewService->preview($request->file('file'), $branchId);

        session([
            'client_import_preview' => $preview,
            'client_import_branch' => $branchId,
            'client_import_file' => $storedPath,
        ]);

        return view('clients.import-preview', [
            'preview' => $preview,
            'canConfirm' => count($preview['invalid']) === 0 && (count($preview['create']) + count($preview['update']) > 0),
        ]);
    }

    public function confirmExcel(ClientImportApplier $applier)
    {
        $this->authorize('import', Client::class);

        $preview = session('client_import_preview');
        $storedPath = session('client_import_file');

        if (! $preview || ! $storedPath) {
            return redirect()->route('clients.index')->with('error', 'Import preview expired. Upload the file again.');
        }

        if (count($preview['invalid']) > 0) {
            return redirect()->route('clients.index')->with('error', 'Fix invalid rows before confirming import.');
        }

        if (! Storage::exists($storedPath)) {
            return redirect()->route('clients.index')->with('error', 'Import file missing. Upload again.');
        }

        try {
            $result = $applier->apply(
                Storage::path($storedPath),
                session('client_import_branch'),
                $preview,
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('clients.index')->with('error', $e->getMessage());
        } catch (UniqueConstraintViolationException $e) {
            if (str_contains($e->getMessage(), 'clients_pan_unique')) {
                return redirect()->route('clients.index')->with(
                    'error',
                    'Import failed: a client with this PAN already exists (often from a previous partial import). '
                    .'Deploy the latest ClientImportApplier.php (version '.ClientImportApplier::VERSION.'), run php artisan optimize:clear, then upload and import again.'
                );
            }

            throw $e;
        }

        Storage::delete($storedPath);
        session()->forget(['client_import_preview', 'client_import_branch', 'client_import_file']);

        return redirect()->route('clients.index')->with(
            'success',
            "Import complete: {$result['created']} created, {$result['updated']} updated."
        );
    }

    public function folderForm()
    {
        abort_unless(auth()->user()?->isPartner(), 403);

        return view('clients.import-folder', [
            'defaultPath' => '',
        ]);
    }

    public function previewFolder(Request $request, NileshFolderImportService $service)
    {
        abort_unless(auth()->user()?->isPartner(), 403);

        $path = $request->validate(['path' => 'required|string|max:500'])['path'];

        $preview = $service->preview($path);
        if (isset($preview['error'])) {
            return back()->with('error', $preview['error'])->withInput();
        }

        session(['folder_import_path' => $path, 'folder_import_preview' => $preview]);

        return view('clients.import-folder-preview', compact('preview', 'path'));
    }

    public function runFolder(Request $request, NileshFolderImporter $importer)
    {
        abort_unless(auth()->user()?->isPartner(), 403);

        $path = session('folder_import_path');
        if (! $path) {
            return redirect()->route('clients.import.folder')->with('error', 'Preview expired. Scan again.');
        }

        $assign = $request->boolean('assign_service');
        $result = $importer->run($path, $assign);

        session()->forget(['folder_import_path', 'folder_import_preview']);

        if (isset($result['error'])) {
            return redirect()->route('clients.import.folder')->with('error', $result['error']);
        }

        return redirect()->route('clients.index')->with(
            'success',
            "Folder import complete: {$result['created']} created, {$result['updated']} updated, {$result['skipped']} skipped."
        );
    }
}
