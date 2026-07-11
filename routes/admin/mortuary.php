<?php

use App\Http\Controllers\Admin\MortuaryClaimController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::resource('mortuary-claims', MortuaryClaimController::class)
    ->only(['index', 'create', 'store', 'show']);

Route::get('mortuary-claims-report', [MortuaryClaimController::class, 'report'])
    ->name('mortuary-claims.report');

Route::get('mortuary-claims/{mortuary_claim}/death-certificate', [MortuaryClaimController::class, 'viewDeathCertificate'])
    ->name('mortuary-claims.death-certificate.show');

Route::middleware('role.in:' . User::ROLE_ADMIN)->group(function (): void {
    Route::post('mortuary-claims/{mortuary_claim}/review', [MortuaryClaimController::class, 'review'])
        ->name('mortuary-claims.review');
});
