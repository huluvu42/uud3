{{-- resources/views/livewire/admin/band-member-import.blade.php --}}

<div class="container mx-auto px-4 py-8">
    @include('partials.navigation')

    <script>
        function selectBandOption(index, bandId, bandName) {
            // Livewire-Methode aufrufen
            @this.call('selectBand', index, bandId);
        }

        // Event-Listener für Dropdown-Schließung
        document.addEventListener('livewire:init', function() {
            Livewire.on('dropdown-close', (event) => {
                const index = event.index;
                const dropdown = document.getElementById(`band-dropdown-${index}`);
                if (dropdown) {
                    dropdown.classList.add('hidden');
                }
            });
        });

        // Event-Listener für bessere UX
        document.addEventListener('DOMContentLoaded', function() {
            // Dropdown schließen bei Klick außerhalb
            document.addEventListener('click', function(e) {
                const dropdowns = document.querySelectorAll('[id^="band-dropdown-"]');
                dropdowns.forEach(dropdown => {
                    if (!dropdown.closest('.relative').contains(e.target)) {
                        dropdown.classList.add('hidden');
                    }
                });
            });
        });
    </script>

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
                <h2 class="text-2xl font-bold">👥 Bandmitglieder Import</h2>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-600">Jahr: {{ $selectedYear }}</div>

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
                        1</div>
                    <span class="{{ $step >= 1 ? 'text-blue-600' : 'text-gray-400' }} ml-2 text-sm">Datei Upload</span>
                </div>
                <div class="h-1 flex-1 bg-gray-300">
                    <div class="h-1 bg-blue-500 transition-all duration-300"
                        style="width: {{ $step >= 2 ? '100%' : '0%' }}"></div>
                </div>
                <div class="flex items-center">
                    <div
                        class="{{ $step >= 2 ? 'bg-blue-500 text-white' : 'bg-gray-300' }} flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold">
                        2</div>
                    <span class="{{ $step >= 2 ? 'text-blue-600' : 'text-gray-400' }} ml-2 text-sm">Zuordnung</span>
                </div>
                <div class="h-1 flex-1 bg-gray-300">
                    <div class="h-1 bg-blue-500 transition-all duration-300"
                        style="width: {{ $step >= 3 ? '100%' : '0%' }}"></div>
                </div>
                <div class="flex items-center">
                    <div
                        class="{{ $step >= 3 ? 'bg-blue-500 text-white' : 'bg-gray-300' }} flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold">
                        3</div>
                    <span class="{{ $step >= 3 ? 'text-blue-600' : 'text-gray-400' }} ml-2 text-sm">Vorschau</span>
                </div>
                <div class="h-1 flex-1 bg-gray-300">
                    <div class="h-1 bg-blue-500 transition-all duration-300"
                        style="width: {{ $step >= 4 ? '100%' : '0%' }}"></div>
                </div>
                <div class="flex items-center">
                    <div
                        class="{{ $step >= 4 ? 'bg-green-500 text-white' : 'bg-gray-300' }} flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold">
                        4</div>
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
                    <h4 class="mb-2 font-semibold text-blue-800">📋 Hinweise zum Bandmitglieder-Import:</h4>
                    <ul class="space-y-1 text-sm text-blue-700">
                        <li>• <strong>Unterstützte Formate:</strong> CSV, Excel (.xlsx, .xls)</li>
                        <li>• <strong>Erste Zeile:</strong> Muss die Spaltenüberschriften enthalten</li>
                        <li>• <strong>Pflichtfelder:</strong> Name (Vor-/Nachname), Band-Name</li>
                        <li>• <strong>Name-Format:</strong> Separate Spalten oder "Vorname Nachname" / "Nachname
                            Vorname"</li>
                        <li>• <strong>Band-Namen:</strong> Müssen exakt den Namen in der Datenbank entsprechen</li>
                        <li>• <strong>Unbekannte Bands:</strong> Können manuell zugeordnet oder ignoriert werden</li>
                        <li>• <strong>Backstage & Gutscheine:</strong> Werden automatisch basierend auf der Bühne
                            gesetzt</li>
                        <li>• <strong>Duplikate:</strong> Personen mit gleichem Namen in derselben Band</li>
                    </ul>
                    <div class="mt-3 text-sm text-blue-600">
                        <strong>Verfügbare Bands ({{ count($bands) }}):</strong>
                        {{ $bands->pluck('band_name')->take(5)->join(', ') }}{{ count($bands) > 5 ? '...' : '' }}
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

                <!-- Name Format Selection -->
                <div class="mb-6">
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        Name-Format <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="nameFormat"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Name-Format auswählen --</option>
                        <option value="separate">Separate Spalten für Vor- und Nachname</option>
                        <option value="firstname_lastname">Eine Spalte: "Vorname Nachname"</option>
                        <option value="lastname_firstname">Eine Spalte: "Nachname Vorname"</option>
                    </select>

                    @if ($nameFormat)
                        <div class="mt-2 text-sm text-blue-600">
                            ✓ Gewählt:
                            @if ($nameFormat === 'separate')
                                Separate Spalten - Sie können unten Vor- und Nachname-Spalten einzeln auswählen
                            @elseif($nameFormat === 'firstname_lastname')
                                "Vorname Nachname" - Wählen Sie unten die Spalte mit dem kompletten Namen
                            @else
                                "Nachname Vorname" - Wählen Sie unten die Spalte mit dem kompletten Namen
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Column Mapping -->
                <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <!-- Required Fields -->
                    <div class="md:col-span-2">
                        <h4 class="mb-3 font-medium text-red-600">Pflichtfelder</h4>
                    </div>

                    @if ($nameFormat === 'separate')
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">
                                Vorname-Spalte <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="firstNameColumn"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Spalte auswählen --</option>
                                @foreach ($fileHeaders as $header)
                                    <option value="{{ $header }}">{{ $header }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">
                                Nachname-Spalte <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="lastNameColumn"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Spalte auswählen --</option>
                                @foreach ($fileHeaders as $header)
                                    <option value="{{ $header }}">{{ $header }}</option>
                                @endforeach
                            </select>
                        </div>
                    @elseif($nameFormat)
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-gray-700">
                                Name-Spalte <span class="text-red-500">*</span>
                                @if ($nameFormat === 'firstname_lastname')
                                    <span class="text-sm text-gray-500">(Format: "Vorname Nachname")</span>
                                @elseif($nameFormat === 'lastname_firstname')
                                    <span class="text-sm text-gray-500">(Format: "Nachname Vorname")</span>
                                @endif
                            </label>
                            <select wire:model="fullNameColumn"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Spalte auswählen --</option>
                                @foreach ($fileHeaders as $header)
                                    <option value="{{ $header }}">{{ $header }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="{{ $nameFormat === 'separate' ? '' : 'md:col-span-2' }}">
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

                    <!-- Optional Fields -->
                    <div class="mt-4 md:col-span-2">
                        <h4 class="mb-3 font-medium text-gray-600">Optionale Felder</h4>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            KFZ-Kennzeichen (optional)
                        </label>
                        <select wire:model="licensePlateColumn"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Keine Kennzeichen --</option>
                            @foreach ($fileHeaders as $header)
                                <option value="{{ $header }}">{{ $header }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Falls vorhanden, werden die Kennzeichen automatisch den
                            Personen zugeordnet.</p>
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

                    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-5">
                        <div class="rounded bg-green-50 p-4 text-center">
                            <div class="text-2xl font-bold text-green-600">{{ count($newMembers) }}</div>
                            <div class="text-sm text-gray-600">Neue Mitglieder</div>
                        </div>
                        <div class="rounded bg-yellow-50 p-4 text-center">
                            <div class="text-2xl font-bold text-yellow-600">{{ count($duplicates) }}</div>
                            <div class="text-sm text-gray-600">Duplikate gefunden</div>
                        </div>
                        <div class="rounded bg-orange-50 p-4 text-center">
                            <div class="text-2xl font-bold text-orange-600">
                                @php
                                    $totalUnknownMembers = array_sum(array_column($unknownBands, 'member_count'));
                                @endphp
                                {{ $totalUnknownMembers }}
                            </div>
                            <div class="text-sm text-gray-600">Unbekannte Band-Mitglieder</div>
                        </div>
                        <div class="rounded bg-red-50 p-4 text-center">
                            <div class="text-2xl font-bold text-red-600">{{ count($importErrors) }}</div>
                            <div class="text-sm text-gray-600">Fehler</div>
                        </div>
                        <div class="rounded bg-blue-50 p-4 text-center">
                            <div class="text-2xl font-bold text-blue-600">
                                @php
                                    $totalUnknownMembers = array_sum(array_column($unknownBands, 'member_count'));
                                @endphp
                                {{ count($newMembers) + count($duplicates) + $totalUnknownMembers }}
                            </div>
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
                                    Bestehende Bandmitglieder mit neuen Daten aktualisieren (Backstage-Rechte,
                                    Gutscheine)
                                </span>
                            </label>
                            <p class="mt-1 text-xs text-yellow-600">
                                Wenn nicht aktiviert, werden Duplikate übersprungen und nicht importiert.
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Unknown Bands Section -->
                @if (count($unknownBands) > 0)
                    <div class="rounded-lg bg-white p-6 shadow-md">
                        <h4 class="mb-4 text-lg font-semibold text-orange-600">🔍 Unbekannte Bands - Manuelle Zuordnung
                            erforderlich</h4>
                        <p class="mb-4 text-sm text-gray-600">
                            Die folgenden Band-Namen wurden nicht in der Datenbank gefunden. Sie können diese manuell
                            zuordnen oder ignorieren.
                            <strong>Alle Mitglieder einer Band werden zusammen zugeordnet.</strong>
                        </p>

                        <div class="space-y-6">
                            @foreach ($unknownBands as $index => $unknownBandGroup)
                                <div class="rounded border border-orange-200 bg-orange-50 p-4">
                                    <!-- Band Header mit Zuordnung -->
                                    <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                                        <div>
                                            <div class="text-lg font-bold text-orange-800">
                                                {{ $unknownBandGroup['band_name'] }}</div>
                                            <div class="text-sm text-orange-700">
                                                {{ $unknownBandGroup['member_count'] }}
                                                {{ $unknownBandGroup['member_count'] === 1 ? 'Mitglied' : 'Mitglieder' }}
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-orange-800">Aktion:</label>
                                            <select wire:model.live="bandMappings.{{ $index }}.action"
                                                class="w-full rounded border border-orange-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                                                <option value="ignore">❌ Ignorieren (nicht importieren)</option>
                                                <option value="map">✅ Zuordnen zu bestehender Band</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-orange-800">Band
                                                auswählen:</label>
                                            @php
                                                $currentAction = $bandMappings[$index]['action'] ?? 'ignore';
                                            @endphp
                                            @if ($currentAction === 'map')
                                                <div class="relative">
                                                    <!-- Such-Input -->
                                                    <input type="text"
                                                        wire:model.live.debounce.300ms="bandSearchTerms.{{ $index }}"
                                                        placeholder="Band suchen (mind. 2 Zeichen)..."
                                                        class="w-full rounded border border-orange-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                                                        autocomplete="off">

                                                    <!-- Dropdown-Liste -->
                                                    @if (!empty($bandSearchTerms[$index]) && strlen($bandSearchTerms[$index]) >= 2)
                                                        @php
                                                            $searchTerm = strtolower($bandSearchTerms[$index]);
                                                            $filteredBands = $bands->filter(function ($band) use (
                                                                $searchTerm,
                                                            ) {
                                                                return str_contains(
                                                                    strtolower($band->band_name),
                                                                    $searchTerm,
                                                                ) ||
                                                                    str_contains(
                                                                        strtolower($band->stage->name),
                                                                        $searchTerm,
                                                                    );
                                                            });
                                                        @endphp

                                                        @if ($filteredBands->count() > 0)
                                                            <div
                                                                class="absolute z-50 mt-1 max-h-48 w-full overflow-auto rounded border border-gray-300 bg-white shadow-lg">
                                                                @foreach ($filteredBands as $band)
                                                                    @php
                                                                        $isSelected =
                                                                            ($bandMappings[$index][
                                                                                'selected_band_id'
                                                                            ] ??
                                                                                '') ==
                                                                            $band->id;
                                                                    @endphp
                                                                    <div class="band-option-{{ $index }}-{{ $band->id }} {{ $isSelected ? 'bg-blue-50 text-blue-700' : '' }} w-full cursor-pointer px-3 py-2 text-left hover:bg-gray-100"
                                                                        onclick="selectBandOption({{ $index }}, {{ $band->id }}, '{{ addslashes($band->band_name) }}')">
                                                                        <div class="font-medium">
                                                                            {{ $band->band_name }}</div>
                                                                        <div class="text-xs text-gray-500">
                                                                            {{ $band->stage->name }}</div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <div
                                                                class="absolute z-50 mt-1 w-full rounded border border-gray-300 bg-white shadow-lg">
                                                                <div class="px-3 py-2 text-sm text-gray-500">
                                                                    Keine Bands gefunden für
                                                                    "{{ $bandSearchTerms[$index] }}"
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @endif

                                                    <!-- Auswahl-Anzeige -->
                                                    @if (!empty($bandMappings[$index]['selected_band_id']))
                                                        @php
                                                            $selectedBand = $bands->find(
                                                                $bandMappings[$index]['selected_band_id'],
                                                            );
                                                        @endphp
                                                        @if ($selectedBand)
                                                            <div
                                                                class="mt-1 flex items-center justify-between rounded bg-green-50 px-2 py-1 text-sm text-green-700">
                                                                <span>✓ {{ $selectedBand->band_name }}
                                                                    ({{ $selectedBand->stage->name }})</span>
                                                                <button type="button"
                                                                    wire:click="clearBandSelection({{ $index }})"
                                                                    class="ml-2 text-lg font-bold leading-none text-red-600 hover:text-red-800">×</button>
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>
                                            @else
                                                <input type="text" disabled placeholder="Erst 'Zuordnen' auswählen"
                                                    class="w-full cursor-not-allowed rounded border border-orange-300 bg-gray-100 px-3 py-2 text-sm">
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Status-Anzeige -->
                                    @if (($bandMappings[$index]['action'] ?? 'ignore') === 'map' && !empty($bandMappings[$index]['selected_band_id']))
                                        @php
                                            $selectedBand = $bands->find($bandMappings[$index]['selected_band_id']);
                                        @endphp
                                        @if ($selectedBand)
                                            <div class="mb-4 rounded bg-green-100 p-3">
                                                <div class="flex items-center text-green-700">
                                                    <span class="mr-2 text-lg">✅</span>
                                                    <div>
                                                        <div class="font-semibold">
                                                            {{ $unknownBandGroup['member_count'] }} Mitglieder werden
                                                            zugeordnet zu:</div>
                                                        <div class="text-sm">
                                                            <strong>{{ $selectedBand->band_name }}</strong>
                                                            (Bühne: {{ $selectedBand->stage->name }})
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @elseif (($bandMappings[$index]['action'] ?? 'ignore') === 'ignore')
                                        <div class="mb-4 rounded bg-gray-100 p-3">
                                            <div class="flex items-center text-gray-600">
                                                <span class="mr-2 text-lg">❌</span>
                                                <div>
                                                    <div class="font-semibold">{{ $unknownBandGroup['member_count'] }}
                                                        Mitglieder werden ignoriert</div>
                                                    <div class="text-sm">Diese Einträge werden nicht importiert</div>
                                                </div>
                                            </div>
                                        </div>
                                    @elseif (($bandMappings[$index]['action'] ?? 'ignore') === 'map')
                                        <div class="mb-4 rounded bg-yellow-100 p-3">
                                            <div class="flex items-center text-yellow-700">
                                                <span class="mr-2 text-lg">⚠️</span>
                                                <div>
                                                    <div class="font-semibold">Bitte wählen Sie eine Band aus</div>
                                                    <div class="text-sm">Wählen Sie eine Band aus der Liste um
                                                        fortzufahren</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Mitgliederliste (zusammenklappbar) -->
                                    <div class="border-t border-orange-200 pt-3">
                                        <details class="group">
                                            <summary
                                                class="cursor-pointer text-sm font-medium text-orange-800 hover:text-orange-900">
                                                <span class="group-open:hidden">▶ Mitglieder anzeigen
                                                    ({{ $unknownBandGroup['member_count'] }})</span>
                                                <span class="hidden group-open:inline">▼ Mitglieder ausblenden</span>
                                            </summary>
                                            <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-3">
                                                @foreach ($unknownBandGroup['members'] as $member)
                                                    <div class="rounded bg-white p-2 text-sm">
                                                        <div class="font-medium text-gray-800">
                                                            {{ $member['member_name'] }}</div>
                                                        <div class="text-xs text-gray-500">Zeile
                                                            {{ $member['row_number'] }}</div>
                                                        @if ($member['license_plate'])
                                                            <div class="text-xs text-blue-600">
                                                                🚗 {{ $member['license_plate'] }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 rounded bg-blue-50 p-3 text-sm text-blue-700">
                            <strong>💡 Tipp:</strong> Ähnliche Band-Namen können auf Schreibfehler hindeuten.
                            Prüfen Sie die Zuordnung sorgfältig oder ignorieren Sie Einträge mit offensichtlichen
                            Fehlern.
                            <br>
                            <strong>🎯 Effizienz:</strong> Alle Mitglieder einer unbekannten Band werden mit einer
                            Zuordnung abgehandelt!
                        </div>
                    </div>
                @endif

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

                <!-- New Members -->
                @if (count($newMembers) > 0)
                    <div class="rounded-lg bg-white p-6 shadow-md">
                        <h4 class="mb-4 text-lg font-semibold text-green-600">✅ Neue Bandmitglieder
                            ({{ count($newMembers) }})</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Zeile</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Band</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Bühne</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Backstage-Tage</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Gutscheine</th>
                                        @if ($licensePlateColumn)
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                                KFZ</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach ($newMembers as $member)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $member['row_number'] }}
                                            </td>
                                            <td class="px-4 py-3 text-sm font-medium text-green-600">
                                                {{ $member['first_name'] }} {{ $member['last_name'] }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ $member['band']->band_name }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ $member['band']->stage->name }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                @php
                                                    $backstageDays = [];
                                                    for ($i = 1; $i <= 4; $i++) {
                                                        if ($member["backstage_day_$i"]) {
                                                            $backstageDays[] = $i;
                                                        }
                                                    }
                                                @endphp
                                                {{ empty($backstageDays) ? 'Keine' : 'Tag ' . implode(', ', $backstageDays) }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                @php
                                                    $voucherDays = [];
                                                    for ($i = 1; $i <= 4; $i++) {
                                                        if ($member["voucher_day_$i"] > 0) {
                                                            $voucherDays[] = $member["voucher_day_$i"];
                                                        }
                                                    }
                                                @endphp
                                                {{ empty($voucherDays) ? 'Keine' : implode(', ', $voucherDays) . ' Gutscheine' }}
                                            </td>
                                            @if ($licensePlateColumn)
                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                    @if ($member['license_plate'])
                                                        <span
                                                            class="rounded bg-blue-50 px-2 py-1 font-mono text-xs text-blue-800">
                                                            {{ $member['license_plate'] }}
                                                        </span>
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
                                                Zeile {{ $duplicate['row_number'] }}: {{ $duplicate['first_name'] }}
                                                {{ $duplicate['last_name'] }}
                                            </div>
                                            <div class="mt-1 text-sm text-yellow-700">
                                                <strong>Band:</strong> {{ $duplicate['band']->band_name }}
                                                ({{ $duplicate['band']->stage->name }})
                                            </div>
                                            <div class="mt-1 text-sm text-yellow-600">
                                                <strong>Bestehende Person (ID:
                                                    {{ $duplicate['existing_member']->id }}):</strong>
                                                Bereits in der Band vorhanden
                                                @if ($duplicate['existing_member']->present)
                                                    | Anwesend ✓
                                                @endif
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            @if ($overwriteExisting)
                                                <span class="rounded bg-blue-100 px-2 py-1 text-xs text-blue-800">
                                                    Wird aktualisiert
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
                                @if (count($newMembers) > 0)
                                    ({{ count($newMembers) }} neu
                                    @if (count($duplicates) > 0 && $overwriteExisting)
                                        + {{ count($duplicates) }} aktualisiert
                                    @endif
                                    @if (count($unknownBands) > 0)
                                        @php
                                            $mappedBands = array_filter($bandMappings, function ($mapping) {
                                                return ($mapping['action'] ?? 'ignore') === 'map' &&
                                                    !empty($mapping['selected_band_id']);
                                            });
                                            $totalMappedMembers = 0;
                                            foreach ($mappedBands as $index => $mapping) {
                                                if (isset($unknownBands[$index])) {
                                                    $totalMappedMembers += $unknownBands[$index]['member_count'];
                                                }
                                            }
                                        @endphp
                                        @if ($totalMappedMembers > 0)
                                            + {{ $totalMappedMembers }} zugeordnet
                                        @endif
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
                        👥 Neuen Import starten
                    </button>
                    <a href="{{ route('management.persons') }}"
                        class="inline-block rounded bg-green-500 px-6 py-2 text-white hover:bg-green-600">
                        👥 Zu den Personen
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
