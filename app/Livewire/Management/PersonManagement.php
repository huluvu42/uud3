<?php
// app/Livewire/Management/PersonManagement.php

namespace App\Livewire\Management;

use App\Models\Person;
use App\Models\Group;
use App\Models\Subgroup;
use App\Models\Band;
use Livewire\Component;
use Livewire\WithPagination;

class PersonManagement extends Component
{
    use WithPagination;

    // Person Properties
    public $first_name = '';
    public $last_name = '';
    public $present = false;
    public $backstage_day_1 = false;
    public $backstage_day_2 = false;
    public $backstage_day_3 = false;
    public $backstage_day_4 = false;
    public $voucher_day_1 = '';
    public $voucher_day_2 = '';
    public $voucher_day_3 = '';
    public $voucher_day_4 = '';
    public $remarks = '';
    public $group_id = '';
    public $subgroup_id = '';
    public $band_id = '';
    public $responsible_person_id = '';
    public $year = '';

    // State Management
    public $showCreateForm = false;
    public $showEditForm = false;
    public $editingPerson = null;
    public $search = '';
    public $filterType = 'all'; // all, groups, bands, guests
    public $continueAdding = false; // Für "Speichern und weiter"
    public $showBandMembers = false; // Toggle für Bandmitglieder

    public function mount()
    {
        $this->year = date('Y');
    }

    public function render()
    {
        $persons = Person::with(['group', 'subgroup', 'band', 'responsiblePerson'])
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                      ->orWhere('last_name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterType !== 'all', function($query) {
                switch($this->filterType) {
                    case 'groups':
                        $query->whereNotNull('group_id')->whereNull('band_id');
                        break;
                    case 'bands':
                        $query->whereNotNull('band_id');
                        break;
                    case 'guests':
                        $query->whereNotNull('responsible_person_id');
                        break;
                }
            })
            ->when(!$this->showBandMembers, function($query) {
                // Bandmitglieder ausblenden wenn Toggle aus ist
                $query->whereNull('band_id');
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);

        $groups = Group::orderBy('name')->get();
        $bands = Band::orderBy('band_name')->get();
        $responsiblePersons = Person::whereNull('responsible_person_id')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('livewire.management.person-management', [
            'persons' => $persons,
            'groups' => $groups,
            'bands' => $bands,
            'responsiblePersons' => $responsiblePersons
        ]);
    }

    // Person CRUD Methods
    public function createPerson()
    {
        $this->showCreateForm = true;
        $this->resetPersonForm();
    }

