<?php

use App\Http\Controllers\ClientPortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->name('portal.')->group(function () {
    $tokenPattern = '[A-Za-z0-9]{48}';

    Route::get('/{token}/upload', function (string $token) {
        return redirect()->route('portal.home', ['token' => $token]);
    })->where('token', $tokenPattern)->name('upload.redirect');

    Route::get('/{token}', [ClientPortalController::class, 'home'])
        ->where('token', $tokenPattern)
        ->name('home');

    Route::post('/{token}/upload', [ClientPortalController::class, 'upload'])
        ->where('token', $tokenPattern)
        ->name('upload');
});
