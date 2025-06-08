<?php
// app/Livewire/Management/BandManagement.php - OPTIMIERTE VERSION

namespace App\Livewire\Management;

use App\Models\Band;
use App\Models\Person;
use App\Models\Stage;
use App\Models\VehiclePlate;
use Livewire\Component;
use Livewire\WithPagination;

class BandManagement extends Component
{
    use WithPagination;

    // Band Properties
    public $band_name = '';
    public $plays_day_1 = false;
    public $plays_day_2 = false;
    public $plays_day_3 = false;
    public $plays_day_4 = false;
    public $stage_id = '';
    public $travel_costs = '';
    public $year = '';

    // Member Properties
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

    // Vehicle Properties
    public $license_plate = '';

    // Guest Properties
    public $guest_first_name = '';
    public $guest_last_name = '';

    // State Management
    public $showCreateForm = false;
    public $showEditForm = false;
    public $showMemberForm = false;
    public $showEditMemberForm = false;
    public $showVehicleForm = false;
    public $showGuestForm = false;
    public $editingBand = null;
    public $editingMember = null;
    public $selectedBand = null;
    public $selectedMember = null;
    public $search = '';

    // Performance Cache
    private $stagesCache = null;

    public function mount()
    {
        $this->year = date('Y');
    }

    // NEU: Suchfeld-Management Methoden
    public function clearSearch()
    {
        $this->search = '';
        $this->resetPage(); // Pagination zurücksetzen

        // JavaScript zum sofortigen Leeren des Input-Felds
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

    // Cache-Management für Stages
    private function getStagesCache()
    {
        if ($this->stagesCache === null) {
            $this->stagesCache = Stage::select(['id', 'name'])->orderBy('name')->get();
        }
        return $this->stagesCache;
    }

    private function resetCache()
    {
        $this->stagesCache = null;
    }

    // Optimierte render() Methode
    public function render()
    {
        // Optimierte Query mit Select Fields
        $bands = Band::with([
            'stage:id,name',
            'members:id,band_id,present', // Nur nötige Felder für Count
            'vehiclePlates:id,band_id,license_plate' // Nur nötige Felder
        ])
            ->select([
                'id',
                'band_name',
                'year',
                'stage_id',
                'all_present',
                'plays_day_1',
                'plays_day_2',
                'plays_day_3',
                'plays_day_4'
            ])
            ->when($this->search, function ($query) {
                $query->where('band_name', 'ILIKE', '%' . $this->search . '%');
            })
            ->orderBy('band_name')
            ->paginate(10);

        return view('livewire.management.band-management', [
            'bands' => $bands,
            'stages' => $this->getStagesCache()
        ]);
    }

    // Band CRUD Methods (mit Cache-Reset)
    public function createBand()
    {
        $this->showCreateForm = true;
        $this->resetBandForm();
    }

    public function saveBand()
    {
        $this->validate([
            'band_name' => 'required|string|max:255',
            'stage_id' => 'required|exists:stages,id',
            'travel_costs' => 'nullable|numeric|min:0',
        ]);

        Band::create([
            'band_name' => $this->band_name,
            'plays_day_1' => $this->plays_day_1,
            'plays_day_2' => $this->plays_day_2,
            'plays_day_3' => $this->plays_day_3,
            'plays_day_4' => $this->plays_day_4,
            'stage_id' => $this->stage_id,
            'travel_costs' => $this->travel_costs ?: null,
            'year' => $this->year, // Wird automatisch auf aktuelles Jahr gesetzt
        ]);

        $this->resetCache(); // Cache leeren
        $this->showCreateForm = false;
        $this->resetBandForm();
        session()->flash('message', 'Band wurde erfolgreich erstellt!');
    }

    public function editBand($id)
    {
        $this->editingBand = Band::findOrFail($id);
        $this->band_name = $this->editingBand->band_name;
        $this->plays_day_1 = $this->editingBand->plays_day_1;
        $this->plays_day_2 = $this->editingBand->plays_day_2;
        $this->plays_day_3 = $this->editingBand->plays_day_3;
        $this->plays_day_4 = $this->editingBand->plays_day_4;
        $this->stage_id = $this->editingBand->stage_id;
        $this->travel_costs = $this->editingBand->travel_costs;
        $this->year = $this->editingBand->year;
        $this->showEditForm = true;
    }

    public function updateBand()
    {
        $this->validate([
            'band_name' => 'required|string|max:255',
            'stage_id' => 'required|exists:stages,id',
            'travel_costs' => 'nullable|numeric|min:0',
        ]);

        $this->editingBand->update([
            'band_name' => $this->band_name,
            'plays_day_1' => $this->plays_day_1,
            'plays_day_2' => $this->plays_day_2,
            'plays_day_3' => $this->plays_day_3,
            'plays_day_4' => $this->plays_day_4,
            'stage_id' => $this->stage_id,
            'travel_costs' => $this->travel_costs ?: null,
            // Jahr wird beim Bearbeiten nicht verändert
        ]);

        $this->resetCache(); // Cache leeren
        $this->showEditForm = false;
        $this->resetBandForm();
        session()->flash('message', 'Band wurde erfolgreich aktualisiert!');
    }

    public function deleteBand($id)
    {
        Band::findOrFail($id)->delete();
        $this->resetCache(); // Cache leeren
        session()->flash('message', 'Band wurde erfolgreich gelöscht!');
    }

    // Member CRUD Methods
    public function showMembers($bandId)
    {
        $this->selectedBand = Band::with(['members', 'vehiclePlates'])->findOrFail($bandId);
    }

    public function addMember($bandId)
    {
        $this->selectedBand = Band::findOrFail($bandId);
        $this->showMemberForm = true;
        $this->resetMemberForm();

        // Vorgaben von der Bühne laden
        $this->loadStageDefaults();
    }

    private function loadStageDefaults()
    {
        if (!$this->selectedBand || !$this->selectedBand->stage) {
            return;
        }

        $stage = $this->selectedBand->stage;

        // Backstage-Access setzen
        $backstageAccess = $this->calculateBackstageAccess($stage);
        $this->backstage_day_1 = $backstageAccess['day_1'];
        $this->backstage_day_2 = $backstageAccess['day_2'];
        $this->backstage_day_3 = $backstageAccess['day_3'];
        $this->backstage_day_4 = $backstageAccess['day_4'];

        // Voucher setzen
        $vouchers = $this->calculateVouchers($stage);
        $this->voucher_day_1 = $vouchers['day_1'] > 0 ? $vouchers['day_1'] : '';
        $this->voucher_day_2 = $vouchers['day_2'] > 0 ? $vouchers['day_2'] : '';
        $this->voucher_day_3 = $vouchers['day_3'] > 0 ? $vouchers['day_3'] : '';
        $this->voucher_day_4 = $vouchers['day_4'] > 0 ? $vouchers['day_4'] : '';
    }

    public function saveMember()
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'voucher_day_1' => 'nullable|numeric|min:0|max:999.9',
            'voucher_day_2' => 'nullable|numeric|min:0|max:999.9',
            'voucher_day_3' => 'nullable|numeric|min:0|max:999.9',
            'voucher_day_4' => 'nullable|numeric|min:0|max:999.9',
            'remarks' => 'nullable|string',
        ]);

        // Bühnen-Vorgaben laden
        $stage = $this->selectedBand->stage;

        // Backstage-Access basierend auf Bühne und Auftrittstagen setzen
        $backstageAccess = $this->calculateBackstageAccess($stage);

        // Voucher basierend auf Bühne und Auftrittstagen setzen
        $vouchers = $this->calculateVouchers($stage);

        Person::create([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'present' => $this->present,
            'backstage_day_1' => $backstageAccess['day_1'],
            'backstage_day_2' => $backstageAccess['day_2'],
            'backstage_day_3' => $backstageAccess['day_3'],
            'backstage_day_4' => $backstageAccess['day_4'],
            'voucher_day_1' => $vouchers['day_1'],
            'voucher_day_2' => $vouchers['day_2'],
            'voucher_day_3' => $vouchers['day_3'],
            'voucher_day_4' => $vouchers['day_4'],
            'remarks' => $this->remarks,
            'band_id' => $this->selectedBand->id,
            'year' => $this->selectedBand->year,
        ]);

        // All-present Status aktualisieren
        $this->selectedBand->updateAllPresentStatus();

        // Band-Mitglieder neu laden
        $this->selectedBand = Band::with(['members', 'vehiclePlates'])->find($this->selectedBand->id);

        $this->showMemberForm = false;
        $this->resetMemberForm();
        session()->flash('message', 'Mitglied wurde erfolgreich hinzugefügt!');
    }

    // Hilfsmethoden für Bühnen-Vorgaben
    private function calculateBackstageAccess($stage)
    {
        $access = [
            'day_1' => false,
            'day_2' => false,
            'day_3' => false,
            'day_4' => false,
        ];

        if ($stage->hasBackstageAllDays()) {
            // Backstage an allen Tagen
            $access['day_1'] = true;
            $access['day_2'] = true;
            $access['day_3'] = true;
            $access['day_4'] = true;
        } else {
            // Nur an Auftrittstagen
            if ($this->selectedBand->plays_day_1) $access['day_1'] = true;
            if ($this->selectedBand->plays_day_2) $access['day_2'] = true;
            if ($this->selectedBand->plays_day_3) $access['day_3'] = true;
            if ($this->selectedBand->plays_day_4) $access['day_4'] = true;
        }

        return $access;
    }

    private function calculateVouchers($stage)
    {
        $vouchers = [
            'day_1' => 0,
            'day_2' => 0,
            'day_3' => 0,
            'day_4' => 0,
        ];

        $voucherAmount = $stage->getVoucherAmount();

        // Voucher nur an Auftrittstagen
        if ($this->selectedBand->plays_day_1) $vouchers['day_1'] = $voucherAmount;
        if ($this->selectedBand->plays_day_2) $vouchers['day_2'] = $voucherAmount;
        if ($this->selectedBand->plays_day_3) $vouchers['day_3'] = $voucherAmount;
        if ($this->selectedBand->plays_day_4) $vouchers['day_4'] = $voucherAmount;

        return $vouchers;
    }

    public function editMember($memberId)
    {
        $this->editingMember = Person::findOrFail($memberId);
        $this->first_name = $this->editingMember->first_name;
        $this->last_name = $this->editingMember->last_name;
        $this->present = $this->editingMember->present;
        $this->backstage_day_1 = $this->editingMember->backstage_day_1;
        $this->backstage_day_2 = $this->editingMember->backstage_day_2;
        $this->backstage_day_3 = $this->editingMember->backstage_day_3;
        $this->backstage_day_4 = $this->editingMember->backstage_day_4;
        $this->voucher_day_1 = $this->editingMember->voucher_day_1;
        $this->voucher_day_2 = $this->editingMember->voucher_day_2;
        $this->voucher_day_3 = $this->editingMember->voucher_day_3;
        $this->voucher_day_4 = $this->editingMember->voucher_day_4;
        $this->remarks = $this->editingMember->remarks;
        $this->showEditMemberForm = true;
    }

    public function updateMember()
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'voucher_day_1' => 'nullable|numeric|min:0|max:999.9',
            'voucher_day_2' => 'nullable|numeric|min:0|max:999.9',
            'voucher_day_3' => 'nullable|numeric|min:0|max:999.9',
            'voucher_day_4' => 'nullable|numeric|min:0|max:999.9',
            'remarks' => 'nullable|string',
        ]);

        $this->editingMember->update([
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
        ]);

        // All-present Status aktualisieren
        $this->selectedBand->updateAllPresentStatus();

        // Band-Mitglieder neu laden
        $this->selectedBand = Band::with(['members', 'vehiclePlates'])->find($this->selectedBand->id);

        $this->showEditMemberForm = false;
        $this->resetMemberForm();
        session()->flash('message', 'Mitglied wurde erfolgreich aktualisiert!');
    }

    public function deleteMember($memberId)
    {
        Person::findOrFail($memberId)->delete();

        // All-present Status aktualisieren
        $this->selectedBand->updateAllPresentStatus();

        // Band-Mitglieder neu laden
        $this->selectedBand = Band::with(['members', 'vehiclePlates'])->find($this->selectedBand->id);

        session()->flash('message', 'Mitglied wurde erfolgreich entfernt!');
    }

    // Vehicle CRUD Methods
    public function addVehicle($bandId)
    {
        $this->selectedBand = Band::findOrFail($bandId);
        $this->showVehicleForm = true;
        $this->license_plate = '';
    }

    public function saveVehicle()
    {
        $this->validate([
            'license_plate' => 'required|string|max:20',
        ]);

        VehiclePlate::create([
            'license_plate' => $this->license_plate,
            'band_id' => $this->selectedBand->id,
        ]);

        // Band-Daten neu laden
        $this->selectedBand = Band::with(['members', 'vehiclePlates'])->find($this->selectedBand->id);

        $this->showVehicleForm = false;
        $this->license_plate = '';
        session()->flash('message', 'KFZ-Kennzeichen wurde erfolgreich hinzugefügt!');
    }

    public function deleteVehicle($vehicleId)
    {
        VehiclePlate::findOrFail($vehicleId)->delete();

        // Band-Daten neu laden
        $this->selectedBand = Band::with(['members', 'vehiclePlates'])->find($this->selectedBand->id);

        session()->flash('message', 'KFZ-Kennzeichen wurde erfolgreich entfernt!');
    }

    // Guest CRUD Methods
    public function addGuest($memberId)
    {
        $this->selectedMember = Person::findOrFail($memberId);

        // Prüfen ob bereits ein Gast existiert
        if ($this->selectedMember->guest) {
            session()->flash('error', 'Dieses Mitglied hat bereits einen Gast!');
            return;
        }

        $this->showGuestForm = true;
        $this->guest_first_name = '';
        $this->guest_last_name = '';
    }

    public function saveGuest()
    {
        $this->validate([
            'guest_first_name' => 'required|string|max:255',
            'guest_last_name' => 'required|string|max:255',
        ]);

        // Prüfen ob das Mitglied bereits einen Gast hat
        if ($this->selectedMember->guest) {
            session()->flash('error', 'Dieses Mitglied hat bereits einen Gast!');
            return;
        }

        // Bühnen-Vorgaben für Gast laden (falls Gäste erlaubt)
        $stage = $this->selectedBand->stage;
        $guestBackstageAccess = [];
        $guestVouchers = [];

        if ($stage->guest_allowed) {
            // Gast bekommt nur an Auftrittstagen Zugang (nie alle Tage)
            $guestBackstageAccess = [
                'day_1' => $this->selectedBand->plays_day_1,
                'day_2' => $this->selectedBand->plays_day_2,
                'day_3' => $this->selectedBand->plays_day_3,
                'day_4' => $this->selectedBand->plays_day_4,
            ];

            // Gast bekommt keine Voucher (Standard-Verhalten)
            $guestVouchers = [
                'day_1' => 0,
                'day_2' => 0,
                'day_3' => 0,
                'day_4' => 0,
            ];
        }

        Person::create([
            'first_name' => $this->guest_first_name,
            'last_name' => $this->guest_last_name,
            'present' => false,
            'backstage_day_1' => $guestBackstageAccess['day_1'] ?? false,
            'backstage_day_2' => $guestBackstageAccess['day_2'] ?? false,
            'backstage_day_3' => $guestBackstageAccess['day_3'] ?? false,
            'backstage_day_4' => $guestBackstageAccess['day_4'] ?? false,
            'voucher_day_1' => $guestVouchers['day_1'] ?? 0,
            'voucher_day_2' => $guestVouchers['day_2'] ?? 0,
            'voucher_day_3' => $guestVouchers['day_3'] ?? 0,
            'voucher_day_4' => $guestVouchers['day_4'] ?? 0,
            'remarks' => 'Gast von ' . $this->selectedMember->first_name . ' ' . $this->selectedMember->last_name,
            'band_id' => $this->selectedBand->id,
            'responsible_person_id' => $this->selectedMember->id, // Verknüpfung zum Band-Mitglied
            'year' => $this->selectedBand->year,
        ]);

        // Band-Daten neu laden
        $this->selectedBand = Band::with(['members', 'vehiclePlates'])->find($this->selectedBand->id);

        $this->showGuestForm = false;
        $this->guest_first_name = '';
        $this->guest_last_name = '';
        session()->flash('message', 'Gast wurde erfolgreich hinzugefügt!');
    }

    public function deleteGuest($guestId)
    {
        Person::findOrFail($guestId)->delete();

        // Band-Daten neu laden
        $this->selectedBand = Band::with(['members', 'vehiclePlates'])->find($this->selectedBand->id);

        session()->flash('message', 'Gast wurde erfolgreich entfernt!');
    }

    // Helper Methods
    public function resetBandForm()
    {
        $this->band_name = '';
        $this->plays_day_1 = false;
        $this->plays_day_2 = false;
        $this->plays_day_3 = false;
        $this->plays_day_4 = false;
        $this->stage_id = '';
        $this->travel_costs = '';
        $this->year = date('Y');
        $this->editingBand = null;
    }

    public function resetMemberForm()
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
        $this->editingMember = null;
    }

    public function cancelBandForm()
    {
        $this->showCreateForm = false;
        $this->showEditForm = false;
        $this->resetBandForm();
    }

    public function cancelMemberForm()
    {
        $this->showMemberForm = false;
        $this->showEditMemberForm = false;
        $this->resetMemberForm();
    }

    public function cancelVehicleForm()
    {
        $this->showVehicleForm = false;
        $this->license_plate = '';
    }

    public function cancelGuestForm()
    {
        $this->showGuestForm = false;
        $this->guest_first_name = '';
        $this->guest_last_name = '';
    }

    public function backToBandList()
    {
        $this->selectedBand = null;
        $this->resetMemberForm();
    }
}
