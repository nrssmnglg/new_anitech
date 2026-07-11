<?php

use App\Http\Controllers\Api\Farmer\InquiryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:farmer_pwa')->group(function (): void {
    Route::get('/inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
    Route::post('/inquiries', [InquiryController::class, 'store'])->name('inquiries.store');
    Route::get('/inquiries/{inquiry}', [InquiryController::class, 'show'])->name('inquiries.show');
    Route::get('/inquiries/{inquiry}/attachments/{attachment}', [InquiryController::class, 'showAttachment'])->name('inquiries.attachments.show');
    Route::get('/inquiries/{inquiry}/responses/{response}/attachments/{attachment}', [InquiryController::class, 'showResponseAttachment'])->name('inquiries.responses.attachments.show');
    Route::post('/inquiries/{inquiry}/reply', [InquiryController::class, 'reply'])->name('inquiries.reply');
});
