<?php

use App\Http\Controllers\Admin\FeeScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('fee-schedules', [FeeScheduleController::class, 'index'])
    ->name('fee-schedules.index');

Route::get('fee-schedules/create', [FeeScheduleController::class, 'create'])
    ->name('fee-schedules.create');

Route::post('fee-schedules', [FeeScheduleController::class, 'store'])
    ->name('fee-schedules.store');

Route::get('fee-schedules/{fee_schedule}/edit', [FeeScheduleController::class, 'edit'])
    ->name('fee-schedules.edit');

Route::put('fee-schedules/{fee_schedule}', [FeeScheduleController::class, 'update'])
    ->name('fee-schedules.update');

Route::post('fee-schedules/{fee_schedule}/activate', [FeeScheduleController::class, 'activate'])
    ->name('fee-schedules.activate');

Route::delete('fee-schedules/{fee_schedule}', [FeeScheduleController::class, 'destroy'])
    ->name('fee-schedules.destroy');
