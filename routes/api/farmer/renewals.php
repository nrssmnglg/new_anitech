<?php

use App\Http\Controllers\Api\Farmer\RenewalController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:farmer_pwa')->group(function (): void {
    Route::get('/renewals', [RenewalController::class, 'index'])->name('renewals.index');
    Route::get('/renewals/eligibility', [RenewalController::class, 'eligibility'])->name('renewals.eligibility');
    Route::post('/renewals', [RenewalController::class, 'startOrResume'])->name('renewals.start');
    Route::post('/renewals/{renewal}/payment', [RenewalController::class, 'pay'])->name('renewals.pay');
    Route::get('/renewals/{renewal}/payment/qr', [RenewalController::class, 'qrPage'])->name('renewals.payment.qr');
});
