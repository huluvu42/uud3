<?php
// app/Livewire/BackstageControl.php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Person;
use App\Models\Band;
use App\Models\Stage;
use App\Models\VoucherPurchase;
use App\Models\Settings;
use App\Models\ChangeLog;
use App\Traits\ManagesVehiclePlates;
use Illuminate\Support\Facades\DB;

class BackstageControl extends Component
{
    public $search = '';
    public $searchResults = [];
    public $selectedPerson = null;
    public $bandMembers = [];
    public $currentDay = 1;
    public $selectedStage = null;
    public $stages = [];
    public $settings = null;
    public $showBandList = false;
    public $selectedBand = null;
    public $sortBy = 'first_name';
    public $sortDirection = 'asc';
    public $showGuestsModal = false; // NEU für Gäste-Modal
    public $selectedPersonForGuests = null; // NEU für Gäste-Modal

    // Voucher purchase
    public $voucherAmount = 0.5;
    public $purchaseStageId = null;
    public $showStageModal = false;
    public $selectedPersonForPurchase = null; // NEU: Property für Person-basierten Kauf

    // Filter
    public $stageFilter = 'all';

    use ManagesVehiclePlates;

    public function mount()
    {
        $this->stages = Stage::where('year', now()->year)->get();
        $this->settings = Settings::current();
        $this->currentDay = $this->getCurrentDay();
        $this->showBandList = false;
        // Keine Session-Logik mehr nötig
    }

    private function performSearch()
    {
        $query = Person::with(['band', 'group', 'subgroup', 'responsiblePerson', 'responsibleFor', 'vehiclePlates']) // NEU: Gast-Beziehungen
            ->where('year', now()->year)
            ->where('is_duplicate', false);

        $query->where(function ($q) {
            $q->where('first_name', 'ILIKE', '%' . $this->search . '%')
                ->orWhere('last_name', 'ILIKE', '%' . $this->search . '%')
                ->orWhereHas('band', function ($bandQuery) {
                    $bandQuery->where('band_name', 'ILIKE', '%' . $this->search . '%');
                })
                // in Kennzeichen suchen
                ->orWhereHas('vehiclePlates', function ($plateQuery) {
                    $plateQuery->where('license_plate', 'ILIKE', '%' . $this->search . '%');
                });
        });

        return $query
            ->orderBy('first_name', 'asc')
            ->orderBy('last_name', 'asc')
            ->limit(15)
            ->get();
    }

    public function updatedSearch()
    {
        if (strlen($this->search) >= 2) {
            $this->searchPerson();
        } else {
            $this->searchResults = [];
            $this->selectedPerson = null;
            $this->bandMembers = [];
        }
    }

    public function searchPerson()
    {
        $this->searchResults = $this->performSearch();
    }

    public function selectPerson($personId)
    {
        $this->selectedPerson = Person::with(['band.members', 'group', 'subgroup', 'responsiblePerson', 'responsibleFor', 'vehiclePlates'])
            ->find($personId);

        if ($this->selectedPerson && $this->selectedPerson->band) {
            $this->loadBandMembers();
        } else {
            $this->bandMembers = [];
        }

        $this->searchResults = [];
        $this->showBandList = false;
    }

    public function loadBandMembers()
    {
        if (!$this->selectedPerson || !$this->selectedPerson->band) return;

        $this->bandMembers = $this->selectedPerson->band->members()
            ->orderBy($this->sortBy, $this->sortDirection)
            ->get();
    }

    public function togglePresence($personId)
    {
        $person = Person::find($personId);
        if (!$person) return;

        $oldValue = $person->present;
        $person->present = !$person->present;
        $person->save();

        $this->forceRefresh();

        session()->flash('success', $person->full_name . ' ist jetzt ' . ($person->present ? 'anwesend' : 'abwesend'));
    }

    // NEU: Gäste-Modal Methoden für BackstageControl
    public function showGuests($personId)
    {
        $this->selectedPersonForGuests = Person::with('responsibleFor')->findOrFail($personId);
        $this->showGuestsModal = true;
    }

