<?php
// app/Livewire/Management/BandManagement.php - PERFORMANCE OPTIMIERTE VERSION

namespace App\Livewire\Management;

use App\Models\Band;
use App\Models\Person;
use App\Models\Stage;
use App\Models\VehiclePlate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
    private $queryCache = [];

    public function mount()
    {
        $this->year = date('Y');

        // Query Logging in Development
        if (app()->environment('local')) {
            DB::enableQueryLog();
        }
    }

    // ===== OPTIMIERTE QUERY METHODEN =====

    /**
     * Optimierte Band-Query mit minimalen Select Fields und Eager Loading
     */
    private function getBandsQuery()
    {
        return Band::query()
            ->select([
                'id',
                'band_name',
                'year',
                'stage_id',
                'all_present',
                'plays_day_1',
                'plays_day_2',
                'plays_day_3',
                'plays_day_4',
                'travel_costs',
                'created_at'
            ])
            ->with([
                'stage:id,name',
                'members' => function ($query) {
                    $query->select(['id', 'band_id', 'present', 'first_name', 'last_name'])
                        ->where('is_duplicate', false);
                },
                'vehiclePlates:id,band_id,license_plate'
            ])
            ->withCount([
                'members as total_members_count',
                'members as present_members_count' => function ($query) {
                    $query->where('present', true)->where('is_duplicate', false);
                }
            ])
            ->where('year', $this->year);
    }

    /**
     * Optimierte Such-Implementierung mit Index-freundlichen Queries
     */
    private function applySearch($query, $search)
    {
        if (!$search || strlen($search) < 2) {
            return $query;
        }

        $searchTerm = trim($search);

        return $query->where(function ($q) use ($searchTerm) {
            // Exakte Treffer zuerst (nutzt Index optimal)
            $q->where('band_name', 'ILIKE', $searchTerm)
                // Dann Starts-with (Index-freundlich)
                ->orWhere('band_name', 'ILIKE', $searchTerm . '%')
                // Dann Contains (weniger effizient, aber nötig)
                ->orWhere('band_name', 'ILIKE', '%' . $searchTerm . '%')
                // Auch in Stage-Namen suchen
                ->orWhereHas('stage', function ($stageQuery) use ($searchTerm) {
                    $stageQuery->where('name', 'ILIKE', '%' . $searchTerm . '%');
                });
        });
    }

    // ===== CACHING METHODEN =====

    /**
     * Cached Stages mit allen nötigen Feldern
     */
    private function getCachedStages()
    {
        if (!isset($this->queryCache['stages'])) {
            $this->queryCache['stages'] = Cache::remember(
                'stages_for_bands_' . $this->year,
                3600, // 1 Stunde Cache
                fn() => Stage::select([
                    'id',
                    'name',
                    'voucher_amount',
                    'guest_allowed',
                    'backstage_all_days',
                    'voucher_day_1',
                    'voucher_day_2',
                    'voucher_day_3',
                    'voucher_day_4'
                ])
                    ->orderBy('name')
                    ->get()
            );
        }
        return $this->queryCache['stages'];
    }

    /**
     * Cached Statistics für Dashboard
     */
    private function getCachedStatistics()
    {
        return Cache::remember("band_stats_{$this->year}", 300, function () {
            return [
                'total_bands' => Band::where('year', $this->year)->count(),
                'total_members' => Person::whereHas('band', function ($q) {
                    $q->where('year', $this->year);
                })->where('is_duplicate', false)->count(),
                'present_members' => Person::whereHas('band', function ($q) {
                    $q->where('year', $this->year);
                })->where('present', true)->where('is_duplicate', false)->count(),
                'bands_complete' => Band::where('year', $this->year)->where('all_present', true)->count(),
                'total_vehicles' => VehiclePlate::whereHas('band', function ($q) {
                    $q->where('year', $this->year);
                })->count()
            ];
        });
    }

    /**
     * Cache Reset mit selektivem Clearing
     */
    private function resetCache($clearStats = false)
    {
        $this->queryCache = [];

        if ($clearStats) {
            Cache::forget("band_stats_{$this->year}");
        }

        // Stage Cache nur bei Bedarf löschen
        Cache::forget('stages_for_bands_' . $this->year);
    }

    // ===== SEARCH & PAGINATION =====

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

    // ===== SMART REFRESH SYSTEM =====

    /**
     * Intelligentes Refresh-System - lädt nur was nötig ist
     */
    private function smartRefresh($refreshMembers = false, $refreshStats = false)
    {
        if ($refreshStats) {
            Cache::forget("band_stats_{$this->year}");
        }

        if ($this->selectedBand && $refreshMembers) {
            // Nur Members neu laden, nicht die ganze Band
            $this->selectedBand->load([
                'members' => function ($query) {
                    $query->select([
                        'id',
                        'band_id',
                        'first_name',
                        'last_name',
                        'present',
                        'backstage_day_1',
                        'backstage_day_2',
                        'backstage_day_3',
                        'backstage_day_4',
                        'voucher_day_1',
                        'voucher_day_2',
                        'voucher_day_3',
                        'voucher_day_4',
                        'remarks',
                        'responsible_person_id'
                    ])
                        ->with('responsiblePerson:id,first_name,last_name')
                        ->where('is_duplicate', false)
                        ->orderBy('first_name');
                },
                'vehiclePlates:id,band_id,license_plate'
            ]);
        }

        $this->resetCache($refreshStats);
    }

    // ===== OPTIMIERTES RENDER =====

    public function render()
    {
        $bandsQuery = $this->getBandsQuery();

        // Suche anwenden
        if ($this->search) {
            $bandsQuery = $this->applySearch($bandsQuery, $this->search);
        }

        // Cursor Pagination für bessere Performance bei großen Datensätzen
        $bands = $bandsQuery->orderBy('band_name')
            ->paginate(15); // Kann zu cursorPaginate() gewechselt werden

        // Query Logging in Development
        if (app()->environment('local')) {
            $this->logQueries();
        }

        return view('livewire.management.band-management', [
            'bands' => $bands,
            'stages' => $this->getCachedStages(),
            'statistics' => $this->getCachedStatistics()
        ]);
    }

    // ===== BAND CRUD (OPTIMIERT) =====

    public function createBand()
    {
        $this->closeAllModals();
        $this->showCreateForm = true;
        $this->resetBandForm();
    }

    public function saveBand()
    {
        $this->validate([
            'band_name' => 'required|string|max:255|unique:bands,band_name,NULL,id,year,' . $this->year,
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
            'year' => $this->year,
        ]);

        $this->resetCache(true);
        $this->showCreateForm = false;
        $this->resetBandForm();
        session()->flash('message', 'Band wurde erfolgreich erstellt!');
    }

    public function editBand($id)
    {
        // Optimierte Einzelband-Abfrage
        $this->editingBand = Band::select([
            'id',
            'band_name',
            'plays_day_1',
            'plays_day_2',
            'plays_day_3',
            'plays_day_4',
            'stage_id',
            'travel_costs',
            'year'
        ])->findOrFail($id);

        $this->closeAllModals();

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
            'band_name' => 'required|string|max:255|unique:bands,band_name,' . $this->editingBand->id . ',id,year,' . $this->year,
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
        ]);

        $this->resetCache(true);
        $this->showEditForm = false;
        $this->resetBandForm();
        session()->flash('message', 'Band wurde erfolgreich aktualisiert!');
    }

    public function deleteBand($id)
    {
        $band = Band::select(['id', 'band_name'])->find($id);
        if ($band) {
            $bandName = $band->band_name;
            $band->delete();
            $this->resetCache(true);
            session()->flash('message', "{$bandName} wurde erfolgreich gelöscht!");
        }
    }

    // ===== MEMBER CRUD (OPTIMIERT) =====

    public function showMembers($bandId)
    {
        // Lazy Loading für Member-Details nur wenn benötigt
        $this->selectedBand = Band::select([
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
            ->with([
                'stage:id,name,voucher_amount,guest_allowed,backstage_all_days',
                'members' => function ($query) {
                    $query->select([
                        'id',
                        'band_id',
                        'first_name',
                        'last_name',
                        'present',
                        'backstage_day_1',
                        'backstage_day_2',
                        'backstage_day_3',
                        'backstage_day_4',
                        'voucher_day_1',
                        'voucher_day_2',
                        'voucher_day_3',
                        'voucher_day_4',
                        'remarks',
                        'responsible_person_id'
                    ])
                        ->with('responsiblePerson:id,first_name,last_name')
                        ->where('is_duplicate', false)
                        ->orderBy('first_name');
                },
                'vehiclePlates:id,band_id,license_plate'
            ])
            ->findOrFail($bandId);

        $this->closeAllModals();
    }

    public function addMember($bandId)
    {
        // Minimale Band-Daten für Mitglieder-Erstellung
        $this->selectedBand = Band::select([
            'id',
            'year',
            'stage_id',
            'plays_day_1',
            'plays_day_2',
            'plays_day_3',
            'plays_day_4'
        ])->with('stage:id,name,voucher_amount,backstage_all_days')
            ->findOrFail($bandId);

        $this->closeAllModals();
        $this->resetMemberForm();
        $this->loadStageDefaults();
        $this->showMemberForm = true;
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

        $stage = $this->selectedBand->stage;
        $backstageAccess = $this->calculateBackstageAccess($stage);
        $vouchers = $this->calculateVouchers($stage);

        $member = Person::create([
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

        // Background processing für Status Update
        dispatch(function () use ($member) {
            $member->band->updateAllPresentStatus();
        })->afterResponse();

        $this->smartRefresh(true, true);
        $this->showMemberForm = false;
        $this->resetMemberForm();
        session()->flash('message', 'Mitglied wurde erfolgreich hinzugefügt!');
    }

    /**
     * Optimierter Presence Toggle mit Batch Updates
     */
    public function toggleMemberPresence($memberId)
    {
        DB::transaction(function () use ($memberId) {
            $member = Person::select(['id', 'present', 'band_id'])
                ->findOrFail($memberId);

            $member->update(['present' => !$member->present]);

            // Background processing für Band-Status
            dispatch(function () use ($member) {
                $member->band->updateAllPresentStatus();
            })->afterResponse();
        });

        $this->smartRefresh(true, true);
    }

    /**
     * Batch Update für alle Mitglieder
     */
    public function updateAllMembersPresence($bandId, $present = true)
    {
        DB::transaction(function () use ($bandId, $present) {
            Person::where('band_id', $bandId)
                ->where('is_duplicate', false)
                ->update([
                    'present' => $present,
                    'updated_at' => now()
                ]);

            $band = Band::find($bandId);
            $band->updateAllPresentStatus();
        });

        $this->smartRefresh(true, true);

        $statusText = $present ? 'anwesend' : 'abwesend';
        session()->flash('message', "Alle Mitglieder wurden als {$statusText} markiert!");
    }

    // ===== HELPER METHODS (OPTIMIERT) =====

    private function closeAllModals()
    {
        $this->showCreateForm = false;
        $this->showEditForm = false;
        $this->showMemberForm = false;
        $this->showEditMemberForm = false;
        $this->showVehicleForm = false;
        $this->showGuestForm = false;
    }

    private function loadStageDefaults()
    {
        if (!$this->selectedBand || !$this->selectedBand->stage) {
            return;
        }

        $stage = $this->selectedBand->stage;
        $backstageAccess = $this->calculateBackstageAccess($stage);
        $vouchers = $this->calculateVouchers($stage);

        $this->backstage_day_1 = $backstageAccess['day_1'];
        $this->backstage_day_2 = $backstageAccess['day_2'];
        $this->backstage_day_3 = $backstageAccess['day_3'];
        $this->backstage_day_4 = $backstageAccess['day_4'];

        $this->voucher_day_1 = $vouchers['day_1'] > 0 ? $vouchers['day_1'] : '';
        $this->voucher_day_2 = $vouchers['day_2'] > 0 ? $vouchers['day_2'] : '';
        $this->voucher_day_3 = $vouchers['day_3'] > 0 ? $vouchers['day_3'] : '';
        $this->voucher_day_4 = $vouchers['day_4'] > 0 ? $vouchers['day_4'] : '';
    }

    private function calculateBackstageAccess($stage)
    {
        $access = array_fill_keys(['day_1', 'day_2', 'day_3', 'day_4'], false);

        if ($stage->backstage_all_days ?? false) {
            return array_fill_keys(['day_1', 'day_2', 'day_3', 'day_4'], true);
        }

        foreach ([1, 2, 3, 4] as $day) {
            if ($this->selectedBand->{"plays_day_{$day}"}) {
                $access["day_{$day}"] = true;
            }
        }

        return $access;
    }

    private function calculateVouchers($stage)
    {
        $vouchers = array_fill_keys(['day_1', 'day_2', 'day_3', 'day_4'], 0);
        $voucherAmount = $stage->voucher_amount ?? 0;

        foreach ([1, 2, 3, 4] as $day) {
            if ($this->selectedBand->{"plays_day_{$day}"}) {
                $vouchers["day_{$day}"] = $voucherAmount;
            }
        }

        return $vouchers;
    }

    // ===== MEMBER CRUD - COMPLETE (OPTIMIERT) =====

    public function editMember($memberId)
    {
        // Optimierte Einzelmitglied-Abfrage
        $this->editingMember = Person::select([
            'id',
            'first_name',
            'last_name',
            'present',
            'backstage_day_1',
            'backstage_day_2',
            'backstage_day_3',
            'backstage_day_4',
            'voucher_day_1',
            'voucher_day_2',
            'voucher_day_3',
            'voucher_day_4',
            'remarks',
            'band_id'
        ])->findOrFail($memberId);

        $this->closeAllModals();

        // Alle Felder setzen
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

        // Optimiertes Update mit nur geänderten Feldern
        $updateData = [
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
        ];

        DB::transaction(function () use ($updateData) {
            $this->editingMember->update($updateData);

            // Background processing für Band-Status
            dispatch(function () {
                $this->editingMember->band->updateAllPresentStatus();
            })->afterResponse();
        });

        $this->smartRefresh(true, true);
        $this->showEditMemberForm = false;
        $this->resetMemberForm();
        session()->flash('message', 'Mitglied wurde erfolgreich aktualisiert!');
    }

    public function deleteMember($memberId)
    {
        DB::transaction(function () use ($memberId) {
            $member = Person::select(['id', 'first_name', 'last_name', 'band_id'])
                ->findOrFail($memberId);

            $memberName = $member->first_name . ' ' . $member->last_name;
            $bandId = $member->band_id;

            // Erst Gäste löschen falls vorhanden
            Person::where('responsible_person_id', $member->id)->delete();

            // Dann das Mitglied selbst
            $member->delete();

            // Background processing für Band-Status
            dispatch(function () use ($bandId) {
                if ($band = Band::find($bandId)) {
                    $band->updateAllPresentStatus();
                }
            })->afterResponse();

            $this->smartRefresh(true, true);
            session()->flash('message', "{$memberName} wurde erfolgreich entfernt!");
        });
    }

    // ===== VEHICLE CRUD (OPTIMIERT) =====

    public function addVehicle($bandId)
    {
        $this->selectedBand = Band::select(['id', 'band_name'])
            ->findOrFail($bandId);
        $this->closeAllModals();
        $this->license_plate = '';
        $this->showVehicleForm = true;
    }

    public function saveVehicle()
    {
        $this->validate([
            'license_plate' => 'required|string|max:20|unique:vehicle_plates,license_plate',
        ]);

        VehiclePlate::create([
            'license_plate' => strtoupper(trim($this->license_plate)),
            'band_id' => $this->selectedBand->id,
        ]);

        $this->smartRefresh(true);
        $this->showVehicleForm = false;
        $this->license_plate = '';
        session()->flash('message', 'KFZ-Kennzeichen wurde erfolgreich hinzugefügt!');
    }

    public function deleteVehicle($vehicleId)
    {
        DB::transaction(function () use ($vehicleId) {
            $vehicle = VehiclePlate::select(['id', 'license_plate'])
                ->findOrFail($vehicleId);

            $licensePlate = $vehicle->license_plate;
            $vehicle->delete();

            $this->smartRefresh(true);
            session()->flash('message', "KFZ-Kennzeichen {$licensePlate} wurde erfolgreich entfernt!");
        });
    }

    public function cancelVehicleForm()
    {
        $this->showVehicleForm = false;
        $this->license_plate = '';
    }

    // ===== GUEST CRUD (OPTIMIERT) =====

    public function addGuest($memberId)
    {
        // Optimierte Mitglieder-Abfrage mit Guest-Check
        $this->selectedMember = Person::select([
            'id',
            'first_name',
            'last_name',
            'band_id'
        ])
            ->with([
                'band:id,band_name,stage_id,plays_day_1,plays_day_2,plays_day_3,plays_day_4,year',
                'band.stage:id,name,guest_allowed'
            ])
            ->withCount('responsibleFor as guests_count')
            ->findOrFail($memberId);

        // Prüfen ob bereits ein Gast existiert
        if ($this->selectedMember->guests_count > 0) {
            session()->flash('error', 'Dieses Mitglied hat bereits einen Gast!');
            return;
        }

        // Band-Daten für Guest-Erstellung laden
        $this->selectedBand = $this->selectedMember->band;

        $this->closeAllModals();
        $this->guest_first_name = '';
        $this->guest_last_name = '';
        $this->showGuestForm = true;
    }

    public function saveGuest()
    {
        $this->validate([
            'guest_first_name' => 'required|string|max:255',
            'guest_last_name' => 'required|string|max:255',
        ]);

        // Double-check für Race Conditions
        $existingGuestCount = Person::where('responsible_person_id', $this->selectedMember->id)
            ->count();

        if ($existingGuestCount > 0) {
            session()->flash('error', 'Dieses Mitglied hat bereits einen Gast!');
            return;
        }

        DB::transaction(function () {
            // Bühnen-Vorgaben für Gast berechnen
            $stage = $this->selectedBand->stage;
            $guestBackstageAccess = [];
            $guestVouchers = [];

            if ($stage && $stage->guest_allowed) {
                // Gast bekommt nur an Auftrittstagen Zugang
                foreach ([1, 2, 3, 4] as $day) {
                    $guestBackstageAccess["day_{$day}"] = $this->selectedBand->{"plays_day_{$day}"};
                    $guestVouchers["day_{$day}"] = 0; // Gäste bekommen standardmäßig keine Voucher
                }
            } else {
                // Keine Berechtigung wenn Stage keine Gäste erlaubt
                foreach ([1, 2, 3, 4] as $day) {
                    $guestBackstageAccess["day_{$day}"] = false;
                    $guestVouchers["day_{$day}"] = 0;
                }
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
                'responsible_person_id' => $this->selectedMember->id,
                'year' => $this->selectedBand->year,
            ]);
        });

        $this->smartRefresh(true, true);
        $this->showGuestForm = false;
        $this->guest_first_name = '';
        $this->guest_last_name = '';
        session()->flash('message', 'Gast wurde erfolgreich hinzugefügt!');
    }

    public function deleteGuest($guestId)
    {
        DB::transaction(function () use ($guestId) {
            $guest = Person::select(['id', 'first_name', 'last_name'])
                ->where('responsible_person_id', '!=', null)
                ->findOrFail($guestId);

            $guestName = $guest->first_name . ' ' . $guest->last_name;
            $guest->delete();

            $this->smartRefresh(true, true);
            session()->flash('message', "Gast {$guestName} wurde erfolgreich entfernt!");
        });
    }

    public function cancelGuestForm()
    {
        $this->showGuestForm = false;
        $this->guest_first_name = '';
        $this->guest_last_name = '';
        $this->selectedMember = null;
    }

    // ===== BULK OPERATIONS (BONUS) =====

    /**
     * Alle Mitglieder einer Band als anwesend markieren
     */
    public function markAllMembersPresent($bandId)
    {
        $this->updateAllMembersPresence($bandId, true);
    }

    /**
     * Alle Mitglieder einer Band als abwesend markieren
     */
    public function markAllMembersAbsent($bandId)
    {
        $this->updateAllMembersPresence($bandId, false);
    }

    /**
     * Optimierte Voucher-Neuberechnung für alle Mitglieder
     */
    public function recalculateVouchersForBand($bandId)
    {
        DB::transaction(function () use ($bandId) {
            $band = Band::with('stage', 'members')->findOrFail($bandId);
            $stage = $band->stage;

            if (!$stage) return;

            $voucherAmount = $stage->voucher_amount ?? 0;

            foreach ($band->members as $member) {
                $updateData = [];

                // Voucher nur an Spieltagen
                foreach ([1, 2, 3, 4] as $day) {
                    $updateData["voucher_day_{$day}"] = $band->{"plays_day_{$day}"} ? $voucherAmount : 0;
                }

                $member->update($updateData);
            }
        });

        $this->smartRefresh(true);
        session()->flash('message', 'Voucher für alle Mitglieder wurden neu berechnet!');
    }

    // ===== ADVANCED SEARCH & FILTERING =====

    /**
     * Erweiterte Suchoptionen
     */
    public function searchInMembers($bandId, $searchTerm)
    {
        if (!$searchTerm || strlen($searchTerm) < 2) {
            return $this->selectedBand->members;
        }

        return $this->selectedBand->members->filter(function ($member) use ($searchTerm) {
            $fullName = strtolower($member->first_name . ' ' . $member->last_name);
            $search = strtolower($searchTerm);

            return str_contains($fullName, $search) ||
                str_contains(strtolower($member->remarks ?? ''), $search);
        });
    }

    // ===== VALIDATION HELPERS =====

    /**
     * Erweiterte Band-Validierung
     */
    private function validateBandConstraints()
    {
        $rules = [
            'band_name' => 'required|string|max:255',
            'stage_id' => 'required|exists:stages,id',
            'travel_costs' => 'nullable|numeric|min:0|max:99999.99',
        ];

        // Unique Constraint für Band + Jahr
        if ($this->editingBand) {
            $rules['band_name'] .= '|unique:bands,band_name,' . $this->editingBand->id . ',id,year,' . $this->year;
        } else {
            $rules['band_name'] .= '|unique:bands,band_name,NULL,id,year,' . $this->year;
        }

        // Mindestens ein Spieltag muss ausgewählt sein
        $this->validate($rules);

        if (!($this->plays_day_1 || $this->plays_day_2 || $this->plays_day_3 || $this->plays_day_4)) {
            $this->addError('plays_day_1', 'Mindestens ein Spieltag muss ausgewählt werden.');
            return false;
        }

        return true;
    }

    /**
     * Stage-spezifische Validierung
     */
    private function validateStageConstraints($stageId)
    {
        $stage = collect($this->getCachedStages())->firstWhere('id', $stageId);

        if (!$stage) return false;

        // Beispiel: Maximale Anzahl Bands pro Bühne prüfen
        $existingBandsCount = Band::where('stage_id', $stageId)
            ->where('year', $this->year)
            ->when($this->editingBand, function ($q) {
                return $q->where('id', '!=', $this->editingBand->id);
            })
            ->count();

        $maxBandsPerStage = $stage->max_bands ?? 999; // Aus Stage-Model

        if ($existingBandsCount >= $maxBandsPerStage) {
            $this->addError('stage_id', "Diese Bühne kann maximal {$maxBandsPerStage} Bands haben.");
            return false;
        }

        return true;
    }

    // ===== FORM RESET METHODS =====

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

    public function backToBandList()
    {
        $this->selectedBand = null;
        $this->closeAllModals();
        $this->resetMemberForm();
    }

    // ===== DEBUG METHODS =====

    private function logQueries()
    {
        if (app()->environment('local')) {
            $queries = DB::getQueryLog();
            \Log::info('Band Management Queries:', [
                'search_term' => $this->search,
                'total_queries' => count($queries),
                'queries' => array_map(function ($query) {
                    return [
                        'sql' => $query['query'],
                        'time' => $query['time'] . 'ms',
                        'bindings' => $query['bindings']
                    ];
                }, $queries)
            ]);

            // Reset query log for next request
            DB::flushQueryLog();
        }
    }
}
