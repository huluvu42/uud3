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
    public function store(Request $request, $token)
    {
        $band = Band::where('registration_token', $token)->firstOrFail();

        if (!$band->isTokenValid($token)) {
            abort(403, 'Registrierungslink ist ungültig oder abgelaufen.');
        }

        // Rate Limiting für Submissions
        $key = 'registration_submit:' . $token;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['error' => 'Zu viele Versuche. Bitte warten Sie 10 Minuten.']);
        }
        RateLimiter::hit($key, 600); // 10 Minuten

        $request->validate([
            'travel_party' => 'required|integer|min:1|max:50',
            'members' => 'required|array|min:1|max:50',
            'members.*.first_name' => 'required|string|max:255|regex:/^[a-zA-ZäöüÄÖÜß\s\-\'\.]+$/',
            'members.*.last_name' => 'required|string|max:255|regex:/^[a-zA-ZäöüÄÖÜß\s\-\'\.]+$/',
            'vehicle_plates' => 'nullable|array|max:10',
            'vehicle_plates.*' => 'nullable|string|max:20|regex:/^[A-Z0-9\-\s]+$/i',
            'emergency_contact' => 'nullable|string|max:255',
            'special_requirements' => 'nullable|string|max:1000',
        ], [
            'travel_party.required' => 'Bitte geben Sie die Anzahl der Bandmitglieder an.',
            'travel_party.min' => 'Mindestens eine Person muss angegeben werden.',
            'travel_party.max' => 'Maximal 50 Personen sind erlaubt.',
            'members.required' => 'Bitte geben Sie mindestens ein Bandmitglied an.',
            'members.*.first_name.required' => 'Vorname ist erforderlich.',
            'members.*.first_name.regex' => 'Vorname enthält ungültige Zeichen.',
            'members.*.last_name.required' => 'Nachname ist erforderlich.',
            'members.*.last_name.regex' => 'Nachname enthält ungültige Zeichen.',
            'vehicle_plates.*.regex' => 'Kennzeichen enthält ungültige Zeichen.',
            'special_requirements.max' => 'Besondere Anforderungen dürfen maximal 1000 Zeichen haben.',
        ]);

        // Zusätzliche Sicherheitsprüfungen
        if (count($request->members) != $request->travel_party) {
            return back()->withErrors(['error' => 'Anzahl der Mitglieder stimmt nicht mit Travel Party überein.']);
        }

        try {
            DB::transaction(function () use ($request, $band) {
                // Travel Party und zusätzliche Daten aktualisieren
                $band->update([
                    'travel_party' => $request->travel_party,
                    'registration_completed' => true,
                    'emergency_contact' => $request->emergency_contact,
                    'special_requirements' => $request->special_requirements,
                ]);

                // Bestehende Mitglieder und Kennzeichen löschen
                $band->persons()->delete();
                $band->vehiclePlates()->delete();

                // Neue Mitglieder hinzufügen
                foreach ($request->members as $memberData) {
                    $band->persons()->create([
                        'first_name' => trim($memberData['first_name']),
                        'last_name' => trim($memberData['last_name']),
                        'year' => $band->year,
                        'present' => false,
                        'is_duplicate' => false,
                    ]);
                }

                // Kennzeichen hinzufügen
                if ($request->vehicle_plates) {
                    foreach (array_filter($request->vehicle_plates) as $plate) {
                        $cleanPlate = strtoupper(preg_replace('/[^A-Z0-9\-]/', '', $plate));
                        if (!empty($cleanPlate)) {
                            $band->vehiclePlates()->create([
                                'license_plate' => $cleanPlate,
                            ]);
                        }
                    }
                }

                // Event auslösen für automatische Bestätigung
                event(new BandRegistrationCompleted($band));

                // Change Log erstellen
                \App\Models\ChangeLog::create([
                    'table_name' => 'bands',
                    'record_id' => $band->id,
                    'field_name' => 'registration_completed',
                    'old_value' => 'false',
                    'new_value' => 'true',
                    'action' => 'update',
                    'user_id' => 1, // System user
                    'created_at' => now(),
                ]);
            });

            // Rate Limit zurücksetzen bei erfolgreichem Submit
            RateLimiter::clear($key);

            return redirect()->route('band.register.success', ['token' => $token]);
        } catch (\Exception $e) {
            \Log::error('Band registration failed for ' . $band->band_name . ': ' . $e->getMessage());
            return back()->withErrors(['error' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.']);
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