    public function closeGuestsModal()
    {
        $this->showGuestsModal = false;
        $this->selectedPersonForGuests = null;
    }

    // NEU: Person-basierte Voucher-Käufe
    public function purchasePersonVoucher($personId, $amount)
    {
        // Speichere Person und Amount für späteren Kauf
        $this->selectedPersonForPurchase = Person::findOrFail($personId);
        $this->voucherAmount = $amount;

        // Verwende die gleiche Logik wie beim normalen Kauf
        $this->initiatePurchase($amount);
    }

    // NEU: Hilfsmethode für den eigentlichen Kauf
    private function executePurchase($personId = null)
    {
        if (!$this->purchaseStageId) {
            session()->flash('error', 'Keine Bühne für diese Person gefunden!');
            return;
        }

        try {
            $voucher = new VoucherPurchase();
            $voucher->amount = $this->voucherAmount;
            $voucher->day = $this->currentDay;
            $voucher->purchase_date = now()->format('Y-m-d');
            $voucher->stage_id = $this->purchaseStageId;
            $voucher->user_id = auth()->id();

            // Wenn Person-ID übergeben, diese hinzufügen
            if ($personId) {
                $voucher->person_id = $personId;
                $person = Person::find($personId);
                $successMessage = "{$this->voucherAmount} " . ($this->settings ? $this->settings->getVoucherLabel() : 'Voucher') . " für {$person->full_name} gekauft!";
            } else {
                $stageName = $this->stages->find($this->purchaseStageId)->name ?? 'Unbekannte Bühne';
                $successMessage = "{$this->voucherAmount} " . ($this->settings ? $this->settings->getVoucherLabel() : 'Voucher') . " für Bühne {$stageName} gekauft!";
            }

            $voucher->save();

            $this->showStageModal = false;
            $this->selectedPersonForPurchase = null;
            session()->flash('success', $successMessage);

            $this->forceRefresh();
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Kauf: ' . $e->getMessage());
            \Log::error('Voucher purchase error: ' . $e->getMessage());
        }
    }

    // Prüfe ob Person-Käufe erlaubt sind
    public function canShowPersonPurchase()
    {
        if (!$this->settings) return false;

        $mode = $this->settings->voucher_purchase_mode ?? 'both';
        return in_array($mode, ['person_only', 'both']);
    }

    // Prüfe ob Bühnen-Käufe erlaubt sind
    public function canShowStagePurchase()
    {
        if (!$this->settings) return true; // Default: zeigen

        $mode = $this->settings->voucher_purchase_mode ?? 'both';
        return in_array($mode, ['stage_only', 'both']);
    }

    // VOLLSTÄNDIGE VERSION
    public function issueVouchers($personId, $day)
    {
        $person = Person::find($personId);
        if (!$person) return;

        // Prüfe Voucher-Ausgabe-Regel
        if (!$this->settings || !$this->settings->canIssueVouchersForDay($day, $this->currentDay)) {
            $dayLabel = $this->settings ? $this->settings->getDayLabel($day) : "Tag $day";
            session()->flash('error', "Voucher für $dayLabel können aktuell nicht ausgegeben werden!");
            return;
        }

        $availableVouchers = $person->{"voucher_day_$day"};
        if ($availableVouchers <= 0) {
            session()->flash('error', 'Keine Voucher verfügbar!');
            return;
        }

        // Bestimme wie viele Voucher ausgegeben werden sollen
        $vouchersToIssue = 1; // Standard: einzeln
        if ($this->settings && !$this->settings->isSingleVoucherMode()) {
            $vouchersToIssue = $availableVouchers; // Alle verfügbaren
        }

        // Berechtigung reduzieren (vom ursprünglichen Tag)
        $person->{"voucher_day_$day"} -= $vouchersToIssue;

        // Ausgabe für HEUTE erhöhen (am aktuellen Tag!)
        $currentIssued = $person->{"voucher_issued_day_{$this->currentDay}"};
        $person->{"voucher_issued_day_{$this->currentDay}"} = $currentIssued + $vouchersToIssue;

        $success = $person->save();

        if ($success) {
            // AGGRESSIVE AKTUALISIERUNG - komplette Neuberechnung
            $this->forceRefresh();

            $voucherLabel = $this->settings ? $this->settings->getVoucherLabel() : 'Voucher';
            $dayLabel = $this->settings ? $this->settings->getDayLabel($day) : "Tag $day";
            $currentDayLabel = $this->settings ? $this->settings->getDayLabel($this->currentDay) : "Tag {$this->currentDay}";

            if ($day != $this->currentDay) {
                session()->flash('success', "$vouchersToIssue $voucherLabel ($dayLabel) heute ($currentDayLabel) für " . $person->full_name . " ausgegeben!");
            } else {
                session()->flash('success', "$vouchersToIssue $voucherLabel für " . $person->full_name . " ausgegeben!");
            }
        } else {
            session()->flash('error', 'Fehler beim Speichern!');
        }
    }

