<?php

use Illuminate\Support\Facades\Route;

Route::middleware('module:clients')->group(function () {
Route::get('clients/export', [\App\Http\Controllers\ClientController::class, 'export'])->name('clients.export');
Route::get('clients/template', [\App\Http\Controllers\ClientController::class, 'downloadTemplate'])->name('clients.template');
Route::post('clients/import/preview', [\App\Http\Controllers\ClientImportController::class, 'previewExcel'])->name('clients.import.preview');
Route::post('clients/import/confirm', [\App\Http\Controllers\ClientImportController::class, 'confirmExcel'])->name('clients.import.confirm');
Route::middleware(['role:partner', 'module:clients'])->group(function () {
    Route::get('clients/import/folder', [\App\Http\Controllers\ClientImportController::class, 'folderForm'])->name('clients.import.folder');
    Route::post('clients/import/folder/preview', [\App\Http\Controllers\ClientImportController::class, 'previewFolder'])->name('clients.import.folder.preview');
    Route::post('clients/import/folder/run', [\App\Http\Controllers\ClientImportController::class, 'runFolder'])->name('clients.import.folder.run');
    Route::redirect('clients/import/nilesh', '/clients/import/folder', 301);
});
Route::delete('clients/bulk-destroy', [\App\Http\Controllers\ClientController::class, 'bulkDestroy'])
    ->middleware('role:partner,manager')
    ->name('clients.bulk-destroy');
Route::delete('clients/purge-by-group', [\App\Http\Controllers\ClientController::class, 'purgeByGroup'])
    ->middleware('role:partner')
    ->name('clients.purge-by-group');

Route::post('clients/{client}/approve', [\App\Http\Controllers\ClientController::class, 'approve'])
    ->middleware('role:partner')
    ->name('clients.approve');

Route::middleware('role:partner,manager')->group(function () {
    Route::post('clients/{client}/portal-link', [\App\Http\Controllers\ClientPortalController::class, 'issueLink'])->name('clients.portal-link');
});

Route::middleware('role:partner,manager')->prefix('clients/{client}/ai')->name('clients.ai.')->group(function () {
    Route::post('summarize', [\App\Http\Controllers\ClientAiController::class, 'summarize'])->name('summarize');
    Route::post('explain-overdue', [\App\Http\Controllers\ClientAiController::class, 'explainOverdue'])->name('explain-overdue');
    Route::post('draft-whatsapp', [\App\Http\Controllers\ClientAiController::class, 'draftWhatsApp'])->name('draft-whatsapp');
});
Route::patch('clients/{client}/client-services/{clientService}/documents/{requirement}', [\App\Http\Controllers\ClientServiceDocumentController::class, 'toggle'])
    ->name('clients.service-documents.toggle');

Route::post('clients/{client}/worksheets', [\App\Http\Controllers\ClientWorksheetController::class, 'store'])->name('clients.worksheets.store');
Route::delete('clients/{client}/worksheets/{worksheet}', [\App\Http\Controllers\ClientWorksheetController::class, 'destroy'])->name('clients.worksheets.destroy');
Route::resource('clients', \App\Http\Controllers\ClientController::class);
});

Route::middleware('module:smart_documents')->group(function () {
Route::get('/smart-documents', [\App\Http\Controllers\SmartDocumentController::class, 'index'])->name('smart-documents.index');
Route::get('/smart-documents/{client}', [\App\Http\Controllers\SmartDocumentController::class, 'show'])->name('smart-documents.show');
});

Route::middleware(['role:partner,manager'])->group(function () {
    Route::get('/document-ingestions', [\App\Http\Controllers\DocumentIngestionController::class, 'index'])->name('document-ingestions.index');
    Route::get('/document-ingestions/{documentIngestion}/review', [\App\Http\Controllers\DocumentIngestionController::class, 'review'])->name('document-ingestions.review');
    Route::post('/document-ingestions/{documentIngestion}/confirm', [\App\Http\Controllers\DocumentIngestionController::class, 'confirm'])->name('document-ingestions.confirm');
    Route::post('/document-ingestions/{documentIngestion}/reject', [\App\Http\Controllers\DocumentIngestionController::class, 'reject'])->name('document-ingestions.reject');
    Route::get('/document-ingestions/{documentIngestion}/download', [\App\Http\Controllers\DocumentIngestionController::class, 'download'])->name('document-ingestions.download');
    Route::post('/clients/{client}/document-ingestions', [\App\Http\Controllers\DocumentIngestionController::class, 'store'])->name('document-ingestions.store');
});

Route::middleware('module:clients')->group(function () {
Route::get('/onboarding/{client}', [\App\Http\Controllers\OnboardingController::class, 'show'])->name('onboarding.show');
Route::middleware('role:partner,manager')->group(function () {
    Route::post('/onboarding/{item}/toggle', [\App\Http\Controllers\OnboardingController::class, 'toggle'])->name('onboarding.toggle');
    Route::post('/onboarding/{client}/add-item', [\App\Http\Controllers\OnboardingController::class, 'addItem'])->name('onboarding.add-item');
});

});

Route::middleware(['role:partner,manager', 'module:credentials'])->group(function () {
    Route::get('/gov-portals/{portal}/clients', [\App\Http\Controllers\GovernmentPortalController::class, 'clients'])
        ->whereIn('portal', \App\Support\GovernmentPortals::ids())
        ->name('gov-portals.clients');
    Route::get('/gov-portals/{portal}/launch/{credential}', [\App\Http\Controllers\GovernmentPortalController::class, 'launch'])
        ->whereIn('portal', \App\Support\GovernmentPortals::ids())
        ->name('gov-portals.launch');

    Route::get('/credentials', [\App\Http\Controllers\ClientCredentialController::class, 'index'])->name('credentials.index');
    Route::post('/credentials', [\App\Http\Controllers\ClientCredentialController::class, 'store'])->name('credentials.store');
    Route::post('/credentials/{credential}/audit', [\App\Http\Controllers\ClientCredentialController::class, 'audit'])->name('credentials.audit');
    Route::delete('/credentials/{credential}', [\App\Http\Controllers\ClientCredentialController::class, 'destroy'])->name('credentials.destroy');
});

