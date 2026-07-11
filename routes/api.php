<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    require __DIR__ . '/api/v1/auth.php';
    require __DIR__ . '/api/v1/profile.php';
    require __DIR__ . '/api/v1/membership.php';
    require __DIR__ . '/api/v1/renewals.php';
    require __DIR__ . '/api/v1/payments.php';
    require __DIR__ . '/api/v1/reactivations.php';
    require __DIR__ . '/api/v1/advisories.php';
    require __DIR__ . '/api/v1/queries.php';
    require __DIR__ . '/api/v1/notifications.php';
});

require __DIR__ . '/api_farmer.php';
