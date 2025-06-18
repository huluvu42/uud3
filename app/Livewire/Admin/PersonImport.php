<?php
// app/Livewire/Admin/PersonImport.php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Person;
use App\Models\Group;
use App\Models\VehiclePlate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class PersonImport extends Component
{
    use WithFileUploads;

    // Upload & File properties
    public $file;
    public $fileHeaders = [];
    public $previewData = [];

    // Mapping properties
    public $firstNameColumn = null;
    public $lastNameColumn = null;
    public $fullNameColumn = null;
    public $remarksColumn = null;
    public $licensePlateColumn = null;
    public $nameFormat = 'separate'; // 'separate', 'firstname_lastname', 'lastname_firstname'

    // Import settings
    public $selectedGroupId = null;
    public $selectedResponsiblePersonId = null;
    public $selectedYear;
    public $overwriteExisting = false;

    // State management
    public $step = 1; // 1: Upload, 2: Mapping, 3: Preview, 4: Import
    public $isLoading = false;
    public $importResults = [];

    // Data for preview
    public $duplicates = [];
    public $newPersons = [];
    public $importErrors = [];

    // Available groups
    public $groups = [];
    public $responsiblePersons = [];

    public $duplicateActions = [];

    public function mount()
    {
        $this->selectedYear = now()->year;
        $this->loadGroups();
        $this->loadResponsiblePersons();
    }

    public function loadGroups()
    {
        $this->groups = Group::where('year', $this->selectedYear)
            ->orderBy('name')
            ->get();
    }

    public function loadResponsiblePersons()
    {
        $this->responsiblePersons = Person::where('year', $this->selectedYear)
            ->where('is_duplicate', false)
            ->where('can_have_guests', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public function updatedSelectedYear()
    {
        $this->loadGroups();
        $this->loadResponsiblePersons();
        $this->resetImport();
    }

    public function updatedFile()
    {
        if (!$this->file) return;

        $this->isLoading = true;

        try {
            $this->processFile();
            $this->step = 2;
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Verarbeiten der Datei: ' . $e->getMessage());
            Log::error('File processing error: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    private function processFile()
    {
        $extension = $this->file->getClientOriginalExtension();

        if (in_array($extension, ['xlsx', 'xls'])) {
            $data = Excel::toArray([], $this->file);
            $rows = $data[0]; // Erste Tabelle
        } else {
            // Verbesserte CSV-Verarbeitung wie in BandImport
            $content = file_get_contents($this->file->getRealPath());

            // BOM entfernen falls vorhanden
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

            // Verschiedene Encodings testen
            $encoding = mb_detect_encoding($content, ['UTF-8', 'UTF-16', 'ISO-8859-1', 'Windows-1252']);
            if ($encoding && $encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            }

            $delimiter = $this->detectDelimiter($content);
            $rows = [];

            // Temporäre Datei ohne BOM erstellen
            $tempFile = tempnam(sys_get_temp_dir(), 'csv_clean');
            file_put_contents($tempFile, $content);

            if (($handle = fopen($tempFile, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                    $rows[] = $data;
                }
                fclose($handle);
            }

            // Temporäre Datei löschen
            unlink($tempFile);
        }

        if (empty($rows)) {
            throw new \Exception('Die Datei scheint leer zu sein.');
        }

        // Headers aus der ersten Zeile extrahieren und gründlich bereinigen
        $rawHeaders = $rows[0];
        $this->fileHeaders = [];

        foreach ($rawHeaders as $index => $header) {
            // Header bereinigen
            $cleanHeader = preg_replace('/^\xEF\xBB\xBF/', '', $header);
            $cleanHeader = trim($cleanHeader, " \t\n\r\0\x0B\"'");
            $cleanHeader = preg_replace('/[\x{201C}\x{201D}\x{2018}\x{2019}]/u', '', $cleanHeader);
            $cleanHeader = preg_replace('/[\x00-\x1F\x7F]/', '', $cleanHeader);

            // Wenn Header leer ist, generiere Spalten-Namen
            if (empty($cleanHeader)) {
                $columnLetter = $this->getColumnName($index);
                $this->fileHeaders[] = "Spalte $columnLetter";
            } else {
                $this->fileHeaders[] = $cleanHeader;
            }
        }

        // Preview-Daten (erste 5 Zeilen nach Header)
        $this->previewData = [];
        for ($i = 1; $i <= min(6, count($rows) - 1); $i++) {
            if (!empty($rows[$i])) {
                $row = [];
                foreach ($this->fileHeaders as $index => $header) {
                    $row[$header] = isset($rows[$i][$index]) ? trim($rows[$i][$index]) : '';
                }
                $this->previewData[] = $row;
            }
        }

        // Auto-mapping versuchen
        $this->attemptAutoMapping();
    }

    private function getColumnName($index)
    {
        $letters = '';
        while ($index >= 0) {
            $letters = chr(65 + ($index % 26)) . $letters;
            $index = intval($index / 26) - 1;
        }
        return $letters;
    }

    private function detectDelimiter($csvContent)
    {
        $delimiters = [',', ';', '\t', '|'];
        $delimiterCount = [];

        foreach ($delimiters as $delimiter) {
            $delimiterCount[$delimiter] = substr_count($csvContent, $delimiter);
        }

        return array_search(max($delimiterCount), $delimiterCount);
    }

    private function attemptAutoMapping()
    {
        foreach ($this->fileHeaders as $index => $header) {
            $lowerHeader = strtolower(trim($header));

            // First Name
            if (in_array($lowerHeader, ['vorname', 'firstname', 'first_name', 'first name'])) {
                $this->firstNameColumn = $header;
            }

            // Last Name
            if (in_array($lowerHeader, ['nachname', 'lastname', 'last_name', 'last name', 'familienname'])) {
                $this->lastNameColumn = $header;
            }

            // Full Name - für CSV wäre das wahrscheinlich die erste Spalte mit Namen
            if (
                in_array($lowerHeader, ['name', 'vollname', 'full_name', 'full name', 'person', 'spalte a']) ||
                ($index === 0 && !$this->fullNameColumn)
            ) {
                $this->fullNameColumn = $header;
                if (!$this->firstNameColumn && !$this->lastNameColumn) {
                    $this->nameFormat = 'firstname_lastname'; // Default
                }
            }

            // License Plate
            if (in_array($lowerHeader, ['kennzeichen', 'kfz', 'license_plate', 'nummernschild', 'auto', 'spalte b', 'spalte c'])) {
                $this->licensePlateColumn = $header;
            }

            // Remarks
            if (in_array($lowerHeader, ['bemerkung', 'bemerkungen', 'remarks', 'comment', 'kommentar', 'notiz'])) {
                $this->remarksColumn = $header;
            }
        }

        // Auto-detect name format
        if ($this->firstNameColumn && $this->lastNameColumn) {
            $this->nameFormat = 'separate';
        } elseif ($this->fullNameColumn) {
            $this->nameFormat = 'firstname_lastname'; // Default
        }
    }

    public function updatedNameFormat()
    {
        // Reset column selections when name format changes
        $this->firstNameColumn = null;
        $this->lastNameColumn = null;
        $this->fullNameColumn = null;
    }

    public function proceedToPreview()
    {
        // Validate mapping
        $this->validate([
            'selectedGroupId' => 'required|exists:groups,id',
            'nameFormat' => 'required|in:separate,firstname_lastname,lastname_firstname',
        ]);

        if ($this->nameFormat === 'separate') {
            $this->validate([
                'firstNameColumn' => 'required',
                'lastNameColumn' => 'required',
            ]);
        } else {
            $this->validate([
                'fullNameColumn' => 'required',
            ]);
        }

        $this->isLoading = true;

        try {
            $this->processImportData();
            $this->step = 3;
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Verarbeiten der Daten: ' . $e->getMessage());
            Log::error('ProcessImportData error: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    private function processImportData()
    {
        // Direkt die bereits verarbeiteten Daten aus dem ersten Schritt verwenden
        $extension = $this->file->getClientOriginalExtension();

        if (in_array($extension, ['xlsx', 'xls'])) {
            $data = Excel::toArray([], $this->file);
            $rows = $data[0];

            // Headers entfernen
            array_shift($rows);

            $allData = [];
            foreach ($rows as $row) {
                if (empty(array_filter($row))) continue; // Leere Zeilen überspringen

                $rowData = [];
                foreach ($this->fileHeaders as $index => $header) {
                    $rowData[$header] = isset($row[$index]) ? trim($row[$index]) : '';
                }
                $allData[] = $rowData;
            }
        } else {
            // CSV - Daten erneut verarbeiten mit der gleichen Logik
            $content = file_get_contents($this->file->getRealPath());
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

            $encoding = mb_detect_encoding($content, ['UTF-8', 'UTF-16', 'ISO-8859-1', 'Windows-1252']);
            if ($encoding && $encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            }

            $delimiter = $this->detectDelimiter($content);
            $rows = [];

            $tempFile = tempnam(sys_get_temp_dir(), 'csv_clean');
            file_put_contents($tempFile, $content);

            if (($handle = fopen($tempFile, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                    $rows[] = $data;
                }
                fclose($handle);
            }
            unlink($tempFile);

            // Headers entfernen
            array_shift($rows);

            $allData = [];
            foreach ($rows as $row) {
                if (empty(array_filter($row))) continue; // Leere Zeilen überspringen

                $rowData = [];
                foreach ($this->fileHeaders as $index => $header) {
                    $rowData[$header] = isset($row[$index]) ? trim($row[$index]) : '';
                }
                $allData[] = $rowData;
            }
        }

        $this->analyzeImportData($allData);
    }

    private function analyzeImportData($data)
    {
        $this->duplicates = [];
        $this->newPersons = [];
        $this->importErrors = [];
        $this->duplicateActions = []; // Reset

        $group = Group::find($this->selectedGroupId);

        foreach ($data as $index => $row) {
            $rowNumber = $index + 2;

            try {
                // Name extraction logic (bleibt gleich)
                if ($this->nameFormat === 'separate') {
                    $firstName = trim((string)($row[$this->firstNameColumn] ?? ''));
                    $lastName = trim((string)($row[$this->lastNameColumn] ?? ''));
                } else {
                    $fullName = trim((string)($row[$this->fullNameColumn] ?? ''));
                    if (empty($fullName)) {
                        throw new \Exception('Name ist leer');
                    }

                    $nameParts = explode(' ', $fullName, 2);
                    if ($this->nameFormat === 'firstname_lastname') {
                        $firstName = $nameParts[0] ?? '';
                        $lastName = $nameParts[1] ?? '';
                    } else {
                        $lastName = $nameParts[0] ?? '';
                        $firstName = $nameParts[1] ?? '';
                    }
                }

                if (empty($firstName) || empty($lastName)) {
                    throw new \Exception('Vor- oder Nachname fehlt');
                }

                $remarks = trim((string)($row[$this->remarksColumn] ?? ''));
                $licensePlate = $this->licensePlateColumn ? trim((string)($row[$this->licensePlateColumn] ?? '')) : '';

                // Check for existing person
                $existingPerson = Person::where('first_name', $firstName)
                    ->where('last_name', $lastName)
                    ->where('year', $this->selectedYear)
                    ->where('is_duplicate', false)
                    ->first();

                $personData = [
                    'row_number' => $rowNumber,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'remarks' => $remarks,
                    'license_plate' => $licensePlate,
                    'group' => $group,
                    'raw_data' => $row
                ];

                if ($existingPerson) {
                    $duplicateIndex = count($this->duplicates);
                    $personData['existing_person'] = $existingPerson;
                    $this->duplicates[] = $personData;

                    // Standard-Aktion: Ignorieren
                    $this->duplicateActions[$duplicateIndex] = 'ignore';
                } else {
                    $this->newPersons[] = $personData;
                }
            } catch (\Exception $e) {
                $this->importErrors[] = [
                    'row_number' => $rowNumber,
                    'message' => $e->getMessage(),
                    'raw_data' => $row
                ];
            }
        }
    }

    public function executeImport()
    {
        $this->isLoading = true;
        $this->importResults = [
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0
        ];

        DB::beginTransaction();

        try {
            $group = Group::find($this->selectedGroupId);

            // Import new persons (bleibt gleich)
            foreach ($this->newPersons as $personData) {
                $this->createPerson($personData, $group);
                $this->importResults['imported']++;
            }

            // Handle duplicates mit individuellen Aktionen
            foreach ($this->duplicates as $index => $personData) {
                $action = $this->duplicateActions[$index] ?? 'ignore';

                if ($action === 'import_new') {
                    // Als neue Person importieren (trotz gleichem Namen)
                    $this->createPerson($personData, $group);
                    $this->importResults['imported']++;
                } else {
                    // Ignorieren (Standard)
                    $this->importResults['skipped']++;
                }
            }

            DB::commit();
            $this->step = 4;

            // Clean up temporary file
            if ($this->file) {
                $this->file = null;
            }
        } catch (\Exception $e) {
            DB::rollback();
            $this->importResults['errors']++;
            Log::error('Import-Fehler: ' . $e->getMessage());
            session()->flash('error', 'Import-Fehler: ' . $e->getMessage());
        }

        $this->isLoading = false;
    }

    private function createPerson($personData, $group)
    {
        $person = Person::create([
            'first_name' => $personData['first_name'],
            'last_name' => $personData['last_name'],
            'year' => $this->selectedYear,
            'group_id' => $group->id,
            'responsible_person_id' => $this->selectedResponsiblePersonId,
            'present' => false,

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

            'can_have_guests' => $group->can_have_guests ?? false,
            'is_duplicate' => false,
            'remarks' => $personData['remarks'] ?? '',
        ]);

        // Create license plate if provided
        if (!empty($personData['license_plate'])) {
            VehiclePlate::create([
                'license_plate' => $personData['license_plate'],
                'person_id' => $person->id,
            ]);
        }

        return $person;
    }

    private function updatePerson($personData, $group)
    {
        $existingPerson = $personData['existing_person'];

        $updateData = [
            'group_id' => $group->id,
            'responsible_person_id' => $this->selectedResponsiblePersonId,

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

            'can_have_guests' => $group->can_have_guests ?? false,
            'remarks' => $personData['remarks'] ?? $existingPerson->remarks,
        ];

        $existingPerson->update($updateData);
        return $existingPerson;
    }

    public function resetImport()
    {
        $this->step = 1;
        $this->file = null;
        $this->duplicateActions = [];
        $this->fileHeaders = [];
        $this->previewData = [];
        $this->firstNameColumn = null;
        $this->lastNameColumn = null;
        $this->fullNameColumn = null;
        $this->remarksColumn = null;
        $this->licensePlateColumn = null;
        $this->nameFormat = 'separate';
        $this->selectedResponsiblePersonId = null;
        $this->overwriteExisting = false;
        $this->duplicates = [];
        $this->newPersons = [];
        $this->importErrors = [];
        $this->importResults = [];
        $this->isLoading = false;
    }

    public function render()
    {
        return view('livewire.admin.person-import');
    }
}
