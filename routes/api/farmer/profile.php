<?php

use App\Http\Controllers\Api\Farmer\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:farmer_pwa')->group(function (): void {
    Route::get('/me', [ProfileController::class, 'me'])->name('me');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});