    public function savePerson($continueAdding = false)
    {
        $this->continueAdding = $continueAdding;
        
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'group_id' => 'nullable|exists:groups,id',
            'subgroup_id' => 'nullable|exists:subgroups,id',
            'band_id' => 'nullable|exists:bands,id',
            'responsible_person_id' => 'nullable|exists:persons,id',
            'voucher_day_1' => 'nullable|numeric|min:0|max:999.9',
            'voucher_day_2' => 'nullable|numeric|min:0|max:999.9',
            'voucher_day_3' => 'nullable|numeric|min:0|max:999.9',
            'voucher_day_4' => 'nullable|numeric|min:0|max:999.9',
            'remarks' => 'nullable|string',
        ]);

        // Validierung: Person kann nur einer Gruppe ODER Band angehören
        if ($this->group_id && $this->band_id) {
            $this->addError('band_id', 'Eine Person kann nicht gleichzeitig einer Gruppe und einer Band angehören.');
            return;
        }

        Person::create([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'present' => $this->present,
            'backstage_day_1' => $this->backstage_day_1,
            'backstage_day_2' => $this->backstage_day_2,
            'backstage_day_3' => $this->backstage_day_3,
            'backstage_day_4' => $this->backstage_day_4,
            'voucher_day_1' => $this->voucher_day_1 ?: 0,
            'voucher_day_2' => $this->voucher_day_2 ?: 0,
            'voucher_day_3' => $this->voucher_day_3 ?: 0,
            'voucher_day_4' => $this->voucher_day_4 ?: 0,
            'remarks' => $this->remarks,
            'group_id' => $this->group_id ?: null,
            'subgroup_id' => $this->subgroup_id ?: null,
            'band_id' => $this->band_id ?: null,
            'responsible_person_id' => $this->responsible_person_id ?: null,
            'year' => $this->year,
        ]);

        if ($this->continueAdding) {
            // Nur Name-Felder zurücksetzen, Rest beibehalten
            $this->first_name = '';
            $this->last_name = '';
            $this->present = false;
            session()->flash('message', 'Person wurde erfolgreich erstellt! Sie können die nächste Person mit den gleichen Einstellungen anlegen.');
        } else {
            $this->showCreateForm = false;
            $this->resetPersonForm();
            session()->flash('message', 'Person wurde erfolgreich erstellt!');
        }
    }

    public function editPerson($id)
    {
        $this->editingPerson = Person::findOrFail($id);
        $this->first_name = $this->editingPerson->first_name;
        $this->last_name = $this->editingPerson->last_name;
        $this->present = $this->editingPerson->present;
        $this->backstage_day_1 = $this->editingPerson->backstage_day_1;
        $this->backstage_day_2 = $this->editingPerson->backstage_day_2;
        $this->backstage_day_3 = $this->editingPerson->backstage_day_3;
        $this->backstage_day_4 = $this->editingPerson->backstage_day_4;
        $this->voucher_day_1 = $this->editingPerson->voucher_day_1;
        $this->voucher_day_2 = $this->editingPerson->voucher_day_2;
        $this->voucher_day_3 = $this->editingPerson->voucher_day_3;
        $this->voucher_day_4 = $this->editingPerson->voucher_day_4;
        $this->remarks = $this->editingPerson->remarks;
        $this->group_id = $this->editingPerson->group_id;
        $this->subgroup_id = $this->editingPerson->subgroup_id;
        $this->band_id = $this->editingPerson->band_id;
        $this->responsible_person_id = $this->editingPerson->responsible_person_id;
        $this->year = $this->editingPerson->year;
        $this->showEditForm = true;
    }

    public function updatePerson()
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'group_id' => 'nullable|exists:groups,id',
            'subgroup_id' => 'nullable|exists:subgroups,id',
            'band_id' => 'nullable|exists:bands,id',
            'responsible_person_id' => 'nullable|exists:persons,id',
            'voucher_day_1' => 'nullable|numeric|min:0|max:999.9',
            'voucher_day_2' => 'nullable|numeric|min:0|max:999.9',
            'voucher_day_3' => 'nullable|numeric|min:0|max:999.9',
            'voucher_day_4' => 'nullable|numeric|min:0|max:999.9',
            'remarks' => 'nullable|string',
        ]);

        // Validierung: Person kann nur einer Gruppe ODER Band angehören
        if ($this->group_id && $this->band_id) {
            $this->addError('band_id', 'Eine Person kann nicht gleichzeitig einer Gruppe und einer Band angehören.');
            return;
        }

        $this->editingPerson->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'present' => $this->present,
            'backstage_day_1' => $this->backstage_day_1,
            'backstage_day_2' => $this->backstage_day_2,
            'backstage_day_3' => $this->backstage_day_3,
            'backstage_day_4' => $this->backstage_day_4,
            'voucher_day_1' => $this->voucher_day_1 ?: 0,
            'voucher_day_2' => $this->voucher_day_2 ?: 0,
            'voucher_day_3' => $this->voucher_day_3 ?: 0,
            'voucher_day_4' => $this->voucher_day_4 ?: 0,
            'remarks' => $this->remarks,
            'group_id' => $this->group_id ?: null,
            'subgroup_id' => $this->subgroup_id ?: null,
            'band_id' => $this->band_id ?: null,
            'responsible_person_id' => $this->responsible_person_id ?: null,
        ]);

        $this->showEditForm = false;
        $this->resetPersonForm();
        session()->flash('message', 'Person wurde erfolgreich aktualisiert!');
    }

    public function deletePerson($id)
    {
        Person::findOrFail($id)->delete();
        session()->flash('message', 'Person wurde erfolgreich gelöscht!');
    }

    // Gruppen-basierte Voreinstellungen laden
    public function updatedGroupId()
    {
        if ($this->group_id) {
            $group = Group::find($this->group_id);
            if ($group) {
                // Voucher von der Gruppe übernehmen
                $this->voucher_day_1 = $group->voucher_day_1 ?? '';
                $this->voucher_day_2 = $group->voucher_day_2 ?? '';
                $this->voucher_day_3 = $group->voucher_day_3 ?? '';
                $this->voucher_day_4 = $group->voucher_day_4 ?? '';
                
                // Backstage-Zugang von der Gruppe übernehmen
                $this->backstage_day_1 = $group->backstage_day_1 ?? false;
                $this->backstage_day_2 = $group->backstage_day_2 ?? false;
                $this->backstage_day_3 = $group->backstage_day_3 ?? false;
                $this->backstage_day_4 = $group->backstage_day_4 ?? false;
            }
            
            // Band-Auswahl zurücksetzen wenn Gruppe gewählt
            $this->band_id = '';
        }
    }

    public function updatedBandId()
    {
        if ($this->band_id) {
            // Gruppen-Auswahl zurücksetzen wenn Band gewählt
            $this->group_id = '';
            $this->subgroup_id = '';
        }
    }

    // Untergruppen laden basierend auf ausgewählter Gruppe
    public function getSubgroupsProperty()
    {
        if ($this->group_id) {
            return Subgroup::where('group_id', $this->group_id)->orderBy('name')->get();
        }
        return collect();
    }

    // Helper Methods
    public function resetPersonForm()
    {
        $this->first_name = '';
        $this->last_name = '';
        $this->present = false;
        $this->backstage_day_1 = false;
        $this->backstage_day_2 = false;
        $this->backstage_day_3 = false;
        $this->backstage_day_4 = false;
        $this->voucher_day_1 = '';
        $this->voucher_day_2 = '';
        $this->voucher_day_3 = '';
        $this->voucher_day_4 = '';
        $this->remarks = '';
        $this->group_id = '';
        $this->subgroup_id = '';
        $this->band_id = '';
        $this->responsible_person_id = '';
        $this->year = date('Y');
        $this->editingPerson = null;
        $this->continueAdding = false;
    }

    public function cancelPersonForm()
    {
        $this->showCreateForm = false;
        $this->showEditForm = false;
        $this->resetPersonForm();
    }
}