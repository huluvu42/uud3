<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Band;

class BandRegistrationReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Band $band;

    public function __construct(Band $band)
    {
        $this->band = $band;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Erinnerung: Bandmitglieder-Registrierung für ' . $this->band->band_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.band-registration-reminder',
            with: [
                'band' => $this->band,
                'registrationUrl' => $this->band->registration_url,
                'expiresAt' => $this->band->registration_token_expires_at,
                'managerName' => $this->band->manager_full_name ?: 'Bandmanager',
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
