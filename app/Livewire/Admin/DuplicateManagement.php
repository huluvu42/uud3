<?php
// app/Livewire/Admin/DuplicateManagement.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Person;
use Illuminate\Support\Facades\DB;

class DuplicateManagement extends Component
{
    // KEINE COLLECTIONS ALS PROPERTIES - NUR PRIMITIVE DATENTYPEN
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
        $this->loadStats();
    }

    public function updatedSelectedYear()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        $this->stats['total_persons'] = Person::where('year', $this->selectedYear)->count();
        $this->stats['marked_duplicates'] = Person::duplicates()->where('year', $this->selectedYear)->count();

        // Potentielle Duplikate berechnen ohne sie zu speichern
        $potentialDuplicates = Person::findPotentialDuplicates($this->selectedYear);
        $this->stats['duplicate_groups'] = $potentialDuplicates->count();
        $this->stats['potential_duplicates'] = $potentialDuplicates->sum('count') - $this->stats['duplicate_groups'];
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

        session()->flash('success', $person->full_name . ' wurde als Duplikat markiert.');

        // Redirect zur gleichen Seite
        return redirect()->route('admin.duplicates', ['year' => $this->selectedYear]);
    }

    public function unmarkAsDuplicate($personId)
    {
        $person = Person::find($personId);
        if (!$person) {
            session()->flash('error', 'Person nicht gefunden!');
            return;
        }

        $person->unmarkAsDuplicate();

        session()->flash('success', $person->full_name . ' ist nicht mehr als Duplikat markiert.');

        // Redirect zur gleichen Seite
        return redirect()->route('admin.duplicates', ['year' => $this->selectedYear]);
    }

    public function markAllInGroup($groupPersons, $keepPersonId)
    {
        $marked = 0;
        foreach ($groupPersons as $personData) {
            $personId = $personData['id'];
            $isCurrentlyDuplicate = $personData['is_duplicate'] ?? false;

            if ($personId != $keepPersonId && !$isCurrentlyDuplicate) {
                $personObj = Person::find($personId);
                if ($personObj) {
                    $personObj->markAsDuplicate('Automatisch markiert - Duplikat von ' . $personData['first_name'] . ' ' . $personData['last_name']);
                    $marked++;
                }
            }
        }

        if ($marked > 0) {
            session()->flash('success', "$marked Personen wurden als Duplikate markiert.");
        }

        // Redirect zur gleichen Seite
        return redirect()->route('admin.duplicates', ['year' => $this->selectedYear]);
    }

    public function toggleShowMarkedDuplicates()
    {
        $this->showMarkedDuplicates = !$this->showMarkedDuplicates;
    }

    public function refreshData()
    {
        $this->loadStats();
        session()->flash('success', 'Daten wurden aktualisiert.');
    }

    public function render()
    {
        // Daten DIREKT im Render laden - NICHT als Properties speichern
        $potentialDuplicates = [];
        $markedDuplicates = collect();

        if (!$this->showMarkedDuplicates) {
            // Potentielle Duplikate - als Array für Template
            $potentialDuplicatesCollection = Person::findPotentialDuplicates($this->selectedYear);
            $potentialDuplicates = $potentialDuplicatesCollection->toArray();
        } else {
            // Markierte Duplikate - direkt als Collection für Template
            $markedDuplicates = Person::duplicates()
                ->with(['band', 'group', 'duplicateMarkedBy'])
                ->where('year', $this->selectedYear)
                ->orderBy('duplicate_marked_at', 'desc')
                ->get()
                ->groupBy(function ($person) {
                    return $person->first_name . ' ' . $person->last_name;
                });
        }

        return view('livewire.admin.duplicate-management', [
            'potentialDuplicates' => $potentialDuplicates,
            'markedDuplicates' => $markedDuplicates
        ]);
    }
}
