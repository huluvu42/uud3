<?php

// ============================================================================
// app/Http/Controllers/BandRegistrationController.php
// Öffentlicher Controller für Band-Registrierung (Option 3: Hybrid)
// ============================================================================

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Band;
use App\Mail\BandRegistrationCompletedMail;
use App\Events\BandRegistrationCompleted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class BandRegistrationController extends Controller
{
    /**
     * Registrierungsformular anzeigen
     */
    public function show($token)
    {
        $band = Band::where('registration_token', $token)->firstOrFail();

        if (!$band->isTokenValid($token)) {
            abort(403, 'Registrierungslink ist ungültig oder abgelaufen.');
        }

        // Existing data laden falls vorhanden
        $existingMembers = $band->persons()
            ->select('first_name', 'last_name')
            ->get()
            ->toArray();

        $existingVehicles = $band->vehiclePlates()
            ->pluck('license_plate')
            ->toArray();

        return view('band-registration.form', compact('band', 'existingMembers', 'existingVehicles'));
    }

    /**
     * Registrierung speichern
     */
    // Ersetzen Sie die store() Methode:
    public function store(Request $request, $token)
    {
        $band = Band::where('registration_token', $token)->firstOrFail();

        if (!$band->isTokenValid($token)) {
            abort(403, 'Registrierungslink ist ungültig oder abgelaufen.');
        }

        $request->validate([
            'travel_party' => 'required|integer|min:1|max:50',
            'members' => 'required|array|min:1|max:50',
            'members.*.first_name' => 'required|string|max:255|regex:/^[a-zA-ZäöüÄÖÜß\s\-\'\.]+$/',
            'members.*.last_name' => 'required|string|max:255|regex:/^[a-zA-ZäöüÄÖÜß\s\-\'\.]+$/',
            'members.*.vehicle_plate' => 'nullable|string|max:20|regex:/^[A-Z0-9\-\s]+$/i',
            'members.*.guest_first_name' => 'nullable|string|max:255|regex:/^[a-zA-ZäöüÄÖÜß\s\-\'\.]+$/',
            'members.*.guest_last_name' => 'nullable|string|max:255|regex:/^[a-zA-ZäöüÄÖÜß\s\-\'\.]+$/',
            'emergency_contact' => 'nullable|string|max:255',
            'special_requirements' => 'nullable|string|max:1000',
        ]);

        try {
            DB::transaction(function () use ($request, $band) {
                $band->update([
                    'travel_party' => $request->travel_party,
                    'registration_completed' => true,
                    'emergency_contact' => $request->emergency_contact,
                    'special_requirements' => $request->special_requirements,
                ]);

                // Bestehende Daten löschen
                $band->persons()->delete();
                $band->vehiclePlates()->delete();

                // Neue Mitglieder hinzufügen
                foreach ($request->members as $memberData) {
                    $person = $band->persons()->create([
                        'first_name' => trim($memberData['first_name']),
                        'last_name' => trim($memberData['last_name']),
                        'year' => $band->year,
                        'present' => false,
                        'is_duplicate' => false,
                    ]);

                    // KFZ-Kennzeichen für diese Person hinzufügen
                    if (!empty($memberData['vehicle_plate'])) {
                        $band->vehiclePlates()->create([
                            'license_plate' => strtoupper(trim($memberData['vehicle_plate'])),
                            'person_id' => $person->id, // ← NUTZT BESTEHENDES FELD
                        ]);
                    }

                    // Gast für diese Person hinzufügen
                    if (!empty($memberData['guest_first_name']) || !empty($memberData['guest_last_name'])) {
                        $band->persons()->create([
                            'first_name' => trim($memberData['guest_first_name'] ?? ''),
                            'last_name' => trim($memberData['guest_last_name'] ?? ''),
                            'year' => $band->year,
                            'present' => false,
                            'is_duplicate' => false,
                            'responsible_person_id' => $person->id, // ← NUTZT BESTEHENDES FELD
                        ]);
                    }
                }

                event(new BandRegistrationCompleted($band));
            });

            return redirect()->route('band.register.success', ['token' => $token]);
        } catch (\Exception $e) {
            \Log::error('Band registration failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Ein Fehler ist aufgetreten.']);
        }
    }

    /**
     * Erfolgsseite anzeigen
     */
    public function success($token)
    {
        $band = Band::where('registration_token', $token)->firstOrFail();

        // Auch abgeschlossene Registrierungen anzeigen
        if (!$band->registration_completed) {
            return redirect()->route('band.register', ['token' => $token]);
        }

        $memberCount = $band->persons()->count();
        $vehicleCount = $band->vehiclePlates()->count();

        return view('band-registration.success', compact('band', 'memberCount', 'vehicleCount'));
    }
}
