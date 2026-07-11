<?php

use App\Http\Controllers\Api\Farmer\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:farmer_pwa')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard.show');
});
