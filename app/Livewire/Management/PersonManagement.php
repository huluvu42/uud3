<?php
// app/Livewire/Management/PersonManagement.php - VEREINFACHTE VERSION

namespace App\Livewire\Management;

use Illuminate\Support\Facades\DB;
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
    public $can_have_guests = false;
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
    public $showGuestsModal = false;
    public $editingPerson = null;
    public $selectedPersonForGuests = null;
    public $search = '';
    public $filterType = 'all';
    public $continueAdding = false;
    public $showBandMembers = false;
    public $settings = null;

    // Performance Cache
    private $groupsCache = null;
    private $bandsCache = null;
    private $responsiblePersonsCache = null;

    public function mount()
    {
        $this->year = date('Y');
        $this->settings = Settings::current();
    }

    // Suchfeld-Management
    public function clearSearch()
    {
        $this->search = '';
        $this->resetPage();

        $this->js('
            const input = document.getElementById("search-input");
            if (input) {
                input.value = "";
                input.focus();
            }
        ');
    }

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

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterType()
    {
        $this->resetPage();
    }

    public function updatedShowBandMembers()
    {
        $this->resetPage();
    }

    // Query-Methode
    private function getPersonsQuery()
    {
        $query = Person::query();

        $query->select([
            'id',
            'first_name',
            'last_name',
            'present',
            'can_have_guests',
            'backstage_day_1',
            'backstage_day_2',
            'backstage_day_3',
            'backstage_day_4',
            'voucher_day_1',
            'voucher_day_2',
            'voucher_day_3',
            'voucher_day_4',
            'remarks',
            'group_id',
            'subgroup_id',
            'band_id',
            'responsible_person_id',
            'year',
            'is_duplicate'
        ]);

        $query->with([
            'group:id,name,can_have_guests',
            'subgroup:id,name,group_id',
            'band:id,band_name',
            'responsiblePerson:id,first_name,last_name',
            'responsibleFor:id,responsible_person_id,first_name,last_name',
            'vehiclePlates:id,person_id,license_plate'
        ]);

        $query->where('year', $this->year)
            ->where('is_duplicate', false);

        // Search-Filter
        if ($this->search && strlen($this->search) >= 2) {
            $searchTerm = strtolower(trim($this->search));

            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(first_name) LIKE ?', ['%' . $searchTerm . '%'])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', ['%' . $searchTerm . '%'])
                    ->orWhereRaw('LOWER(CONCAT(first_name, \' \', last_name)) LIKE ?', ['%' . $searchTerm . '%'])
                    ->orWhereExists(function ($plateQuery) use ($searchTerm) {
                        $plateQuery->select(DB::raw(1))
                            ->from('vehicle_plates')
                            ->whereColumn('vehicle_plates.person_id', 'persons.id')
                            ->whereRaw('LOWER(license_plate) LIKE ?', ['%' . $searchTerm . '%']);
                    })
                    ->orWhereExists(function ($groupQuery) use ($searchTerm) {
                        $groupQuery->select(DB::raw(1))
                            ->from('groups')
                            ->whereColumn('groups.id', 'persons.group_id')
                            ->whereRaw('LOWER(name) LIKE ?', ['%' . $searchTerm . '%']);
                    })
                    ->orWhereExists(function ($bandQuery) use ($searchTerm) {
                        $bandQuery->select(DB::raw(1))
                            ->from('bands')
                            ->whereColumn('bands.id', 'persons.band_id')
                            ->whereRaw('LOWER(band_name) LIKE ?', ['%' . $searchTerm . '%']);
                    });
            });
        }

        // Type-Filter
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

    // Presence Toggle
    public function togglePresence($personId)
    {
        $person = Person::select(['id', 'first_name', 'last_name', 'present', 'band_id'])
            ->find($personId);

        if (!$person) {
            session()->flash('error', 'Person nicht gefunden.');
            return;
        }

        $person->present = !$person->present;
        $person->save();

        if ($person->band_id) {
            $band = Band::find($person->band_id);
            if ($band) {
                $band->updateAllPresentStatus();
            }
        }

        $this->smartRefresh();

        $statusText = $person->present ? 'anwesend' : 'abwesend';
        session()->flash('success', "{$person->first_name} {$person->last_name} ist jetzt {$statusText}.");
    }

    // Smart Refresh
    private function smartRefresh($preserveSearch = true)
    {
        if ($this->selectedPersonForGuests) {
            $this->selectedPersonForGuests = Person::with([
                'responsibleFor:id,responsible_person_id,first_name,last_name,present'
            ])->find($this->selectedPersonForGuests->id);
        }

        $this->resetCache();

        if (!$preserveSearch && $this->search) {
            $this->resetPage();
        }
    }

    // Cache Management
    private function resetCache()
    {
        $this->groupsCache = null;
        $this->bandsCache = null;
        $this->responsiblePersonsCache = null;
    }

    // Cache Methods
    private function getGroupsCache()
    {
        if ($this->groupsCache === null) {
            $this->groupsCache = Group::select([
                'id',
                'name',
                'can_have_guests',
                'voucher_day_1',
                'voucher_day_2',
                'voucher_day_3',
                'voucher_day_4',
                'backstage_day_1',
                'backstage_day_2',
                'backstage_day_3',
                'backstage_day_4'
            ])->orderBy('name')->get();
        }
        return $this->groupsCache;
    }

    private function getBandsCache()
    {
        if ($this->bandsCache === null) {
            $this->bandsCache = Band::select(['id', 'band_name'])
                ->orderBy('band_name')
                ->get();
        }
        return $this->bandsCache;
    }

    private function getResponsiblePersonsCache()
    {
        if ($this->responsiblePersonsCache === null) {
            $this->responsiblePersonsCache = Person::select(['id', 'first_name', 'last_name'])
                ->where('can_have_guests', true)
                ->where('year', $this->year)
                ->where('is_duplicate', false)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }
        return $this->responsiblePersonsCache;
    }

    // Person CRUD Methods
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

        if ($this->group_id && $this->band_id) {
            $this->addError('band_id', 'Eine Person kann nicht gleichzeitig einer Gruppe und einer Band angehören.');
            return;
        }

        $person = Person::create([
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

        $this->resetCache();

        if ($this->continueAdding) {
            $this->first_name = '';
            $this->last_name = '';
            $this->present = false;
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

        if ($this->group_id && $this->band_id) {
            $this->addError('band_id', 'Eine Person kann nicht gleichzeitig einer Gruppe und einer Band angehören.');
            return;
        }

        $this->editingPerson->update([
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
        ]);

        $this->resetCache();
        $this->showEditForm = false;
        $this->resetPersonForm();
        session()->flash('success', 'Person wurde erfolgreich aktualisiert!');
    }

    public function deletePerson($id)
    {
        $person = Person::select(['id', 'first_name', 'last_name'])->find($id);
        if ($person) {
            $name = $person->first_name . ' ' . $person->last_name;
            $person->delete();
            $this->resetCache();
            session()->flash('success', "{$name} wurde erfolgreich gelöscht!");
        }
    }

    // VEREINFACHTE editPerson Methode (wie im Band-Management)
    public function editPerson($id)
    {
        $this->editingPerson = Person::findOrFail($id);

        // Andere Modals explizit schließen
        $this->showCreateForm = false;
        $this->showGuestsModal = false;

        // Alle Felder setzen
        $this->first_name = $this->editingPerson->first_name;
        $this->last_name = $this->editingPerson->last_name;
        $this->present = $this->editingPerson->present;
        $this->can_have_guests = $this->editingPerson->can_have_guests;
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

        // Modal öffnen - NACH dem Setzen aller Felder
        $this->showEditForm = true;
    }

    // Gäste-Modal Methoden
    public function toggleGuestPresence($guestId)
    {
        $guest = Person::select(['id', 'first_name', 'last_name', 'present'])->find($guestId);

        if (!$guest) {
            session()->flash('error', 'Gast nicht gefunden.');
            return;
        }

        $guest->present = !$guest->present;
        $guest->save();

        if ($this->selectedPersonForGuests) {
            $this->selectedPersonForGuests = Person::with([
                'responsibleFor:id,responsible_person_id,first_name,last_name,present,backstage_day_1,backstage_day_2,backstage_day_3,backstage_day_4,voucher_day_1,voucher_day_2,voucher_day_3,voucher_day_4,remarks'
            ])->find($this->selectedPersonForGuests->id);
        }

        $statusText = $guest->present ? 'anwesend' : 'abwesend';
        session()->flash('success', "{$guest->first_name} {$guest->last_name} ist jetzt als {$statusText} markiert.");
    }

    // Wristband color helper
    public function getWristbandColorForPerson($person)
    {
        if (!$this->settings) return null;

        $currentDay = $this->settings->getCurrentDay();

        if (!$person->{"backstage_day_{$currentDay}"}) {
            return null;
        }

        $hasAllRemainingDays = true;
        for ($day = $currentDay; $day <= 4; $day++) {
            if (!$person->{"backstage_day_$day"}) {
                $hasAllRemainingDays = false;
                break;
            }
        }

        return $hasAllRemainingDays
            ? $this->settings->getColorForDay(4)
            : $this->settings->getColorForDay($currentDay);
    }

    public function hasAnyBackstageAccess($person)
    {
        return $person->backstage_day_1 || $person->backstage_day_2 ||
            $person->backstage_day_3 || $person->backstage_day_4;
    }

    // Gruppen-basierte Voreinstellungen
    public function updatedGroupId()
    {
        if ($this->group_id) {
            $group = collect($this->getGroupsCache())->firstWhere('id', $this->group_id);
            if ($group) {
                $this->voucher_day_1 = $group->voucher_day_1 ?? '';
                $this->voucher_day_2 = $group->voucher_day_2 ?? '';
                $this->voucher_day_3 = $group->voucher_day_3 ?? '';
                $this->voucher_day_4 = $group->voucher_day_4 ?? '';

                $this->backstage_day_1 = $group->backstage_day_1 ?? false;
                $this->backstage_day_2 = $group->backstage_day_2 ?? false;
                $this->backstage_day_3 = $group->backstage_day_3 ?? false;
                $this->backstage_day_4 = $group->backstage_day_4 ?? false;

                $this->can_have_guests = $group->can_have_guests ?? false;
            }
            $this->band_id = '';
        }
    }

    public function updatedBandId()
    {
        if ($this->band_id) {
            $this->group_id = '';
            $this->subgroup_id = '';
        }
    }

    // Untergruppen
    public function getSubgroupsProperty()
    {
        if ($this->group_id) {
            return Subgroup::select(['id', 'name', 'group_id'])
                ->where('group_id', $this->group_id)
                ->orderBy('name')
                ->get();
        }
        return collect();
    }

    // Helper Methods
    public function resetPersonForm()
    {
        $this->first_name = '';
        $this->last_name = '';
        $this->present = false;
        $this->can_have_guests = false;
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

    public function createPerson()
    {
        $this->showCreateForm = true;
        $this->resetPersonForm();
    }

    public function showGuests($personId)
    {
        $this->selectedPersonForGuests = Person::with([
            'responsibleFor:id,responsible_person_id,first_name,last_name,present,backstage_day_1,backstage_day_2,backstage_day_3,backstage_day_4,voucher_day_1,voucher_day_2,voucher_day_3,voucher_day_4,remarks'
        ])->findOrFail($personId);
        $this->showGuestsModal = true;
    }

    public function closeGuestsModal()
    {
        $this->showGuestsModal = false;
        $this->selectedPersonForGuests = null;
    }

    public function render()
    {
        $persons = $this->getPersonsQuery()->paginate(15);

        return view('livewire.management.person-management', [
            'persons' => $persons,
            'groups' => $this->getGroupsCache(),
            'bands' => $this->getBandsCache(),
            'responsiblePersons' => $this->getResponsiblePersonsCache()
        ]);
    }
}
