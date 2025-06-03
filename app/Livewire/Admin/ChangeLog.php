<?php
// app/Livewire/Admin/ChangeLog.php
namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\ChangeLog as ChangeLogModel;
use Livewire\WithPagination;

class ChangeLog extends Component
{
    use WithPagination;

    public $filterUser = '';
    public $filterTable = '';
    public $filterAction = '';
    public $filterRecord = ''; // Neue Suche nach Datensatz-Inhalt

    public function resetChanges($changeId)
    {
        if (!auth()->user()->can_reset_changes) {
            session()->flash('error', 'Keine Berechtigung zum Zurücksetzen von Änderungen!');
            return;
        }

        $change = ChangeLogModel::find($changeId);
        if (!$change) {
            session()->flash('error', 'Änderung nicht gefunden!');
            return;
        }

        // Find the model and revert the change
        $modelClass = $this->getModelClass($change->table_name);
        if (!$modelClass) {
            session()->flash('error', 'Model-Klasse nicht gefunden!');
            return;
        }

        $model = $modelClass::find($change->record_id);
        if (!$model) {
            session()->flash('error', 'Datensatz nicht gefunden!');
            return;
        }

        // Revert the change
        $oldValue = $change->old_value;
        $currentValue = $model->{$change->field_name};

        // Only revert if the current value matches what we expect
        if ($currentValue == $change->new_value) {
            $model->update([$change->field_name => $oldValue]);
            
            // Log the revert action
            ChangeLogModel::logChange($model, $change->field_name, $change->new_value, $oldValue, 'revert');
            
            session()->flash('success', 'Änderung erfolgreich rückgängig gemacht!');
        } else {
            session()->flash('error', 'Änderung konnte nicht rückgängig gemacht werden - Wert wurde zwischenzeitlich geändert!');
        }
    }

    private function getModelClass($tableName)
    {
        $models = [
            'persons' => \App\Models\Person::class,
            'bands' => \App\Models\Band::class,
            'users' => \App\Models\User::class,
            'groups' => \App\Models\Group::class,
            'subgroups' => \App\Models\Subgroup::class,
            'stages' => \App\Models\Stage::class,
            'band_guests' => \App\Models\BandGuest::class,
            'voucher_purchases' => \App\Models\VoucherPurchase::class,
            'vehicle_plates' => \App\Models\VehiclePlate::class,
            'settings' => \App\Models\Settings::class,
            'field_labels' => \App\Models\FieldLabel::class,
        ];

        return $models[$tableName] ?? null;
    }

    // Neue Methode: Hole zusätzliche Informationen über den geänderten Datensatz
    public function getRecordInfo($change)
    {
        $modelClass = $this->getModelClass($change->table_name);
        if (!$modelClass) return null;

        $model = $modelClass::find($change->record_id);
        if (!$model) return null;

        // Je nach Tabelle verschiedene Anzeige-Logik
        switch ($change->table_name) {
            case 'persons':
                return [
                    'title' => ($model->first_name ?? '') . ' ' . ($model->last_name ?? ''),
                    'subtitle' => $model->band ? $model->band->band_name : null,
                    'icon' => '👤'
                ];
            case 'bands':
                return [
                    'title' => $model->band_name ?? 'Unbekannte Band',
                    'subtitle' => $model->members ? $model->members->count() . ' Mitglieder' : null,
                    'icon' => '🎵'
                ];
            case 'groups':
                return [
                    'title' => $model->name ?? 'Unbekannte Gruppe',
                    'subtitle' => null,
                    'icon' => '👥'
                ];
            case 'stages':
                return [
                    'title' => $model->name ?? 'Unbekannte Bühne',
                    'subtitle' => null,
                    'icon' => '🎪'
                ];
            case 'users':
                return [
                    'title' => ($model->first_name ?? '') . ' ' . ($model->last_name ?? ''),
                    'subtitle' => $model->username ?? null,
                    'icon' => '👤'
                ];
            case 'voucher_purchases':
                return [
                    'title' => $model->amount . ' Voucher',
                    'subtitle' => $model->stage ? $model->stage->name : null,
                    'icon' => '🎫'
                ];
            default:
                return [
                    'title' => 'ID: ' . $change->record_id,
                    'subtitle' => null,
                    'icon' => '📄'
                ];
        }
    }

    public function updatingFilterUser()
    {
        $this->resetPage();
    }

    public function updatingFilterTable()
    {
        $this->resetPage();
    }

    public function updatingFilterAction()
    {
        $this->resetPage();
    }

    public function updatingFilterRecord()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->filterUser = '';
        $this->filterTable = '';
        $this->filterAction = '';
        $this->filterRecord = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = ChangeLogModel::with('user')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($this->filterUser) {
            $query->whereHas('user', function($q) {
                $q->where('first_name', 'ILIKE', '%' . $this->filterUser . '%')
                  ->orWhere('last_name', 'ILIKE', '%' . $this->filterUser . '%')
                  ->orWhere('username', 'ILIKE', '%' . $this->filterUser . '%');
            });
        }

        if ($this->filterTable) {
            $query->where('table_name', $this->filterTable);
        }

        if ($this->filterAction) {
            $query->where('action', $this->filterAction);
        }

        // Neue Suche nach Datensatz-Inhalt
        if ($this->filterRecord) {
            $query->where(function($q) {
                // Suche in verschiedenen relevanten Feldern
                $q->where('old_value', 'ILIKE', '%' . $this->filterRecord . '%')
                  ->orWhere('new_value', 'ILIKE', '%' . $this->filterRecord . '%')
                  ->orWhere('field_name', 'ILIKE', '%' . $this->filterRecord . '%');
                
                // Suche auch in verknüpften Modellen
                $q->orWhereExists(function($subQuery) {
                    $subQuery->selectRaw('1')
                        ->from('persons')
                        ->whereColumn('persons.id', 'change_logs.record_id')
                        ->where('change_logs.table_name', 'persons')
                        ->where(function($personQuery) {
                            $personQuery->where('first_name', 'ILIKE', '%' . $this->filterRecord . '%')
                                ->orWhere('last_name', 'ILIKE', '%' . $this->filterRecord . '%');
                        });
                });
                
                $q->orWhereExists(function($subQuery) {
                    $subQuery->selectRaw('1')
                        ->from('bands')
                        ->whereColumn('bands.id', 'change_logs.record_id')
                        ->where('change_logs.table_name', 'bands')
                        ->where('band_name', 'ILIKE', '%' . $this->filterRecord . '%');
                });
                
                $q->orWhereExists(function($subQuery) {
                    $subQuery->selectRaw('1')
                        ->from('groups')
                        ->whereColumn('groups.id', 'change_logs.record_id')
                        ->where('change_logs.table_name', 'groups')
                        ->where('name', 'ILIKE', '%' . $this->filterRecord . '%');
                });
                
                $q->orWhereExists(function($subQuery) {
                    $subQuery->selectRaw('1')
                        ->from('stages')
                        ->whereColumn('stages.id', 'change_logs.record_id')
                        ->where('change_logs.table_name', 'stages')
                        ->where('name', 'ILIKE', '%' . $this->filterRecord . '%');
                });
            });
        }

        $changes = $query->paginate(25);

        // Get available tables for filter dropdown
        $availableTables = ChangeLogModel::select('table_name')
            ->distinct()
            ->orderBy('table_name')
            ->pluck('table_name');

        return view('livewire.admin.change-log', [
            'changes' => $changes,
            'canReset' => auth()->user()->can_reset_changes,
            'availableTables' => $availableTables
        ]);
    }
}