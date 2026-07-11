<?php

use App\Http\Controllers\Admin\InternalNoteController;
use App\Http\Controllers\Admin\QueryController;
use Illuminate\Support\Facades\Route;

Route::resource('queries', QueryController::class)->only(['index', 'show']);
Route::get('queries-export', [QueryController::class, 'export'])->name('queries.export');
Route::post('queries/{query}/respond', [QueryController::class, 'respond'])->name('queries.respond');
Route::post('queries/{query}/close', [QueryController::class, 'close'])->name('queries.close');
Route::post('queries/{query}/reopen', [QueryController::class, 'reopen'])->name('queries.reopen');
Route::post('queries/{query}/escalate', [QueryController::class, 'escalate'])->name('queries.escalate');
Route::post('queries/{query}/internal-notes', [InternalNoteController::class, 'storeForQuery'])->name('queries.internal-notes.store');
Route::get('queries/{query}/images/{image}', [QueryController::class, 'viewImage'])->name('queries.images.show');
Route::get('queries/{query}/responses/{response}/attachments/{attachment}', [QueryController::class, 'viewResponseAttachment'])->name('queries.responses.attachments.show');
