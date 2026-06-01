<?php

use App\Http\Controllers\ClientPortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/{token}', [ClientPortalController::class, 'home'])->name('home');
    Route::post('/{token}/upload', [ClientPortalController::class, 'upload'])->name('upload');
});
