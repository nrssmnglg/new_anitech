<?php

use App\Http\Controllers\Admin\InternalNoteController;
use App\Http\Controllers\Admin\MembershipApplicationController;
use Illuminate\Support\Facades\Route;

Route::resource('membership-applications', MembershipApplicationController::class)
    ->only(['index', 'create', 'store', 'show']);

Route::get('membership-applications/{membership_application}/documents/{document}/view', [MembershipApplicationController::class, 'viewDocument'])
    ->name('membership-applications.documents.view');

Route::get('membership-applications/{membership_application}/documents/{document}/review', [MembershipApplicationController::class, 'showDocumentReview'])
    ->name('membership-applications.documents.review.show');

Route::post('membership-applications/{membership_application}/documents/{document}/review', [MembershipApplicationController::class, 'reviewDocument'])
    ->name('membership-applications.documents.review');

Route::post('membership-applications/{membership_application}/documents/{document}/attach-scan', [MembershipApplicationController::class, 'attachDocumentScan'])
    ->name('membership-applications.documents.attach-scan');

Route::post('membership-applications/{membership_application}/payment', [MembershipApplicationController::class, 'recordPayment'])
    ->name('membership-applications.payment.store');

Route::post('membership-applications/{membership_application}/review', [MembershipApplicationController::class, 'review'])
    ->name('membership-applications.review');

Route::post('membership-applications/{membership_application}/internal-notes', [InternalNoteController::class, 'storeForApplication'])
    ->name('membership-applications.internal-notes.store');
