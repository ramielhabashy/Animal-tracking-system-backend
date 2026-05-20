<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('auctions:end-expired')->everyMinute();
        $schedule->command('auctions:process-expired-payments')->everyMinute();
        $schedule->command('devices:update-live-data')->everyMinute();
        $schedule->command('devices:poll-real-data')->everyFiveMinutes();
        $schedule->command('alerts:check-temperature')->everyFiveMinutes();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
