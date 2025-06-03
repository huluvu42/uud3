<?php
// app/Livewire/Management/PersonManagement.php

namespace App\Livewire\Management;

use App\Models\Person;
use App\Models\Group;
use App\Models\Subgroup;
use App\Models\Band;
use App\Models\Settings;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\ManagesVehiclePlates;

class PersonManagement extends Component
{
    use WithPagination, ManagesVehiclePlates;

    // Person Properties
    public $first_name = '';
    public $last_name = '';
    public $present = false;
    public $can_have_guests = false; // NEU
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
    public $showGuestsModal = false; // NEU
    public $editingPerson = null;
    public $selectedPersonForGuests = null; // NEU
    public $search = '';
    public $filterType = 'all'; // all, groups, bands, guests
    public $continueAdding = false; // Für "Speichern und weiter"
    public $showBandMembers = false; // Toggle für Bandmitglieder
    public $settings = null;

    public function mount()
    {
        $this->year = date('Y');
        $this->settings = Settings::current();
    }

    public function updatedSearch()
    {
        $this->resetPage(); // Reset pagination when searching
    }

    public function updatedFilterType()
    {
        $this->resetPage();
    }

    public function updatedShowBandMembers()
    {
        $this->resetPage();
    }

    private function getPersonsQuery()
    {
        $query = Person::with(['group', 'subgroup', 'band', 'responsiblePerson', 'responsibleFor', 'vehiclePlates'])
            ->where('year', $this->year)
            ->where('is_duplicate', false);

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('first_name', 'ILIKE', '%' . $this->search . '%')
                    ->orWhere('last_name', 'ILIKE', '%' . $this->search . '%')
                    // NEU: Auch in Kennzeichen suchen
                    ->orWhereHas('vehiclePlates', function ($plateQuery) {
                        $plateQuery->where('license_plate', 'ILIKE', '%' . $this->search . '%');
                    });
            });
        }
        // Type filter
        if ($this->filterType !== 'all') {
            switch ($this->filterType) {
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
        }

        // Band members filter
        if (!$this->showBandMembers) {
            $query->whereNull('band_id');
        }

        return $query->orderBy('last_name')->orderBy('first_name');
    }

    // Person CRUD Methods
    public function createPerson()
    {
        $this->showCreateForm = true;
        $this->resetPersonForm();
    }

    public function editPerson($id)
    {
        $this->editingPerson = Person::findOrFail($id);
        $this->first_name = $this->editingPerson->first_name;
        $this->last_name = $this->editingPerson->last_name;
        $this->present = $this->editingPerson->present;
        $this->can_have_guests = $this->editingPerson->can_have_guests; // NEU
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
            'can_have_guests' => $this->can_have_guests,
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
            // Nur Name-Felder und present zurücksetzen, Rest beibehalten
            $this->first_name = '';
            $this->last_name = '';
            $this->present = false;

            // Alternative: Direkte JavaScript-Ausführung
            $this->js('clearNameFields()');

            session()->flash('success', 'Person wurde erfolgreich erstellt! Sie können die nächste Person mit den gleichen Einstellungen anlegen.');
        } else {
            $this->showCreateForm = false;
            $this->resetPersonForm();
            session()->flash('success', 'Person wurde erfolgreich erstellt!');
        }
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
            'can_have_guests' => $this->can_have_guests, // NEU
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
        session()->flash('success', 'Person wurde erfolgreich aktualisiert!');
    }

    public function deletePerson($id)
    {
        Person::findOrFail($id)->delete();
        session()->flash('success', 'Person wurde erfolgreich gelöscht!');
    }

    // Wristband color helper
    public function getWristbandColorForPerson($person)
    {
        if (!$this->settings) return null;

        $currentDay = $this->settings->getCurrentDay();

        // First check if person has backstage access for the current day
        if (!$person->{"backstage_day_{$currentDay}"}) {
            return null; // No wristband if no access for current day
        }

        // Check if person has backstage access for all remaining days (from current day to day 4)
        $hasAllRemainingDays = true;
        for ($day = $currentDay; $day <= 4; $day++) {
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
        return $this->settings->getColorForDay($currentDay);
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

                // Kann Gäste haben von der Gruppe übernehmen - NEU
                $this->can_have_guests = $group->can_have_guests ?? false;
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
        $this->can_have_guests = false; // NEU
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

    // NEU: Gäste-Modal Methoden
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

    // NEU: Anwesenheitsstatus für Gast ändern
    public function toggleGuestPresence($guestId)
    {
        $guest = Person::findOrFail($guestId);
        $guest->update(['present' => !$guest->present]);

        // Aktualisiere die ausgewählte Person um die Änderungen zu zeigen
        $this->selectedPersonForGuests = Person::with('responsibleFor')->findOrFail($this->selectedPersonForGuests->id);

        $statusText = $guest->present ? 'anwesend' : 'abwesend';
        session()->flash('success', "{$guest->full_name} ist jetzt als {$statusText} markiert.");
    }

    public function render()
    {
        $persons = $this->getPersonsQuery()->paginate(15);

        $groups = Group::orderBy('name')->get();
        $bands = Band::orderBy('band_name')->get();

        // GEÄNDERT: Nur Personen die Gäste haben dürfen
        $responsiblePersons = Person::where('can_have_guests', true)
            ->where('year', $this->year)
            ->where('is_duplicate', false)
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
}
