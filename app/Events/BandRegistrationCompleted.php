<?php

// ============================================================================
// app/Events/BandRegistrationCompleted.php
// Event das ausgelöst wird bei Abschluss der Registrierung
// ============================================================================

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Band;

class BandRegistrationCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Band $band;

    public function __construct(Band $band)
    {
        $this->band = $band;
    }
}