    // Initiate Purchase verbessern
    public function initiatePurchase($amount)
    {
        $this->voucherAmount = $amount;

        if (!$this->purchaseStageId) {
            $this->showStageModal = true;
        } else {
            // Prüfen ob die aktuell ausgewählte Bühne noch existiert
            $selectedStage = $this->stages->find($this->purchaseStageId);
            if (!$selectedStage) {
                // Ungültige Bühne - Modal anzeigen
                $this->purchaseStageId = null;
                $this->showStageModal = true;
            } else {
                $this->executePurchase();
            }
        }
    }

    // Modifizierte purchaseVouchers Methode
    public function purchaseVouchers()
    {
        if (!$this->purchaseStageId) {
            session()->flash('error', 'Bitte Bühne auswählen!');
            return;
        }

        // Prüfe ob es ein Person-basierter Kauf ist
        if ($this->selectedPersonForPurchase) {
            $this->executePurchase($this->selectedPersonForPurchase->id);
        } else {
            $this->executePurchase();
        }

        // Component neu laden für die Anzeige der Verkaufssumme
        $this->dispatch('refresh-component');
    }

    public function resetStageSelection()
    {
        $this->purchaseStageId = null;
        $this->showStageModal = false;
        $this->selectedPersonForPurchase = null; // Reset auch hier
        // Frontend über Reset informieren
        $this->dispatch('stage-selected', null);
    }

    // 3. Modal schließen ohne Bühne zu behalten
    public function cancelStageSelection()
    {
        $this->purchaseStageId = null;
        $this->showStageModal = false;
        $this->selectedPersonForPurchase = null; // Reset auch hier
        // Session-Wert NICHT löschen beim Abbrechen
    }

    public function getSoldVouchersForStage($stageId, $day = null)
    {
        if (!$day) {
            $day = $this->currentDay;
        }

        return VoucherPurchase::where('stage_id', $stageId)
            ->where('day', $day)
            ->sum('amount');
    }

    // 5. Alle verkauften Bons heute abrufen (für die Anzeige)
    public function getTodaysSoldVouchers()
    {
        $soldVouchers = [];
        foreach ($this->stages as $stage) {
            $soldVouchers[$stage->id] = $this->getSoldVouchersForStage($stage->id, $this->currentDay);
        }
        return $soldVouchers;
    }

    public function updatedPurchaseStageId($value)
    {
        // Event an Frontend senden
        $this->dispatch('stage-selected', $value);
    }

    // Debug-Methode hinzufügen (temporär zum Testen)
    public function debugSession()
    {
        $sessionValue = session('backstage_purchase_stage_id');
        $currentValue = $this->purchaseStageId;

        session()->flash('success', "Session: $sessionValue, Current: $currentValue, Stages count: " . $this->stages->count());
    }

