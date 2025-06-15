<?php
// app/Livewire/Admin/BandMemberImport.php

namespace App\Livewire\Admin;

use App\Models\Person;
use App\Models\Band;
use App\Models\VehiclePlate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class BandMemberImport extends Component
{
    use WithFileUploads;

    // File handling
    public $file;
    public $fileHeaders = [];
    public $previewData = [];

    // Import settings
    public $selectedYear;
    public $overwriteExisting = false;

    // Column mapping
    public $nameFormat = ''; // 'separate', 'firstname_lastname', 'lastname_firstname'
    public $firstNameColumn = '';
    public $lastNameColumn = '';
    public $fullNameColumn = '';
    public $bandNameColumn = '';
    public $licensePlateColumn = '';

    // Import process
    public $step = 1; // 1: Upload, 2: Mapping, 3: Preview, 4: Results
    public $isLoading = false;

    // Data arrays
    public $newMembers = [];
    public $duplicates = [];
    public $importErrors = [];
    public $importResults = [];
    public $unknownBands = []; // NEU: Sammelt Einträge mit unbekannten Bands
    public $bandMappings = []; // NEU: Manuelle Band-Zuordnungen
    public $bandSearchTerms = []; // NEU: Suchbegriffe für Band-Dropdowns

    // Available data
    public $bands = [];

    public function mount()
    {
        $this->selectedYear = now()->year;
        $this->bands = Band::where('year', $this->selectedYear)->orderBy('band_name')->get();
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
            // CSV mit verbesserter Verarbeitung
            $content = file_get_contents($this->file->getRealPath());

            // BOM entfernen falls vorhanden
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

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
                    $row[$header] = isset($rows[$i][$index]) ? $rows[$i][$index] : '';
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

            // Band Name - für deine CSV wäre das wahrscheinlich Spalte A
            if (in_array($lowerHeader, ['band', 'bandname', 'band_name', 'künstler', 'spalte a']) || $index === 0) {
                $this->bandNameColumn = $header;
            }

            // Full Name - für deine CSV wäre das wahrscheinlich Spalte B  
            if (in_array($lowerHeader, ['name', 'vollname', 'full_name', 'full name', 'person', 'spalte b']) || $index === 1) {
                $this->fullNameColumn = $header;
                $this->nameFormat = 'firstname_lastname'; // Default
            }

            // First Name
            if (in_array($lowerHeader, ['vorname', 'firstname', 'first_name', 'first name'])) {
                $this->firstNameColumn = $header;
            }

            // Last Name
            if (in_array($lowerHeader, ['nachname', 'lastname', 'last_name', 'last name', 'familienname'])) {
                $this->lastNameColumn = $header;
            }

            // License Plate - für deine CSV wäre das wahrscheinlich Spalte C
            if (in_array($lowerHeader, ['kennzeichen', 'kfz', 'license_plate', 'nummernschild', 'auto', 'spalte c']) || $index === 2) {
                $this->licensePlateColumn = $header;
            }
        }

        // Auto-detect name format
        if ($this->firstNameColumn && $this->lastNameColumn) {
            $this->nameFormat = 'separate';
        } elseif ($this->fullNameColumn && !$this->nameFormat) {
            $this->nameFormat = 'firstname_lastname'; // Default
        }
    }

    public function proceedToPreview()
    {
        $this->validate([
            'nameFormat' => 'required',
            'bandNameColumn' => 'required',
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
            $this->analyzeImportData();
            $this->step = 3;
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler bei der Datenanalyse: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    private function analyzeImportData()
    {
        $this->newMembers = [];
        $this->duplicates = [];
        $this->importErrors = [];
        $this->unknownBands = []; // Zurücksetzen
        $tempUnknownMembers = []; // Temporäres Array für Gruppierung

        // Alle Daten aus der Datei verarbeiten
        $extension = $this->file->getClientOriginalExtension();

        if (in_array($extension, ['xlsx', 'xls'])) {
            $data = Excel::toArray([], $this->file);
            $rows = $data[0];
        } else {
            // CSV mit verbesserter Verarbeitung
            $content = file_get_contents($this->file->getRealPath());
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
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
        }

        // Headers überspringen
        $originalHeaders = array_shift($rows);
        $cleanHeaders = [];

        foreach ($originalHeaders as $index => $header) {
            // Header bereinigen
            $cleanHeader = preg_replace('/^\xEF\xBB\xBF/', '', $header);
            $cleanHeader = trim($cleanHeader, " \t\n\r\0\x0B\"'");
            $cleanHeader = preg_replace('/[\x{201C}\x{201D}\x{2018}\x{2019}]/u', '', $cleanHeader);
            $cleanHeader = preg_replace('/[\x00-\x1F\x7F]/', '', $cleanHeader);

            // Wenn Header leer ist, generiere Spalten-Namen
            if (empty($cleanHeader)) {
                $columnLetter = $this->getColumnName($index);
                $cleanHeaders[] = "Spalte $columnLetter";
            } else {
                $cleanHeaders[] = $cleanHeader;
            }
        }

        foreach ($rows as $rowNumber => $rawRow) {
            if (empty($rawRow)) {
                continue;
            }

            // Row zu assoziativer Array konvertieren
            $row = [];
            foreach ($cleanHeaders as $index => $header) {
                $value = isset($rawRow[$index]) ? $rawRow[$index] : '';
                $value = trim($value, " \t\n\r\0\x0B\"'");
                $value = preg_replace('/[\x{201C}\x{201D}\x{2018}\x{2019}]/u', '', $value);
                $row[$header] = $value;
            }

            try {
                $memberData = $this->processMemberRow($row, $rowNumber + 2);

                if ($memberData) {
                    // Auf Duplikate prüfen
                    $existingMember = Person::where('first_name', $memberData['first_name'])
                        ->where('last_name', $memberData['last_name'])
                        ->where('band_id', $memberData['band_id'])
                        ->where('year', $this->selectedYear)
                        ->first();

                    if ($existingMember) {
                        $this->duplicates[] = array_merge($memberData, [
                            'existing_member' => $existingMember,
                            'row_number' => $rowNumber + 2
                        ]);
                    } else {
                        $this->newMembers[] = array_merge($memberData, [
                            'row_number' => $rowNumber + 2
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // Prüfen ob es sich um eine unbekannte Band handelt
                if (str_contains($e->getMessage(), 'nicht gefunden')) {
                    $bandName = trim($row[$this->bandNameColumn] ?? '');

                    // Name verarbeiten für Anzeige
                    $displayNames = $this->extractNames($row);

                    // Nach Band-Namen gruppieren
                    $bandKey = strtolower($bandName);
                    if (!isset($tempUnknownMembers[$bandKey])) {
                        $tempUnknownMembers[$bandKey] = [
                            'band_name' => $bandName,
                            'members' => [],
                            'member_count' => 0
                        ];
                    }

                    $tempUnknownMembers[$bandKey]['members'][] = [
                        'row_number' => $rowNumber + 2,
                        'member_name' => $displayNames['display_name'],
                        'first_name' => $displayNames['first_name'],
                        'last_name' => $displayNames['last_name'],
                        'license_plate' => $this->licensePlateColumn ? trim($row[$this->licensePlateColumn] ?? '') : '',
                        'raw_data' => $row
                    ];
                    $tempUnknownMembers[$bandKey]['member_count']++;
                } else {
                    $this->importErrors[] = [
                        'row_number' => $rowNumber + 2,
                        'message' => $e->getMessage(),
                        'raw_data' => $row
                    ];
                }
            }
        }

        // Gruppierte unbekannte Bands zu finalem Array konvertieren
        $this->unknownBands = array_values($tempUnknownMembers);

        // Band-Mappings initialisieren
        $this->bandMappings = [];
        $this->bandSearchTerms = [];
        foreach ($this->unknownBands as $index => $unknownBand) {
            $this->bandMappings[$index] = [
                'selected_band_id' => '',
                'action' => 'ignore'
            ];
            $this->bandSearchTerms[$index] = '';
        }
    }

    private function extractNames($row)
    {
        if ($this->nameFormat === 'separate') {
            $firstName = trim($row[$this->firstNameColumn] ?? '');
            $lastName = trim($row[$this->lastNameColumn] ?? '');
        } else {
            $fullName = trim($row[$this->fullNameColumn] ?? '');

            if ($this->nameFormat === 'firstname_lastname') {
                $nameParts = explode(' ', $fullName, 2);
                $firstName = $nameParts[0] ?? '';
                $lastName = $nameParts[1] ?? '';
            } else { // lastname_firstname
                $nameParts = explode(' ', $fullName, 2);
                $lastName = $nameParts[0] ?? '';
                $firstName = $nameParts[1] ?? '';
            }
        }

        // Intelligente Name-Behandlung
        if (empty($firstName) && empty($lastName)) {
            throw new \Exception('Name ist erforderlich');
        }

        // Fallback-Logik für fehlende Namen
        if (empty($firstName) && !empty($lastName)) {
            // Nur Nachname vorhanden
            $firstName = $lastName;
            $lastName = '(Kein Vorname)';
        } elseif (!empty($firstName) && empty($lastName)) {
            // Nur Vorname vorhanden
            $lastName = '(Kein Nachname)';
        }

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'display_name' => trim($firstName . ' ' . $lastName)
        ];
    }

    // NEU: Band-Auswahl über Suche
    public function selectBand($index, $bandId)
    {
        $this->bandMappings[$index]['selected_band_id'] = $bandId;
        $this->bandSearchTerms[$index] = ''; // Such-Input leeren nach Auswahl

        // Band-Name für bessere UX anzeigen
        $selectedBand = $this->bands->find($bandId);
        if ($selectedBand) {
            $this->bandSearchTerms[$index] = $selectedBand->band_name;
        }

        // NEU: Dropdown schließen
        $this->dispatch('dropdown-close', ['index' => $index]);
    }

    public function clearBandSelection($index)
    {
        $this->bandMappings[$index]['selected_band_id'] = '';
        $this->bandSearchTerms[$index] = '';

        // NEU: Dropdown schließen
        $this->dispatch('dropdown-close', ['index' => $index]);
    }

    // NEU: Unbekannte Bands zu bekannten Bands zuordnen
    public function processUnknownBands()
    {
        foreach ($this->unknownBands as $index => $unknownBandGroup) {
            $mapping = $this->bandMappings[$index] ?? ['action' => 'ignore'];

            if ($mapping['action'] === 'map' && !empty($mapping['selected_band_id'])) {
                $band = Band::find($mapping['selected_band_id']);
                if (!$band) continue;

                // Alle Mitglieder dieser Band-Gruppe verarbeiten
                foreach ($unknownBandGroup['members'] as $memberInfo) {
                    try {
                        // Member-Daten erstellen wie in processMemberRow
                        $stage = $band->stage;
                        $backstageAccess = $this->calculateBackstageAccess($stage, $band);
                        $vouchers = $this->calculateVouchers($stage, $band);

                        $memberData = [
                            'first_name' => $memberInfo['first_name'],
                            'last_name' => $memberInfo['last_name'],
                            'band' => $band,
                            'band_id' => $band->id,
                            'present' => false,
                            'backstage_day_1' => $backstageAccess['day_1'],
                            'backstage_day_2' => $backstageAccess['day_2'],
                            'backstage_day_3' => $backstageAccess['day_3'],
                            'backstage_day_4' => $backstageAccess['day_4'],
                            'voucher_day_1' => $vouchers['day_1'],
                            'voucher_day_2' => $vouchers['day_2'],
                            'voucher_day_3' => $vouchers['day_3'],
                            'voucher_day_4' => $vouchers['day_4'],
                            'license_plate' => $memberInfo['license_plate'] ?: null,
                            'year' => $this->selectedYear,
                            'row_number' => $memberInfo['row_number']
                        ];

                        // Auf Duplikate prüfen
                        $existingMember = Person::where('first_name', $memberData['first_name'])
                            ->where('last_name', $memberData['last_name'])
                            ->where('band_id', $memberData['band_id'])
                            ->where('year', $this->selectedYear)
                            ->first();

                        if ($existingMember) {
                            $this->duplicates[] = array_merge($memberData, [
                                'existing_member' => $existingMember
                            ]);
                        } else {
                            $this->newMembers[] = $memberData;
                        }
                    } catch (\Exception $e) {
                        $this->importErrors[] = [
                            'row_number' => $memberInfo['row_number'],
                            'message' => 'Fehler bei manueller Zuordnung: ' . $e->getMessage(),
                            'raw_data' => $memberInfo['raw_data']
                        ];
                    }
                }
            }
            // Bei 'ignore' passiert nichts - die Einträge werden nicht importiert
        }

        // Unbekannte Bands aus der Liste entfernen (wurden verarbeitet)
        $this->unknownBands = array_filter($this->unknownBands, function ($unknownBandGroup, $index) {
            $mapping = $this->bandMappings[$index] ?? ['action' => 'ignore'];
            return $mapping['action'] === 'ignore' && empty($mapping['selected_band_id']);
        }, ARRAY_FILTER_USE_BOTH);
    }

    private function processMemberRow($row, $rowNumber)
    {
        // Band Name
        $bandName = trim($row[$this->bandNameColumn] ?? '');
        if (empty($bandName)) {
            throw new \Exception('Band-Name ist erforderlich');
        }

        // Band suchen - prüfen auf Eindeutigkeit (case-insensitive)
        $matchingBands = $this->bands->filter(function ($band) use ($bandName) {
            return strtolower($band->band_name) === strtolower($bandName);
        });

        if ($matchingBands->count() === 0) {
            throw new \Exception("Band '{$bandName}' nicht gefunden");
        }
        if ($matchingBands->count() > 1) {
            throw new \Exception("Band '{$bandName}' ist nicht eindeutig - mehrere Bands mit diesem Namen gefunden");
        }

        $band = $matchingBands->first();

        // Name verarbeiten
        $nameData = $this->extractNames($row);
        $firstName = $nameData['first_name'];
        $lastName = $nameData['last_name'];

        // Namen sind durch extractNames bereits validiert und mit Fallbacks versehen

        // Optional fields - nur KFZ-Kennzeichen
        $licensePlate = $this->licensePlateColumn ? trim($row[$this->licensePlateColumn] ?? '') : '';

        // Bühnen-Vorgaben laden
        $stage = $band->stage;
        $backstageAccess = $this->calculateBackstageAccess($stage, $band);
        $vouchers = $this->calculateVouchers($stage, $band);

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'band' => $band,
            'band_id' => $band->id,
            'present' => false,
            'backstage_day_1' => $backstageAccess['day_1'],
            'backstage_day_2' => $backstageAccess['day_2'],
            'backstage_day_3' => $backstageAccess['day_3'],
            'backstage_day_4' => $backstageAccess['day_4'],
            'voucher_day_1' => $vouchers['day_1'],
            'voucher_day_2' => $vouchers['day_2'],
            'voucher_day_3' => $vouchers['day_3'],
            'voucher_day_4' => $vouchers['day_4'],
            'license_plate' => $licensePlate ?: null,
            'year' => $this->selectedYear,
        ];
    }

    private function calculateBackstageAccess($stage, $band)
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
            if ($band->plays_day_1) $access['day_1'] = true;
            if ($band->plays_day_2) $access['day_2'] = true;
            if ($band->plays_day_3) $access['day_3'] = true;
            if ($band->plays_day_4) $access['day_4'] = true;
        }

        return $access;
    }

    private function calculateVouchers($stage, $band)
    {
        $vouchers = [
            'day_1' => 0,
            'day_2' => 0,
            'day_3' => 0,
            'day_4' => 0,
        ];

        $voucherAmount = $stage->getVoucherAmount();

        // Voucher nur an Auftrittstagen
        if ($band->plays_day_1) $vouchers['day_1'] = $voucherAmount;
        if ($band->plays_day_2) $vouchers['day_2'] = $voucherAmount;
        if ($band->plays_day_3) $vouchers['day_3'] = $voucherAmount;
        if ($band->plays_day_4) $vouchers['day_4'] = $voucherAmount;

        return $vouchers;
    }

    public function executeImport()
    {
        // Erst unbekannte Bands verarbeiten
        $this->processUnknownBands();

        $this->isLoading = true;

        try {
            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $errors = 0;

            // Neue Mitglieder importieren
            foreach ($this->newMembers as $memberData) {
                try {
                    $person = Person::create([
                        'first_name' => $memberData['first_name'],
                        'last_name' => $memberData['last_name'],
                        'band_id' => $memberData['band_id'],
                        'present' => false,
                        'backstage_day_1' => $memberData['backstage_day_1'],
                        'backstage_day_2' => $memberData['backstage_day_2'],
                        'backstage_day_3' => $memberData['backstage_day_3'],
                        'backstage_day_4' => $memberData['backstage_day_4'],
                        'voucher_day_1' => $memberData['voucher_day_1'],
                        'voucher_day_2' => $memberData['voucher_day_2'],
                        'voucher_day_3' => $memberData['voucher_day_3'],
                        'voucher_day_4' => $memberData['voucher_day_4'],
                        'year' => $memberData['year'],
                    ]);

                    // Kennzeichen hinzufügen falls vorhanden
                    if ($memberData['license_plate']) {
                        VehiclePlate::create([
                            'license_plate' => $memberData['license_plate'],
                            'person_id' => $person->id,
                        ]);
                    }

                    // Band all_present Status aktualisieren
                    $memberData['band']->updateAllPresentStatus();

                    $imported++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Band member import error: ' . $e->getMessage());
                }
            }

            // Duplikate behandeln
            if ($this->overwriteExisting) {
                foreach ($this->duplicates as $duplicate) {
                    try {
                        $duplicate['existing_member']->update([
                            'backstage_day_1' => $duplicate['backstage_day_1'],
                            'backstage_day_2' => $duplicate['backstage_day_2'],
                            'backstage_day_3' => $duplicate['backstage_day_3'],
                            'backstage_day_4' => $duplicate['backstage_day_4'],
                            'voucher_day_1' => $duplicate['voucher_day_1'],
                            'voucher_day_2' => $duplicate['voucher_day_2'],
                            'voucher_day_3' => $duplicate['voucher_day_3'],
                            'voucher_day_4' => $duplicate['voucher_day_4'],
                        ]);

                        // Kennzeichen aktualisieren falls vorhanden
                        if ($duplicate['license_plate']) {
                            // Prüfen ob bereits Kennzeichen vorhanden
                            $existingPlate = VehiclePlate::where('person_id', $duplicate['existing_member']->id)
                                ->where('license_plate', $duplicate['license_plate'])
                                ->first();
                            if (!$existingPlate) {
                                VehiclePlate::create([
                                    'license_plate' => $duplicate['license_plate'],
                                    'person_id' => $duplicate['existing_member']->id,
                                ]);
                            }
                        }

                        $updated++;
                    } catch (\Exception $e) {
                        $errors++;
                        Log::error('Band member update error: ' . $e->getMessage());
                    }
                }
            } else {
                $skipped = count($this->duplicates);
            }

            // Ignorierte unbekannte Bands zu Skipped zählen
            $ignoredUnknownBands = count(array_filter($this->unknownBands, function ($unknownBand, $index) {
                $mapping = $this->bandMappings[$index] ?? ['action' => 'ignore'];
                return $mapping['action'] === 'ignore';
            }, ARRAY_FILTER_USE_BOTH));

            $this->importResults = [
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped + $ignoredUnknownBands,
                'errors' => $errors + count($this->importErrors),
            ];

            $this->step = 4;
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler beim Import: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function resetImport()
    {
        $this->reset([
            'file',
            'fileHeaders',
            'previewData',
            'step',
            'nameFormat',
            'firstNameColumn',
            'lastNameColumn',
            'fullNameColumn',
            'bandNameColumn',
            'licensePlateColumn',
            'newMembers',
            'duplicates',
            'importErrors',
            'importResults',
            'overwriteExisting',
            'unknownBands',
            'bandMappings',
            'bandSearchTerms'
        ]);
        $this->step = 1;
    }

    public function render()
    {
        return view('livewire.admin.band-member-import');
    }
}
