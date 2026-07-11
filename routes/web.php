<?php

use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\Auth\PasswordChangeController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\PreventBackHistory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\LogoutController;
use App\Http\Controllers\AnalyticsController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

Route::get('/', function (Request $request) {
    if (! Auth::check()) {
        $userAgent = Str::lower((string) $request->userAgent());
        $isMobileDevice = Str::contains($userAgent, [
            'android',
            'iphone',
            'ipad',
            'ipod',
            'mobile',
            'opera mini',
            'iemobile',
        ]);

        if ($isMobileDevice) {
            return redirect()->route('farmer.pwa.app');
        }

        return redirect()->route('login');
    }

    return redirect()->route(
        in_array(Auth::user()->role, [User::ROLE_ADMIN, User::ROLE_STAFF], true)
            ? 'admin.reports.index'
            : 'admin.farmers.index'
    );
});

Route::middleware(['guest', PreventBackHistory::class])->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.attempt');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('/reset-password', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password/verify', [ResetPasswordController::class, 'verify'])->name('password.verify');
    Route::get('/reset-password/new', [ResetPasswordController::class, 'createNewPassword'])->name('password.create');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.store');
});

Route::post('/logout', [LogoutController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::post('/analytics/events', [AnalyticsController::class, 'store'])->name('analytics.events.store');

Route::prefix('admin')
    ->name('admin.')
    ->middleware([
        'auth',
        PreventBackHistory::class,
        EnsureActiveUser::class,
        'role.in:' . User::ROLE_ADMIN . ',' . User::ROLE_STAFF,
    ])
    ->group(function (): void {
        Route::get('/password/change', [PasswordChangeController::class, 'edit'])->name('password.edit');
        Route::put('/password/change', [PasswordChangeController::class, 'update'])->name('password.update');
    });

require __DIR__.'/admin.php';

require __DIR__.'/farmer.php';

