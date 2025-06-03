<?php
// app/Livewire/Admin/PersonImport.php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Person;
use App\Models\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use League\Csv\Reader;
use League\Csv\Statement;

class PersonImport extends Component
{
    use WithFileUploads;

    // Upload & File properties
    public $file;
    public $uploadedFile = null;
    public $fileHeaders = [];
    public $previewData = [];

    // Mapping properties
    public $firstNameColumn = null;
    public $lastNameColumn = null;
    public $fullNameColumn = null;
    public $remarksColumn = null;
    public $nameFormat = 'separate'; // 'separate', 'firstname_lastname', 'lastname_firstname'

    // Import settings
    public $selectedGroupId = null;
    public $selectedYear;
    public $overwriteExisting = false;

    // State management
    public $step = 1; // 1: Upload, 2: Mapping, 3: Preview, 4: Import
    public $isLoading = false;
    public $importResults = [];

    // Data for preview
    public $duplicates = [];
    public $newPersons = [];
    public $importErrors = []; // Renamed from $errors to avoid conflict

    // Available groups
    public $groups = [];

    public function mount()
    {
        $this->selectedYear = now()->year;
        $this->loadGroups();
    }

    public function loadGroups()
    {
        $this->groups = Group::where('year', $this->selectedYear)
            ->orderBy('name')
            ->get();
    }

    public function updatedSelectedYear()
    {
        $this->loadGroups();
        $this->resetImport();
    }

    public function updatedFile()
    {
        $this->validateFile();
    }

    public function updatedNameFormat()
    {
        // Reset column selections when name format changes
        $this->firstNameColumn = null;
        $this->lastNameColumn = null;
        $this->fullNameColumn = null;
    }

