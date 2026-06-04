<?php

use Illuminate\Support\Facades\Route;

Route::view('/clear-app-cache', 'clear-app-cache');
Route::get('/unregister-pwa', fn () => redirect('/clear-app-cache'));

Route::get('/login', [\App\Http\Controllers\LoginController::class, 'show'])->name('login');
Route::post('/login', [\App\Http\Controllers\LoginController::class, 'login']);

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

    require __DIR__ . '/modules/operations.php';
    require __DIR__ . '/modules/clients.php';
    require __DIR__ . '/modules/work.php';
    require __DIR__ . '/modules/compliance.php';
    require __DIR__ . '/modules/finance.php';
    require __DIR__ . '/modules/reports.php';
});

