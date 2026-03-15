<?php
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Register schedules directly here (Laravel 11 style)
Schedule::command('bookings:release-locks')->everyFiveMinutes();
Schedule::command('waitlist:process')->everyTenMinutes();
Schedule::command('trips:update-status')->everyThirtyMinutes();
