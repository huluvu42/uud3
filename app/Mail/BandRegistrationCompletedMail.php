<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Band;

class BandRegistrationCompletedMail extends Mailable implements ShouldQueue
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
            subject: 'Registrierung erfolgreich abgeschlossen: ' . $this->band->band_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.band-registration-completed',
            with: [
                'band' => $this->band,
                'managerName' => $this->band->manager_full_name ?: 'Bandmanager',
                'memberCount' => $this->band->persons()->count(),
                'vehicleCount' => $this->band->vehiclePlates()->count(),
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
