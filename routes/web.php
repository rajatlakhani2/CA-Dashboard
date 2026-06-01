<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', [\App\Http\Controllers\LoginController::class, 'show'])->name('login');
Route::post('/login', [\App\Http\Controllers\LoginController::class, 'login']);

Route::get('/', function () {
    return redirect()->route('login');
});

require __DIR__ . '/portal.php';

Route::middleware(['auth', \App\Http\Middleware\RestrictArticleAccess::class])->group(function () {
    Route::post('/logout', [\App\Http\Controllers\LoginController::class, 'logout'])->name('logout');

    require __DIR__ . '/modules/operations.php';
    require __DIR__ . '/modules/clients.php';
    require __DIR__ . '/modules/work.php';
    require __DIR__ . '/modules/compliance.php';
    require __DIR__ . '/modules/finance.php';
    require __DIR__ . '/modules/reports.php';
});

