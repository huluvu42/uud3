<?php
// app/Livewire/Admin/KnackImport.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Person;
use App\Models\Group;
use App\Models\KnackObject;
use App\Services\KnackApiService;

class KnackImport extends Component
{
    // Knack Object Auswahl (ersetzt die direkten API Felder)
    public $selectedKnackObjectId = null;

    // Filter Einstellungen
    public $includeFilters = [];
    public $excludeFilters = [];
    public $newIncludeFilter = '';
    public $newExcludeFilter = '';
    public $filterByYear = false;
    public $knackYearField = 'Jahr';

    // Import Einstellungen
    public $selectedGroupId = null;
    public $year;

    // Status
    public $isLoading = false;
    public $previewData = [];
    public $showPreview = false;
    public $importResults = null;

    public function mount()
    {
        $this->year = now()->year;
    }

    public function addIncludeFilter()
    {
        if (!empty(trim($this->newIncludeFilter))) {
            $this->includeFilters[] = trim($this->newIncludeFilter);
            $this->reset('newIncludeFilter');
        }
    }

    public function removeIncludeFilter($index)
    {
        unset($this->includeFilters[$index]);
        $this->includeFilters = array_values($this->includeFilters);
    }

    public function addExcludeFilter()
    {
        if (!empty(trim($this->newExcludeFilter))) {
            $this->excludeFilters[] = trim($this->newExcludeFilter);
            $this->reset('newExcludeFilter');
        }
    }

    public function removeExcludeFilter($index)
    {
        unset($this->excludeFilters[$index]);
        $this->excludeFilters = array_values($this->excludeFilters);
    }

    public function loadPreview()
    {
        $this->validate([
            'selectedKnackObjectId' => 'required|exists:knack_objects,id',
        ]);

        // Knack Object laden und prüfen
        $knackObject = KnackObject::find($this->selectedKnackObjectId);
        if (!$knackObject) {
            session()->flash('error', 'Knack Object nicht gefunden!');
            return;
        }

        if (!$knackObject->isComplete()) {
            session()->flash('error', 'Knack Object ist nicht vollständig konfiguriert! App ID und API Key müssen gesetzt sein.');
            return;
        }

        $this->isLoading = true;
        $this->previewData = [];

        try {
            $knackApiService = app(KnackApiService::class);
            $allRecords = $knackApiService->getAllRecords($knackObject);

            // Debug: Ersten Record ausgeben
            if (!empty($allRecords)) {
                \Log::info('First record structure:', $allRecords[0]);
            }

            // Records filtern und verarbeiten
            $filteredRecords = [];

            foreach ($allRecords as $index => $record) {
                // Jahr-Filter anwenden (wenn aktiviert)
                if ($this->filterByYear && !$this->matchesYearFilter($record)) {
                    continue;
                }

                // Jobliste-Filter anwenden
                if (!empty($this->includeFilters) || !empty($this->excludeFilters)) {
                    if (!$this->matchesJoblisteFilters($record)) {
                        continue;
                    }
                }

                // Kontakt extrahieren
                $kontakt = $this->extractKontakt($record);
                if (!$kontakt) continue;

                [$firstName, $lastName] = $this->splitName($kontakt);

                // Duplikat-Prüfung mit Update-Logik
                $knackId = $record['id'] ?? null;
                $existingPerson = null;
                $updateReason = null;

                // 1. Priorität: Suche nach Knack ID (zuverlässigste Methode)
                if ($knackId) {
                    $existingPerson = Person::where('knack_id', $knackId)
                        ->where('year', $this->year)
                        ->where('is_duplicate', false)
                        ->first();

                    if ($existingPerson) {
                        $updateReason = 'Knack-ID Match';
                    }
                }

                // 2. Fallback: Suche nach Namen (nur wenn keine Knack ID Match)
                if (!$existingPerson) {
                    $existingPerson = Person::where('first_name', $firstName)
                        ->where('last_name', $lastName)
                        ->where('year', $this->year)
                        ->where('is_duplicate', false)
                        ->first();

                    if ($existingPerson) {
                        $updateReason = 'Name Match';
                    }
                }

                $filteredRecords[] = [
                    'knack_id' => $knackId,
                    'kontakt' => $kontakt,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'jobliste' => $this->extractJobliste($record),
                    'knack_year' => $this->extractYear($record),
                    'existing_person' => $existingPerson,
                    'update_reason' => $updateReason,
                    'action' => $existingPerson ? 'update' : 'create'
                ];
            }

            $this->previewData = $filteredRecords;
            $this->showPreview = true;

            // Erfolgs-Nachricht
            $totalRecords = count($allRecords);
            $filteredCount = count($filteredRecords);

            $filterInfo = [];
            if ($this->filterByYear) {
                $filterInfo[] = "Jahr {$this->year}";
            }
            if (!empty($this->includeFilters) || !empty($this->excludeFilters)) {
                $filterInfo[] = "Jobliste-Filter";
            }

            $filterText = empty($filterInfo) ? "ohne Filter" : "mit " . implode(" + ", $filterInfo);
            session()->flash('success', "$filteredCount von $totalRecords Datensätzen aus '{$knackObject->name}' gefunden ($filterText)!");
        } catch (\Exception $e) {
            $knackApiService = app(KnackApiService::class);
            $errorMessage = $knackApiService->handleApiError($e);
            session()->flash('error', 'Fehler beim Laden der Vorschau: ' . $errorMessage);
        } finally {
            $this->isLoading = false;
        }
    }

