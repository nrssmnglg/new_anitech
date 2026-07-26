<?php

use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\PreventBackHistory;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware([
        'auth',
        PreventBackHistory::class,
        EnsureActiveUser::class,
        'role.in:' . User::ROLE_ADMIN . ',' . User::ROLE_STAFF,
    ])
    ->group(function (): void {
        Route::middleware(EnsurePasswordIsChanged::class)->group(function (): void {
            require __DIR__ . '/admin/dashboard.php';
            require __DIR__ . '/admin/farmers.php';
            require __DIR__ . '/admin/membership.php';
            require __DIR__ . '/admin/renewals.php';
            require __DIR__ . '/admin/reactivations.php';
            require __DIR__ . '/admin/document-verification.php';
            require __DIR__ . '/admin/mortuary.php';
            require __DIR__ . '/admin/queries.php';
            require __DIR__ . '/admin/notifications.php';
            require __DIR__ . '/admin/advisories.php';
            require __DIR__ . '/admin/reports.php';

            Route::middleware('role.in:' . User::ROLE_ADMIN)->group(function (): void {
                require __DIR__ . '/admin/locations.php';
                require __DIR__ . '/admin/fees.php';
                require __DIR__ . '/admin/document-requirements.php';
                require __DIR__ . '/admin/users.php';
                require __DIR__ . '/admin/audit-logs.php';
            });
        });
    });
