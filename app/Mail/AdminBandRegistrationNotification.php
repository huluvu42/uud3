<?php

// ============================================================================
// app/Mail/AdminBandRegistrationNotification.php
// Admin-Benachrichtigung über neue Registrierung
// ============================================================================

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Band;

class AdminBandRegistrationNotification extends Mailable implements ShouldQueue
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
            subject: 'Neue Band-Registrierung: ' . $this->band->band_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin-band-registration-notification',
            with: [
                'band' => $this->band,
                'memberCount' => $this->band->persons()->count(),
                'vehicleCount' => $this->band->vehiclePlates()->count(),
                'adminUrl' => route('admin.bands'),
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
