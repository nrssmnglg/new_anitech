<?php

use App\Http\Controllers\Admin\DocumentRequirementController;
use Illuminate\Support\Facades\Route;

Route::get('/document-requirements', [DocumentRequirementController::class, 'index'])
    ->name('document-requirements.index');

Route::post('/document-requirements', [DocumentRequirementController::class, 'store'])
    ->name('document-requirements.store');

Route::put('/document-requirements/{documentRequirement}', [DocumentRequirementController::class, 'update'])
    ->name('document-requirements.update');

Route::delete('/document-requirements/{documentRequirement}', [DocumentRequirementController::class, 'destroy'])
    ->name('document-requirements.destroy');
