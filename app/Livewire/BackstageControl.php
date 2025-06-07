<?php
// app/Livewire/BackstageControl.php - VOLLSTÄNDIGE VERSION

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
    public $bandSearch = ''; // NEU: Separate Band-Suche
    public $searchResults = [];
    public $bandSearchResults = []; // NEU: Band-Suchergebnisse
    public $selectedPerson = null;
    public $selectedBandFromSearch = null; // NEU: Ausgewählte Band aus Suche
    public $bandMembers = [];
    public $currentDay = 1;
    public $selectedStage = null;
    public $stages = [];
    public $settings = null;
    public $showBandList = false;
    public $selectedBand = null;
    public $sortBy = 'first_name';
    public $sortDirection = 'asc';
    public $showGuestsModal = false;
    public $selectedPersonForGuests = null;

    // Voucher purchase
    public $voucherAmount = 0.5;
    public $purchaseStageId = null;
    public $showStageModal = false;
    public $selectedPersonForPurchase = null;

    // Filter
    public $stageFilter = 'all';

    use ManagesVehiclePlates;

    public function mount()
    {
        $this->stages = Stage::where('year', now()->year)->get();
        $this->settings = Settings::current();
        $this->currentDay = $this->getCurrentDay();
        $this->showBandList = false;
    }

    // NEU: Band-Status für aktuellen Tag berechnen
    public function getBandStatusForToday($band)
    {
        if (!$this->settings) {
            return ['status' => 'unknown', 'class' => 'bg-gray-100 text-gray-700', 'text' => 'Unbekannt'];
        }

        $currentDay = $this->currentDay;

        // Spielt die Band heute überhaupt?
        if (!$band->{"plays_day_$currentDay"}) {
            return ['status' => 'not_today', 'class' => 'bg-gray-100 text-gray-700', 'text' => 'Spielt nicht heute'];
        }

        // Auftrittszeit für heute holen
        $performanceTime = $band->getFormattedPerformanceTimeForDay($currentDay);
        if (!$performanceTime) {
            return ['status' => 'no_time', 'class' => 'bg-yellow-100 text-yellow-700', 'text' => 'Keine Auftrittszeit'];
        }

        // Späteste Ankunftszeit berechnen
        $arrivalMinutes = $this->settings->getLatestArrivalTimeMinutes();
        try {
            $performanceDateTime = \Carbon\Carbon::createFromFormat('H:i', $performanceTime);
            $latestArrivalTime = $performanceDateTime->subMinutes($arrivalMinutes);
            $now = \Carbon\Carbon::now();

            // Sind alle Mitglieder anwesend?
            $allPresent = $band->all_present;

            if ($allPresent) {
                return ['status' => 'ready', 'class' => 'bg-green-100 text-green-700 border-green-300', 'text' => '✓ Alle da'];
            }

            // Ankunftszeit schon überschritten?
            if ($now->gt($latestArrivalTime)) {
                return ['status' => 'late', 'class' => 'bg-red-100 text-red-700 border-red-300', 'text' => '⚠ Zu spät!'];
            }

            // Noch Zeit, aber nicht alle da
            $timeLeft = $now->diffInMinutes($latestArrivalTime);
            if ($timeLeft <= 15) {
                return ['status' => 'warning', 'class' => 'bg-orange-100 text-orange-700 border-orange-300', 'text' => "⏰ {$timeLeft}min"];
            }

            return ['status' => 'waiting', 'class' => 'bg-blue-100 text-blue-700', 'text' => 'Warten...'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'class' => 'bg-yellow-100 text-yellow-700', 'text' => 'Zeitfehler'];
        }
    }

    // PERSONEN-SUCHE (wie bisher)
    private function performPersonSearch()
    {
        $searchTerm = $this->search;

        $query = Person::with(['band', 'group', 'subgroup', 'responsiblePerson', 'responsibleFor', 'vehiclePlates'])
            ->where('year', now()->year)
            ->where('is_duplicate', false);

        $query->where(function ($q) use ($searchTerm) {
            $q->where('first_name', 'ILIKE', '%' . $searchTerm . '%')
                ->orWhere('last_name', 'ILIKE', '%' . $searchTerm . '%')
                ->orWhereHas('band', function ($bandQuery) use ($searchTerm) {
                    $bandQuery->where('band_name', 'ILIKE', '%' . $searchTerm . '%');
                })
                ->orWhereHas('vehiclePlates', function ($plateQuery) use ($searchTerm) {
                    $plateQuery->where('license_plate', 'ILIKE', '%' . $searchTerm . '%');
                });
        });

        return $query
            ->orderBy('first_name', 'asc')
            ->orderBy('last_name', 'asc')
            ->limit(15)
            ->get();
    }

    // NEU: BAND-SUCHE
    private function performBandSearch()
    {
        $searchTerm = $this->bandSearch;

        return Band::with(['members', 'stage'])
            ->where('year', now()->year)
            ->where('band_name', 'ILIKE', '%' . $searchTerm . '%')
            ->orderBy('band_name', 'asc')
            ->limit(10)
            ->get();
    }

    // NEU: Band-Suche Update Handler
    public function updatedBandSearch()
    {
        if (strlen($this->bandSearch) >= 2) {
            $this->bandSearchResults = $this->performBandSearch();

            // Person-bezogene Daten zurücksetzen aber Band-Auswahl behalten
            $this->selectedPerson = null;
            $this->searchResults = [];
            // $this->selectedBandFromSearch = null; // ← Nur beim ersten Suchen
        } else {
            $this->bandSearchResults = [];
            // Nur zurücksetzen wenn keine Band ausgewählt ist
            if (!$this->selectedBandFromSearch) {
                $this->bandMembers = [];
            }
        }
    }

    // Personen-Suche Update Handler (angepasst)
    public function updatedSearch()
    {
        if (strlen($this->search) >= 2) {
            $this->searchResults = $this->performPersonSearch();

            // Band-bezogene Suchdaten zurücksetzen, aber NICHT die ausgewählte Band
            $this->bandSearchResults = [];
            // $this->selectedBandFromSearch = null; // ← NICHT zurücksetzen!
        } else {
            $this->searchResults = [];
            $this->selectedPerson = null;
            // Band-Mitglieder nur zurücksetzen wenn keine Band aus Suche ausgewählt ist
            if (!$this->selectedBandFromSearch) {
                $this->bandMembers = [];
            }
        }
    }

    // Legacy: Für Kompatibilität
    public function searchPerson()
    {
        $this->searchResults = $this->performPersonSearch();
    }

    // NEU: Band aus Suche auswählen
    public function selectBandFromSearch($bandId)
    {
        $this->selectedBandFromSearch = Band::with(['members', 'stage'])->find($bandId);

        if ($this->selectedBandFromSearch) {
            $this->loadBandMembersFromSearch();
        }

        // Andere Suchergebnisse zurücksetzen
        $this->bandSearchResults = [];
        $this->searchResults = [];
        $this->selectedPerson = null;
        $this->showBandList = false;
    }

    // NEU: Band-Mitglieder laden für ausgewählte Band aus Suche
    public function loadBandMembersFromSearch()
    {
        if (!$this->selectedBandFromSearch) return;

        // Frische Daten der Band laden (für aktualisierte all_present Status)
        $this->selectedBandFromSearch = Band::with(['members', 'stage'])->find($this->selectedBandFromSearch->id);

        if (!$this->selectedBandFromSearch) {
            $this->bandMembers = [];
            return;
        }

        $this->bandMembers = $this->selectedBandFromSearch->members()
            ->with('vehiclePlates') // NEU: Kennzeichen laden
            ->where('year', now()->year)
            ->where('is_duplicate', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->get();
    }

    // NEU: Zur Band der Person wechseln
    public function goToBand($personId)
    {
        $person = Person::with('band')->find($personId);

        if (!$person || !$person->band) {
            session()->flash('error', 'Diese Person ist keiner Band zugeordnet.');
            return;
        }

        // Band aus Suche auswählen
        $this->selectedBandFromSearch = Band::with(['members', 'stage'])->find($person->band->id);

        if ($this->selectedBandFromSearch) {
            $this->loadBandMembersFromSearch();

            // Suchfelder und andere Auswahlen zurücksetzen
            $this->searchResults = [];
            $this->bandSearchResults = [];
            $this->selectedPerson = null;
            $this->showBandList = false;

            session()->flash('success', 'Zur Band "' . $this->selectedBandFromSearch->band_name . '" gewechselt.');
        }
    }

    // NEU: Alle Suchfelder zurücksetzen
    public function clearAllSearches()
    {
        $this->search = '';
        $this->bandSearch = '';
        $this->searchResults = [];
        $this->bandSearchResults = [];
        $this->selectedPerson = null;
        $this->selectedBandFromSearch = null;
        $this->bandMembers = [];
        $this->showBandList = false;

        $this->dispatch('search-cleared');
    }

    // NEU: Band-Auswahl explizit zurücksetzen
    public function clearBandSelection()
    {
        $this->selectedBandFromSearch = null;
        $this->bandMembers = [];
        $this->selectedPerson = null;
    }

    // Bestehende selectPerson Methode (angepasst)
    public function selectPerson($personId)
    {
        $this->selectedPerson = Person::with(['band.members', 'group', 'subgroup', 'responsiblePerson', 'responsibleFor', 'vehiclePlates'])
            ->find($personId);

        if ($this->selectedPerson && $this->selectedPerson->band) {
            $this->loadBandMembers();
        } else {
            $this->bandMembers = [];
        }

        // Suchfelder zurücksetzen
        $this->searchResults = [];
        $this->bandSearchResults = [];
        $this->selectedBandFromSearch = null;
        $this->showBandList = false;
    }

    // Bestehende loadBandMembers Methode
    public function loadBandMembers()
    {
        if (!$this->selectedPerson || !$this->selectedPerson->band) return;

        $this->bandMembers = $this->selectedPerson->band->members()
            ->with('vehiclePlates') // NEU: Kennzeichen laden
            ->where('year', now()->year)
            ->where('is_duplicate', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->get();
    }

    // Angepasste togglePresence Methode
    public function togglePresence($personId)
    {
        $person = Person::find($personId);
        if (!$person) return;

        $oldValue = $person->present;
        $person->present = !$person->present;
        $person->save();

        // Band's all_present Status aktualisieren falls Person in einer Band ist
        if ($person->band) {
            $person->band->updateAllPresentStatus();
        }

        $this->forceRefresh();

        session()->flash('success', $person->full_name . ' ist jetzt ' . ($person->present ? 'anwesend' : 'abwesend'));
    }

    // Angepasste forceRefresh Methode
    private function forceRefresh()
    {
        // Merke dir die aktuell ausgewählte Band aus der Suche
        $keepSelectedBandFromSearch = $this->selectedBandFromSearch;

        $this->searchResults = [];
        $this->bandSearchResults = [];
        $this->selectedPerson = null;
        // $this->selectedBandFromSearch = null; // ← NICHT zurücksetzen!
        $this->selectedBand = null;

        // Erneut suchen falls Suchbegriffe vorhanden
        if ($this->search && strlen($this->search) >= 2) {
            usleep(10000);
            $this->searchResults = $this->performPersonSearch();
        }

        if ($this->bandSearch && strlen($this->bandSearch) >= 2) {
            usleep(10000);
            $this->bandSearchResults = $this->performBandSearch();
        }

        // Wenn eine Band aus der Suche ausgewählt war, Mitglieder neu laden
        if ($keepSelectedBandFromSearch) {
            $this->selectedBandFromSearch = $keepSelectedBandFromSearch;
            $this->loadBandMembersFromSearch();
        } else {
            $this->bandMembers = [];
        }

        $this->dispatch('refresh-component');
    }

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

    public function purchasePersonVoucher($personId, $amount)
    {
        $this->selectedPersonForPurchase = Person::findOrFail($personId);
        $this->voucherAmount = $amount;
        $this->initiatePurchase($amount);
    }

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

    public function canShowPersonPurchase()
    {
        if (!$this->settings) return false;
        $mode = $this->settings->voucher_purchase_mode ?? 'both';
        return in_array($mode, ['person_only', 'both']);
    }

    public function canShowStagePurchase()
    {
        if (!$this->settings) return true;
        $mode = $this->settings->voucher_purchase_mode ?? 'both';
        return in_array($mode, ['stage_only', 'both']);
    }

    public function issueVouchers($personId, $day)
    {
        $person = Person::find($personId);
        if (!$person) return;

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

        $vouchersToIssue = 1;
        if ($this->settings && !$this->settings->isSingleVoucherMode()) {
            $vouchersToIssue = $availableVouchers;
        }

        $person->{"voucher_day_$day"} -= $vouchersToIssue;

        $currentIssued = $person->{"voucher_issued_day_{$this->currentDay}"};
        $person->{"voucher_issued_day_{$this->currentDay}"} = $currentIssued + $vouchersToIssue;

        $success = $person->save();

        if ($success) {
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

    public function initiatePurchase($amount)
    {
        $this->voucherAmount = $amount;

        if (!$this->purchaseStageId) {
            $this->showStageModal = true;
        } else {
            $selectedStage = $this->stages->find($this->purchaseStageId);
            if (!$selectedStage) {
                $this->purchaseStageId = null;
                $this->showStageModal = true;
            } else {
                $this->executePurchase();
            }
        }
    }

    public function purchaseVouchers()
    {
        if (!$this->purchaseStageId) {
            session()->flash('error', 'Bitte Bühne auswählen!');
            return;
        }

        if ($this->selectedPersonForPurchase) {
            $this->executePurchase($this->selectedPersonForPurchase->id);
        } else {
            $this->executePurchase();
        }

        $this->dispatch('refresh-component');
    }

    public function resetStageSelection()
    {
        $this->purchaseStageId = null;
        $this->showStageModal = false;
        $this->selectedPersonForPurchase = null;
        $this->dispatch('stage-selected', null);
    }

    public function cancelStageSelection()
    {
        $this->purchaseStageId = null;
        $this->showStageModal = false;
        $this->selectedPersonForPurchase = null;
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
        $this->dispatch('stage-selected', $value);
    }

    public function getWristbandColorForPerson($person)
    {
        if (!$this->settings) return null;

        if (!$person->{"backstage_day_{$this->currentDay}"}) {
            return null;
        }

        $hasAllRemainingDays = true;
        for ($day = $this->currentDay; $day <= 4; $day++) {
            if (!$person->{"backstage_day_$day"}) {
                $hasAllRemainingDays = false;
                break;
            }
        }

        if ($hasAllRemainingDays) {
            return $this->settings->getColorForDay(4);
        }

        return $this->settings->getColorForDay($this->currentDay);
    }

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
        $this->selectedBandFromSearch = null;
        $this->bandMembers = [];
        $this->searchResults = [];
        $this->bandSearchResults = [];
        $this->search = '';
        $this->bandSearch = '';

        $this->dispatch('search-cleared');
    }

    public function selectBand($bandId)
    {
        $this->selectedBand = Band::with(['members', 'stage'])->find($bandId);
        $this->selectedPerson = null;
        $this->selectedBandFromSearch = null;
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
        $this->loadBandMembersFromSearch();
    }

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
