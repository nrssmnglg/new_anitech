<?php

use App\Http\Controllers\Admin\AdvisoryController;
use Illuminate\Support\Facades\Route;

Route::resource('advisories', AdvisoryController::class)
    ->only(['index', 'create', 'store', 'show', 'edit', 'update']);

Route::post('advisories/{advisory}/publish', [AdvisoryController::class, 'publish'])
    ->name('advisories.publish');

Route::post('advisories/{advisory}/unpublish', [AdvisoryController::class, 'unpublish'])
    ->name('advisories.unpublish');

Route::get('advisories/{advisory}/attachments/{attachment}', [AdvisoryController::class, 'viewAttachment'])
    ->name('advisories.attachments.show');

Route::delete('advisories/{advisory}/attachments/{attachment}', [AdvisoryController::class, 'destroyAttachment'])
    ->name('advisories.attachments.destroy');