    public function executeImport()
    {
        $this->validate([
            'selectedGroupId' => 'required|exists:groups,id',
        ]);

        if (empty($this->previewData)) {
            session()->flash('error', 'Keine Daten zum Importieren! Bitte erst Vorschau laden.');
            return;
        }

        $this->isLoading = true;
        $group = Group::find($this->selectedGroupId);

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $errorMessages = [];

        try {
            foreach ($this->previewData as $data) {
                try {
                    if ($data['action'] === 'create') {
                        // Neue Person erstellen
                        Person::create([
                            'knack_id' => $data['knack_id'],
                            'first_name' => $data['first_name'],
                            'last_name' => $data['last_name'],
                            'group_id' => $group->id,
                            'year' => $this->year,
                            'present' => false,
                            'remarks' => $data['jobliste'],

                            // Backstage-Berechtigungen von der Gruppe übernehmen
                            'backstage_day_1' => $group->backstage_day_1 ?? false,
                            'backstage_day_2' => $group->backstage_day_2 ?? false,
                            'backstage_day_3' => $group->backstage_day_3 ?? false,
                            'backstage_day_4' => $group->backstage_day_4 ?? false,

                            // Voucher-Berechtigungen von der Gruppe übernehmen
                            'voucher_day_1' => $group->voucher_day_1 ?? 0,
                            'voucher_day_2' => $group->voucher_day_2 ?? 0,
                            'voucher_day_3' => $group->voucher_day_3 ?? 0,
                            'voucher_day_4' => $group->voucher_day_4 ?? 0,

                            // Ausgegebene Voucher auf 0 setzen
                            'voucher_issued_day_1' => 0,
                            'voucher_issued_day_2' => 0,
                            'voucher_issued_day_3' => 0,
                            'voucher_issued_day_4' => 0,
                        ]);
                        $imported++;
                    } elseif ($data['action'] === 'update') {
                        // Bestehende Person aktualisieren
                        $existingPerson = $data['existing_person'];

                        $updateData = [
                            'first_name' => $data['first_name'],
                            'last_name' => $data['last_name'],
                            'remarks' => $data['jobliste'],
                            'group_id' => $group->id,

                            // Backstage-Berechtigungen von der Gruppe übernehmen
                            'backstage_day_1' => $group->backstage_day_1 ?? false,
                            'backstage_day_2' => $group->backstage_day_2 ?? false,
                            'backstage_day_3' => $group->backstage_day_3 ?? false,
                            'backstage_day_4' => $group->backstage_day_4 ?? false,

                            // Voucher-Berechtigungen von der Gruppe übernehmen
                            'voucher_day_1' => $group->voucher_day_1 ?? 0,
                            'voucher_day_2' => $group->voucher_day_2 ?? 0,
                            'voucher_day_3' => $group->voucher_day_3 ?? 0,
                            'voucher_day_4' => $group->voucher_day_4 ?? 0,
                        ];

                        // Knack ID setzen falls nicht vorhanden
                        if ($data['knack_id'] && !$existingPerson->knack_id) {
                            $updateData['knack_id'] = $data['knack_id'];
                        }

                        $existingPerson->update($updateData);
                        $updated++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $errorMessages[] = "Fehler bei {$data['first_name']} {$data['last_name']}: " . $e->getMessage();
                }
            }

            $this->importResults = [
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
                'error_messages' => $errorMessages
            ];

            session()->flash('success', "Import abgeschlossen! $imported neu erstellt, $updated aktualisiert, $errors Fehler.");
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Import: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function resetImport()
    {
        $this->previewData = [];
        $this->showPreview = false;
        $this->importResults = null;
    }

    // Hilfsmethode: Testen der API-Verbindung
    public function testConnection()
    {
        if (!$this->selectedKnackObjectId) {
            session()->flash('error', 'Bitte wählen Sie zuerst ein Knack Object aus.');
            return;
        }

        $knackObject = KnackObject::find($this->selectedKnackObjectId);
        if (!$knackObject) {
            session()->flash('error', 'Knack Object nicht gefunden!');
            return;
        }

        $knackApiService = app(KnackApiService::class);
        $result = $knackApiService->testConnection($knackObject);

        if ($result['success']) {
            session()->flash('success', 'Verbindungstest erfolgreich: ' . $result['message']);
        } else {
            session()->flash('error', 'Verbindungstest fehlgeschlagen: ' . $result['message']);
        }
    }

    // Private Hilfsmethoden (bleiben unverändert)
    private function matchesYearFilter($record)
    {
        $recordYear = $this->extractYear($record);

        if (!$recordYear) {
            return false;
        }

        return intval($recordYear) === intval($this->year);
    }

    private function matchesJoblisteFilters($record)
    {
        $jobliste = $this->extractJobliste($record);

        // Include-Filter prüfen (wenn vorhanden)
        if (!empty($this->includeFilters)) {
            $includeMatch = false;
            foreach ($this->includeFilters as $filter) {
                if (stripos($jobliste, $filter) !== false) {
                    $includeMatch = true;
                    break;
                }
            }
            if (!$includeMatch) {
                return false;
            }
        }

        // Exclude-Filter prüfen (wenn vorhanden)
        if (!empty($this->excludeFilters)) {
            foreach ($this->excludeFilters as $filter) {
                if (stripos($jobliste, $filter) !== false) {
                    return false;
                }
            }
        }

        return true;
    }

    private function extractYear($record)
    {
        // Ihre echten Feldnamen basierend auf JSON Export
        $possibleFields = [
            'field_405_raw',
            'field_405',  // Ihr Jahr-Feld
            'field_jahr_raw',
            'field_jahr',
            'jahr_raw',
            'jahr',
            'field_year_raw',
            'field_year',
            'year_raw',
            'year',
            'Jahr_raw',
            'Jahr',
            'Year_raw',
            'Year'
        ];

        foreach ($possibleFields as $fieldKey) {
            if (isset($record[$fieldKey])) {
                $value = $record[$fieldKey];

                // Direkter String/Number Wert (wie field_405_raw: "2025")
                if (is_string($value) || is_numeric($value)) {
                    if (preg_match('/(\d{4})/', $value, $matches)) {
                        return intval($matches[1]);
                    }
                }

                // Array mit label (falls anders strukturiert)
                if (is_array($value) && isset($value['label'])) {
                    $yearValue = $value['label'];
                    if (preg_match('/(\d{4})/', $yearValue, $matches)) {
                        return intval($matches[1]);
                    }
                }
            }
        }

        return null;
    }

    private function extractKontakt($record)
    {
        // Ihre echten Feldnamen basierend auf JSON Export
        $possibleFields = [
            'field_194_raw',
            'field_194',  // Ihr Name-Feld
            'field_kontakt_raw',
            'field_kontakt',
            'kontakt_raw',
            'kontakt',
            'field_name_raw',
            'field_name',
            'name_raw',
            'name',
            'contact_raw',
            'contact',
            'full_name_raw',
            'full_name',
            'Kontakt_raw',
            'Kontakt'
        ];

        foreach ($possibleFields as $fieldKey) {
            if (isset($record[$fieldKey])) {
                $value = $record[$fieldKey];

                // Array mit identifier (wie in Ihrem JSON)
                if (is_array($value) && !empty($value)) {
                    if (isset($value[0]['identifier'])) {
                        return trim($value[0]['identifier']);
                    }
                    // Fallback für label
                    if (isset($value[0]['label'])) {
                        return trim($value[0]['label']);
                    }
                }

                // Direkter String-Wert
                if (is_string($value)) {
                    return trim($value);
                }

                // Objekt mit label/identifier
                if (is_array($value) && isset($value['identifier'])) {
                    return trim($value['identifier']);
                }
                if (is_array($value) && isset($value['label'])) {
                    return trim($value['label']);
                }
            }
        }

        return null;
    }

    private function extractJobliste($record)
    {
        // Ihre echten Feldnamen basierend auf JSON Export
        $possibleFields = [
            'field_195_raw',
            'field_195',  // Ihr Gruppen/Jobliste-Feld
            'field_jobliste_raw',
            'field_jobliste',
            'jobliste_raw',
            'jobliste',
            'field_job_liste_raw',
            'field_job_liste',
            'job_liste_raw',
            'job_liste',
            'field_gruppe_raw',
            'field_gruppe',
            'gruppe_raw',
            'gruppe',
            'Jobliste_raw',
            'Jobliste'
        ];

        foreach ($possibleFields as $fieldKey) {
            if (isset($record[$fieldKey])) {
                $value = $record[$fieldKey];

                // Array von Objekten mit identifier (wie in Ihrem JSON)
                if (is_array($value)) {
                    $labels = [];
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            if (isset($item['identifier'])) {
                                $labels[] = $item['identifier'];
                            } elseif (isset($item['label'])) {
                                $labels[] = $item['label'];
                            }
                        } elseif (is_string($item)) {
                            $labels[] = $item;
                        }
                    }
                    if (!empty($labels)) {
                        return implode(', ', $labels);
                    }
                }

                // Direkter String-Wert
                if (is_string($value)) {
                    return $value;
                }

                // Einzelnes Objekt mit identifier/label
                if (is_array($value) && isset($value['identifier'])) {
                    return $value['identifier'];
                }
                if (is_array($value) && isset($value['label'])) {
                    return $value['label'];
                }
            }
        }

        return '';
    }

    private function splitName($fullName)
    {
        $parts = explode(' ', trim($fullName));

        if (count($parts) == 1) {
            return [$parts[0], ''];
        } elseif (count($parts) == 2) {
            return [$parts[0], $parts[1]];
        } else {
            $firstName = array_shift($parts);
            $lastName = implode(' ', $parts);
            return [$firstName, $lastName];
        }
    }

    public function render()
    {
        $groups = Group::orderBy('name')->get();
        // Nur vollständig konfigurierte Knack Objects anzeigen
        $knackObjects = KnackObject::complete()->orderBy('name')->get();

        return view('livewire.admin.knack-import', [
            'groups' => $groups,
            'knackObjects' => $knackObjects,
            'filterByYear' => $this->filterByYear,
            'year' => $this->year
        ]);
    }
}
