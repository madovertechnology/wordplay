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
        // Generate the next day's Word Scramble puzzle at midnight
        $schedule->command('game:generate-word-scramble-puzzle --days=1')
                 ->dailyAt('00:01')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/word-scramble-puzzle-generation.log'));

        // Create daily database backups at 2 AM
        $schedule->command('db:backup --compress --retention=30')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/database-backup.log'));

        // Create weekly full backups on Sundays at 3 AM
        $schedule->command('db:backup --compress --retention=90')
                 ->weeklyOn(0, '03:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/database-backup-weekly.log'));

        // Run database health checks every hour
        $schedule->command('db:health-check --alert-threshold=1000')
                 ->hourly()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/database-health-check.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}