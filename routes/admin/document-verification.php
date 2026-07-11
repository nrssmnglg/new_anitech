<?php

use App\Http\Controllers\Admin\DocumentVerificationQueueController;
use Illuminate\Support\Facades\Route;

Route::get('/document-verification-queue', [DocumentVerificationQueueController::class, 'index'])
    ->name('document-verification-queue.index');

Route::get('/document-verification-queue/export', [DocumentVerificationQueueController::class, 'export'])
    ->name('document-verification-queue.export');
