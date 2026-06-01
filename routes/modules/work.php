<?php

use Illuminate\Support\Facades\Route;

Route::patch('/tasks/{task}/mark-foc', [\App\Http\Controllers\TaskController::class, 'markFoc'])
    ->middleware('role:partner,manager')
    ->name('tasks.mark-foc');

Route::middleware('module:tasks')->group(function () {
    Route::get('/my-day', [\App\Http\Controllers\TaskController::class, 'myDay'])->name('tasks.my-day');
    Route::patch('tasks/{task}/mobile-note', [\App\Http\Controllers\TaskMobileWorkController::class, 'appendNote'])->name('tasks.mobile-note');
    Route::post('tasks/{task}/mobile-time', [\App\Http\Controllers\TaskMobileWorkController::class, 'logTime'])->name('tasks.mobile-time');
    Route::patch('tasks/{task}/status', [\App\Http\Controllers\TaskController::class, 'updateStatus'])->name('tasks.update-status');
    Route::resource('tasks', \App\Http\Controllers\TaskController::class)->except(['show']);
    Route::resource('time-entries', \App\Http\Controllers\TimeEntryController::class)->only(['index', 'store', 'destroy']);
});

Route::middleware(['role:partner,manager', 'module:tasks'])->group(function () {
    Route::get('/workload', [\App\Http\Controllers\WorkloadPlannerController::class, 'index'])->name('workload.index');
    Route::patch('/workload/reassign', [\App\Http\Controllers\WorkloadPlannerController::class, 'reassign'])->name('workload.reassign');
});

Route::middleware(['role:partner,manager', 'module:staff'])->group(function () {
    Route::get('/staff', [\App\Http\Controllers\StaffController::class, 'index'])->name('staff.index');
    Route::post('/staff', [\App\Http\Controllers\StaffController::class, 'store'])->name('staff.store');
    Route::get('/staff/{employee}', [\App\Http\Controllers\StaffController::class, 'show'])->name('staff.show');
    Route::post('/staff/{employee}/allot-work', [\App\Http\Controllers\StaffController::class, 'allotWork'])->name('staff.allot-work');
    Route::post('/staff/{user}/send-reminder', [\App\Http\Controllers\StaffController::class, 'sendReminder'])->name('staff.send-reminder');
});

Route::patch('/leaves/{leave}/status', [\App\Http\Controllers\LeaveController::class, 'updateStatus'])
    ->middleware('role:partner,manager')
    ->name('leaves.update-status');
Route::resource('leaves', \App\Http\Controllers\LeaveController::class)->only(['index', 'create', 'store']);
