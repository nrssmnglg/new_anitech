<?php

use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::resource('users', UserController::class)->except(['destroy']);
Route::delete('users/{user}/archive', [UserController::class, 'archive'])->name('users.archive');
