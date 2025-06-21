<?php

// ============================================================================
// app/Livewire/Admin/BandManagerImport.php
// Import-Interface für Manager-Daten
// ============================================================================

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Band;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

class BandManagerImport extends Component
{
    use WithFileUploads;

    public $file;
    public $previewData = [];
    public $columnMapping = [];
    public $availableColumns = [];
    public $step = 1;
    public $isLoading = false;

    protected $rules = [
        'file' => 'required|mimes:csv,xlsx,xls|max:10240', // 10MB max
    ];

    public function updatedFile()
    {
        $this->validate();
        $this->processFile();
    }

    private function processFile()
    {
        $this->isLoading = true;

        try {
            $path = $this->file->store('temp-imports');
            $fullPath = storage_path('app/' . $path);

            // Excel-Datei laden
            $data = Excel::toArray(new \stdClass, $fullPath)[0];

            if (empty($data)) {
                session()->flash('error', 'Die Datei scheint leer zu sein.');
                return;
            }

            $this->availableColumns = $data[0] ?? [];
            $this->previewData = array_slice($data, 0, 6); // Header + 5 Zeilen

            // Standard-Mapping versuchen
            $this->autoMapColumns();

            $this->step = 2;
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Verarbeiten der Datei: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    private function autoMapColumns()
    {
        $mappings = [
            'band_name' => ['band', 'bandname', 'band_name', 'name'],
            'manager_first_name' => ['vorname', 'firstname', 'first_name', 'manager_vorname'],
            'manager_last_name' => ['nachname', 'lastname', 'last_name', 'manager_nachname'],
            'manager_email' => ['email', 'e-mail', 'manager_email', 'kontakt'],
            'manager_phone' => ['telefon', 'phone', 'tel', 'handy', 'manager_phone'],
        ];

        foreach ($mappings as $field => $possibilities) {
            foreach ($this->availableColumns as $index => $column) {
                if (in_array(strtolower(trim($column)), $possibilities)) {
                    $this->columnMapping[$field] = $index;
                    break;
                }
            }
        }
    }

    public function importManagerData()
    {
        $this->validate([
            'columnMapping.band_name' => 'required|integer',
            'columnMapping.manager_first_name' => 'required|integer',
            'columnMapping.manager_last_name' => 'required|integer',
            'columnMapping.manager_email' => 'required|integer',
        ], [
            'columnMapping.band_name.required' => 'Band-Name Spalte muss ausgewählt werden.',
            'columnMapping.manager_first_name.required' => 'Manager Vorname Spalte muss ausgewählt werden.',
            'columnMapping.manager_last_name.required' => 'Manager Nachname Spalte muss ausgewählt werden.',
            'columnMapping.manager_email.required' => 'Manager Email Spalte muss ausgewählt werden.',
        ]);

        $imported = 0;
        $updated = 0;
        $errors = [];
        $skipped = 0;

        // Header überspringen, nur Datenzeilen verarbeiten
        $dataRows = array_slice($this->previewData, 1);

        foreach ($dataRows as $rowIndex => $row) {
            try {
                $bandName = trim($row[$this->columnMapping['band_name']] ?? '');
                if (empty($bandName)) {
                    $skipped++;
                    continue;
                }

                $band = Band::where('band_name', $bandName)->first();
                if (!$band) {
                    $errors[] = "Zeile " . ($rowIndex + 2) . ": Band nicht gefunden: {$bandName}";
                    continue;
                }

                $managerData = [
                    'manager_first_name' => trim($row[$this->columnMapping['manager_first_name']] ?? ''),
                    'manager_last_name' => trim($row[$this->columnMapping['manager_last_name']] ?? ''),
                    'manager_email' => trim($row[$this->columnMapping['manager_email']] ?? ''),
                    'manager_phone' => isset($this->columnMapping['manager_phone'])
                        ? trim($row[$this->columnMapping['manager_phone']] ?? '')
                        : null,
                ];

                // Validierung der Daten
                $validator = Validator::make($managerData, [
                    'manager_first_name' => 'required|string|max:255',
                    'manager_last_name' => 'required|string|max:255',
                    'manager_email' => 'required|email|max:255',
                    'manager_phone' => 'nullable|string|max:50',
                ]);

                if ($validator->fails()) {
                    $errors[] = "Zeile " . ($rowIndex + 2) . " ({$bandName}): " . implode(', ', $validator->errors()->all());
                    continue;
                }

                $wasEmpty = empty($band->manager_email);
                $band->update($managerData);

                if ($wasEmpty) {
                    $imported++;
                } else {
                    $updated++;
                }
            } catch (\Exception $e) {
                $errors[] = "Zeile " . ($rowIndex + 2) . ": " . $e->getMessage();
            }
        }

        $message = "Import abgeschlossen! Neu: {$imported}, Aktualisiert: {$updated}";
        if ($skipped > 0) {
            $message .= ", Übersprungen: {$skipped}";
        }
        if (!empty($errors)) {
            $message .= ", Fehler: " . count($errors);
        }

        session()->flash('message', $message);

        if (!empty($errors)) {
            session()->flash('errors', array_slice($errors, 0, 10)); // Nur erste 10 Fehler anzeigen
        }

        $this->reset(['file', 'previewData', 'columnMapping', 'step']);
    }

    public function resetImport()
    {
        $this->reset(['file', 'previewData', 'columnMapping', 'step']);
    }

    public function render()
    {
        return view('livewire.admin.band-manager-import');
    }
}
