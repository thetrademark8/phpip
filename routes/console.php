<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes & Scheduling
|--------------------------------------------------------------------------
*/

Schedule::command('tasks:send-urgent-notifications')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/urgent-notifications.log'));

Schedule::command('matters:send-renewal-reminders')
    ->dailyAt('08:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/renewal-reminders.log'));

Schedule::command('tasks:renewr-sync')
    ->weeklyOn(1, '3:00')
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('teamleader:refresh-token')
    ->everyFifteenMinutes()
    ->onOneServer()
    ->withoutOverlapping();
