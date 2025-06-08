<?php
// app/Livewire/Admin/BandImport.php

namespace App\Livewire\Admin;

use App\Models\Band;
use App\Models\Stage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Normalizer;

class BandImport extends Component
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
    public $bandNameColumn = '';
    public $playsDayColumn = ''; // Single day column
    public $stageColumn = '';
    public $performanceTimeColumn = '';
    public $performanceDurationColumn = '';
    public $hotelColumn = '';
    public $commentColumn = '';
    public $travelCostsColumn = '';
    public $travelCostsCommentColumn = '';

    // Import process
    public $step = 1; // 1: Upload, 2: Mapping, 3: Preview, 4: Results
    public $isLoading = false;

    // Data arrays
    public $newBands = [];
    public $duplicates = [];
    public $importErrors = [];
    public $importResults = [];

    // Available data
    public $stages = [];

    public function mount()
    {
        $this->selectedYear = now()->year;
        $this->stages = Stage::orderBy('name')->get();
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
            // Verbesserte CSV-Verarbeitung
            $rows = $this->processCsvFile();
        }

        if (empty($rows)) {
            throw new \Exception('Die Datei scheint leer zu sein.');
        }

        // Headers aus der ersten Zeile extrahieren und normalisieren
        $this->fileHeaders = $this->cleanAndNormalizeHeaders($rows[0]);

        Log::info('CSV Headers after cleaning and normalization:', array_slice($this->fileHeaders, 0, 10));

        // Preview-Daten (erste 5 Zeilen nach Header)
        $this->previewData = $this->createPreviewData($rows);

        // Auto-mapping versuchen
        $this->attemptAutoMapping();

        Log::info('Auto-mapping results:', [
            'bandNameColumn' => $this->bandNameColumn,
            'stageColumn' => $this->stageColumn,
            'playsDayColumn' => $this->playsDayColumn
        ]);
    }

    private function processCsvFile()
    {
        $content = file_get_contents($this->file->getRealPath());

        // BOM entfernen falls vorhanden
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Verschiedene Encodings testen
        $encoding = mb_detect_encoding($content, ['UTF-8', 'UTF-16', 'ISO-8859-1', 'Windows-1252']);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        Log::info('CSV file encoding detected: ' . $encoding);

        // Delimiter erkennen
        $delimiter = $this->detectDelimiter($content);
        Log::info('CSV delimiter detected: ' . $delimiter);

        $rows = [];
        $lines = explode("\n", $content);

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // CSV-Zeile parsen mit korrektem Delimiter
            $data = str_getcsv($line, $delimiter, '"');

            // Daten bereinigen und normalisieren
            $data = array_map([$this, 'cleanAndNormalizeValue'], $data);

            $rows[] = $data;

            // Debug für erste paar Zeilen
            if ($lineNumber < 3) {
                Log::info("CSV Line $lineNumber (columns: " . count($data) . ")");
            }
        }

        Log::info('Total CSV lines processed: ' . count($rows));
        return $rows;
    }

    private function cleanAndNormalizeHeaders($headers)
    {
        return array_map(function ($header) {
            return $this->cleanAndNormalizeValue($header);
        }, $headers);
    }

    private function cleanAndNormalizeValue($value)
    {
        // BOM entfernen
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);

        // Alle möglichen Anführungszeichen und Whitespace entfernen
        $value = trim($value, " \t\n\r\0\x0B\"'");

        // Unicode-Anführungszeichen entfernen
        $value = preg_replace('/[\x{201C}\x{201D}\x{2018}\x{2019}]/u', '', $value);

        // Zusätzliche unsichtbare Zeichen entfernen
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value);

        // WICHTIG: Unicode normalisieren (NFD -> NFC)
        // Das löst das Problem mit combining characters
        if (class_exists('Normalizer')) {
            $value = Normalizer::normalize($value, Normalizer::FORM_C);
        }

        return $value;
    }

    private function createPreviewData($rows)
    {
        $previewData = [];
        $headerCount = count($this->fileHeaders);

        for ($i = 1; $i <= min(6, count($rows) - 1); $i++) {
            if (!empty($rows[$i])) {
                $row = [];

                // Daten mit Headers verknüpfen
                for ($j = 0; $j < max($headerCount, count($rows[$i])); $j++) {
                    $header = isset($this->fileHeaders[$j]) ? $this->fileHeaders[$j] : "Extra_Column_$j";
                    $value = isset($rows[$i][$j]) ? $this->cleanAndNormalizeValue($rows[$i][$j]) : '';
                    $row[$header] = $value;
                }

                // Log wichtige Felder für Debugging
                if ($i <= 2) {
                    Log::info("Preview row $i - Künstler: '" . ($row['Künstler'] ?? 'NOT FOUND') . "', Tag: '" . ($row['Tag'] ?? 'NOT FOUND') . "', Bühne: '" . ($row['Bühne'] ?? 'NOT FOUND') . "'");
                }

                $previewData[] = $row;
            }
        }

        return $previewData;
    }

    private function detectDelimiter($csvContent)
    {
        $delimiters = [',', ';', '\t', '|'];
        $delimiterCount = [];

        foreach ($delimiters as $delimiter) {
            if ($delimiter === '\t') {
                $delimiterCount[$delimiter] = substr_count($csvContent, "\t");
            } else {
                $delimiterCount[$delimiter] = substr_count($csvContent, $delimiter);
            }
        }

        $bestDelimiter = array_search(max($delimiterCount), $delimiterCount);

        // Tab-Delimiter wieder zu echtem Tab konvertieren
        if ($bestDelimiter === '\t') {
            $bestDelimiter = "\t";
        }

        return $bestDelimiter;
    }

    private function attemptAutoMapping()
    {
        Log::info('Attempting auto-mapping with headers:', $this->fileHeaders);

        foreach ($this->fileHeaders as $header) {
            $lowerHeader = strtolower(trim($header));

            Log::info('Processing header: ' . $header . ' (lower: ' . $lowerHeader . ')');

            // Band Name - exakte Übereinstimmung mit normalisierten Headers
            if (
                $header === 'Künstler' || $lowerHeader === 'künstler' ||
                in_array($lowerHeader, ['band', 'bandname', 'band_name', 'name'])
            ) {
                $this->bandNameColumn = $header;
                Log::info('Mapped band name to: ' . $header);
            }

            // Stage
            if (
                $header === 'Bühne' || $lowerHeader === 'bühne' ||
                in_array($lowerHeader, ['stage', 'buehne', 'bühnen', 'stage_name'])
            ) {
                $this->stageColumn = $header;
                Log::info('Mapped stage to: ' . $header);
            }

            // Performance Time - mehrere mögliche Spalten
            if (in_array($lowerHeader, ['auftrittszeit_ohne_datum', 'spielzeit', 'time', 'zeit', 'performance_time', 'auftrittszeit'])) {
                $this->performanceTimeColumn = $header;
                Log::info('Mapped performance time to: ' . $header);
            }

            // Performance Duration
            if (in_array($lowerHeader, ['spieldauer_minuten', 'duration', 'dauer', 'performance_duration', 'spieldauer'])) {
                $this->performanceDurationColumn = $header;
                Log::info('Mapped performance duration to: ' . $header);
            }

            // Hotel
            if (in_array($lowerHeader, ['hotel', 'unterkunft'])) {
                $this->hotelColumn = $header;
                Log::info('Mapped hotel to: ' . $header);
            }

            // Comment
            if (in_array($lowerHeader, ['text', 'comment', 'kommentar', 'bemerkung', 'notes', 'info'])) {
                $this->commentColumn = $header;
                Log::info('Mapped comment to: ' . $header);
            }

            // Travel Costs
            if (in_array($lowerHeader, ['reisekosten', 'travel_costs', 'kosten', 'costs'])) {
                $this->travelCostsColumn = $header;
                Log::info('Mapped travel costs to: ' . $header);
            }

            // Travel Costs Comment
            if (in_array($lowerHeader, ['kommentar_reisekosten', 'travel_costs_comment', 'reisekosten_kommentar', 'kostenkommentar'])) {
                $this->travelCostsCommentColumn = $header;
                Log::info('Mapped travel costs comment to: ' . $header);
            }

            // Plays Day
            if (
                $header === 'Tag' || $lowerHeader === 'tag' ||
                in_array($lowerHeader, ['plays_day', 'spieltag', 'auftrittstag', 'day', 'wochentag'])
            ) {
                $this->playsDayColumn = $header;
                Log::info('Mapped plays day to: ' . $header);
            }
        }

        Log::info('Auto-mapping completed. Results:', [
            'bandNameColumn' => $this->bandNameColumn,
            'stageColumn' => $this->stageColumn,
            'playsDayColumn' => $this->playsDayColumn
        ]);
    }

    public function proceedToPreview()
    {
        $this->validate([
            'bandNameColumn' => 'required',
            'stageColumn' => 'required',
            'playsDayColumn' => 'required',
        ]);

        $this->isLoading = true;

        try {
            $this->analyzeImportData();
            $this->step = 3;
        } catch (\Exception $e) {
            session()->flash('error', 'Fehler bei der Datenanalyse: ' . $e->getMessage());
            Log::error('Data analysis error: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    private function analyzeImportData()
    {
        $this->newBands = [];
        $this->duplicates = [];
        $this->importErrors = [];

        $extension = $this->file->getClientOriginalExtension();

        if (in_array($extension, ['xlsx', 'xls'])) {
            $data = Excel::toArray([], $this->file);
            $rows = $data[0];
        } else {
            $rows = $this->processCsvFile();
        }

        // Headers überspringen und normalisieren
        $cleanHeaders = $this->cleanAndNormalizeHeaders(array_shift($rows));

        // Sammle alle Auftritte nach Band-Namen mit Zeilennummern
        $bandPerformances = [];

        foreach ($rows as $rowNumber => $rawRow) {
            if (empty($rawRow) || $this->isEmptyRow($rawRow)) {
                continue;
            }

            // Row zu assoziativer Array konvertieren mit Normalisierung
            $row = $this->convertRowToAssociativeArray($rawRow, $cleanHeaders);

            // Prüfen ob das eine valide Band-Zeile ist
            if (!$this->isValidBandRow($row)) {
                continue;
            }

            try {
                $performanceData = $this->extractPerformanceData($row, $rowNumber + 2);

                if ($performanceData) {
                    $bandName = $performanceData['band_name'];

                    // Füge die Zeilennummer zur Performance hinzu
                    $performanceData['original_row_number'] = $rowNumber + 2;

                    // Sammle alle Auftritte nach Band-Namen
                    if (!isset($bandPerformances[$bandName])) {
                        $bandPerformances[$bandName] = [
                            'band_name' => $bandName,
                            'performances' => [],
                            'stage_id' => $performanceData['stage_id'],
                            'stage' => $performanceData['stage'],
                            'hotel' => $performanceData['hotel'],
                            'comment' => $performanceData['comment'],
                            'travel_costs' => $performanceData['travel_costs'],
                            'travel_costs_comment' => $performanceData['travel_costs_comment'],
                            'year' => $performanceData['year'],
                        ];
                    }

                    // Füge diesen Auftritt hinzu
                    $bandPerformances[$bandName]['performances'][] = $performanceData;
                }
            } catch (\Exception $e) {
                $this->importErrors[] = [
                    'row_number' => $rowNumber + 2,
                    'message' => $e->getMessage(),
                    'raw_data' => $row
                ];
            }
        }

        // Verarbeite gesammelte Band-Auftritte
        foreach ($bandPerformances as $bandName => $bandData) {
            try {
                $consolidatedBand = $this->consolidateBandPerformances($bandData);

                // Bestimme die row_number basierend auf der Anzahl der Auftritte
                $performanceCount = count($bandData['performances']);
                if ($performanceCount > 1) {
                    // Multiple performances - zeige alle Zeilennummern
                    $rowNumbers = [];
                    foreach ($bandData['performances'] as $performance) {
                        $rowNumbers[] = $performance['original_row_number'];
                    }
                    $rowNumberDisplay = implode(', ', array_unique($rowNumbers));
                } else {
                    // Single performance - zeige die spezifische Zeilennummer
                    $rowNumberDisplay = $bandData['performances'][0]['original_row_number'];
                }

                // Auf Duplikate prüfen
                $existingBand = Band::where('band_name', $bandName)
                    ->where('year', $this->selectedYear)
                    ->first();

                if ($existingBand) {
                    $this->duplicates[] = array_merge($consolidatedBand, [
                        'existing_band' => $existingBand,
                        'row_number' => $rowNumberDisplay,
                        'performance_count' => $performanceCount
                    ]);
                } else {
                    $this->newBands[] = array_merge($consolidatedBand, [
                        'row_number' => $rowNumberDisplay,
                        'performance_count' => $performanceCount
                    ]);
                }
            } catch (\Exception $e) {
                $this->importErrors[] = [
                    'row_number' => 'Multiple',
                    'message' => "Fehler bei Band '{$bandName}': " . $e->getMessage(),
                    'raw_data' => $bandData
                ];
            }
        }
    }

    private function isEmptyRow($row)
    {
        foreach ($row as $cell) {
            if (!empty(trim($cell))) {
                return false;
            }
        }
        return true;
    }

    private function convertRowToAssociativeArray($rawRow, $cleanHeaders)
    {
        $row = [];
        $maxColumns = max(count($rawRow), count($cleanHeaders));

        for ($i = 0; $i < $maxColumns; $i++) {
            $header = isset($cleanHeaders[$i]) ? $cleanHeaders[$i] : "Extra_Column_$i";
            $value = isset($rawRow[$i]) ? $this->cleanAndNormalizeValue($rawRow[$i]) : '';
            $row[$header] = $value;
        }

        return $row;
    }

    private function isValidBandRow($row)
    {
        $bandName = trim($row[$this->bandNameColumn] ?? '');
        $stage = trim($row[$this->stageColumn] ?? '');
        $day = trim($row[$this->playsDayColumn] ?? '');

        // Überspringen wenn alle wichtigen Felder leer sind
        if (empty($bandName) && empty($stage) && empty($day)) {
            return false;
        }

        // Nur die erste Header-Zeile überspringen - sehr spezifische Prüfung
        if ($bandName === 'Künstler' && $stage === 'Bühne' && $day === 'Tag') {
            return false;
        }

        // Mindestens Band-Name muss vorhanden sein
        return !empty($bandName);
    }

    private function extractPerformanceData($row, $rowNumber)
    {
        // Band Name
        $bandName = trim($row[$this->bandNameColumn] ?? '');

        if (empty($bandName)) {
            throw new \Exception('Band-Name ist erforderlich');
        }

        // Stage
        $stageName = trim($row[$this->stageColumn] ?? '');

        if (empty($stageName)) {
            // Wenn Stage leer ist, schauen wir ob wir Stage-Info aus anderen Spalten extrahieren können
            $stageTimeInfo = trim($row['Bühne_Tag_Zeit'] ?? '');
            if (!empty($stageTimeInfo)) {
                // Format: "Freitag, ZirkusZelt: 17:20"
                if (preg_match('/([^,]+),\s*([^:]+):\s*(.+)/', $stageTimeInfo, $matches)) {
                    $stageName = trim($matches[2]);
                }
            }

            if (empty($stageName)) {
                throw new \Exception('Bühne ist erforderlich');
            }
        }
        // $stage = collect($this->stages)->firstWhere('name', $stageName);
        $stage = $this->stages->firstWhere('name', $stageName);
        if (!$stage) {
            throw new \Exception("Bühne '{$stageName}' nicht gefunden");
        }

        // Performance Day (einzelner Tag für diesen Auftritt)
        $playDay = $this->parseSinglePlayDay($row);

        // Performance Time und Duration für diesen spezifischen Auftritt
        $performanceTime = $this->parseTime($row[$this->performanceTimeColumn] ?? '');
        $performanceDuration = $this->parseDuration($row[$this->performanceDurationColumn] ?? '');

        // Optional fields
        $hotel = trim($row[$this->hotelColumn] ?? '') ?: null;
        $comment = trim($row[$this->commentColumn] ?? '') ?: null;
        $travelCosts = $this->parseDecimal($row[$this->travelCostsColumn] ?? '');
        $travelCostsComment = trim($row[$this->travelCostsCommentColumn] ?? '') ?: null;

        return [
            'band_name' => $bandName,
            'stage' => $stage,
            'stage_id' => $stage->id,
            'play_day' => $playDay, // Einzelner Tag (1-4)
            'performance_time' => $performanceTime,
            'performance_duration' => $performanceDuration,
            'hotel' => $hotel,
            'comment' => $comment,
            'travel_costs' => $travelCosts,
            'travel_costs_comment' => $travelCostsComment,
            'year' => $this->selectedYear,
        ];
    }

    private function parseSinglePlayDay($row)
    {
        $playsDayValue = strtolower(trim($row[$this->playsDayColumn] ?? ''));

        // Wenn Tag-Spalte leer ist, versuche aus Bühne_Tag_Zeit zu extrahieren
        if (empty($playsDayValue)) {
            $stageTimeInfo = trim($row['Bühne_Tag_Zeit'] ?? '');
            if (!empty($stageTimeInfo)) {
                // Format: "Freitag, ZirkusZelt: 17:20"
                if (preg_match('/([^,]+),/', $stageTimeInfo, $matches)) {
                    $playsDayValue = strtolower(trim($matches[1]));
                }
            }
        }

        if (empty($playsDayValue)) {
            throw new \Exception('Auftrittstag ist erforderlich');
        }

        // Map day names to day numbers
        if (in_array($playsDayValue, ['donnerstag', 'thursday', 'do', 'thu', '1', 'tag1', 'tag 1'])) {
            return 1;
        } elseif (in_array($playsDayValue, ['freitag', 'friday', 'fr', 'fri', '2', 'tag2', 'tag 2'])) {
            return 2;
        } elseif (in_array($playsDayValue, ['samstag', 'saturday', 'sa', 'sat', '3', 'tag3', 'tag 3'])) {
            return 3;
        } elseif (in_array($playsDayValue, ['sonntag', 'sunday', 'so', 'sun', '4', 'tag4', 'tag 4'])) {
            return 4;
        } else {
            throw new \Exception("Ungültiger Auftrittstag: '{$playsDayValue}'. Erlaubte Werte: Donnerstag, Freitag, Samstag, Sonntag");
        }
    }

    private function consolidateBandPerformances($bandData)
    {
        $performances = $bandData['performances'];

        // Initialisiere alle Tage als false
        $plays_days = [
            'plays_day_1' => false,
            'plays_day_2' => false,
            'plays_day_3' => false,
            'plays_day_4' => false,
        ];

        // Performance Times und Durations pro Tag
        $performance_times = [
            'performance_time_day_1' => null,
            'performance_time_day_2' => null,
            'performance_time_day_3' => null,
            'performance_time_day_4' => null,
        ];

        $performance_durations = [
            'performance_duration_day_1' => null,
            'performance_duration_day_2' => null,
            'performance_duration_day_3' => null,
            'performance_duration_day_4' => null,
        ];

        // Verarbeite alle Auftritte
        foreach ($performances as $performance) {
            $day = $performance['play_day'];

            // Setze den Tag als aktiv
            $plays_days["plays_day_{$day}"] = true;

            // Setze Performance Time nur wenn noch nicht gesetzt (erste Auftrittszeit)
            if (!$performance_times["performance_time_day_{$day}"] && $performance['performance_time']) {
                $performance_times["performance_time_day_{$day}"] = $performance['performance_time'];
            }

            // Setze Performance Duration nur wenn noch nicht gesetzt (erste Auftrittszeit)
            if (!$performance_durations["performance_duration_day_{$day}"] && $performance['performance_duration']) {
                $performance_durations["performance_duration_day_{$day}"] = $performance['performance_duration'];
            }
        }

        // Verwende Daten vom ersten Auftritt als Basis (für hotel, comment, etc.)
        $firstPerformance = $performances[0];

        return array_merge($bandData, $plays_days, $performance_times, $performance_durations, [
            'stage' => $firstPerformance['stage'],
            'stage_id' => $firstPerformance['stage_id'],
        ]);
    }

    private function parseTime($timeString)
    {
        if (empty($timeString)) return null;

        $timeString = trim($timeString);

        // Try different time formats
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $timeString, $matches)) {
            return sprintf('%02d:%02d:00', $matches[1], $matches[2]);
        }

        if (preg_match('/^(\d{1,2})\.(\d{2})$/', $timeString, $matches)) {
            return sprintf('%02d:%02d:00', $matches[1], $matches[2]);
        }

        // If already in full format
        if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $timeString)) {
            return $timeString;
        }

        return null;
    }

    private function parseDuration($durationString)
    {
        if (empty($durationString)) return null;

        $durationString = trim($durationString);

        // Try to parse as minutes
        if (is_numeric($durationString)) {
            return (int) $durationString;
        }

        // Try to parse formats like "1h 30min", "90min", "1.5h"
        if (preg_match('/(\d+)h\s*(\d+)?min?/i', $durationString, $matches)) {
            $hours = (int) $matches[1];
            $minutes = isset($matches[2]) ? (int) $matches[2] : 0;
            return ($hours * 60) + $minutes;
        }

        if (preg_match('/(\d+)min/i', $durationString, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/(\d+(?:\.\d+)?)h/i', $durationString, $matches)) {
            return (int) (floatval($matches[1]) * 60);
        }

        return null;
    }

    private function parseDecimal($decimalString)
    {
        if (empty($decimalString)) return null;

        $decimalString = trim(str_replace(',', '.', $decimalString));

        if (is_numeric($decimalString)) {
            return (float) $decimalString;
        }

        return null;
    }

    public function executeImport()
    {
        $this->isLoading = true;

        try {
            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $errors = 0;

            // Neue Bands importieren
            foreach ($this->newBands as $bandData) {
                try {
                    Band::create([
                        'band_name' => $bandData['band_name'],
                        'stage_id' => (int) $bandData['stage_id'],
                        'plays_day_1' => (bool) $bandData['plays_day_1'],
                        'plays_day_2' => (bool) $bandData['plays_day_2'],
                        'plays_day_3' => (bool) $bandData['plays_day_3'],
                        'plays_day_4' => (bool) $bandData['plays_day_4'],
                        // Legacy fields (für Backward Compatibility)
                        'performance_time' => $bandData['performance_time_day_1'] ?? $bandData['performance_time_day_2'] ?? $bandData['performance_time_day_3'] ?? $bandData['performance_time_day_4'] ?? null,
                        'performance_duration' => $bandData['performance_duration_day_1'] ?? $bandData['performance_duration_day_2'] ?? $bandData['performance_duration_day_3'] ?? $bandData['performance_duration_day_4'] ?? null,
                        // Neue Performance Time/Duration Felder pro Tag
                        'performance_time_day_1' => $bandData['performance_time_day_1'],
                        'performance_time_day_2' => $bandData['performance_time_day_2'],
                        'performance_time_day_3' => $bandData['performance_time_day_3'],
                        'performance_time_day_4' => $bandData['performance_time_day_4'],
                        'performance_duration_day_1' => $bandData['performance_duration_day_1'] ? (int) $bandData['performance_duration_day_1'] : null,
                        'performance_duration_day_2' => $bandData['performance_duration_day_2'] ? (int) $bandData['performance_duration_day_2'] : null,
                        'performance_duration_day_3' => $bandData['performance_duration_day_3'] ? (int) $bandData['performance_duration_day_3'] : null,
                        'performance_duration_day_4' => $bandData['performance_duration_day_4'] ? (int) $bandData['performance_duration_day_4'] : null,
                        'hotel' => $bandData['hotel'] ?: null,
                        'comment' => $bandData['comment'] ?: null,
                        'travel_costs' => $bandData['travel_costs'] ? (float) $bandData['travel_costs'] : null,
                        'travel_costs_comment' => $bandData['travel_costs_comment'] ?: null,
                        'year' => (int) $bandData['year'],
                        'all_present' => false,
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Band import error: ' . $e->getMessage());
                }
            }

            // Duplikate behandeln
            if ($this->overwriteExisting) {
                foreach ($this->duplicates as $duplicate) {
                    try {
                        $duplicate['existing_band']->update([
                            'stage_id' => (int) $duplicate['stage_id'],
                            'plays_day_1' => (bool) $duplicate['plays_day_1'],
                            'plays_day_2' => (bool) $duplicate['plays_day_2'],
                            'plays_day_3' => (bool) $duplicate['plays_day_3'],
                            'plays_day_4' => (bool) $duplicate['plays_day_4'],
                            // Legacy fields 
                            'performance_time' => $duplicate['performance_time_day_1'] ?? $duplicate['performance_time_day_2'] ?? $duplicate['performance_time_day_3'] ?? $duplicate['performance_time_day_4'] ?? null,
                            'performance_duration' => $duplicate['performance_duration_day_1'] ?? $duplicate['performance_duration_day_2'] ?? $duplicate['performance_duration_day_3'] ?? $duplicate['performance_duration_day_4'] ?? null,
                            // Neue Performance Time/Duration Felder pro Tag
                            'performance_time_day_1' => $duplicate['performance_time_day_1'],
                            'performance_time_day_2' => $duplicate['performance_time_day_2'],
                            'performance_time_day_3' => $duplicate['performance_time_day_3'],
                            'performance_time_day_4' => $duplicate['performance_time_day_4'],
                            'performance_duration_day_1' => $duplicate['performance_duration_day_1'] ? (int) $duplicate['performance_duration_day_1'] : null,
                            'performance_duration_day_2' => $duplicate['performance_duration_day_2'] ? (int) $duplicate['performance_duration_day_2'] : null,
                            'performance_duration_day_3' => $duplicate['performance_duration_day_3'] ? (int) $duplicate['performance_duration_day_3'] : null,
                            'performance_duration_day_4' => $duplicate['performance_duration_day_4'] ? (int) $duplicate['performance_duration_day_4'] : null,
                            'hotel' => $duplicate['hotel'] ?: null,
                            'comment' => $duplicate['comment'] ?: null,
                            'travel_costs' => $duplicate['travel_costs'] ? (float) $duplicate['travel_costs'] : null,
                            'travel_costs_comment' => $duplicate['travel_costs_comment'] ?: null,
                        ]);

                        $updated++;
                    } catch (\Exception $e) {
                        $errors++;
                        Log::error('Band update error: ' . $e->getMessage());
                    }
                }
            } else {
                $skipped = count($this->duplicates);
            }

            $this->importResults = [
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
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
            'bandNameColumn',
            'playsDayColumn',
            'stageColumn',
            'performanceTimeColumn',
            'performanceDurationColumn',
            'hotelColumn',
            'commentColumn',
            'travelCostsColumn',
            'travelCostsCommentColumn',
            'newBands',
            'duplicates',
            'importErrors',
            'importResults',
            'overwriteExisting'
        ]);
        $this->step = 1;
    }

    public function render()
    {
        return view('livewire.admin.band-import');
    }
}
