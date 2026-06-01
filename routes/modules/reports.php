<?php

use Illuminate\Support\Facades\Route;

Route::prefix('reports')->name('reports.')->middleware(['role:partner,manager', 'module:reports'])->group(function () {
    Route::get('/', [\App\Http\Controllers\ReportController::class, 'index'])->name('index');
    Route::get('/financial', [\App\Http\Controllers\ReportController::class, 'financial'])->name('financial');
    Route::get('/financial/export', [\App\Http\Controllers\ReportController::class, 'exportFinancial'])->name('financial.export');
    Route::get('/compliance', [\App\Http\Controllers\ReportController::class, 'compliance'])->name('compliance');
    Route::get('/compliance/export', [\App\Http\Controllers\ReportController::class, 'exportCompliance'])->name('compliance.export');
    Route::get('/service', [\App\Http\Controllers\ReportController::class, 'service'])->name('service');
    Route::get('/client', [\App\Http\Controllers\ReportController::class, 'client'])->name('client');
    Route::get('/task', [\App\Http\Controllers\ReportController::class, 'task'])->name('task');
    Route::get('/due-date', [\App\Http\Controllers\ReportController::class, 'dueDate'])->name('due-date');
    Route::get('/staff-productivity', [\App\Http\Controllers\ReportController::class, 'staffProductivity'])->name('staff-productivity');
    Route::get('/client-profitability', [\App\Http\Controllers\ReportController::class, 'clientProfitability'])->name('client-profitability');
});

