<?php

use App\Http\Controllers\Api\MembershipApplicationController;
use App\Http\Controllers\Api\Farmer\MembershipController;
use Illuminate\Support\Facades\Route;

Route::get('/application/{applicationNo}/payment/qr', [MembershipApplicationController::class, 'qrPage'])
    ->name('application.payment.qr');

Route::middleware('auth:farmer_pwa')->group(function (): void {
    Route::get('/membership', [MembershipController::class, 'show'])->name('membership.show');
});
