<?php
// app/Livewire/Management/PersonManagement.php - NUR MINIMALE ÄNDERUNGEN

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
use App\Traits\HandlesWristbands;

class PersonManagement extends Component
{
    use WithPagination, ManagesVehiclePlates, HandlesWristbands;

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
    public $selectedGroupFilter = ''; // NEU: Gruppenfilter
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

    // NEU: Reaktion auf Gruppenfilter-Änderung
    public function updatedSelectedGroupFilter()
    {
        $this->resetPage();
    }

    public function updatedShowBandMembers()
    {
        $this->resetPage();
    }

    // Query-Methode - NUR GRUPPENFILTER HINZUGEFÜGT
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

        // NEU: Spezifischer Gruppenfilter
        if ($this->selectedGroupFilter) {
            $query->where('group_id', $this->selectedGroupFilter);
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
            ])
                ->where('year', $this->year)
                ->orderBy('name')
                ->get();
        }
        return $this->groupsCache;
    }

    private function getBandsCache()
    {
        if ($this->bandsCache === null) {
            $this->bandsCache = Band::select(['id', 'band_name'])
                ->where('year', $this->year)
                ->orderBy('band_name')
                ->get();
        }
        return $this->bandsCache;
    }

    private function getResponsiblePersonsCache()
    {
        if ($this->responsiblePersonsCache === null) {
            $this->responsiblePersonsCache = Person::select(['id', 'first_name', 'last_name'])
                ->where('can_have_guests', true) // NUR Personen die Gäste haben können
                ->where('year', $this->year)
                ->where('is_duplicate', false)
                ->whereNull('responsible_person_id') // Keine Gäste als verantwortliche Personen
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }
        return $this->responsiblePersonsCache;
    }

    // KORRIGIERTE savePerson Methode mit korrektem JavaScript
    public function savePerson($continueAdding = false)
    {
        $this->validate([
            'first_name' => 'required|min:2',
            'last_name' => 'required|min:2',
            'voucher_day_1' => 'nullable|numeric|min:0',
            'voucher_day_2' => 'nullable|numeric|min:0',
            'voucher_day_3' => 'nullable|numeric|min:0',
            'voucher_day_4' => 'nullable|numeric|min:0',
        ], [
            'first_name.required' => 'Der Vorname ist erforderlich.',
            'first_name.min' => 'Der Vorname muss mindestens 2 Zeichen lang sein.',
            'last_name.required' => 'Der Nachname ist erforderlich.',
            'last_name.min' => 'Der Nachname muss mindestens 2 Zeichen lang sein.',
        ]);

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
            'is_duplicate' => false,
        ]);

        $this->resetCache();

        if ($continueAdding) {
            // SPEICHERN & WEITER: Nur Namen löschen, andere Werte beibehalten
            $this->first_name = '';
            $this->last_name = '';

            // Frontend-Update erzwingen mit korrektem JavaScript-Selector
            $this->js('
                setTimeout(() => {
                    const firstNameInput = document.querySelector("input[wire\\\\:model=\'first_name\']");
                    const lastNameInput = document.querySelector("input[wire\\\\:model=\'last_name\']");
                    if (firstNameInput) {
                        firstNameInput.value = "";
                        firstNameInput.focus();
                    }
                    if (lastNameInput) {
                        lastNameInput.value = "";
                    }
                }, 100);
            ');

            // Modal bleibt offen, alle anderen Werte bleiben erhalten
            session()->flash('success', 'Person wurde erfolgreich erstellt! Nächste Person hinzufügen...');
        } else {
            // NORMALES SPEICHERN: Modal schließen und alles zurücksetzen
            $this->showCreateForm = false;
            $this->resetPersonForm();
            session()->flash('success', 'Person wurde erfolgreich erstellt!');
        }
    }

    public function updatePerson()
    {
        $this->validate([
            'first_name' => 'required|min:2',
            'last_name' => 'required|min:2',
            'voucher_day_1' => 'nullable|numeric|min:0',
            'voucher_day_2' => 'nullable|numeric|min:0',
            'voucher_day_3' => 'nullable|numeric|min:0',
            'voucher_day_4' => 'nullable|numeric|min:0',
        ]);

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

    // GEFIXT: Gruppen-basierte Voreinstellungen - OHNE wire:change
    public function updateGroupValues()
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

    public function updateBandValues()
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
