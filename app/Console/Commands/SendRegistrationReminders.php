<?php

// ============================================================================
// app/Console/Commands/SendRegistrationReminders.php
// Command für automatische Reminder-Emails
// ============================================================================

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Band;
use App\Mail\BandRegistrationReminderMail;
use Illuminate\Support\Facades\Mail;

class SendRegistrationReminders extends Command
{
    protected $signature = 'registration:send-reminders {--dry-run : Show what would be sent without actually sending}';
    protected $description = 'Send registration reminder emails to bands';

    public function handle()
    {
        $bands = Band::whereNotNull('registration_token')
            ->where('registration_completed', false)
            ->whereNotNull('manager_email')
            ->where('registration_link_sent_at', '<', now()->subDays(7))
            ->where(function ($query) {
                $query->whereNull('registration_reminder_sent_at')
                    ->orWhere('registration_reminder_sent_at', '<', now()->subDays(7));
            })
            ->get();

        if ($bands->isEmpty()) {
            $this->info('No bands need reminder emails.');
            return 0;
        }

        $this->info("Found {$bands->count()} bands that need reminders:");

        $sent = 0;
        $failed = 0;

        foreach ($bands as $band) {
            $this->line("Processing: {$band->band_name} ({$band->manager_email})");

            if ($this->option('dry-run')) {
                $this->info(" [DRY RUN] Would send reminder email");
                continue;
            }

            try {
                Mail::to($band->manager_email)->send(new BandRegistrationReminderMail($band));
                $band->update(['registration_reminder_sent_at' => now()]);
                $this->info(" ✓ Reminder sent successfully");
                $sent++;
            } catch (\Exception $e) {
                $this->error(" ✗ Failed to send reminder: " . $e->getMessage());
                \Log::error('Failed to send reminder to ' . $band->manager_email . ': ' . $e->getMessage());
                $failed++;
            }
        }

        $this->info("\nSummary:");
        $this->info("Sent: {$sent}");
        if ($failed > 0) {
            $this->error("Failed: {$failed}");
        }

        return 0;
    }
}
