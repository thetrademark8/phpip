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
        // Send urgent task notifications daily at 8:00 AM
        $schedule->command('tasks:send-urgent-notifications')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/urgent-notifications.log'));

        // Send matter renewal reminders daily at 8:30 AM
        // Sends reminders at 6 months, 3 months, and 1 month before expiry
        $schedule->command('matters:send-renewal-reminders')
            ->dailyAt('08:30')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/renewal-reminders.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
