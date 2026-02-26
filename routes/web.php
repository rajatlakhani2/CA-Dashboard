<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', [\App\Http\Controllers\LoginController::class, 'show'])->name('login');
Route::post('/login', [\App\Http\Controllers\LoginController::class, 'login']);

Route::get('/', function () {
    return redirect()->route('login');
});

// Bypass Login Route (Dev Only)
Route::get('/bypass-login', function () {
    $user = \App\Models\User::first();
    if ($user) {
        auth()->login($user);
        return redirect()->route('dashboard');
    }
    return redirect()->route('login')->withErrors(['email' => 'No users found to bypass login.']);
})->name('login.bypass');

Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [\App\Http\Controllers\LoginController::class, 'logout'])->name('logout');

    Route::patch('/tasks/{task}/mark-foc', [\App\Http\Controllers\TaskController::class, 'markFoc'])->name('tasks.mark-foc');

    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/recycle-bin', [\App\Http\Controllers\RecycleBinController::class, 'index'])->name('recycle-bin.index');
    Route::post('/recycle-bin/restore/{type}/{id}', [\App\Http\Controllers\RecycleBinController::class, 'restore'])->name('recycle-bin.restore');
    Route::delete('/recycle-bin/force-delete/{type}/{id}', [\App\Http\Controllers\RecycleBinController::class, 'forceDelete'])->name('recycle-bin.force-delete');

    Route::post('/calendar/update-date', [\App\Http\Controllers\DashboardController::class, 'updateDate'])->name('calendar.update');

    Route::get('clients/export', [\App\Http\Controllers\ClientController::class, 'export'])->name('clients.export');
    Route::get('clients/template', [\App\Http\Controllers\ClientController::class, 'downloadTemplate'])->name('clients.template');
    Route::post('clients/import', [\App\Http\Controllers\ClientController::class, 'import'])->name('clients.import');
    Route::delete('clients/bulk-destroy', [\App\Http\Controllers\ClientController::class, 'bulkDestroy'])->name('clients.bulk-destroy');
    Route::resource('clients', \App\Http\Controllers\ClientController::class);
    Route::get('service-dues', [\App\Http\Controllers\ServiceDueController::class, 'index'])->name('service-dues.index');
    Route::post('service-dues/generate', [\App\Http\Controllers\ServiceDueController::class, 'generate'])->name('service-dues.generate');
    Route::post('service-dues/{serviceDue}/complete', [\App\Http\Controllers\ServiceDueController::class, 'markComplete'])->name('service-dues.complete');
    Route::patch('tasks/{task}/status', [\App\Http\Controllers\TaskController::class, 'updateStatus'])->name('tasks.update-status');
    Route::resource('tasks', \App\Http\Controllers\TaskController::class);
    Route::get('invoices/{invoice}/download-pdf', [\App\Http\Controllers\InvoiceController::class, 'downloadPdf'])->name('invoices.download-pdf');
    Route::resource('invoices', \App\Http\Controllers\InvoiceController::class);
    Route::resource('services', \App\Http\Controllers\ServiceController::class);

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ReportController::class, 'index'])->name('index');
        Route::get('/financial', [\App\Http\Controllers\ReportController::class, 'financial'])->name('financial');
        Route::get('/financial/export', [\App\Http\Controllers\ReportController::class, 'exportFinancial'])->name('financial.export');
        Route::get('/compliance', [\App\Http\Controllers\ReportController::class, 'compliance'])->name('compliance');
        Route::get('/compliance/export', [\App\Http\Controllers\ReportController::class, 'exportCompliance'])->name('compliance.export');
        Route::get('/service', [\App\Http\Controllers\ReportController::class, 'service'])->name('service');
        Route::get('/client', [\App\Http\Controllers\ReportController::class, 'client'])->name('client');
        Route::get('/task', [\App\Http\Controllers\ReportController::class, 'task'])->name('task');
        Route::get('/due-date', [\App\Http\Controllers\ReportController::class, 'dueDate'])->name('due-date');
    });

    // Billing Queue
    Route::get('/billing', [\App\Http\Controllers\BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/process', [\App\Http\Controllers\BillingController::class, 'process'])->name('billing.process');

    // Settings
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [\App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');

    Route::get('/search/global', [\App\Http\Controllers\SearchController::class, 'globalSearch'])->name('search.global');


    // WhatsApp
    Route::get('/notifications/whatsapp', [\App\Http\Controllers\WhatsAppController::class, 'index'])->name('whatsapp.index');
    Route::post('/notifications/whatsapp/send', [\App\Http\Controllers\WhatsAppController::class, 'sendTest'])->name('whatsapp.send-test');

    Route::post('/personal-renewals/{personalRenewal}/whatsapp', [\App\Http\Controllers\PersonalRenewalController::class, 'sendWhatsApp'])->name('personal-renewals.whatsapp');
    Route::resource('personal-renewals', \App\Http\Controllers\PersonalRenewalController::class);

    // Employee 360
    Route::get('/employees', [\App\Http\Controllers\EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/{employee}', [\App\Http\Controllers\EmployeeController::class, 'show'])->name('employees.show');

    // Compliance 360
    Route::get('/compliance-360', [\App\Http\Controllers\ComplianceController::class, 'index'])->name('compliance.index');

    // The Pulse
    Route::get('/activity', [\App\Http\Controllers\ActivityController::class, 'index'])->name('activity.index');

    // Smart Archive
    Route::get('/smart-documents', [\App\Http\Controllers\SmartDocumentController::class, 'index'])->name('smart-documents.index');
    Route::get('/smart-documents/{client}', [\App\Http\Controllers\SmartDocumentController::class, 'show'])->name('smart-documents.show');

    // System Health & Deployment
    Route::prefix('system')->name('system.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SystemController::class, 'index'])->name('index');
        Route::post('/clear-cache', [\App\Http\Controllers\SystemController::class, 'clearCache'])->name('clear-cache');
        Route::post('/optimize', [\App\Http\Controllers\SystemController::class, 'optimize'])->name('optimize');
        Route::post('/migrate', [\App\Http\Controllers\SystemController::class, 'migrate'])->name('migrate');
    });

    // Notifications
    // Leave Management
    Route::patch('/leaves/{leave}/status', [\App\Http\Controllers\LeaveController::class, 'updateStatus'])->name('leaves.update-status');
    Route::resource('leaves', \App\Http\Controllers\LeaveController::class);

    Route::get('/notifications/mark-read/{id}', [\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.read.all');
});
