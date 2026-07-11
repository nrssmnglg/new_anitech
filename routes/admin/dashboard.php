<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SearchController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
Route::get('/tasks', [DashboardController::class, 'tasks'])->name('tasks.index');
Route::post('/tasks/quick-action', [DashboardController::class, 'quickAction'])->name('tasks.quick-action');
Route::get('/search', [SearchController::class, 'index'])->name('search.index');

Route::get('/', function () {
    return redirect()->route('admin.reports.index');
})->name('home');
