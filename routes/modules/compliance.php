<?php

use Illuminate\Support\Facades\Route;

Route::middleware('module:service_dues')->group(function () {
Route::get('service-dues', [\App\Http\Controllers\ServiceDueController::class, 'index'])->name('service-dues.index');
Route::post('service-dues/generate', [\App\Http\Controllers\ServiceDueController::class, 'generate'])
    ->middleware('role:partner,manager')
    ->name('service-dues.generate');
Route::post('service-dues/{serviceDue}/complete', [\App\Http\Controllers\ServiceDueController::class, 'markComplete'])->name('service-dues.complete');
});

Route::middleware(['role:partner,manager', 'module:service_dues'])->group(function () {
    Route::post('/service-dues/{alert}/whatsapp', [\App\Http\Controllers\ServiceDueController::class, 'sendWhatsApp'])->name('service-dues.whatsapp');
    Route::post('/personal-renewals/{personalRenewal}/whatsapp', [\App\Http\Controllers\PersonalRenewalController::class, 'sendWhatsApp'])->name('personal-renewals.whatsapp');
});

Route::middleware('module:personal_renewals')->group(function () {
Route::resource('personal-renewals', \App\Http\Controllers\PersonalRenewalController::class)->except(['show']);
});

Route::post('services/{service}/document-requirements', [\App\Http\Controllers\ServiceDocumentRequirementController::class, 'store'])
    ->middleware('role:partner,manager')
    ->name('services.document-requirements.store');
Route::delete('document-requirements/{documentRequirement}', [\App\Http\Controllers\ServiceDocumentRequirementController::class, 'destroy'])
    ->middleware('role:partner,manager')
    ->name('document-requirements.destroy');

Route::post('services/{service}/task-templates', [\App\Http\Controllers\TaskTemplateController::class, 'store'])->name('services.task-templates.store');
Route::delete('task-templates/{taskTemplate}', [\App\Http\Controllers\TaskTemplateController::class, 'destroy'])->name('task-templates.destroy');
Route::post('services/{service}/spawn-tasks/{client}', [\App\Http\Controllers\TaskTemplateController::class, 'spawn'])->name('services.spawn-tasks');

Route::resource('services', \App\Http\Controllers\ServiceController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->middleware('role:partner,manager');

Route::get('/compliance-360', [\App\Http\Controllers\ComplianceController::class, 'index'])
    ->middleware(['role:partner,manager', 'module:compliance'])
    ->name('compliance.index');

Route::resource('dscs', \App\Http\Controllers\DscController::class)
    ->except(['show'])
    ->middleware(['role:partner,manager', 'module:dsc']);

Route::resource('tds', \App\Http\Controllers\TdsController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->parameters(['tds' => 'tdsEntry'])
    ->middleware(['role:partner,manager', 'module:tds']);
