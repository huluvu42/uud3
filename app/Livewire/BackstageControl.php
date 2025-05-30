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

    // Voucher purchase
    public $voucherAmount = 0.5;
    public $purchaseStageId = null;
    public $showStageModal = false;
    
    // Filter
    public $stageFilter = 'all';

    public function mount()
    {
        $this->stages = Stage::where('year', now()->year)->get();
        $this->settings = Settings::current();
        $this->currentDay = $this->getCurrentDay();
        $this->showBandList = false;
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
        $query = Person::with(['band', 'group', 'subgroup'])
            ->where('year', now()->year);

        $query->where(function($q) {
            $q->where('first_name', 'ILIKE', '%' . $this->search . '%')
              ->orWhere('last_name', 'ILIKE', '%' . $this->search . '%')
              ->orWhereHas('band', function($bandQuery) {
                  $bandQuery->where('band_name', 'ILIKE', '%' . $this->search . '%');
              });
        });

        $this->searchResults = $query->limit(10)->get();
    }

    public function selectPerson($personId)
    {
        $this->selectedPerson = Person::with(['band.members', 'group', 'subgroup'])
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

    // Voucher-Kauf initiieren
    public function initiatePurchase($amount)
    {
        $this->voucherAmount = $amount;
        
        if (!$this->purchaseStageId) {
            $this->showStageModal = true;
        } else {
            $this->purchaseVouchers();
        }
    }

    public function purchaseVouchers()
    {
        if (!$this->purchaseStageId) {
            session()->flash('error', 'Bitte Bühne auswählen!');
            return;
        }

        VoucherPurchase::create([
            'amount' => $this->voucherAmount,
            'day' => $this->currentDay,
            'purchase_date' => now()->format('Y-m-d'),
            'stage_id' => $this->purchaseStageId,
            'user_id' => auth()->id(),
        ]);

        $this->showStageModal = false;
        $voucherLabel = $this->settings ? $this->settings->getVoucherLabel() : 'Voucher';
        session()->flash('success', "{$this->voucherAmount} $voucherLabel gekauft!");
    }

    public function resetStageSelection()
    {
        $this->purchaseStageId = null;
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
            
            $query = Person::with(['band', 'group', 'subgroup'])
                ->where('year', now()->year);

            $query->where(function($q) {
                $q->where('first_name', 'ILIKE', '%' . $this->search . '%')
                  ->orWhere('last_name', 'ILIKE', '%' . $this->search . '%')
                  ->orWhereHas('band', function($bandQuery) {
                      $bandQuery->where('band_name', 'ILIKE', '%' . $this->search . '%');
                  });
            });

            $this->searchResults = $query->limit(10)->get();
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