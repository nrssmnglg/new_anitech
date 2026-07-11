<?php

use App\Http\Controllers\Api\MembershipApplicationController;
use Illuminate\Support\Facades\Route;

Route::prefix('membership-applications')->name('membership-applications.')->group(function (): void {
    Route::post('/', [MembershipApplicationController::class, 'store'])->name('store');
    Route::get('/track', [MembershipApplicationController::class, 'track'])->name('track');
    Route::post('/{applicationNo}/documents', [MembershipApplicationController::class, 'uploadDocument'])->name('documents.store');
});
