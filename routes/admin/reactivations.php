<?php

use App\Http\Controllers\Admin\ReactivationController;
use Illuminate\Support\Facades\Route;

Route::post('farmers/{farmer}/reactivate', [ReactivationController::class, 'store'])
    ->name('farmers.reactivate');

Route::get('reactivations/{reactivation}', [ReactivationController::class, 'show'])
    ->name('reactivations.show');

Route::post('reactivations/{reactivation}/documents/{document}/review', [ReactivationController::class, 'reviewDocument'])
    ->name('reactivations.documents.review');

Route::post('reactivations/{reactivation}/documents/{document}/attach-scan', [ReactivationController::class, 'attachDocumentScan'])
    ->name('reactivations.documents.attach-scan');

Route::get('reactivations/{reactivation}/documents/{document}/view', [ReactivationController::class, 'viewDocument'])
    ->name('reactivations.documents.view');

Route::post('reactivations/{reactivation}/payment', [ReactivationController::class, 'recordPayment'])
    ->name('reactivations.payment.store');
