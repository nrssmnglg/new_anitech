<?php

use App\Http\Controllers\Api\PayMongoWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->name('payments.')->group(function (): void {
    Route::post('/paymongo/webhook', PayMongoWebhookController::class)
        ->middleware('paymongo.webhook')
        ->name('paymongo.webhook');
});
