<?php

// app/Traits/ManagesGuests.php - VOLLSTÄNDIGE FINALE VERSION

namespace App\Traits;

use App\Models\Person;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait ManagesGuests
{
    // Guest Modal State
    public $showGuestCreateModal = false;
    public $selectedMemberForGuest = null;
    public $guest_first_name = '';
    public $guest_last_name = '';

    // Delete Modal State
    public $showGuestDeleteModal = false;
    public $guestToDelete = null;

    // Person Details Modal State
    public $showPersonDetailsModal = false;
    public $selectedPersonForDetails = null;

    /**
     * Gast für Bandmitglied hinzufügen - Modal öffnen
     */
    public function addGuestForMember($memberId)
    {
        $member = Person::with([
            'band.stage',
            'responsibleFor' // Bestehende Gäste
        ])->find($memberId);

        if (!$member) {
            session()->flash('error', 'Mitglied nicht gefunden!');
            return;
        }

        if (!$member->band) {
            session()->flash('error', 'Mitglied ist keiner Band zugeordnet!');
            return;
        }

        if (!$member->band->stage || !$member->band->stage->guest_allowed) {
            session()->flash('error', 'Diese Bühne erlaubt keine Gäste!');
            return;
        }

        // Prüfen ob bereits ein Gast existiert (max. 1 pro Bandmitglied)
        if ($member->responsibleFor->count() > 0) {
            session()->flash('error', 'Dieses Mitglied hat bereits einen Gast!');
            return;
        }

        $this->selectedMemberForGuest = $member;
        $this->guest_first_name = '';
        $this->guest_last_name = '';
        $this->showGuestCreateModal = true;
    }

    /**
     * Gast speichern
     */
    public function saveGuest()
    {
        $this->validate([
            'guest_first_name' => 'required|string|max:255',
            'guest_last_name' => 'required|string|max:255',
        ]);

        if (!$this->selectedMemberForGuest) {
            session()->flash('error', 'Kein Mitglied ausgewählt!');
            return;
        }

        // Double-check für Race Conditions
        $existingGuestCount = Person::where('responsible_person_id', $this->selectedMemberForGuest->id)->count();
        if ($existingGuestCount > 0) {
            session()->flash('error', 'Dieses Mitglied hat bereits einen Gast!');
            $this->closeGuestCreateModal();
            return;
        }

        try {
            DB::transaction(function () {
                $band = $this->selectedMemberForGuest->band;
                $stage = $band->stage;

                // Gast-Berechtigung berechnen: Nur an Spieltagen der Band
                $guestBackstageAccess = [];
                foreach ([1, 2, 3, 4] as $day) {
                    $guestBackstageAccess["backstage_day_{$day}"] = $band->{"plays_day_{$day}"};
                }

                // Gast erstellen
                Person::create([
                    'first_name' => $this->guest_first_name,
                    'last_name' => $this->guest_last_name,
                    'present' => false,
                    'can_have_guests' => false, // Gäste können keine eigenen Gäste haben
                    'backstage_day_1' => $guestBackstageAccess['backstage_day_1'],
                    'backstage_day_2' => $guestBackstageAccess['backstage_day_2'],
                    'backstage_day_3' => $guestBackstageAccess['backstage_day_3'],
                    'backstage_day_4' => $guestBackstageAccess['backstage_day_4'],
                    'voucher_day_1' => 0, // Gäste bekommen standardmäßig keine Voucher
                    'voucher_day_2' => 0,
                    'voucher_day_3' => 0,
                    'voucher_day_4' => 0,
                    'voucher_issued_day_1' => 0,
                    'voucher_issued_day_2' => 0,
                    'voucher_issued_day_3' => 0,
                    'voucher_issued_day_4' => 0,
                    'remarks' => 'Gast von ' . $this->selectedMemberForGuest->full_name,
                    'band_id' => $band->id,
                    'responsible_person_id' => $this->selectedMemberForGuest->id,
                    'year' => $band->year,
                    'is_duplicate' => false,
                ]);
            });

            $this->refreshAfterGuestChange();
            $this->closeGuestCreateModal();
            session()->flash('success', "Gast {$this->guest_first_name} {$this->guest_last_name} wurde erfolgreich hinzugefügt!");
        } catch (\Exception $e) {
            Log::error('Guest Creation Failed', [
                'member_id' => $this->selectedMemberForGuest->id,
                'error' => $e->getMessage()
            ]);
            session()->flash('error', 'Fehler beim Erstellen des Gastes: ' . $e->getMessage());
        }
    }

    /**
     * Gast löschen - Mit Confirmation Modal
     */
    public function deleteGuest($guestId)
    {
        $guest = Person::where('responsible_person_id', '!=', null)->find($guestId);

        if (!$guest) {
            session()->flash('error', 'Gast nicht gefunden!');
            return;
        }

        // Modal öffnen statt direkt löschen
        $this->guestToDelete = $guest;
        $this->showGuestDeleteModal = true;
    }

    /**
     * NEU: Guest Delete Modal öffnen (Alternative Methode für Blade-Templates)
     */
    public function deleteGuestWithConfirmation($guestId)
    {
        $guest = Person::where('responsible_person_id', '!=', null)->find($guestId);

        if (!$guest) {
            session()->flash('error', 'Gast nicht gefunden!');
            return;
        }

        $this->guestToDelete = $guest;
        $this->showGuestDeleteModal = true;
    }

    /**
     * Gast-Löschung bestätigen
     */
    public function confirmDeleteGuest()
    {
        if (!$this->guestToDelete) {
            session()->flash('error', 'Kein Gast zum Löschen ausgewählt!');
            $this->closeGuestDeleteModal();
            return;
        }

        try {
            $guestName = $this->guestToDelete->full_name;
            $this->guestToDelete->delete();

            $this->refreshAfterGuestChange();
            $this->closeGuestDeleteModal();
            session()->flash('success', "Gast {$guestName} wurde erfolgreich entfernt!");
        } catch (\Exception $e) {
            Log::error('Guest Deletion Failed', [
                'guest_id' => $this->guestToDelete->id,
                'error' => $e->getMessage()
            ]);
            session()->flash('error', 'Fehler beim Löschen des Gastes.');
            $this->closeGuestDeleteModal();
        }
    }

    /**
     * Delete Modal schließen
     */
    public function closeGuestDeleteModal()
    {
        $this->showGuestDeleteModal = false;
        $this->guestToDelete = null;
    }

    /**
     * KORRIGIERTE Gast-Anwesenheit togglen mit Modal-spezifischer Aktualisierung
     */
    public function toggleGuestPresence($guestId)
    {
        $guest = Person::with(['band', 'responsiblePerson'])->find($guestId);

        if (!$guest || !$guest->isGuest()) {
            session()->flash('error', 'Gast nicht gefunden!');
            return;
        }

        $currentDay = $this->currentDay ?? $this->getCurrentDay() ?? 1;
        $hasBackstageToday = $guest->{"backstage_day_{$currentDay}"};

        if (!$hasBackstageToday) {
            session()->flash('error', 'Gast hat heute keinen Backstage-Zugang!');
            return;
        }

        // Prüfung der verantwortlichen Person
        $responsiblePerson = $guest->responsiblePerson;

        if (!$responsiblePerson) {
            session()->flash('error', 'Keine verantwortliche Person gefunden!');
            return;
        }

        // Spezielle Prüfung nur für Gäste von Bandmitgliedern
        if ($responsiblePerson->band_id && $guest->band_id && $responsiblePerson->band_id === $guest->band_id) {
            // Für Gäste von Bandmitgliedern: Band muss heute spielen
            if (!$guest->band || !$guest->band->{"plays_day_{$currentDay}"}) {
                session()->flash('error', 'Die Band spielt heute nicht - Gast kann nicht anwesend gesetzt werden!');
                return;
            }
        }

        // Für alle anderen Gäste: Nur Backstage-Zugang prüfen (bereits oben gemacht)

        $guest->present = !$guest->present;
        $guest->save();

        // Band-Status aktualisieren falls Gast einer Band zugeordnet ist
        if ($guest->band) {
            $guest->band->updateAllPresentStatus();

            // Cache invalidieren falls verfügbar
            if (method_exists($this, 'invalidateBandCache')) {
                $this->invalidateBandCache($guest->band->id);
            }
        }

        // Person in Collections aktualisieren falls Methode verfügbar
        if (method_exists($this, 'updatePersonInCollections')) {
            $this->updatePersonInCollections($guest);
        }

        // Modal-spezifische Aktualisierung
        $this->refreshAfterGuestChange();

        session()->flash('success', $guest->full_name . ' ist jetzt ' . ($guest->present ? 'anwesend' : 'abwesend'));
    }

    /**
     * Prüfen ob Mitglied Gast hinzufügen kann
     */
    public function canMemberHaveGuest($member)
    {
        // Ist bereits ein Gast?
        if ($member->isGuest()) {
            return false;
        }

        // Hat bereits einen Gast?
        if ($member->responsibleFor && $member->responsibleFor->count() > 0) {
            return false;
        }

        // Band und Stage prüfen
        if (!$member->band || !$member->band->stage) {
            return false;
        }

        return $member->band->stage->guest_allowed;
    }

    /**
     * Prüfen ob Gast heute anwesend gesetzt werden kann
     */
    public function canToggleGuestToday($guest)
    {
        if (!$guest->isGuest()) {
            return false;
        }

        $currentDay = $this->currentDay ?? $this->getCurrentDay() ?? 1;

        // Hat Backstage-Zugang heute?
        if (!$guest->{"backstage_day_{$currentDay}"}) {
            return false;
        }

        // Prüfung der verantwortlichen Person
        $responsiblePerson = $guest->responsiblePerson;

        if (!$responsiblePerson) {
            return false;
        }

        // Spezielle Prüfung nur für Gäste von Bandmitgliedern
        if ($responsiblePerson->band_id && $guest->band_id && $responsiblePerson->band_id === $guest->band_id) {
            // Für Gäste von Bandmitgliedern: Band muss heute spielen
            return $guest->band && $guest->band->{"plays_day_{$currentDay}"};
        }

        // Für alle anderen Gäste: Nur Backstage-Zugang prüfen
        return true;
    }

    /**
     * NEU: Prüfen ob Gast von einem Bandmitglied ist (und daher löschbar)
     */
    public function isGuestOfBandMember($guest)
    {
        if (!$guest->isGuest()) {
            return false;
        }

        $responsiblePerson = $guest->responsiblePerson;

        if (!$responsiblePerson) {
            return false;
        }

        // Ist die verantwortliche Person ein Bandmitglied der gleichen Band?
        return $responsiblePerson->band_id &&
            $guest->band_id &&
            $responsiblePerson->band_id === $guest->band_id;
    }

    /**
     * Gast-Info für Bandmitglied laden
     */
    public function getMemberGuest($member)
    {
        if (!$member->responsibleFor) {
            return null;
        }

        return $member->responsibleFor->first();
    }

    /**
     * KORRIGIERTE Gast-Status für heute berechnen
     * Unterscheidet zwischen Gästen von Bandmitgliedern und anderen Personen
     */
    public function getGuestStatusForToday($guest)
    {
        if (!$guest->isGuest()) {
            return [
                'can_toggle' => false,
                'reason' => 'Kein Gast'
            ];
        }

        $currentDay = $this->currentDay ?? $this->getCurrentDay() ?? 1;

        // Basis-Prüfung: Hat der Gast heute Backstage-Zugang?
        if (!$guest->{"backstage_day_{$currentDay}"}) {
            return [
                'can_toggle' => false,
                'reason' => 'Kein Backstage-Zugang heute'
            ];
        }

        // Prüfen wer die verantwortliche Person ist
        $responsiblePerson = $guest->responsiblePerson;

        if (!$responsiblePerson) {
            return [
                'can_toggle' => false,
                'reason' => 'Keine verantwortliche Person'
            ];
        }

        // WICHTIG: Unterscheidung nach Art der verantwortlichen Person

        // Fall 1: Verantwortliche Person ist ein Bandmitglied
        if ($responsiblePerson->band_id && $guest->band_id && $responsiblePerson->band_id === $guest->band_id) {
            // Für Gäste von Bandmitgliedern: Band muss heute spielen
            if (!$guest->band || !$guest->band->{"plays_day_{$currentDay}"}) {
                return [
                    'can_toggle' => false,
                    'reason' => 'Band spielt heute nicht'
                ];
            }
        }

        // Fall 2: Verantwortliche Person ist KEIN Bandmitglied (Gruppe, VIP, etc.)
        // ODER Gast ist keiner Band zugeordnet
        // Hier gilt nur: Hat der Gast heute Backstage-Zugang? (bereits oben geprüft)

        // Alle Prüfungen bestanden
        return [
            'can_toggle' => true,
            'reason' => null
        ];
    }

    /**
     * Modal schließen
     */
    public function closeGuestCreateModal()
    {
        $this->showGuestCreateModal = false;
        $this->selectedMemberForGuest = null;
        $this->guest_first_name = '';
        $this->guest_last_name = '';
    }

    /**
     * ÜBERSCHRIEBENE refreshAfterGuestChange für Modal-spezifische Behandlung
     */
    protected function refreshAfterGuestChange()
    {
        // Wenn im Gäste-Modal, verwende spezielle Modal-Refresh
        if (isset($this->showGuestsModal) && $this->showGuestsModal && isset($this->selectedPersonForGuests) && $this->selectedPersonForGuests) {
            $this->refreshGuestsModal();
            return;
        }

        // Standard-Verhalten für andere Fälle
        if (method_exists($this, 'smartRefresh')) {
            $this->smartRefresh(true);
        } elseif (method_exists($this, 'refresh')) {
            $this->refresh();
        }
    }

    /**
     * NEU: Spezifische Gäste-Modal Aktualisierung
     */
    private function refreshGuestsModal()
    {
        if (!isset($this->showGuestsModal) || !$this->showGuestsModal || !isset($this->selectedPersonForGuests) || !$this->selectedPersonForGuests) {
            return;
        }

        // Person mit Gästen neu laden - GLEICHE Sortierung wie beim Öffnen
        $personId = $this->selectedPersonForGuests->id;

        $this->selectedPersonForGuests = Person::with([
            'responsibleFor' => function ($query) {
                $query->orderBy('first_name', 'asc')
                    ->orderBy('last_name', 'asc')
                    ->orderBy('id', 'asc');
            },
            'group',
            'band'
        ])->find($personId);

        // Falls Person gelöscht wurde
        if (!$this->selectedPersonForGuests) {
            if (method_exists($this, 'closeGuestsModal')) {
                $this->closeGuestsModal();
            }
            session()->flash('error', 'Person wurde gelöscht oder ist nicht mehr verfügbar.');
            return;
        }

        // Livewire Event dispatchen falls verfügbar
        if (method_exists($this, 'dispatch')) {
            $this->dispatch('guests-modal-refreshed', [
                'person_id' => $personId,
                'guest_count' => $this->selectedPersonForGuests->responsibleFor->count()
            ]);
        }
    }

    /**
     * Aktuellen Tag bestimmen - Fallback für verschiedene Implementierungen
     */
    protected function getCurrentDay()
    {
        if (isset($this->currentDay)) {
            return $this->currentDay;
        }

        if (method_exists($this, 'getCurrentDay')) {
            return $this->getCurrentDay();
        }

        // Settings-basierte Ermittlung als Fallback
        $settings = \App\Models\Settings::current();
        if (!$settings) return 1;

        $today = now()->format('Y-m-d');
        if ($settings->day_1_date && $today === $settings->day_1_date->format('Y-m-d')) return 1;
        if ($settings->day_2_date && $today === $settings->day_2_date->format('Y-m-d')) return 2;
        if ($settings->day_3_date && $today === $settings->day_3_date->format('Y-m-d')) return 3;
        if ($settings->day_4_date && $today === $settings->day_4_date->format('Y-m-d')) return 4;

        return 1; // Default
    }

    /**
     * Validation Rules für Gast-Formular
     */
    protected function getGuestValidationRules()
    {
        return [
            'guest_first_name' => 'required|string|max:255',
            'guest_last_name' => 'required|string|max:255',
        ];
    }

    /**
     * Validation Messages für Gast-Formular
     */
    protected function getGuestValidationMessages()
    {
        return [
            'guest_first_name.required' => 'Vorname ist erforderlich.',
            'guest_first_name.max' => 'Vorname darf maximal 255 Zeichen lang sein.',
            'guest_last_name.required' => 'Nachname ist erforderlich.',
            'guest_last_name.max' => 'Nachname darf maximal 255 Zeichen lang sein.',
        ];
    }

    // ===== PERSON DETAILS MODAL METHODEN =====

    /**
     * Person Details Modal öffnen
     */
    public function selectPersonForDetails($personId)
    {
        $this->selectedPersonForDetails = Person::with([
            'band',
            'group',
            'responsiblePerson',
            'responsibleFor',
            'vehiclePlates'
        ])->find($personId);

        if ($this->selectedPersonForDetails) {
            $this->showPersonDetailsModal = true;
        } else {
            session()->flash('error', 'Person nicht gefunden!');
        }
    }

    /**
     * Person Details Modal schließen
     */
    public function closePersonDetailsModal()
    {
        $this->showPersonDetailsModal = false;
        $this->selectedPersonForDetails = null;
    }

    /**
     * Anwesenheit aus Modal togglen
     */
    public function togglePresenceFromModal($personId)
    {
        $person = Person::find($personId);
        if (!$person) return;

        $person->present = !$person->present;
        $person->save();

        if ($person->band) {
            $person->band->updateAllPresentStatus();

            if (method_exists($this, 'invalidateBandCache')) {
                $this->invalidateBandCache($person->band->id);
            }
        }

        // Update Modal-Daten
        $this->selectedPersonForDetails = Person::with([
            'band',
            'group',
            'responsiblePerson',
            'responsibleFor',
            'vehiclePlates'
        ])->find($personId);

        $this->refreshAfterGuestChange();
        session()->flash('success', $person->full_name . ' ist jetzt ' . ($person->present ? 'anwesend' : 'abwesend'));
    }

    /**
     * KORRIGIERTE Gast-Anwesenheit aus Modal togglen
     */
    public function toggleGuestPresenceFromModal($guestId)
    {
        $guest = Person::with(['band', 'responsiblePerson'])->find($guestId);

        if (!$guest || !$guest->isGuest()) {
            session()->flash('error', 'Gast nicht gefunden!');
            return;
        }

        $currentDay = $this->currentDay ?? $this->getCurrentDay() ?? 1;
        $hasBackstageToday = $guest->{"backstage_day_{$currentDay}"};

        if (!$hasBackstageToday) {
            session()->flash('error', 'Gast hat heute keinen Backstage-Zugang!');
            return;
        }

        // Prüfung der verantwortlichen Person
        $responsiblePerson = $guest->responsiblePerson;

        if (!$responsiblePerson) {
            session()->flash('error', 'Keine verantwortliche Person gefunden!');
            return;
        }

        // Spezielle Prüfung nur für Gäste von Bandmitgliedern
        if ($responsiblePerson->band_id && $guest->band_id && $responsiblePerson->band_id === $guest->band_id) {
            // Für Gäste von Bandmitgliedern: Band muss heute spielen
            if (!$guest->band || !$guest->band->{"plays_day_{$currentDay}"}) {
                session()->flash('error', 'Die Band spielt heute nicht - Gast kann nicht anwesend gesetzt werden!');
                return;
            }
        }

        $guest->present = !$guest->present;
        $guest->save();

        if ($guest->band) {
            $guest->band->updateAllPresentStatus();

            if (method_exists($this, 'invalidateBandCache')) {
                $this->invalidateBandCache($guest->band->id);
            }
        }

        // Update Modal-Daten
        $this->selectedPersonForDetails = Person::with([
            'band',
            'group',
            'responsiblePerson',
            'responsibleFor',
            'vehiclePlates'
        ])->find($guestId);

        $this->refreshAfterGuestChange();
        session()->flash('success', $guest->full_name . ' ist jetzt ' . ($guest->present ? 'anwesend' : 'abwesend'));
    }

    /**
     * Voucher aus Modal ausgeben
     */
    public function issueVouchersFromModal($personId, $day)
    {
        // Verwende die bestehende issueVouchers Methode
        if (method_exists($this, 'issueVouchers')) {
            $this->issueVouchers($personId, $day);
        }

        // Update Modal-Daten nach Voucher-Ausgabe
        $this->selectedPersonForDetails = Person::with([
            'band',
            'group',
            'responsiblePerson',
            'responsibleFor',
            'vehiclePlates'
        ])->find($personId);
    }

    /**
     * Kennzeichen aus Modal verwalten
     */
    public function showVehiclePlatesFromModal($personId)
    {
        // Modal schließen und Kennzeichen-Modal öffnen
        $this->closePersonDetailsModal();

        if (method_exists($this, 'showVehiclePlates')) {
            $this->showVehiclePlates($personId);
        }
    }

    /**
     * Gast aus Modal hinzufügen
     */
    public function addGuestFromModal($memberId)
    {
        // Details Modal schließen und Gast-Create Modal öffnen
        $this->closePersonDetailsModal();
        $this->addGuestForMember($memberId);
    }

    /**
     * Gäste aus Modal anzeigen
     */
    public function showGuestsFromModal($personId)
    {
        // Details Modal schließen und Gäste Modal öffnen
        $this->closePersonDetailsModal();

        if (method_exists($this, 'showGuests')) {
            $this->showGuests($personId);
        }
    }
}
