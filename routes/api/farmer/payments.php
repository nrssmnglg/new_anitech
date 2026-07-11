<?php

use App\Http\Controllers\Api\Farmer\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:farmer_pwa')->group(function (): void {
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/{payment}/proof', [PaymentController::class, 'uploadProof'])->name('payments.proof.store');
});
