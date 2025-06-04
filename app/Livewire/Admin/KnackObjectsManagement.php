<?php
// app/Livewire/Admin/KnackObjectsManagement.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\KnackObject;
use App\Models\Group;

class KnackObjectsManagement extends Component
{
    public $knackObjects = [];
    public $showModal = false;
    public $editingId = null;

    // Form fields
    public $name = '';
    public $object_key = '';
    public $app_id = '';
    public $api_key = '';
    public $description = '';
    public $active = true;
    public $showApiKey = false;

    // Zusätzliche Variablen die in der Blade verwendet werden
    public $filterByYear = true;
    public $year;

    // Filter Arrays (falls in der Blade verwendet)
    public $includeFilters = [];
    public $excludeFilters = [];
    public $newIncludeFilter = '';
    public $newExcludeFilter = '';

    // Preview/Import Daten (falls in der Blade verwendet)
    public $previewData = [];
    public $showPreview = false;
    public $importResults = null;

    // API Einstellungen (falls in der Blade verwendet)
    public $appId = '';
    public $apiKey = '';
    public $selectedKnackObjectId = null;
    public $selectedGroupId = null;
    public $knackYearField = 'Jahr';

    protected $rules = [
        'name' => 'required|string|max:255',
        'object_key' => 'required|string|max:50',
        'app_id' => 'required|string|max:255',
        'api_key' => 'required|string|min:10|max:500',
        'description' => 'nullable|string|max:1000',
        'active' => 'boolean',
    ];

    public function mount()
    {
        $this->year = now()->year;
        $this->loadKnackObjects();
    }

    public function loadKnackObjects()
    {
        $this->knackObjects = KnackObject::orderBy('name')->get();
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function openEditModal($id)
    {
        $knackObject = KnackObject::find($id);
        if ($knackObject) {
            $this->editingId = $id;
            $this->name = $knackObject->name;
            $this->object_key = $knackObject->object_key;
            $this->app_id = $knackObject->app_id ?? '';
            $this->api_key = $knackObject->getApiKey() ?? '';
            $this->description = $knackObject->description ?? '';
            $this->active = $knackObject->active;
            $this->showApiKey = false;
            $this->showModal = true;
        }
    }

    public function save()
    {
        $this->validate();

        // API Key Validierung mit Service
        $knackApiService = app(\App\Services\KnackApiService::class);
        if (!$knackApiService->validateApiKey($this->api_key)) {
            $this->addError('api_key', 'Ungültiges API Key Format');
            return;
        }

        try {
            if ($this->editingId) {
                // Update existing
                $knackObject = KnackObject::find($this->editingId);
                $knackObject->update([
                    'name' => $this->name,
                    'object_key' => $this->object_key,
                    'app_id' => $this->app_id,
                    'description' => $this->description ?: null,
                    'active' => $this->active,
                ]);

                // API Key separat setzen (verschlüsselt)
                $knackObject->setApiKey($this->api_key);
                $knackObject->save();

                session()->flash('success', 'Knack Object aktualisiert!');
            } else {
                // Create new
                $knackObject = KnackObject::create([
                    'name' => $this->name,
                    'object_key' => $this->object_key,
                    'app_id' => $this->app_id,
                    'description' => $this->description ?: null,
                    'active' => $this->active,
                ]);

                // API Key separat setzen (verschlüsselt)
                $knackObject->setApiKey($this->api_key);
                $knackObject->save();

                session()->flash('success', 'Knack Object erstellt!');
            }

            $this->closeModal();
            $this->loadKnackObjects();
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Speichern: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            KnackObject::find($id)->delete();
            session()->flash('success', 'Knack Object gelöscht!');
            $this->loadKnackObjects();
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Löschen: ' . $e->getMessage());
        }
    }

    public function toggleActive($id)
    {
        try {
            $knackObject = KnackObject::find($id);
            $knackObject->update(['active' => !$knackObject->active]);
            session()->flash('success', 'Status geändert!');
            $this->loadKnackObjects();
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Ändern des Status: ' . $e->getMessage());
        }
    }

    public function testConnection($id)
    {
        try {
            $knackObject = KnackObject::find($id);
            if (!$knackObject) {
                session()->flash('error', 'Knack Object nicht gefunden!');
                return;
            }

            $knackApiService = app(\App\Services\KnackApiService::class);
            $result = $knackApiService->testConnection($knackObject);

            if ($result['success']) {
                session()->flash('success', 'Verbindungstest erfolgreich: ' . $result['message']);
            } else {
                session()->flash('error', 'Verbindungstest fehlgeschlagen: ' . $result['message']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Verbindungstest: ' . $e->getMessage());
        }
    }

    public function toggleShowApiKey()
    {
        $this->showApiKey = !$this->showApiKey;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->name = '';
        $this->object_key = '';
        $this->app_id = '';
        $this->api_key = '';
        $this->description = '';
        $this->active = true;
        $this->editingId = null;
        $this->showApiKey = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        $groups = Group::orderBy('name')->get();

        return view('livewire.admin.knack-objects-management', [
            'groups' => $groups,
            'filterByYear' => $this->filterByYear,
            'year' => $this->year
        ]);
    }
}
