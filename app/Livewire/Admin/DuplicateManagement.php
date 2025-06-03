<?php
// app/Livewire/Admin/DuplicateManagement.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Person;
use Illuminate\Support\Facades\DB;

class DuplicateManagement extends Component
{
    public $potentialDuplicates = [];
    public $markedDuplicates = [];
    public $selectedYear;
    public $showMarkedDuplicates = false;
    public $isLoading = false;
    
    // Statistiken
    public $stats = [
        'total_persons' => 0,
        'potential_duplicates' => 0,
        'marked_duplicates' => 0,
        'duplicate_groups' => 0
    ];

    public function mount()
    {
        $this->selectedYear = now()->year;
        $this->loadDuplicates();
        $this->loadStats();
    }

    public function updatedSelectedYear()
    {
        $this->loadDuplicates();
        $this->loadStats();
    }

    public function loadDuplicates()
    {
        $this->isLoading = true;
        
        // Potentielle Duplikate finden
        $this->potentialDuplicates = Person::findPotentialDuplicates($this->selectedYear);
        
        // Bereits markierte Duplikate
        $this->markedDuplicates = Person::duplicates()
            ->with(['band', 'group', 'duplicateMarkedBy'])
            ->where('year', $this->selectedYear)
            ->orderBy('duplicate_marked_at', 'desc')
            ->get()
            ->groupBy(function($person) {
                return $person->first_name . ' ' . $person->last_name;
            });
        
        $this->isLoading = false;
    }

    public function loadStats()
    {
        $this->stats['total_persons'] = Person::where('year', $this->selectedYear)->count();
        $this->stats['marked_duplicates'] = Person::duplicates()->where('year', $this->selectedYear)->count();
        $this->stats['duplicate_groups'] = count($this->potentialDuplicates);
        $this->stats['potential_duplicates'] = $this->potentialDuplicates->sum('count') - $this->stats['duplicate_groups']; // Minus das Original pro Gruppe
    }

    public function markAsDuplicate($personId, $reason = null)
    {
        $person = Person::find($personId);
        if (!$person) {
            session()->flash('error', 'Person nicht gefunden!');
            return;
        }

        $reason = $reason ?: 'Manuell als Duplikat markiert';
        $person->markAsDuplicate($reason);
        
        // Neu laden
        $this->loadDuplicates();
        $this->loadStats();
        
        session()->flash('success', $person->full_name . ' wurde als Duplikat markiert.');
    }

    public function unmarkAsDuplicate($personId)
    {
        $person = Person::find($personId);
        if (!$person) {
            session()->flash('error', 'Person nicht gefunden!');
            return;
        }

        $person->unmarkAsDuplicate();
        
        // Neu laden
        $this->loadDuplicates();
        $this->loadStats();
        
        session()->flash('success', $person->full_name . ' ist nicht mehr als Duplikat markiert.');
    }

    public function markAllInGroup($groupPersons, $keepPersonId)
    {
        $marked = 0;
        foreach ($groupPersons as $person) {
            if ($person['id'] != $keepPersonId && !$person['is_duplicate']) {
                $personObj = Person::find($person['id']);
                if ($personObj) {
                    $personObj->markAsDuplicate('Automatisch markiert - Duplikat von ' . $person['first_name'] . ' ' . $person['last_name']);
                    $marked++;
                }
            }
        }
        
        if ($marked > 0) {
            $this->loadDuplicates();
            $this->loadStats();
            session()->flash('success', "$marked Personen wurden als Duplikate markiert.");
        }
    }

    public function toggleShowMarkedDuplicates()
    {
        $this->showMarkedDuplicates = !$this->showMarkedDuplicates;
    }

    public function refreshData()
    {
        $this->loadDuplicates();
        $this->loadStats();
        session()->flash('success', 'Daten wurden aktualisiert.');
    }

    public function render()
    {
        return view('livewire.admin.duplicate-management');
    }
}