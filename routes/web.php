<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/clear-app-cache', 'clear-app-cache');
Route::get('/unregister-pwa', fn () => redirect('/clear-app-cache'));

Route::get('/login', [\App\Http\Controllers\LoginController::class, 'show'])->name('login');
Route::post('/login', [\App\Http\Controllers\LoginController::class, 'login']);

Route::get('/password/forgot', [\App\Http\Controllers\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/password/email', [\App\Http\Controllers\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/password/reset/{token}', [\App\Http\Controllers\ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/password/reset', [\App\Http\Controllers\ForgotPasswordController::class, 'reset'])->name('password.update');

Route::get('/register', [\App\Http\Controllers\RegisterOrganizationController::class, 'show'])->name('register.organization');
Route::post('/register', [\App\Http\Controllers\RegisterOrganizationController::class, 'store']);

Route::get('/', function () {
    return redirect()->route('login');
});

require __DIR__ . '/portal.php';

Route::middleware([
    'auth',
    \App\Http\Middleware\EnsureOrganizationIsActive::class,
    \App\Http\Middleware\RestrictArticleAccess::class,
])->group(function () {
    Route::post('/logout', [\App\Http\Controllers\LoginController::class, 'logout'])->name('logout');

    // Dashboard routes in web.php so they register even if route cache / operations.php is stale
    Route::get('/dashboard/deploy-probe', [DashboardController::class, 'deployProbe'])
        ->middleware(['module:dashboard', 'role:partner,manager'])
        ->name('dashboard.deploy-probe');
    Route::get('/dashboard-build-probe', [DashboardController::class, 'deployProbe'])
        ->middleware(['module:dashboard', 'role:partner,manager'])
        ->name('dashboard.build-probe');
    Route::get('/dashboard/finance-snapshot', [DashboardController::class, 'financeSnapshot'])
        ->middleware('module:dashboard')
        ->name('dashboard.finance-snapshot');
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('module:dashboard')
        ->name('dashboard');

    require __DIR__ . '/modules/operations.php';
    require __DIR__ . '/modules/clients.php';
    require __DIR__ . '/modules/work.php';
    require __DIR__ . '/modules/compliance.php';
    require __DIR__ . '/modules/finance.php';
    require __DIR__ . '/modules/reports.php';
});

