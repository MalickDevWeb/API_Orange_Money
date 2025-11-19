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
        // Nettoyer les codes OTP expirés et utilisés toutes les 5 minutes
        $schedule->job(new \App\Jobs\CleanExpiredOtpCodes())
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->runInBackground();

        // Nettoyer les commerçants orphelins (sans code marchand) tous les jours à 2h du matin
        $schedule->job(new \App\Jobs\CleanOrphanedMerchants())
                ->dailyAt('02:00')
                ->withoutOverlapping()
                ->runInBackground();
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
