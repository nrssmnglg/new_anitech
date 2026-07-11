<?php

use App\Http\Controllers\Api\Farmer\AdvisoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:farmer_pwa')->group(function (): void {
    Route::get('/advisories', [AdvisoryController::class, 'index'])->name('advisories.index');
    Route::get('/advisories/{advisory}', [AdvisoryController::class, 'show'])->name('advisories.show');
    Route::post('/advisories/{advisory}/reaction', [AdvisoryController::class, 'react'])->name('advisories.react');
    Route::get('/advisories/{advisory}/attachments/{attachment}', [AdvisoryController::class, 'attachment'])
        ->name('advisories.attachments.show');
});
