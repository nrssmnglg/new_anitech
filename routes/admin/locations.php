<?php

use App\Http\Controllers\Admin\AssociationController;
use App\Http\Controllers\Admin\BarangayController;
use Illuminate\Support\Facades\Route;

Route::patch('barangays/{barangay}/status', [BarangayController::class, 'toggleStatus'])
    ->name('barangays.status');
Route::resource('barangays', BarangayController::class)->except(['destroy']);

Route::patch('associations/{association}/status', [AssociationController::class, 'toggleStatus'])
    ->name('associations.status');
Route::resource('associations', AssociationController::class)->except(['destroy']);
