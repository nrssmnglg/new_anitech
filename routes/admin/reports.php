<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AnalyticsDashboardController;
use App\Http\Controllers\Admin\BackupRecoveryController;
use App\Http\Controllers\Admin\ReportController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/reports', [DashboardController::class, 'index'])->name('reports.index');
Route::get('/reports/exports/{reportExport}/download', [ReportController::class, 'download'])->name('reports.exports.download');

Route::middleware('role.in:' . User::ROLE_ADMIN)->group(function (): void {
    Route::get('/reports/analytics', [AnalyticsDashboardController::class, 'index'])->name('analytics.index');
    Route::get('/reports/analytics/export', [AnalyticsDashboardController::class, 'export'])->name('analytics.export');
    Route::get('/reports/backups', [BackupRecoveryController::class, 'index'])->name('backups.index');
    Route::post('/reports/backups/database', [BackupRecoveryController::class, 'storeDatabaseBackup'])->name('backups.database.store');
    Route::post('/reports/backups/farmers/snapshot', [BackupRecoveryController::class, 'storeFarmerSnapshot'])->name('backups.farmers.snapshot.store');
    Route::get('/reports/backups/{key}/download', [BackupRecoveryController::class, 'download'])->name('backups.download');
    Route::post('/reports/backups/farmers/{farmer}/recover', [BackupRecoveryController::class, 'recoverFarmer'])->name('backups.farmers.recover');
});