    public function validateFile()
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // 10MB max
        ]);

        try {
            // Store file in temporary directory
            $this->uploadedFile = $this->file->store('temp-imports');

            // Verify file exists
            if (!Storage::exists($this->uploadedFile)) {
                throw new \Exception('Datei wurde nicht korrekt gespeichert.');
            }

            $this->parseFileHeaders();
            $this->step = 2;
        } catch (\Exception $e) {
            $this->addError('file', 'Fehler beim Lesen der Datei: ' . $e->getMessage());
        }
    }

    private function parseFileHeaders()
    {
        $filePath = Storage::path($this->uploadedFile);

        // Verify file exists
        if (!file_exists($filePath)) {
            throw new \Exception('Gespeicherte Datei wurde nicht gefunden: ' . $filePath);
        }

        $extension = strtolower($this->file->getClientOriginalExtension());

        try {
            if ($extension === 'csv') {
                $this->parseCSVHeaders($filePath);
            } else {
                $this->parseExcelHeaders($filePath);
            }
        } catch (\Exception $e) {
            throw new \Exception('Datei konnte nicht gelesen werden: ' . $e->getMessage());
        }
    }

    private function parseCSVHeaders($filePath)
    {
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        $this->fileHeaders = $csv->getHeader();

        // Preview data (first 5 rows)
        $stmt = Statement::create()->limit(5);
        $this->previewData = iterator_to_array($stmt->process($csv));
    }

    private function parseExcelHeaders($filePath)
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        // Get headers from first row
        $this->fileHeaders = [];
        $highestColumn = $worksheet->getHighestColumn();

        for ($col = 'A'; $col <= $highestColumn; $col++) {
            $headerValue = $worksheet->getCell($col . '1')->getCalculatedValue();
            if ($headerValue !== null && $headerValue !== '') {
                $this->fileHeaders[] = trim((string)$headerValue);
            } else {
                // If empty header, use column letter
                $this->fileHeaders[] = "Spalte " . $col;
            }
        }

        // Preview data (rows 2-6)
        $this->previewData = [];
        $highestRow = min(6, $worksheet->getHighestRow());

        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [];
            for ($colIndex = 0; $colIndex < count($this->fileHeaders); $colIndex++) {
                $col = chr(65 + $colIndex); // A, B, C, etc.
                $cellValue = $worksheet->getCell($col . $row)->getCalculatedValue();
                $rowData[$this->fileHeaders[$colIndex]] = $cellValue;
            }
            $this->previewData[] = $rowData;
        }
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
            $this->addError('general', 'Fehler beim Verarbeiten der Daten: ' . $e->getMessage());
        }

        $this->isLoading = false;
    }

    private function processImportData()
    {
        $filePath = Storage::path($this->uploadedFile);

        // Verify file still exists
        if (!file_exists($filePath)) {
            throw new \Exception('Datei wurde nicht gefunden: ' . $filePath);
        }

        $extension = strtolower($this->file->getClientOriginalExtension());

        $allData = [];

        if ($extension === 'csv') {
            $allData = $this->readCSVData($filePath);
        } else {
            $allData = $this->readExcelData($filePath);
        }

        $this->analyzeImportData($allData);
    }

    private function readCSVData($filePath)
    {
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        return iterator_to_array($csv);
    }

    private function readExcelData($filePath)
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $data = [];
        $highestRow = $worksheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [];
            $hasData = false;

            for ($colIndex = 0; $colIndex < count($this->fileHeaders); $colIndex++) {
                $col = chr(65 + $colIndex);
                $cellValue = $worksheet->getCell($col . $row)->getCalculatedValue();
                $rowData[$this->fileHeaders[$colIndex]] = $cellValue;

                if (!empty(trim((string)$cellValue))) {
                    $hasData = true;
                }
            }

            // Only add rows that have some data
            if ($hasData) {
                $data[] = $rowData;
            }
        }

        return $data;
    }

    private function analyzeImportData($data)
    {
        $this->duplicates = [];
        $this->newPersons = [];
        $this->importErrors = [];

        $group = Group::find($this->selectedGroupId);

        foreach ($data as $index => $row) {
            $rowNumber = $index + 2; // +2 because we start from row 2 in Excel/CSV

            try {
                // Extract names
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
                    } else { // lastname_firstname
                        $lastName = $nameParts[0] ?? '';
                        $firstName = $nameParts[1] ?? '';
                    }
                }

                if (empty($firstName) || empty($lastName)) {
                    throw new \Exception('Vor- oder Nachname fehlt');
                }

                $remarks = trim((string)($row[$this->remarksColumn] ?? ''));

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
                    'group' => $group,
                    'raw_data' => $row
                ];

                if ($existingPerson) {
                    $personData['existing_person'] = $existingPerson;
                    $this->duplicates[] = $personData;
                } else {
                    $this->newPersons[] = $personData;
                }
            } catch (\Exception $e) {
                $this->errors[] = [
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

            // Import new persons
            foreach ($this->newPersons as $personData) {
                $this->createPerson($personData, $group);
                $this->importResults['imported']++;
            }

            // Handle duplicates
            foreach ($this->duplicates as $personData) {
                if ($this->overwriteExisting) {
                    $this->updatePerson($personData, $group);
                    $this->importResults['updated']++;
                } else {
                    $this->importResults['skipped']++;
                }
            }

            DB::commit();
            $this->step = 4;

            // Clean up temporary file
            if ($this->uploadedFile) {
                Storage::delete($this->uploadedFile);
            }
        } catch (\Exception $e) {
            DB::rollback();
            $this->importResults['errors']++;
            $this->addError('import', 'Import-Fehler: ' . $e->getMessage());
            Log::error('Person Import Error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        }

        $this->isLoading = false;
    }

    private function createPerson($personData, $group)
    {
        Person::create([
            'first_name' => $personData['first_name'],
            'last_name' => $personData['last_name'],
            'remarks' => $personData['remarks'],
            'group_id' => $group->id,
            'year' => $this->selectedYear,
            'present' => false,
            'can_have_guests' => $group->can_have_guests,
            'backstage_day_1' => $group->backstage_day_1,
            'backstage_day_2' => $group->backstage_day_2,
            'backstage_day_3' => $group->backstage_day_3,
            'backstage_day_4' => $group->backstage_day_4,
            'voucher_day_1' => $group->voucher_day_1,
            'voucher_day_2' => $group->voucher_day_2,
            'voucher_day_3' => $group->voucher_day_3,
            'voucher_day_4' => $group->voucher_day_4,
            'voucher_issued_day_1' => 0,
            'voucher_issued_day_2' => 0,
            'voucher_issued_day_3' => 0,
            'voucher_issued_day_4' => 0,
            'is_duplicate' => false,
        ]);
    }

    private function updatePerson($personData, $group)
    {
        $existingPerson = $personData['existing_person'];

        $existingPerson->update([
            'remarks' => $personData['remarks'],
            'group_id' => $group->id,
            'can_have_guests' => $group->can_have_guests,
            'backstage_day_1' => $group->backstage_day_1,
            'backstage_day_2' => $group->backstage_day_2,
            'backstage_day_3' => $group->backstage_day_3,
            'backstage_day_4' => $group->backstage_day_4,
            'voucher_day_1' => $group->voucher_day_1,
            'voucher_day_2' => $group->voucher_day_2,
            'voucher_day_3' => $group->voucher_day_3,
            'voucher_day_4' => $group->voucher_day_4,
        ]);
    }

    public function resetImport()
    {
        $this->step = 1;
        $this->file = null;
        $this->uploadedFile = null;
        $this->fileHeaders = [];
        $this->previewData = [];
        $this->firstNameColumn = null;
        $this->lastNameColumn = null;
        $this->fullNameColumn = null;
        $this->remarksColumn = null;
        $this->nameFormat = 'separate';
        $this->overwriteExisting = false;
        $this->duplicates = [];
        $this->newPersons = [];
        $this->importErrors = [];
        $this->importResults = [];
        $this->isLoading = false;

        // Clean up temporary file
        if ($this->uploadedFile) {
            Storage::delete($this->uploadedFile);
        }
    }

    public function render()
    {
        return view('livewire.admin.person-import');
    }
}
