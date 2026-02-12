<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('tasks:send-urgent-notifications')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/urgent-notifications.log'));

        $schedule->command('tasks:send-due-email')
            ->weeklyOn(1, '6:00')
            ->onOneServer()
            ->withoutOverlapping();

        $schedule->command('matters:send-renewal-reminders')
            ->dailyAt('08:30')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/renewal-reminders.log'));

        $schedule->command('tasks:renewr-sync')
            ->weeklyOn(1, '3:00')
            ->onOneServer()
            ->withoutOverlapping();

        $schedule->command('teamleader:refresh-token')
            ->everyFifteenMinutes()
            ->onOneServer()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
