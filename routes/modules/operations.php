<?php

use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware('module:dashboard')->name('dashboard');
Route::get('/dashboard/deploy-probe', [\App\Http\Controllers\DashboardController::class, 'deployProbe'])
    ->middleware(['module:dashboard', 'role:partner,manager'])
    ->name('dashboard.deploy-probe');
Route::post('/onboarding/dismiss', [\App\Http\Controllers\WorkspaceOnboardingController::class, 'dismiss'])->name('onboarding.dismiss');
Route::get('/partner-dashboard', [\App\Http\Controllers\PartnerDashboardController::class, 'index'])->middleware('role:partner')->name('partner.dashboard');

Route::middleware('role:partner,manager')->group(function () {
    Route::post('/firm-alerts/{firmAlert}/dismiss', [\App\Http\Controllers\FirmAlertController::class, 'dismiss'])->name('firm-alerts.dismiss');
    Route::post('/firm-alerts/scan', [\App\Http\Controllers\FirmAlertController::class, 'scan'])->middleware('role:partner')->name('firm-alerts.scan');
});
Route::get('/calendar/events', [\App\Http\Controllers\DashboardController::class, 'calendarEvents'])->middleware('module:dashboard')->name('calendar.events');
Route::post('/calendar/update-date', [\App\Http\Controllers\DashboardController::class, 'updateDate'])->name('calendar.update');

Route::middleware('role:partner,manager')->group(function () {
    Route::get('/recycle-bin', [\App\Http\Controllers\RecycleBinController::class, 'index'])->name('recycle-bin.index');
    Route::post('/recycle-bin/restore/{type}/{id}', [\App\Http\Controllers\RecycleBinController::class, 'restore'])->name('recycle-bin.restore');
    Route::delete('/recycle-bin/force-delete/{type}/{id}', [\App\Http\Controllers\RecycleBinController::class, 'forceDelete'])->name('recycle-bin.force-delete');

    Route::get('/notifications/whatsapp', [\App\Http\Controllers\WhatsAppController::class, 'index'])->name('whatsapp.index');
    Route::post('/notifications/whatsapp/send', [\App\Http\Controllers\WhatsAppController::class, 'sendTest'])->name('whatsapp.send-test');
    Route::post('/notifications/whatsapp/settings', [\App\Http\Controllers\WhatsAppController::class, 'saveSettings'])->name('whatsapp.settings');

    Route::get('/activity', [\App\Http\Controllers\ActivityController::class, 'index'])->middleware('module:activity')->name('activity.index');
});

Route::middleware('module:settings')->group(function () {
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [\App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');
});

Route::get('/search/palette', [\App\Http\Controllers\SearchController::class, 'palette'])->name('search.palette');
Route::get('/search/global', [\App\Http\Controllers\SearchController::class, 'globalSearch'])->name('search.global');

Route::prefix('system')->name('system.')->middleware(['role:partner', 'module:system'])->group(function () {
    Route::get('/', [\App\Http\Controllers\SystemController::class, 'index'])->name('index');
    Route::post('/clear-cache', [\App\Http\Controllers\SystemController::class, 'clearCache'])->name('clear-cache');
    Route::post('/optimize', [\App\Http\Controllers\SystemController::class, 'optimize'])->name('optimize');
    Route::post('/migrate', [\App\Http\Controllers\SystemController::class, 'migrate'])
        ->middleware('system.dangerous')
        ->name('migrate');
    Route::post('/backup/run', [\App\Http\Controllers\SystemController::class, 'runBackup'])->name('backup.run');
    Route::get('/backup/download/{filename}', [\App\Http\Controllers\SystemController::class, 'downloadBackup'])->name('backup.download');
    Route::delete('/backup/delete/{filename}', [\App\Http\Controllers\SystemController::class, 'deleteBackup'])->name('backup.delete');
});

Route::get('/notifications/mark-read/{id}', [\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.read');
Route::get('/notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.read.all');

Route::resource('branches', \App\Http\Controllers\BranchController::class)
    ->only(['index', 'store', 'destroy'])
    ->middleware('role:partner');

Route::middleware('role:partner')->group(function () {
    Route::get('/users', [\App\Http\Controllers\SettingsController::class, 'users'])->name('users.index');
    Route::post('/users', [\App\Http\Controllers\SettingsController::class, 'storeUser'])->name('users.store');
    Route::patch('/users/{user}/role', [\App\Http\Controllers\SettingsController::class, 'updateRole'])->name('users.update-role');
    Route::patch('/users/{user}/module-access', [\App\Http\Controllers\SettingsController::class, 'updateModuleAccess'])->name('users.update-module-access');
});
