<?php

use App\Http\Controllers\Admin\FarmerController;
use App\Http\Controllers\Admin\InternalNoteController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('farmers/duplicate-check', [FarmerController::class, 'duplicateCheck'])
    ->name('farmers.duplicate-check');

Route::get('farmers/report', [FarmerController::class, 'report'])
    ->name('farmers.report');

Route::get('farmers/export', [FarmerController::class, 'export'])
    ->name('farmers.export');

Route::resource('farmers', FarmerController::class)
    ->only(['index', 'show', 'create', 'store']);

Route::post('farmers/{farmer}/internal-notes', [InternalNoteController::class, 'storeForFarmer'])
    ->name('farmers.internal-notes.store');

Route::middleware('role.in:' . User::ROLE_ADMIN . ',' . User::ROLE_STAFF)->group(function (): void {
    Route::resource('farmers', FarmerController::class)
        ->only(['edit', 'update']);

    Route::post('farmers/bulk-notify', [FarmerController::class, 'bulkNotify'])
        ->name('farmers.bulk-notify');
    Route::post('farmers/bulk-assign', [FarmerController::class, 'bulkAssign'])
        ->name('farmers.bulk-assign');
    Route::post('farmers/bulk-follow-up', [FarmerController::class, 'bulkFollowUp'])
        ->name('farmers.bulk-follow-up');
});

Route::middleware('role.in:' . User::ROLE_ADMIN)->group(function (): void {
    Route::post('farmers/bulk-status-review', [FarmerController::class, 'bulkStatusReview'])
        ->name('farmers.bulk-status-review');
    Route::post('farmers/bulk-archive', [FarmerController::class, 'bulkArchive'])
        ->name('farmers.bulk-archive');
    Route::resource('farmers', FarmerController::class)
        ->only(['destroy']);
});
