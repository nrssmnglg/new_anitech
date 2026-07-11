<?php

use App\Http\Controllers\Api\Farmer\AuthController;
use App\Http\Controllers\Api\FarmerPasswordResetController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/setup', [AuthController::class, 'setup'])->name('setup');
Route::post('/forgot-password', [FarmerPasswordResetController::class, 'storeForgot'])->name('password.email');
Route::post('/reset-password/verify', [FarmerPasswordResetController::class, 'verifyOtp'])->name('password.verify');
Route::post('/reset-password', [FarmerPasswordResetController::class, 'storeReset'])->name('password.store');
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:farmer_pwa')
    ->name('logout');
