<?php

use App\Http\Controllers\Api\MembershipApplicationController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix('farmer')
    ->name('api.farmer.')
    ->group(function (): void {
        Route::post('/application', [MembershipApplicationController::class, 'store'])->name('application.store');
        Route::get('/application/track', [MembershipApplicationController::class, 'track'])->name('application.track');
        Route::post('/application/{applicationNo}/documents', [MembershipApplicationController::class, 'uploadDocument'])->name('application.documents.store');
        Route::post('/application/{applicationNo}/payment', [MembershipApplicationController::class, 'pay'])->name('application.payment.store');
        Route::get('/application/{applicationNo}/payment/qr', [MembershipApplicationController::class, 'qrPage'])->name('application.payment.qr');

        require __DIR__ . '/api/farmer/auth.php';
        require __DIR__ . '/api/farmer/dashboard.php';
        require __DIR__ . '/api/farmer/profile.php';
        require __DIR__ . '/api/farmer/membership.php';
        require __DIR__ . '/api/farmer/renewals.php';
        require __DIR__ . '/api/farmer/payments.php';
        require __DIR__ . '/api/farmer/advisories.php';
        require __DIR__ . '/api/farmer/inquiries.php';
        require __DIR__ . '/api/farmer/notifications.php';
    });
