<?php
// app/Livewire/BackstageControl.php - VOLLSTÄNDIGE VERSION MIT FIXES

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use App\Models\Person;
use App\Models\Band;
use App\Models\Stage;
use App\Models\VoucherPurchase;
use App\Models\Settings;
use App\Models\ChangeLog;

use Illuminate\Support\Facades\DB;

// Service Imports
use App\Services\BackstageSearchService;
use App\Services\VoucherService;
use App\Services\BandStatusService;

// Trait Imports
use App\Traits\ManagesGuests;
use App\Traits\ManagesVehiclePlates;

class BackstageControl extends Component
{

    use ManagesVehiclePlates, ManagesGuests;

    public $search = '';
    public $bandSearch = '';
    public $searchResults = [];
    public $bandSearchResults = [];
    public $selectedPerson = null;
    public $selectedBandFromSearch = null;
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

    // NEU: Processing Flag für Voucher
    public $isProcessingVoucher = false;

    // Caching für bessere Performance
    private $cachedSettings = null;
    private $bandStatusCache = [];

    public $cachedBandStatuses = [];
    public $lastCacheUpdate = null;
    public $cacheTimeout = 30; // Sekunden

    // Such-Cache für stabile Reihenfolge
    public $searchResultsCache = [];
    public $bandSearchResultsCache = [];
    public $lastSearchTerm = '';
    public $lastBandSearchTerm = '';

    // Service Properties
    protected BackstageSearchService $searchService;
    protected VoucherService $voucherService;
    protected BandStatusService $bandStatusService;

    // Dependency Injection für Services
    public function boot(
        BackstageSearchService $searchService,
        VoucherService $voucherService,
        BandStatusService $bandStatusService
    ) {
        $this->searchService = $searchService;
        $this->voucherService = $voucherService;
        $this->bandStatusService = $bandStatusService;
    }

    public function mount()
    {
        $this->stages = Stage::where('year', now()->year)->get();
        $this->settings = $this->getSettings();
        $this->currentDay = $this->getCurrentDay();
        $this->showBandList = false;
        $this->cachedBandStatuses = [];
        $this->lastCacheUpdate = now();
        $this->isProcessingVoucher = false;
    }

    // Hydrate/Dehydrate für bessere Daten-Konsistenz
    public function hydrate()
    {
        // Nach jedem Request fresh Settings laden
        $this->settings = Settings::current();

        // Veraltete Caches leeren
        if ($this->lastCacheUpdate && now()->diffInSeconds($this->lastCacheUpdate) > 60) {
            $this->cachedBandStatuses = [];
            $this->lastCacheUpdate = now();
        }
    }

    public function dehydrate()
    {
        // Processing-Flag zurücksetzen
        $this->isProcessingVoucher = false;
    }

    // Settings mit Caching
    private function getSettings()
    {
        if ($this->cachedSettings === null) {
            $this->cachedSettings = Settings::current();
        }
        return $this->cachedSettings;
    }

    // Band-Status mit Service und Caching
    public function getBandStatusForToday($band)
    {
        $cacheKey = "band_status_{$band->id}_{$this->currentDay}";
        $now = now();

        // Cache prüfen: Ist noch gültig?
        if (
            isset($this->cachedBandStatuses[$cacheKey]) &&
            $this->lastCacheUpdate &&
            $now->diffInSeconds($this->lastCacheUpdate) < $this->cacheTimeout
        ) {
            return $this->cachedBandStatuses[$cacheKey];
        }

        // Service verwenden für Status-Berechnung
        $status = $this->bandStatusService->calculateBandStatus($band, $this->currentDay, $this->settings);

        // In Cache speichern
        $this->cachedBandStatuses[$cacheKey] = $status;
        $this->lastCacheUpdate = $now;

        return $status;
    }

    // Personen-Suche mit Service
    private function performPersonSearch()
    {
        $searchTerm = $this->search;

        // Verwende Cache wenn gleicher Suchbegriff
        if ($searchTerm === $this->lastSearchTerm && !empty($this->searchResultsCache)) {
            $cachedIds = collect($this->searchResultsCache);
            return $this->searchService->getPersonsByIds($cachedIds->toArray(), now()->year);
        }

        // Service für neue Suche verwenden
        $results = $this->searchService->searchPersons($searchTerm, now()->year, 15);

        // Cache aktualisieren
        $this->searchResultsCache = $results->pluck('id')->toArray();
        $this->lastSearchTerm = $searchTerm;

        return $results;
    }

