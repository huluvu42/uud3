<?php

// ============================================================================
// app/Livewire/Admin/BandRegistrationLinks.php
// Admin Interface für Link-Management
// ============================================================================

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Band;
use App\Mail\BandRegistrationMail;
use App\Mail\BandRegistrationReminderMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class BandRegistrationLinks extends Component
{
    public $bands;
    public $selectedBands = [];
    public $stats = [];
    public $filterStatus = 'all';
    public $search = '';

    protected $queryString = ['filterStatus', 'search'];

    public function mount()
    {
        $this->loadBands();
        $this->calculateStats();
    }

    private function loadBands()
    {
        $query = Band::with('stage')
            ->when($this->search, function ($q) {
                $q->where('band_name', 'like', '%' . $this->search . '%')
                    ->orWhere('manager_first_name', 'like', '%' . $this->search . '%')
                    ->orWhere('manager_last_name', 'like', '%' . $this->search . '%')
                    ->orWhere('manager_email', 'like', '%' . $this->search . '%');
            });

        switch ($this->filterStatus) {
            case 'completed':
                $query->where('registration_completed', true);
                break;
            case 'pending':
                $query->whereNotNull('registration_token')
                    ->where('registration_completed', false);
                break;
            case 'no_email':
                $query->whereNull('manager_email');
                break;
            case 'needs_reminder':
                $query->whereNotNull('registration_token')
                    ->where('registration_completed', false)
                    ->where('registration_link_sent_at', '<', now()->subDays(7))
                    ->whereNull('registration_reminder_sent_at');
                break;
            default:
                // all - keine weitere Filterung
                break;
        }

        $this->bands = $query->orderBy('band_name')->get();
    }

    public function updatedSearch()
    {
        $this->loadBands();
    }

    public function updatedFilterStatus()
    {
        $this->loadBands();
        $this->selectedBands = [];
    }

    private function calculateStats()
    {
        $this->stats = [
            'total' => Band::count(),
            'with_manager_email' => Band::whereNotNull('manager_email')->count(),
            'tokens_generated' => Band::whereNotNull('registration_token')->count(),
            'completed' => Band::where('registration_completed', true)->count(),
            'pending' => Band::whereNotNull('registration_token')
                ->where('registration_completed', false)->count(),
            'needs_reminder' => Band::whereNotNull('registration_token')
                ->where('registration_completed', false)
                ->where('registration_link_sent_at', '<', now()->subDays(7))
                ->whereNull('registration_reminder_sent_at')
                ->count(),
            'no_email' => Band::whereNull('manager_email')->count(),
            'completion_rate' => Band::whereNotNull('registration_token')->count() > 0
                ? round(Band::where('registration_completed', true)->count() / Band::whereNotNull('registration_token')->count() * 100, 1)
                : 0,
        ];
    }

    public function generateTokensAndSendEmails()
    {
        if (empty($this->selectedBands)) {
            session()->flash('error', 'Bitte wählen Sie mindestens eine Band aus.');
            return;
        }

        $sent = 0;
        $failed = 0;
        $generated = 0;

        foreach ($this->selectedBands as $bandId) {
            $band = Band::find($bandId);
            if (!$band) continue;

            // Token generieren falls noch nicht vorhanden
            if (!$band->registration_token) {
                $band->generateRegistrationToken();
                $generated++;
            }

            // Email senden wenn möglich
            if ($band->canSendRegistrationEmail()) {
                try {
                    Mail::to($band->manager_email)->send(new BandRegistrationMail($band));
                    $band->update(['registration_link_sent_at' => now()]);
                    $sent++;
                } catch (\Exception $e) {
                    Log::error('Failed to send registration email to ' . $band->manager_email . ': ' . $e->getMessage());
                    $failed++;
                }
            }
        }

        session()->flash(
            'message',
            "Tokens generiert: {$generated}, Emails gesendet: {$sent}" .
                ($failed > 0 ? ", Fehlgeschlagen: {$failed}" : "")
        );

        $this->selectedBands = [];
        $this->mount();
    }

    public function sendReminders()
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
            session()->flash('message', 'Keine Erinnerungen zu senden.');
            return;
        }

        $sent = 0;
        $failed = 0;

        foreach ($bands as $band) {
            try {
                Mail::to($band->manager_email)->send(new BandRegistrationReminderMail($band));
                $band->update(['registration_reminder_sent_at' => now()]);
                $sent++;
            } catch (\Exception $e) {
                Log::error('Failed to send reminder email to ' . $band->manager_email . ': ' . $e->getMessage());
                $failed++;
            }
        }

        session()->flash(
            'message',
            "Erinnerungen gesendet: {$sent}" .
                ($failed > 0 ? ", Fehlgeschlagen: {$failed}" : "")
        );

        $this->mount();
    }

    public function copyLink($bandId)
    {
        $band = Band::find($bandId);
        if ($band && $band->registration_token) {
            $this->dispatch('copy-to-clipboard', text: $band->registration_url);
        }
    }

    public function sendEmail($bandId)
    {
        $band = Band::find($bandId);

        if ($band->canSendRegistrationEmail()) {
            try {
                Mail::to($band->manager_email)->send(new BandRegistrationMail($band));
                $band->update(['registration_link_sent_at' => now()]);
                session()->flash('message', 'Email wurde an ' . $band->manager_full_name . ' gesendet!');
            } catch (\Exception $e) {
                session()->flash('error', 'Email-Versand fehlgeschlagen: ' . $e->getMessage());
            }
        } else {
            session()->flash('error', 'Keine Manager-Email vorhanden für ' . $band->band_name);
        }

        $this->mount();
    }

    public function deleteToken($bandId)
    {
        $band = Band::find($bandId);
        if ($band) {
            $band->update([
                'registration_token' => null,
                'registration_token_expires_at' => null,
                'registration_link_sent_at' => null,
                'registration_reminder_sent_at' => null,
            ]);

            session()->flash('message', 'Token für ' . $band->band_name . ' wurde gelöscht.');
            $this->mount();
        }
    }

    public function resetRegistration($bandId)
    {
        $band = Band::find($bandId);
        if ($band) {
            $band->update([
                'registration_completed' => false,
                'registration_token' => null,
                'registration_token_expires_at' => null,
                'registration_link_sent_at' => null,
                'registration_reminder_sent_at' => null,
                'travel_party' => null,
                'emergency_contact' => null,
                'special_requirements' => null,
            ]);

            // Mitglieder und Kennzeichen löschen
            $band->persons()->delete();
            $band->vehiclePlates()->delete();

            session()->flash('message', 'Registrierung für ' . $band->band_name . ' wurde zurückgesetzt.');
            $this->mount();
        }
    }

    public function render()
    {
        return view('livewire.admin.band-registration-links');
    }
}