    /**
     * Determine the wristband color for a person based on their backstage access
     */
    public function getWristbandColorForPerson($person)
    {
        if (!$this->settings) return null;

        // First check if person has backstage access for the current day
        if (!$person->{"backstage_day_{$this->currentDay}"}) {
            return null; // No wristband if no access for current day
        }

        // Check if person has backstage access for all remaining days (from current day to day 4)
        $hasAllRemainingDays = true;
        for ($day = $this->currentDay; $day <= 4; $day++) {
            if (!$person->{"backstage_day_$day"}) {
                $hasAllRemainingDays = false;
                break;
            }
        }

        // If they have all remaining days, return day 4 color
        if ($hasAllRemainingDays) {
            return $this->settings->getColorForDay(4);
        }

        // Otherwise, return the color for the current day
        return $this->settings->getColorForDay($this->currentDay);
    }

    /**
     * Check if person has any backstage access
     */
    public function hasAnyBackstageAccess($person)
    {
        for ($day = 1; $day <= 4; $day++) {
            if ($person->{"backstage_day_$day"}) {
                return true;
            }
        }
        return false;
    }

    public function showTodaysBands()
    {
        $this->showBandList = true;
        $this->selectedBand = null;
        $this->selectedPerson = null;
        $this->bandMembers = [];
        $this->searchResults = [];
        $this->search = '';

        $this->dispatch('search-cleared');
    }

    public function selectBand($bandId)
    {
        $this->selectedBand = Band::with(['members', 'stage'])
            ->find($bandId);
        $this->selectedPerson = null;
        $this->bandMembers = [];
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }

        $this->loadBandMembers();
    }

    // Aggressive Refresh
    private function forceRefresh()
    {
        $this->searchResults = [];
        $this->selectedPerson = null;
        $this->bandMembers = [];
        $this->selectedBand = null;

        if ($this->search && strlen($this->search) >= 2) {
            usleep(10000); // 10ms
            $this->searchResults = $this->performSearch();
        }

        $this->dispatch('refresh-component');
    }

    // Nächsten verfügbaren Tag finden
    public function getNextAvailableVoucherDay($person)
    {
        $freshPerson = Person::find($person->id);
        if (!$freshPerson) return null;

        $allowedDays = $this->getAllowedVoucherDays();

        foreach ($allowedDays as $day) {
            $available = $freshPerson->{"voucher_day_$day"};
            if ($available > 0) {
                return $day;
            }
        }

        return null;
    }

    // Erlaubte Tage für Voucher-Ausgabe
    public function getAllowedVoucherDays()
    {
        if (!$this->settings) return [$this->currentDay];

        switch ($this->settings->voucher_issuance_rule) {
            case 'current_day_only':
                return [$this->currentDay];
            case 'current_and_past':
                return range(1, $this->currentDay);
            case 'all_days':
                return [1, 2, 3, 4];
            default:
                return [$this->currentDay];
        }
    }

    public function getCurrentDay()
    {
        if (!$this->settings) return 1;

        $today = now()->format('Y-m-d');

        if ($this->settings->day_1_date && $today === $this->settings->day_1_date->format('Y-m-d')) return 1;
        if ($this->settings->day_2_date && $today === $this->settings->day_2_date->format('Y-m-d')) return 2;
        if ($this->settings->day_3_date && $today === $this->settings->day_3_date->format('Y-m-d')) return 3;
        if ($this->settings->day_4_date && $today === $this->settings->day_4_date->format('Y-m-d')) return 4;

        return 1; // Default
    }

    public function render()
    {
        $todaysBands = collect();
        if ($this->showBandList) {
            $query = Band::with('stage')
                ->where('year', now()->year)
                ->where("plays_day_{$this->currentDay}", true);

            if ($this->stageFilter !== 'all') {
                $query->where('stage_id', $this->stageFilter);
            }

            $todaysBands = $query->get();
        }

        return view('livewire.backstage-control', [
            'todaysBands' => $todaysBands,
            'currentDayDate' => $this->settings ? $this->settings->{"day_{$this->currentDay}_date"} : null,
        ]);
    }
}