    // Band-Suche mit Service
    private function performBandSearch()
    {
        $searchTerm = $this->bandSearch;

        // Verwende Cache wenn gleicher Suchbegriff
        if ($searchTerm === $this->lastBandSearchTerm && !empty($this->bandSearchResultsCache)) {
            $cachedIds = collect($this->bandSearchResultsCache);
            return $this->searchService->getBandsByIds($cachedIds->toArray(), now()->year);
        }

        // Service für neue Suche verwenden
        $results = $this->searchService->searchBands($searchTerm, now()->year, 10);

        // Cache aktualisieren
        $this->bandSearchResultsCache = $results->pluck('id')->toArray();
        $this->lastBandSearchTerm = $searchTerm;

        return $results;
    }

    // Cache-Invalidierung für bestimmte Band
    private function invalidateBandCache($bandId)
    {
        $cacheKey = "band_status_{$bandId}_{$this->currentDay}";
        unset($this->cachedBandStatuses[$cacheKey]);
    }

    // Cache für Person komplett invalidieren
    private function clearAllPersonCaches($personId)
    {
        // Such-Cache invalidieren
        if (in_array($personId, $this->searchResultsCache)) {
            $this->searchResultsCache = [];
            $this->lastSearchTerm = '';
        }

        // Band-Cache invalidieren
        if (in_array($personId, $this->bandSearchResultsCache)) {
            $this->bandSearchResultsCache = [];
            $this->lastBandSearchTerm = '';
        }

        // Model-Cache invalidieren (falls verwendet)
        if (method_exists($this, 'forgetComputed')) {
            $this->forgetComputed('searchResults');
            $this->forgetComputed('bandSearchResults');
        }
    }

    // Person in Collections aktualisieren
    private function updatePersonInCollections($updatedPerson)
    {
        // In Suchergebnissen aktualisieren
        foreach ($this->searchResults as $key => $person) {
            if ($person->id === $updatedPerson->id) {
                $this->searchResults[$key] = $updatedPerson;
                break;
            }
        }

        // In Band-Mitgliedern aktualisieren
        foreach ($this->bandMembers as $key => $member) {
            if ($member->id === $updatedPerson->id) {
                $this->bandMembers[$key] = $updatedPerson;
                break;
            }
        }

        // Ausgewählte Person aktualisieren
        if ($this->selectedPerson && $this->selectedPerson->id === $updatedPerson->id) {
            $this->selectedPerson = Person::with([
                'band.members',
                'group',
                'subgroup',
                'responsiblePerson',
                'responsibleFor',
                'vehiclePlates'
            ])->find($updatedPerson->id);
        }
    }

    // Update-Handler
    public function updatedBandSearch()
    {
        // Cache zurücksetzen bei neuer Suche
        if ($this->bandSearch !== $this->lastBandSearchTerm) {
            $this->bandSearchResultsCache = [];
        }

        if (strlen($this->bandSearch) >= 2) {
            if (!$this->selectedBandFromSearch) {
                $this->bandSearchResults = $this->performBandSearch();
            }
            $this->selectedPerson = null;
            $this->searchResults = [];
        } else {
            $this->bandSearchResults = [];
            $this->bandSearchResultsCache = [];
            $this->lastBandSearchTerm = '';
            if (!$this->selectedBandFromSearch) {
                $this->bandMembers = [];
            }
        }
    }

