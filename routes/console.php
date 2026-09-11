<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:send-renewal-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping();

Schedule::command('app:process-queued-notifications')
    ->everyTenMinutes()
    ->withoutOverlapping();

Schedule::command('app:reject-stale-incomplete-membership-applications')
    ->dailyAt('00:15')
    ->withoutOverlapping();
