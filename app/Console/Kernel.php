<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */

    protected $commands = [
        \App\Modules\Aggregation\Commands\FetchNewsCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        // Append output to a scheduler log file
        $schedule->command('news:fetch')
            ->everyMinute()
            ->withoutOverlapping() // Prevents multiple executions of the same command
            ->appendOutputTo(storage_path('logs/scheduler.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        $this->load(base_path('app/Modules/Aggregation/Commands'));

        require base_path('routes/console.php');
    }
}
