<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tasks:send-due-email')
    ->weeklyOn(1, '6:00')
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('tasks:renewr-sync')
    ->weeklyOn(1, '3:00')
    ->onOneServer()
    ->withoutOverlapping();

// Send urgent task notifications daily at 8:00 AM
Schedule::command('tasks:send-urgent-notifications')
    ->dailyAt('08:00')
    ->onOneServer()
    ->withoutOverlapping();

// Send matter renewal reminders daily at 8:30 AM
// Sends reminders at 6 months, 3 months, and 1 month before expiry
Schedule::command('matters:send-renewal-reminders')
    ->dailyAt('08:30')
    ->onOneServer()
    ->withoutOverlapping();
