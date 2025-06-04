{{-- resources/views/livewire/admin/person-import.blade.php --}}

<div class="container mx-auto px-4 py-8">
    @include('partials.navigation')

    <div class="mx-auto max-w-7xl">
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

        @if ($errors->any())
            <div class="mb-4 rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (count($errors) > 0)
            <div class="mb-4 rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Header -->
        <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-2xl font-bold">📥 Personen Import</h2>
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
                    <h4 class="mb-2 font-semibold text-blue-800">📋 Hinweise zum Import:</h4>
                    <ul class="space-y-1 text-sm text-blue-700">
                        <li>• <strong>Unterstützte Formate:</strong> CSV, Excel (.xlsx, .xls)</li>
                        <li>• <strong>Erste Zeile:</strong> Muss die Spaltenüberschriften enthalten</li>
                        <li>• <strong>Namen:</strong> Können in separaten Spalten oder als "Vorname Nachname" vorliegen
                        </li>
                        <li>• <strong>Gruppe:</strong> Muss ausgewählt werden (bestimmt Backstage-Rechte und Gutscheine)
                        </li>
                        <li>• <strong>Duplikate:</strong> Werden automatisch erkannt und können überschrieben werden
                        </li>
                    </ul>
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

                <!-- Group Selection -->
                <div class="mb-6">
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        Gruppe auswählen <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="selectedGroupId"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Gruppe auswählen --</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Die Gruppe bestimmt die Backstage-Berechtigung und
                        Gutschein-Anzahl für alle importierten Personen.</p>
                </div>

                <!-- Responsible Person Selection (Optional) -->
                <div class="mb-6">
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        Verantwortliche Person / Gastgeber (optional)
                    </label>
                    <select wire:model="selectedResponsiblePersonId"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Keine Verantwortliche Person --</option>
                        @foreach ($responsiblePersons as $person)
                            <option value="{{ $person->id }}">
                                {{ $person->first_name }} {{ $person->last_name }}
                                @if ($person->band)
                                    ({{ $person->band->band_name }})
                                @endif
                                @if ($person->group)
                                    - {{ $person->group->name }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Falls die importierten Personen Gäste sind, wählen Sie hier
                        den verantwortlichen Gastgeber aus.</p>
                </div>

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
                    @else
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

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Bemerkungen-Spalte (optional)
                        </label>
                        <select wire:model="remarksColumn"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Keine Bemerkungen --</option>
                            @foreach ($fileHeaders as $header)
                                <option value="{{ $header }}">{{ $header }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Nummernschild-Spalte (optional)
                        </label>
                        <select wire:model="licensePlateColumn"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Keine Nummernschilder --</option>
                            @foreach ($fileHeaders as $header)
                                <option value="{{ $header }}">{{ $header }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Falls vorhanden, werden die Nummernschilder automatisch
                            den Personen zugeordnet.</p>
                    </div>
                </div>

                <!-- Preview Table -->
                @if (count($previewData) > 0)
                    <div class="mb-6">
                        <h4 class="text-md mb-2 font-semibold">Vorschau der ersten Zeilen:</h4>

                        <!-- Debug: Show what headers were detected -->
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
                    <button wire:click="proceedToPreview"
                        class="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600"
                        wire:loading.attr="disabled" @if (!$selectedGroupId) disabled @endif>
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
                            <div class="text-2xl font-bold text-green-600">{{ count($newPersons) }}</div>
                            <div class="text-sm text-gray-600">Neue Personen</div>
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
                                {{ count($newPersons) + count($duplicates) }}</div>
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
                                    Bestehende Personen mit neuen Daten aktualisieren (Gruppe, Bemerkungen)
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

                <!-- New Persons -->
                @if (count($newPersons) > 0)
                    <div class="rounded-lg bg-white p-6 shadow-md">
                        <h4 class="mb-4 text-lg font-semibold text-green-600">✅ Neue Personen
                            ({{ count($newPersons) }})</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Zeile</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Vorname</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Nachname</th>
                                        @if ($nameFormat !== 'separate')
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                                Original Name</th>
                                        @endif
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Gruppe</th>
                                        @if ($selectedResponsiblePersonId)
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                                Gastgeber</th>
                                        @endif
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                            Bemerkungen</th>
                                        @if ($licensePlateColumn)
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                                Nummernschild</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach ($newPersons as $person)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $person['row_number'] }}
                                            </td>
                                            <td class="px-4 py-3 text-sm font-medium text-green-600">
                                                {{ $person['first_name'] }}</td>
                                            <td class="px-4 py-3 text-sm font-medium text-green-600">
                                                {{ $person['last_name'] }}</td>
                                            @if ($nameFormat !== 'separate')
                                                <td class="px-4 py-3 text-sm text-gray-500">
                                                    {{ $person['original_name_field'] ?? '-' }}</td>
                                            @endif
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $person['group']->name }}
                                            </td>
                                            @if ($selectedResponsiblePersonId)
                                                @php
                                                    $responsiblePerson = $responsiblePersons->find(
                                                        $selectedResponsiblePersonId,
                                                    );
                                                @endphp
                                                <td class="px-4 py-3 text-sm text-blue-600">
                                                    {{ $responsiblePerson ? $responsiblePerson->first_name . ' ' . $responsiblePerson->last_name : '-' }}
                                                </td>
                                            @endif
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ $person['remarks'] ?: '-' }}</td>
                                            @if ($licensePlateColumn)
                                                <td class="px-4 py-3 text-sm text-gray-900">
                                                    @if ($person['license_plate'])
                                                        <span
                                                            class="rounded bg-blue-50 px-2 py-1 font-mono text-xs text-blue-800">
                                                            {{ $person['license_plate'] }}
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
                                                <strong>Neue Daten:</strong>
                                                Gruppe: {{ $duplicate['group']->name }}
                                                @if ($duplicate['remarks'])
                                                    | Bemerkungen: {{ $duplicate['remarks'] }}
                                                @endif
                                            </div>
                                            <div class="mt-1 text-sm text-yellow-600">
                                                <strong>Bestehende Person (ID:
                                                    {{ $duplicate['existing_person']->id }}):</strong>
                                                @if ($duplicate['existing_person']->group)
                                                    Gruppe: {{ $duplicate['existing_person']->group->name }}
                                                @endif
                                                @if ($duplicate['existing_person']->band)
                                                    | Band: {{ $duplicate['existing_person']->band->band_name }}
                                                @endif
                                                @if ($duplicate['existing_person']->remarks)
                                                    | Bemerkungen: {{ $duplicate['existing_person']->remarks }}
                                                @endif
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
                                @if (count($newPersons) > 0)
                                    ({{ count($newPersons) }} neu
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
                        📥 Neuen Import starten
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
