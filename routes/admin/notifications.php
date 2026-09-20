<?php

use App\Http\Controllers\Admin\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
Route::post('/notifications/{recipient}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
Route::post('/notifications/{recipient}/open', [NotificationController::class, 'open'])->name('notifications.open');
Route::post('/notifications/{notification}/resend', [NotificationController::class, 'resend'])->name('notifications.resend');
