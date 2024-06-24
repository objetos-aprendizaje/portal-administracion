<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\SendEmailNotifications::class,
        \App\Console\Commands\ChangeStatusToInscription::class,
        \App\Console\Commands\ChangeStatusToEnrolling::class,
        \App\Console\Commands\ChangeStatusToFinished::class,
        \App\Console\Commands\SendEmailNotificationsAutomatic::class,
        \App\Console\Commands\SendSuggestions::class,

        \App\Console\Commands\ChangeStatusToInscriptionEducationalProgram::class,
        \App\Console\Commands\ChangeStatusToDevelopmentEducationalProgram::class,
        \App\Console\Commands\ChangeStatusToEnrollingEducationalProgram::class,
        \App\Console\Commands\ChangeStatusToFinishedEducationalProgram::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:send-email-notifications')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('app:change-status-to-inscription')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('app:change-status-to-enrolling')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('app:change-status-to-development')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('app:change-status-to-finished')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('app:send-suggestions')->everyFiveMinutes()->withoutOverlapping();

        // Programas educativos
        $schedule->command('app:change-status-to-inscription-educational-program')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('app:change-status-to-development-educational-program')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('app:change-status-to-enrolling-educational-program')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('app:change-status-to-finished-educational-program')->everyFiveMinutes()->withoutOverlapping();
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