    public function updatedSearch()
    {
        // Cache zurücksetzen bei neuer Suche
        if ($this->search !== $this->lastSearchTerm) {
            $this->searchResultsCache = [];
        }

        if (strlen($this->search) >= 2) {
            if (!$this->selectedBandFromSearch) {
                $this->searchResults = $this->performPersonSearch();
            }
            $this->bandSearchResults = [];
        } else {
            $this->searchResults = [];
            $this->searchResultsCache = [];
            $this->lastSearchTerm = '';
            $this->selectedPerson = null;
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

    // Band aus Suche auswählen
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

    // Band-Mitglieder laden für ausgewählte Band aus Suche
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
            ->with('vehiclePlates')
            ->where('year', now()->year)
            ->where('is_duplicate', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->get();
    }

    // Zur Band der Person wechseln
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

    // Alle Suchfelder zurücksetzen
    public function clearAllSearches()
    {
        $this->search = '';
        $this->bandSearch = '';
        $this->searchResults = [];
        $this->bandSearchResults = [];
        $this->searchResultsCache = [];
        $this->bandSearchResultsCache = [];
        $this->lastSearchTerm = '';
        $this->lastBandSearchTerm = '';
        $this->selectedPerson = null;
        $this->selectedBandFromSearch = null;
        $this->bandMembers = [];
        $this->showBandList = false;

        $this->js('
        const searchInput = document.getElementById("search-input");
        const bandSearchInput = document.getElementById("band-search-input");
        
        if (searchInput) {
            searchInput.value = "";
        }
        if (bandSearchInput) {
            bandSearchInput.value = "";
        }
    ');

        $this->dispatch('search-cleared');
    }

    // Band-Auswahl explizit zurücksetzen
    public function clearBandSelection()
    {
        $this->selectedBandFromSearch = null;
        $this->bandMembers = [];
        $this->selectedPerson = null;
    }

    // Person auswählen
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

    // Band-Mitglieder laden
    public function loadBandMembers()
    {
        if (!$this->selectedPerson || !$this->selectedPerson->band) return;

        $this->bandMembers = $this->selectedPerson->band->members()
            ->with('vehiclePlates')
            ->where('year', now()->year)
            ->where('is_duplicate', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->get();
    }

    // togglePresence mit Cache-Invalidierung
    public function togglePresence($personId)
    {
        $person = Person::find($personId);
        if (!$person) return;

        $person->present = !$person->present;
        $person->save();

        if ($person->band) {
            $person->band->updateAllPresentStatus();

            // Cache für diese Band invalidieren
            $this->invalidateBandCache($person->band->id);
        }

        $this->smartRefresh();

        session()->flash('success', $person->full_name . ' ist jetzt ' . ($person->present ? 'anwesend' : 'abwesend'));
    }

    // KOMPLETT ÜBERARBEITETE issueVouchers Methode
    public function issueVouchers($personId, $day)
    {
        // Doppelte Ausführung verhindern
        if ($this->isProcessingVoucher) {
            return;
        }
        $this->isProcessingVoucher = true;

        try {
            // Aktuelle Daten aus DB laden mit Lock
            $person = Person::lockForUpdate()->find($personId);
            if (!$person) {
                session()->flash('error', 'Person nicht gefunden!');
                return;
            }

            $settings = Settings::current();
            if (!$settings) {
                session()->flash('error', 'Einstellungen nicht verfügbar!');
                return;
            }

            // Validierung mit frischen Daten
            if (!$settings->canIssueVouchersForDay($day, $this->currentDay)) {
                $dayLabel = $settings->getDayLabel($day);
                session()->flash('error', "Voucher für $dayLabel können aktuell nicht ausgegeben werden!");
                return;
            }

            $availableVouchers = $person->{"voucher_day_$day"};
            if ($availableVouchers <= 0) {
                session()->flash('error', 'Keine Voucher verfügbar!');
                return;
            }

            // Transaction mit explizitem Lock
            DB::beginTransaction();

            try {
                // Anzahl der auszugebenden Voucher bestimmen
                $vouchersToIssue = $settings->isSingleVoucherMode() ? 1 : $availableVouchers;
                $vouchersToIssue = min($vouchersToIssue, $availableVouchers);

                // Raw SQL Update für 100% Konsistenz
                $newVoucherDayValue = $person->{"voucher_day_$day"} - $vouchersToIssue;
                $newIssuedValue = $person->{"voucher_issued_day_{$this->currentDay}"} + $vouchersToIssue;

                $updateResult = DB::table('persons')
                    ->where('id', $personId)
                    ->where("voucher_day_$day", '>=', $vouchersToIssue) // Optimistic Lock
                    ->update([
                        "voucher_day_$day" => $newVoucherDayValue,
                        "voucher_issued_day_{$this->currentDay}" => $newIssuedValue,
                        'updated_at' => now()
                    ]);

                if ($updateResult === 0) {
                    throw new \Exception('Voucher wurden bereits ausgegeben oder sind nicht mehr verfügbar.');
                }

                DB::commit();

                // Lokale Caches komplett invalidieren
                $this->clearAllPersonCaches($personId);

                // Person neu laden für UI-Update
                $updatedPerson = Person::find($personId);
                $this->updatePersonInCollections($updatedPerson);

                // Success Message
                $voucherLabel = $settings->getVoucherLabel();
                $dayLabel = $settings->getDayLabel($day);

                if ($day != $this->currentDay) {
                    $message = "$vouchersToIssue $voucherLabel ($dayLabel) heute für {$updatedPerson->full_name} ausgegeben!";
                } else {
                    $message = "$vouchersToIssue $voucherLabel für {$updatedPerson->full_name} ausgegeben!";
                }

                session()->flash('success', $message);

                // UI-Force-Refresh
                $this->dispatch('voucher-issued', [
                    'personId' => $personId,
                    'vouchersIssued' => $vouchersToIssue,
                    'remainingVouchers' => $newVoucherDayValue
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Voucher DB Update Failed', [
                    'person_id' => $personId,
                    'day' => $day,
                    'error' => $e->getMessage()
                ]);
                session()->flash('error', 'Fehler beim Ausgeben: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('Voucher Issue Critical Error', [
                'person_id' => $personId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Kritischer Fehler beim Voucher-Ausgeben');
        } finally {
            $this->isProcessingVoucher = false;
        }
    }

    // Smart-Refresh-Methode
    private function smartRefresh($preserveSearch = true)
    {
        if ($this->selectedBandFromSearch) {
            $this->bandSearchResults = [];
            $this->searchResults = [];
            $this->loadBandMembersFromSearch();
            return;
        }

        if ($preserveSearch) {
            // Verwende Cache für stabile Reihenfolge
            if ($this->search && strlen($this->search) >= 2) {
                if (!empty($this->searchResultsCache)) {
                    // Service verwenden für ID-basierte Suche
                    $this->searchResults = $this->searchService->getPersonsByIds($this->searchResultsCache, now()->year);
                } else {
                    $this->searchResults = $this->performPersonSearch();
                }
            }

            if ($this->bandSearch && strlen($this->bandSearch) >= 2) {
                if (!empty($this->bandSearchResultsCache)) {
                    // Service verwenden für ID-basierte Suche
                    $this->bandSearchResults = $this->searchService->getBandsByIds($this->bandSearchResultsCache, now()->year);
                } else {
                    $this->bandSearchResults = $this->performBandSearch();
                }
            }
        }
    }

    // Legacy forceRefresh für Kompatibilität
    private function forceRefresh()
    {
        $this->smartRefresh();
    }

    // Gäste-Modal
    public function showGuests($personId)
    {
        $this->selectedPersonForGuests = Person::with([
            'responsibleFor' => function ($query) {
                // Stabile, konsistente Sortierung
                $query->orderBy('first_name', 'asc')
                    ->orderBy('last_name', 'asc')
                    ->orderBy('id', 'asc'); // Fallback für identische Namen
            },
            'group',
            'band'
        ])->findOrFail($personId);

        $this->showGuestsModal = true;
    }

    public function closeGuestsModal()
    {
        $this->showGuestsModal = false;
        $this->selectedPersonForGuests = null;

        // Session-Flash Messages löschen
        session()->forget(['success', 'error', 'warning', 'info']);
    }

    // Person-spezifischer Voucher-Kauf
    public function purchasePersonVoucher($personId, $amount)
    {
        $this->selectedPersonForPurchase = Person::findOrFail($personId);
        $this->voucherAmount = $amount;
        $this->initiatePurchase($amount);
    }

    // Voucher-Kauf mit Service
    private function executePurchase($personId = null)
    {
        if (!$this->purchaseStageId) {
            session()->flash('error', 'Keine Bühne für diese Person gefunden!');
            return;
        }

        // Service für Voucher-Kauf verwenden
        $result = $this->voucherService->purchaseVoucher(
            $this->voucherAmount,
            $this->purchaseStageId,
            $this->currentDay,
            $personId
        );

        if ($result['success']) {
            $this->showStageModal = false;
            $this->selectedPersonForPurchase = null;

            // Erfolgsmeldung erstellen
            if ($personId) {
                $person = Person::find($personId);
                $successMessage = "{$this->voucherAmount} " . ($this->settings ? $this->settings->getVoucherLabel() : 'Voucher') . " für {$person->full_name} gekauft!";
            } else {
                $stageName = $this->stages->find($this->purchaseStageId)->name ?? 'Unbekannte Bühne';
                $successMessage = "{$this->voucherAmount} " . ($this->settings ? $this->settings->getVoucherLabel() : 'Voucher') . " für Bühne {$stageName} gekauft!";
            }

            session()->flash('success', $successMessage);
            $this->forceRefresh();
        } else {
            session()->flash('error', $result['message']);
        }
    }

    // Voucher-Purchase-Mode Checks
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

    // Voucher-Kauf initialisieren
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

    // Voucher kaufen
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

    // Stage-Selection zurücksetzen
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

    // Verkaufszahlen mit Service
    public function getSoldVouchersForStage($stageId, $day = null)
    {
        return $this->voucherService->getSoldVouchersForStage($stageId, $day ?? $this->currentDay);
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

    // Bändchen-Farbe
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

    // Heutige Bands anzeigen
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

    // Band auswählen
    public function selectBand($bandId)
    {
        $this->selectedBand = Band::with(['members', 'stage'])->find($bandId);
        $this->selectedPerson = null;
        $this->selectedBandFromSearch = null;
        $this->bandMembers = [];
    }

    // Sortierung
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

    // Nächster verfügbarer Voucher-Tag mit Fresh Data
    public function getNextAvailableVoucherDay($person)
    {
        // IMMER frische Daten aus DB
        $freshPerson = Person::find($person->id);
        if (!$freshPerson) return null;

        $allowedDays = $this->voucherService->getAllowedVoucherDays($this->settings, $this->currentDay);
        return $this->voucherService->getNextAvailableVoucherDay($freshPerson, $allowedDays);
    }

    // Erlaubte Voucher-Tage mit Service
    public function getAllowedVoucherDays()
    {
        if (!$this->settings) return [$this->currentDay];
        return $this->voucherService->getAllowedVoucherDays($this->settings, $this->currentDay);
    }

    // Aktueller Tag
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

    // Debug-Methode für Voucher-Status
    public function debugVoucherStatus($personId)
    {
        $person = Person::find($personId);
        if (!$person) return;

        $debug = [
            'person_id' => $personId,
            'person_name' => $person->full_name,
            'current_day' => $this->currentDay,
            'vouchers' => []
        ];

        for ($day = 1; $day <= 4; $day++) {
            $debug['vouchers'][] = [
                'day' => $day,
                'available' => $person->{"voucher_day_$day"},
                'issued' => $person->{"voucher_issued_day_$day"},
                'can_issue' => $this->settings ? $this->settings->canIssueVouchersForDay($day, $this->currentDay) : false
            ];
        }

        $debug['next_available_day'] = $this->getNextAvailableVoucherDay($person);
        $debug['settings'] = [
            'single_mode' => $this->settings ? $this->settings->isSingleVoucherMode() : null,
            'issuance_rule' => $this->settings ? $this->settings->voucher_issuance_rule : null
        ];

        Log::info('Voucher Debug Status', $debug);

        // Für Development: Als Flash Message anzeigen
        if (config('app.debug')) {
            session()->flash('info', 'Debug Info: ' . json_encode($debug, JSON_PRETTY_PRINT));
        }
    }

    // Render
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

    // Suchfelder leeren
    public function clearSearch()
    {
        $this->search = '';
        $this->searchResults = [];
        $this->searchResultsCache = [];
        $this->lastSearchTerm = '';
        $this->selectedPerson = null;

        $this->js('
        const input = document.getElementById("search-input");
        if (input) {
            input.value = "";
            input.focus();
        }
    ');
    }

    public function clearBandSearch()
    {
        $this->bandSearch = '';
        $this->bandSearchResults = [];
        $this->bandSearchResultsCache = [];
        $this->lastBandSearchTerm = '';
        $this->selectedBandFromSearch = null;
        $this->bandMembers = [];

        // Clear the band search input field in the frontend
        $this->js('
        const bandSearchInput = document.getElementById("band-search-input");
        if (bandSearchInput) {
            bandSearchInput.value = "";
        }
    ');

        $this->dispatch('band-search-cleared');
    }

    // Focus-Methoden
    public function focusSearch()
    {
        $this->js('
        setTimeout(() => {
            const input = document.getElementById("search-input");
            if (input && input.value.trim() !== "") {
                input.select();
            }
        }, 10);
    ');
    }

    public function focusBandSearch()
    {
        $this->js('
        setTimeout(() => {
            const input = document.getElementById("band-search-input");
            if (input && input.value.trim() !== "") {
                input.select();
            }
        }, 10);
    ');
    }
}
