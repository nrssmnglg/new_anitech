<?php

use App\Http\Controllers\Admin\InternalNoteController;
use App\Http\Controllers\Admin\RenewalController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('renewals', [RenewalController::class, 'index'])
    ->name('renewals.index');

Route::get('renewals/summary-report', [RenewalController::class, 'summaryReport'])
    ->name('renewals.summary-report');

Route::get('renewals/create', [RenewalController::class, 'create'])
    ->name('renewals.create');

Route::post('renewals', [RenewalController::class, 'store'])
    ->name('renewals.store');

Route::post('renewals/reminders/{farmer}/email', [RenewalController::class, 'sendReminderEmail'])
    ->name('renewals.reminders.email');

Route::get('renewals/{renewal}', [RenewalController::class, 'show'])
    ->name('renewals.show');

Route::get('renewals/{renewal}/documents/{document}', [RenewalController::class, 'viewDocument'])
    ->name('renewals.documents.view');

Route::post('renewals/{renewal}/payment', [RenewalController::class, 'recordPayment'])
    ->name('renewals.payment.store');

Route::post('renewals/{renewal}/documents/{document}/review', [RenewalController::class, 'reviewDocument'])
    ->name('renewals.documents.review');

Route::post('renewals/{renewal}/internal-notes', [InternalNoteController::class, 'storeForRenewal'])
    ->name('renewals.internal-notes.store');

Route::middleware('role.in:' . User::ROLE_ADMIN)->group(function (): void {
    Route::post('renewals/{renewal}/review', [RenewalController::class, 'review'])
        ->name('renewals.review');
});
