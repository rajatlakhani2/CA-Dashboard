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

    // Backup deploy check (works after syncing web.php; run php artisan route:clear if 404)
    Route::get('/dashboard-build-probe', function () {
        abort_unless(auth()->user()?->hasRole('partner', 'manager'), 403);

        $path = resource_path('views/dashboard.blade.php');
        $content = is_readable($path) ? (string) file_get_contents($path) : '';

        return response()->json([
            'build' => 'tabs-v2-20260604',
            'tabs_v2_marker' => str_contains($content, 'dashboard-tabs-v2'),
            'workspace_header_in_view' => str_contains($content, 'workspace-header'),
            'probe' => 'web-route',
        ]);
    })->name('dashboard.build-probe');

    require __DIR__ . '/modules/operations.php';
    require __DIR__ . '/modules/clients.php';
    require __DIR__ . '/modules/work.php';
    require __DIR__ . '/modules/compliance.php';
    require __DIR__ . '/modules/finance.php';
    require __DIR__ . '/modules/reports.php';
});

