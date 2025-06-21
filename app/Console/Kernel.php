<?php

// ============================================================================
// app/Console/Kernel.php (erweitert)
// Scheduler-Konfiguration
// ============================================================================

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
        // Band Registration Automation
        $schedule->command('registration:send-reminders')
            ->dailyAt('09:00')
            ->environments(['production'])
            ->onSuccess(function () {
                \Log::info('Registration reminders sent successfully');
            })
            ->onFailure(function () {
                \Log::error('Failed to send registration reminders');
            });

        $schedule->command('registration:clean-expired')
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->environments(['production']);

        // Optional: Daily statistics logging
        $schedule->call(function () {
            $stats = [
                'date' => now()->toDateString(),
                'total_bands' => \App\Models\Band::count(),
                'completed_today' => \App\Models\Band::where('registration_completed', true)
                    ->whereDate('updated_at', today())->count(),
                'pending' => \App\Models\Band::whereNotNull('registration_token')
                    ->where('registration_completed', false)->count(),
                'needs_reminder' => \App\Models\Band::whereNotNull('registration_token')
                    ->where('registration_completed', false)
                    ->where('registration_link_sent_at', '<', now()->subDays(7))
                    ->whereNull('registration_reminder_sent_at')
                    ->count(),
            ];

            \Log::channel('registration')->info('Daily registration stats', $stats);
        })->dailyAt('23:30');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
