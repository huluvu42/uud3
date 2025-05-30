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

    public function clearFilters()
    {
        $this->filterUser = '';
        $this->filterTable = '';
        $this->filterAction = '';
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

        $changes = $query->paginate(20);

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