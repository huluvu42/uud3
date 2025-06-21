<?php

// ============================================================================
// app/Listeners/SendRegistrationCompletedNotification.php
// Event Listener für automatische Benachrichtigungen
// ============================================================================

namespace App\Listeners;

use App\Events\BandRegistrationCompleted;
use App\Mail\BandRegistrationCompletedMail;
use App\Mail\AdminBandRegistrationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendRegistrationCompletedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(BandRegistrationCompleted $event): void
    {
        $band = $event->band;

        try {
            // Bestätigung an Manager senden
            if ($band->manager_email) {
                Mail::to($band->manager_email)
                    ->send(new BandRegistrationCompletedMail($band));

                Log::info("Registration completed email sent to {$band->manager_email} for band {$band->band_name}");
            }

            // Benachrichtigung an Admins senden (konfigurierbar)
            $adminEmails = config('app.admin_emails', ['admin@festival.com']);
            foreach ($adminEmails as $email) {
                Mail::to($email)->send(new AdminBandRegistrationNotification($band));
            }

            Log::info("Admin notifications sent for band registration: {$band->band_name}");
        } catch (\Exception $e) {
            Log::error("Failed to send registration completed notifications for band {$band->band_name}: " . $e->getMessage());

            // Nicht den ganzen Prozess zum Absturz bringen wenn Email fehlschlägt
            // Event kann später nochmal versucht werden
        }
    }

    public function failed(BandRegistrationCompleted $event, $exception): void
    {
        Log::error("Registration completed notification failed for band {$event->band->band_name}: " . $exception->getMessage());
    }
}
