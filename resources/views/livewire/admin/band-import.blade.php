{{-- resources/views/livewire/admin/band-import.blade.php --}}

<div class="container mx-auto px-4 py-8">
    @include('partials.navigation')

    <div class="mx-auto mt-8 max-w-7xl">
        <!-- Success/Error Messages -->
        @if (session()->has('success'))
            <div class="mb-4 rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-4 rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <!-- Header -->
        <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-2xl font-bold">🎵 Band Import</h2>
                <div class="flex items-center space-x-4">
                    <select wire:model="selectedYear"
                        class="rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        @if ($step > 1) disabled @endif>
                        @for ($year = now()->year; $year >= 2020; $year--)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endfor
                    </select>

                    @if ($step > 1)
                        <button wire:click="resetImport"
                            class="rounded bg-gray-500 px-4 py-2 text-white hover:bg-gray-600">
                            🔄 Neustart
                        </button>
                    @endif
                </div>
            </div>

            <!-- Progress Steps -->
            <div class="mb-6 flex items-center space-x-4">
                <div class="flex items-center">
                    <div
                        class="{{ $step >= 1 ? 'bg-blue-500 text-white' : 'bg-gray-300' }} flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold">
                        1
                    </div>
                    <span class="{{ $step >= 1 ? 'text-blue-600' : 'text-gray-400' }} ml-2 text-sm">Datei Upload</span>
                </div>
                <div class="h-1 flex-1 bg-gray-300">
                    <div class="h-1 bg-blue-500 transition-all duration-300"
                        style="width: {{ $step >= 2 ? '100%' : '0%' }}"></div>
                </div>
                <div class="flex items-center">
                    <div
                        class="{{ $step >= 2 ? 'bg-blue-500 text-white' : 'bg-gray-300' }} flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold">
                        2
                    </div>
                    <span class="{{ $step >= 2 ? 'text-blue-600' : 'text-gray-400' }} ml-2 text-sm">Zuordnung</span>
                </div>
                <div class="h-1 flex-1 bg-gray-300">
                    <div class="h-1 bg-blue-500 transition-all duration-300"
                        style="width: {{ $step >= 3 ? '100%' : '0%' }}"></div>
                </div>
                <div class="flex items-center">
                    <div
                        class="{{ $step >= 3 ? 'bg-blue-500 text-white' : 'bg-gray-300' }} flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold">
                        3
                    </div>
                    <span class="{{ $step >= 3 ? 'text-blue-600' : 'text-gray-400' }} ml-2 text-sm">Vorschau</span>
                </div>
                <div class="h-1 flex-1 bg-gray-300">
                    <div class="h-1 bg-blue-500 transition-all duration-300"
                        style="width: {{ $step >= 4 ? '100%' : '0%' }}"></div>
                </div>
                <div class="flex items-center">
                    <div
                        class="{{ $step >= 4 ? 'bg-green-500 text-white' : 'bg-gray-300' }} flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold">
                        4
                    </div>
                    <span
                        class="{{ $step >= 4 ? 'text-green-600' : 'text-gray-400' }} ml-2 text-sm">Abgeschlossen</span>
                </div>
            </div>
        </div>

        <!-- Step 1: File Upload -->
        @if ($step === 1)
            <div class="rounded-lg bg-white p-6 shadow-md">
                <h3 class="mb-4 text-lg font-semibold">1. Datei auswählen</h3>

                <div class="mb-6">
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        Datei hochladen (CSV, Excel .xlsx/.xls)
                    </label>
                    <input type="file" wire:model="file" accept=".csv,.xlsx,.xls"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                    @if ($file)
                        <div class="mt-2 text-sm text-green-600">
                            ✓ {{ $file->getClientOriginalName() }} ausgewählt
                        </div>
                    @endif
                </div>

                <div class="rounded-lg bg-blue-50 p-4">
                    <h4 class="mb-2 font-semibold text-blue-800">📋 Hinweise zum Band-Import:</h4>
                    <ul class="space-y-1 text-sm text-blue-700">
                        <li>• <strong>Unterstützte Formate:</strong> CSV, Excel (.xlsx, .xls)</li>
                        <li>• <strong>Erste Zeile:</strong> Muss die Spaltenüberschriften enthalten</li>
                        <li>• <strong>Pflichtfelder:</strong> Band-Name, Bühne, Auftrittstag</li>
                        <li>• <strong>Auftrittstag:</strong> Donnerstag, Freitag, Samstag oder Sonntag</li>
                        <li>• <strong>Bühne:</strong> Muss exakt dem Namen in der Datenbank entsprechen</li>
                        <li>• <strong>Mehrfache Auftritte:</strong> Werden automatisch zu einem Band-Eintrag
                            zusammengeführt</li>
                        <li>• <strong>Duplikate:</strong> Werden automatisch erkannt und können überschrieben werden
                        </li>
                    </ul>
                    <div class="mt-3 text-sm text-blue-600">
                        <strong>Verfügbare Bühnen:</strong>
                        {{ $stages->pluck('name')->join(', ') }}
                    </div>
                </div>

                @if ($isLoading)
                    <div class="mt-4 text-center">
                        <div class="text-blue-600">Datei wird verarbeitet...</div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Step 2: Column Mapping -->
        @if ($step === 2)
            <div class="rounded-lg bg-white p-6 shadow-md">
                <h3 class="mb-4 text-lg font-semibold">2. Spalten zuordnen</h3>

                <!-- Column Mapping -->
                <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <!-- Required Fields -->
                    <div class="md:col-span-2">
                        <h4 class="mb-3 font-medium text-red-600">Pflichtfelder</h4>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Band-Name <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="bandNameColumn"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Spalte auswählen --</option>
                            @foreach ($fileHeaders as $header)
                                <option value="{{ $header }}">{{ $header }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Bühne <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="stageColumn"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Spalte auswählen --</option>
                            @foreach ($fileHeaders as $header)
                                <option value="{{ $header }}">{{ $header }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Auftrittstag <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="playsDayColumn"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Spalte auswählen --</option>
                            @foreach ($fileHeaders as $header)
                                <option value="{{ $header }}">{{ $header }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            Erlaubte Werte: Donnerstag, Freitag, Samstag, Sonntag (oder Thu, Fri, Sat, Sun)
                        </p>
                    </div>

                    <!-- Optional Fields -->
                    <div class="mt-4 md:col-span-2">
                        <h4 class="mb-3 font-medium text-gray-600">Optionale Felder</h4>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Auftrittszeit (optional)
                        </label>
                        <select wire:model="performanceTimeColumn"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Keine Auftrittszeit --</option>
                            @foreach ($fileHeaders as $header)
                                <option value="{{ $header }}">{{ $header }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Format: HH:MM (z.B. 20:30)</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Auftrittszeit in Minuten (optional)
                        </label>
                        <select wire:model="performanceDurationColumn"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Keine Dauer --</option>
                            @foreach ($fileHeaders as $header)
                                <option value="{{ $header }}">{{ $header }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Als Zahl (Minuten) oder "1h 30min"</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Hotel (optional)
                        </label>
                        <select wire:model="hotelColumn"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Kein Hotel --</option>
                            @foreach ($fileHeaders as $header)
                                <option value="{{ $header }}">{{ $header }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Kommentar (optional)
                        </label>
                        <select wire:model="commentColumn"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Kein Kommentar --</option>
                            @foreach ($fileHeaders as $header)
                                <option value="{{ $header }}">{{ $header }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Reisekosten (optional)
                        </label>
                        <select wire:model="travelCostsColumn"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Keine Reisekosten --</option>
                            @foreach ($fileHeaders as $header)
                                <option value="{{ $header }}">{{ $header }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Als Dezimalzahl (z.B. 150.50)</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Reisekosten-Kommentar (optional)
                        </label>
                        <select wire:model="travelCostsCommentColumn"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Kein Reisekosten-Kommentar --</option>
                            @foreach ($fileHeaders as $header)
                                <option value="{{ $header }}">{{ $header }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Preview Table -->
                @if (count($previewData) > 0)
                    <div class="mb-6">
                        <h4 class="text-md mb-2 font-semibold">Vorschau der ersten Zeilen:</h4>

                        <div class="mb-2 text-xs text-gray-500">
                            <strong>Erkannte Spalten:</strong> {{ implode(', ', $fileHeaders) }}
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border">
                                <thead class="bg-gray-50">
                                    <tr>
                                        @foreach ($fileHeaders as $header)
                                            <th
                                                class="border-r px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                                {{ $header }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach ($previewData as $row)
                                        <tr>
                                            @foreach ($fileHeaders as $header)
                                                <td class="border-r px-3 py-2 text-sm text-gray-900">
                                                    {{ $row[$header] ?? '-' }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Action Buttons -->
                <div class="flex justify-between">
                    <button wire:click="resetImport"
                        class="rounded bg-gray-500 px-4 py-2 text-white hover:bg-gray-600">
                        ← Zurück
                    </button>
                    <button type="button" wire:click="proceedToPreview"
                        class="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600 disabled:cursor-not-allowed disabled:opacity-50"
                        wire:loading.attr="disabled" wire:target="proceedToPreview">
                        <span wire:loading.remove wire:target="proceedToPreview">Weiter zur Vorschau →</span>
                        <span wire:loading wire:target="proceedToPreview">Analysiere Daten...</span>
                    </button>
                </div>
            </div>
        @endif

        <!-- Step 3: Preview -->
        @if ($step === 3)
            <div class="space-y-6">
                <!-- Summary -->
                <div class="rounded-lg bg-white p-6 shadow-md">
                    <h3 class="mb-4 text-lg font-semibold">3. Import-Vorschau</h3>

                    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div class="rounded bg-green-50 p-4 text-center">
                            <div class="text-2xl font-bold text-green-600">{{ count($newBands) }}</div>
                            <div class="text-sm text-gray-600">Neue Bands</div>
                        </div>
                        <div class="rounded bg-yellow-50 p-4 text-center">
                            <div class="text-2xl font-bold text-yellow-600">{{ count($duplicates) }}</div>
                            <div class="text-sm text-gray-600">Duplikate gefunden</div>
                        </div>
                        <div class="rounded bg-red-50 p-4 text-center">
                            <div class="text-2xl font-bold text-red-600">{{ count($importErrors) }}</div>
                            <div class="text-sm text-gray-600">Fehler</div>
                        </div>
                        <div class="rounded bg-blue-50 p-4 text-center">
                            <div class="text-2xl font-bold text-blue-600">
                                {{ count($newBands) + count($duplicates) }}</div>
                            <div class="text-sm text-gray-600">Gesamt verarbeitet</div>
                        </div>
                    </div>

                    <!-- Duplicate Handling Option -->
                    @if (count($duplicates) > 0)
                        <div class="mb-6 rounded border border-yellow-200 bg-yellow-50 p-4">
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="overwriteExisting" class="mr-2">
                                <span class="text-yellow-800">
                                    <strong>Duplikate überschreiben:</strong>
                                    Bestehende Bands mit neuen Daten aktualisieren
                                </span>
                            </label>
                            <p class="mt-1 text-xs text-yellow-600">
                                Wenn nicht aktiviert, werden Duplikate übersprungen und nicht importiert.
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Errors -->
                @if (count($importErrors) > 0)
                    <div class="rounded-lg bg-white p-6 shadow-md">
                        <h4 class="mb-4 text-lg font-semibold text-red-600">❌ Fehler beim Verarbeiten</h4>
                        <div class="space-y-3">
                            @foreach ($importErrors as $error)
                                <div class="rounded border border-red-200 bg-red-50 p-3">
                                    <div class="font-medium text-red-800">Zeile {{ $error['row_number'] }}:
                                        {{ $error['message'] }}</div>
                                    <div class="mt-1 text-xs text-red-600">
                                        Rohdaten: {{ json_encode($error['raw_data']) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- New Bands -->
                @if (count($newBands) > 0)
                    <div class="rounded-lg bg-white p-6 shadow-md">
                        <h4 class="mb-4 text-lg font-semibold text-green-600">✅ Neue Bands
                            ({{ count($newBands) }})</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Zeile</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Band-Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Bühne</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Auftrittstage</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Performance Times</th>
                                        @if ($hotelColumn)
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                                Hotel</th>
                                        @endif
                                        @if ($travelCostsColumn)
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                                Reisekosten</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach ($newBands as $band)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $band['row_number'] }}</td>
                                            <td class="px-4 py-3 text-sm font-medium text-green-600">
                                                {{ $band['band_name'] }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $band['stage']->name }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                @php
                                                    $days = [];
                                                    if ($band['plays_day_1']) {
                                                        $days[] = 'Do';
                                                    }
                                                    if ($band['plays_day_2']) {
                                                        $days[] = 'Fr';
                                                    }
                                                    if ($band['plays_day_3']) {
                                                        $days[] = 'Sa';
                                                    }
                                                    if ($band['plays_day_4']) {
                                                        $days[] = 'So';
                                                    }
                                                @endphp
                                                {{ implode(', ', $days) }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                @php
                                                    $times = [];
                                                    if (
                                                        isset($band['performance_time_day_1']) &&
                                                        $band['performance_time_day_1']
                                                    ) {
                                                        $times[] =
                                                            'Do: ' . substr($band['performance_time_day_1'], 0, 5);
                                                    }
                                                    if (
                                                        isset($band['performance_time_day_2']) &&
                                                        $band['performance_time_day_2']
                                                    ) {
                                                        $times[] =
                                                            'Fr: ' . substr($band['performance_time_day_2'], 0, 5);
                                                    }
                                                    if (
                                                        isset($band['performance_time_day_3']) &&
                                                        $band['performance_time_day_3']
                                                    ) {
                                                        $times[] =
                                                            'Sa: ' . substr($band['performance_time_day_3'], 0, 5);
                                                    }
                                                    if (
                                                        isset($band['performance_time_day_4']) &&
                                                        $band['performance_time_day_4']
                                                    ) {
                                                        $times[] =
                                                            'So: ' . substr($band['performance_time_day_4'], 0, 5);
                                                    }
                                                @endphp
                                                {{ implode(', ', $times) ?: '-' }}
                                            </td>
                                            @if ($hotelColumn)
                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                    {{ $band['hotel'] ?: '-' }}</td>
                                            @endif
                                            @if ($travelCostsColumn)
                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                    @if (isset($band['travel_costs']) && $band['travel_costs'])
                                                        {{ number_format($band['travel_costs'], 2) }}€
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Duplicates -->
                @if (count($duplicates) > 0)
                    <div class="rounded-lg bg-white p-6 shadow-md">
                        <h4 class="mb-4 text-lg font-semibold text-yellow-600">⚠️ Duplikate gefunden
                            ({{ count($duplicates) }})</h4>
                        <div class="space-y-4">
                            @foreach ($duplicates as $duplicate)
                                <div class="rounded border border-yellow-200 bg-yellow-50 p-4">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="font-medium text-yellow-800">
                                                Zeile {{ $duplicate['row_number'] }}: {{ $duplicate['band_name'] }}
                                            </div>
                                            <div class="mt-1 text-sm text-yellow-700">
                                                <strong>Neue Daten:</strong>
                                                Bühne: {{ $duplicate['stage']->name }} |
                                                Tage: @php
                                                    $days = [];
                                                    if ($duplicate['plays_day_1']) {
                                                        $days[] = 'Do';
                                                    }
                                                    if ($duplicate['plays_day_2']) {
                                                        $days[] = 'Fr';
                                                    }
                                                    if ($duplicate['plays_day_3']) {
                                                        $days[] = 'Sa';
                                                    }
                                                    if ($duplicate['plays_day_4']) {
                                                        $days[] = 'So';
                                                    }
                                                @endphp
                                                {{ implode(', ', $days) }}
                                            </div>
                                            <div class="mt-1 text-sm text-yellow-600">
                                                <strong>Bestehende Band (ID:
                                                    {{ $duplicate['existing_band']->id }}):</strong>
                                                @if ($duplicate['existing_band']->stage)
                                                    Bühne: {{ $duplicate['existing_band']->stage->name }}
                                                @endif
                                                | Jahr: {{ $duplicate['existing_band']->year }}
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            @if ($overwriteExisting)
                                                <span class="rounded bg-blue-100 px-2 py-1 text-xs text-blue-800">
                                                    Wird überschrieben
                                                </span>
                                            @else
                                                <span class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-800">
                                                    Wird übersprungen
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Action Buttons -->
                <div class="rounded-lg bg-white p-6 shadow-md">
                    <div class="flex justify-between">
                        <button wire:click="$set('step', 2)"
                            class="rounded bg-gray-500 px-4 py-2 text-white hover:bg-gray-600">
                            ← Zurück zur Zuordnung
                        </button>
                        <button wire:click="executeImport"
                            class="rounded bg-green-500 px-6 py-2 text-white hover:bg-green-600"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="executeImport">
                                🚀 Import starten
                                @if (count($newBands) > 0)
                                    ({{ count($newBands) }} neu
                                    @if (count($duplicates) > 0 && $overwriteExisting)
                                        + {{ count($duplicates) }} aktualisiert
                                    @endif
                                    )
                                @endif
                            </span>
                            <span wire:loading wire:target="executeImport">Importiere...</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Step 4: Results -->
        @if ($step === 4)
            <div class="rounded-lg bg-white p-6 shadow-md">
                <h3 class="mb-4 text-lg font-semibold text-green-600">🎉 Import abgeschlossen!</h3>

                <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div class="rounded bg-green-50 p-4 text-center">
                        <div class="text-2xl font-bold text-green-600">{{ $importResults['imported'] ?? 0 }}</div>
                        <div class="text-sm text-gray-600">Neu importiert</div>
                    </div>
                    <div class="rounded bg-blue-50 p-4 text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ $importResults['updated'] ?? 0 }}</div>
                        <div class="text-sm text-gray-600">Aktualisiert</div>
                    </div>
                    <div class="rounded bg-yellow-50 p-4 text-center">
                        <div class="text-2xl font-bold text-yellow-600">{{ $importResults['skipped'] ?? 0 }}</div>
                        <div class="text-sm text-gray-600">Übersprungen</div>
                    </div>
                    <div class="rounded bg-red-50 p-4 text-center">
                        <div class="text-2xl font-bold text-red-600">{{ $importResults['errors'] ?? 0 }}</div>
                        <div class="text-sm text-gray-600">Fehler</div>
                    </div>
                </div>

                <div class="flex justify-center space-x-4">
                    <button wire:click="resetImport"
                        class="rounded bg-blue-500 px-6 py-2 text-white hover:bg-blue-600">
                        🎵 Neuen Import starten
                    </button>
                    <a href="{{ route('management.bands') }}"
                        class="inline-block rounded bg-green-500 px-6 py-2 text-white hover:bg-green-600">
                        🎵 Zu den Bands
                    </a>
                </div>
            </div>
        @endif

        @if ($isLoading)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                <div class="mx-4 w-full max-w-sm rounded-lg bg-white p-6">
                    <div class="text-center">
                        <div class="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-b-2 border-blue-500"></div>
                        <div class="text-lg font-semibold">Verarbeite Daten...</div>
                        <div class="text-sm text-gray-500">Bitte warten Sie einen Moment.</div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
