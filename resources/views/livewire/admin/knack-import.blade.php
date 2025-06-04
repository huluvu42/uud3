{{-- resources/views/livewire/admin/knack-import.blade.php --}}

<div class="container mx-auto px-4 py-8">
    @include('partials.navigation')

    <div class="mx-auto max-w-full px-4">
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

        <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
            <h2 class="mb-6 text-2xl font-bold">Knack Daten Import</h2>

            <!-- Knack Object Auswahl -->
            <div class="mb-8">
                <h3 class="mb-4 text-lg font-semibold">Knack Object auswählen</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Knack Object</label>
                        <select wire:model="selectedKnackObjectId"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Object auswählen...</option>
                            @foreach ($knackObjects as $knackObject)
                                <option value="{{ $knackObject->id }}">
                                    {{ $knackObject->name }} ({{ $knackObject->object_key }})
                                </option>
                            @endforeach
                        </select>
                        @if (count($knackObjects) === 0)
                            <p class="mt-1 text-xs text-red-500">
                                ⚠️ Keine vollständig konfigurierten Knack Objects verfügbar!
                                <a href="{{ route('admin.knack-objects') }}" class="text-blue-500 hover:underline">
                                    Jetzt konfigurieren
                                </a>
                            </p>
                        @else
                            <p class="mt-1 text-xs text-gray-500">
                                💡 Objects mit App ID und API Key.
                                <a href="{{ route('admin.knack-objects') }}" class="text-blue-500 hover:underline">
                                    Objects verwalten
                                </a>
                            </p>
                        @endif
                    </div>

                    @if ($selectedKnackObjectId)
                        <div class="flex items-end">
                            <button wire:click="testConnection"
                                class="rounded bg-green-500 px-4 py-2 text-white hover:bg-green-600"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="testConnection">🔍 Verbindung testen</span>
                                <span wire:loading wire:target="testConnection">Teste...</span>
                            </button>
                        </div>
                    @endif
                </div>

                @if ($selectedKnackObjectId)
                    @php
                        $selectedObject = $knackObjects->find($selectedKnackObjectId);
                    @endphp
                    @if ($selectedObject)
                        <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <h4 class="mb-2 font-medium text-blue-800">📋 Ausgewähltes Object:</h4>
                            <div class="space-y-1 text-sm text-blue-700">
                                <div><strong>Name:</strong> {{ $selectedObject->name }}</div>
                                <div><strong>Object Key:</strong> <code
                                        class="rounded bg-blue-100 px-1">{{ $selectedObject->object_key }}</code></div>
                                <div><strong>App ID:</strong> {{ $selectedObject->app_id }}</div>
                                <div><strong>API Key:</strong> {{ $selectedObject->getMaskedApiKey() }}</div>
                                @if ($selectedObject->description)
                                    <div><strong>Beschreibung:</strong> {{ $selectedObject->description }}</div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            <!-- Filter Konfiguration -->
            <div class="mb-8">
                <h3 class="mb-4 text-lg font-semibold">Filter Einstellungen</h3>
                <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <p class="text-sm text-blue-800">
                        <strong>Optional:</strong> Filter können leer gelassen werden um alle Datensätze zu importieren.
                        <br><strong>Wichtig:</strong> Fügen Sie jeden Filter einzeln hinzu! Nicht mehrere Filter
                        gleichzeitig in ein Feld eingeben.
                    </p>
                </div>

                <!-- Include Filter -->
                <div class="mb-4">
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        Include Filter - <span class="text-gray-500">(optional)</span>
                        <br><span class="text-xs text-gray-500">Jobliste muss einen dieser Werte enthalten</span>
                    </label>
                    <div class="mb-2 flex gap-2">
                        <input type="text" wire:model="newIncludeFilter" wire:keydown.enter="addIncludeFilter"
                            wire:key="include-filter-input"
                            class="flex-1 rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Einen Filter eingeben (z.B. Mitarbeiter) und auf 'Hinzufügen' klicken">
                        <button wire:click="addIncludeFilter"
                            class="rounded bg-green-500 px-4 py-2 text-white hover:bg-green-600">
                            Hinzufügen
                        </button>
                    </div>

                    <div class="mb-3 rounded border border-yellow-200 bg-yellow-50 p-2">
                        <p class="text-xs text-yellow-800">
                            💡 <strong>Tipp:</strong> Fügen Sie jeden Begriff einzeln hinzu (z.B. erst "Mitarbeiter",
                            dann "Backstage").
                            Geben Sie nicht "Mitarbeiter, Backstage" in ein Feld ein!
                        </p>
                    </div>

                    @if (count($includeFilters) > 0)
                        <div class="mb-2 flex flex-wrap gap-2">
                            @foreach ($includeFilters as $index => $filter)
                                <span
                                    class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm text-green-800">
                                    {{ $filter }}
                                    <button wire:click="removeIncludeFilter({{ $index }})"
                                        class="ml-2 text-green-600 hover:text-green-800" title="Filter entfernen">
                                        ×
                                    </button>
                                </span>
                            @endforeach
                        </div>
                        <p class="text-xs font-medium text-green-600">
                            ✓ {{ count($includeFilters) }} Include-Filter aktiv:
                            Jobliste muss mindestens einen der obigen Begriffe enthalten
                        </p>
                    @else
                        <p class="text-xs italic text-gray-500">Keine Include-Filter gesetzt - alle Datensätze werden
                            berücksichtigt</p>
                    @endif
                </div>

                <!-- Exclude Filter -->
                <div class="mb-4">
                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        Exclude Filter - <span class="text-gray-500">(optional)</span>
                        <br><span class="text-xs text-gray-500">Jobliste darf diese Werte NICHT enthalten</span>
                    </label>
                    <div class="mb-2 flex gap-2">
                        <input type="text" wire:model="newExcludeFilter" wire:keydown.enter="addExcludeFilter"
                            wire:key="exclude-filter-input"
                            class="flex-1 rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Einen Filter eingeben (z.B. Abbau) und auf 'Hinzufügen' klicken">
                        <button wire:click="addExcludeFilter"
                            class="rounded bg-red-500 px-4 py-2 text-white hover:bg-red-600">
                            Hinzufügen
                        </button>
                    </div>

                    <div class="mb-3 rounded border border-yellow-200 bg-yellow-50 p-2">
                        <p class="text-xs text-yellow-800">
                            💡 <strong>Tipp:</strong> Fügen Sie jeden Begriff einzeln hinzu (z.B. erst "Abbau", dann
                            "extern").
                            Geben Sie nicht "Abbau, extern" in ein Feld ein!
                        </p>
                    </div>

                    @if (count($excludeFilters) > 0)
                        <div class="mb-2 flex flex-wrap gap-2">
                            @foreach ($excludeFilters as $index => $filter)
                                <span
                                    class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-sm text-red-800">
                                    {{ $filter }}
                                    <button wire:click="removeExcludeFilter({{ $index }})"
                                        class="ml-2 text-red-600 hover:text-red-800" title="Filter entfernen">
                                        ×
                                    </button>
                                </span>
                            @endforeach
                        </div>
                        <p class="text-xs font-medium text-red-600">
                            ✓ {{ count($excludeFilters) }} Exclude-Filter aktiv:
                            Datensätze mit diesen Begriffen werden ausgeschlossen
                        </p>
                    @else
                        <p class="text-xs italic text-gray-500">Keine Exclude-Filter gesetzt - keine Datensätze werden
                            ausgeschlossen</p>
                    @endif
                </div>

                <!-- Filter-Zusammenfassung -->
                @if (count($includeFilters) > 0 || count($excludeFilters) > 0)
                    <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-3">
                        <h4 class="mb-2 text-sm font-medium text-gray-700">📋 Filter-Zusammenfassung:</h4>
                        <ul class="space-y-1 text-xs text-gray-600">
                            @if (count($includeFilters) > 0)
                                <li>✅ <strong>Include:</strong> Jobliste muss enthalten:
                                    {{ implode(' ODER ', $includeFilters) }}</li>
                            @endif
                            @if (count($excludeFilters) > 0)
                                <li>❌ <strong>Exclude:</strong> Jobliste darf NICHT enthalten:
                                    {{ implode(' UND NICHT ', $excludeFilters) }}</li>
                            @endif
                        </ul>
                    </div>
                @endif
            </div>

            <!-- Import Einstellungen -->
            <div class="mb-8">
                <h3 class="mb-4 text-lg font-semibold">Import Einstellungen</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Gruppe</label>
                        <select wire:model="selectedGroupId"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Gruppe auswählen...</option>
                            @foreach ($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Jahr</label>
                        <input type="number" wire:model="year"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            min="2020" max="2030">
                    </div>
                </div>
            </div>

            <!-- Aktionen -->
            <div class="mb-6 flex gap-4">
                <button wire:click="loadPreview" wire:loading.attr="disabled"
                    class="rounded bg-blue-500 px-6 py-2 text-white hover:bg-blue-600 disabled:opacity-50">
                    <span wire:loading.remove wire:target="loadPreview">Vorschau laden</span>
                    <span wire:loading wire:target="loadPreview">Lade...</span>
                </button>

                @if ($showPreview && count($previewData) > 0)
                    <button wire:click="executeImport" wire:loading.attr="disabled"
                        class="rounded bg-green-500 px-6 py-2 text-white hover:bg-green-600 disabled:opacity-50">
                        <span wire:loading.remove wire:target="executeImport">Import starten</span>
                        <span wire:loading wire:target="executeImport">Importiere...</span>
                    </button>

                    <button wire:click="resetImport"
                        class="rounded bg-gray-500 px-6 py-2 text-white hover:bg-gray-600">
                        Zurücksetzen
                    </button>
                @endif
            </div>
        </div>

        <!-- Vorschau -->
        @if ($showPreview && count($previewData) > 0)
            <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
                <h3 class="mb-4 text-lg font-semibold">Vorschau ({{ count($previewData) }} Datensätze)</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Knack ID
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Original
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Vorname
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Nachname
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                    Jobliste/Bemerkung</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($previewData as $data)
                                <tr class="{{ $data['action'] === 'update' ? 'bg-blue-50' : '' }}">
                                    <td class="whitespace-nowrap px-6 py-4 font-mono text-xs">
                                        {{ $data['knack_id'] ?: '-' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">{{ $data['kontakt'] }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                        {{ $data['first_name'] }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                        {{ $data['last_name'] }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span class="rounded bg-blue-50 px-2 py-1 text-xs text-blue-800">
                                            {{ $data['jobliste'] }}
                                        </span>
                                        <div class="mt-1 text-xs text-gray-500">→ wird als Bemerkung gespeichert</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        @if ($data['action'] === 'create')
                                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs text-green-800">
                                                ✅ Neu erstellen
                                            </span>
                                        @elseif($data['action'] === 'update')
                                            <span class="rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-800"
                                                title="{{ $data['update_reason'] }}">
                                                🔄 Wird aktualisiert ({{ $data['update_reason'] }})
                                            </span>
                                            @if ($data['existing_person'])
                                                <div class="mt-1 text-xs text-gray-500">
                                                    ID: {{ $data['existing_person']->id }}
                                                    @if ($data['existing_person']->group)
                                                        | Alt: {{ $data['existing_person']->group->name }}
                                                    @endif
                                                </div>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Import Ergebnisse -->
        @if ($importResults)
            <div class="rounded-lg bg-white p-6 shadow-md">
                <h3 class="mb-4 text-lg font-semibold">Import Ergebnisse</h3>
                <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div class="rounded bg-green-50 p-4 text-center">
                        <div class="text-2xl font-bold text-green-600">{{ $importResults['imported'] ?? 0 }}</div>
                        <div class="text-sm text-gray-600">Neu erstellt</div>
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

                @if (isset($importResults['error_messages']) && count($importResults['error_messages']) > 0)
                    <div class="mt-4">
                        <h4 class="mb-2 font-medium text-red-600">Fehlermeldungen:</h4>
                        <ul class="space-y-1 text-sm text-red-600">
                            @foreach ($importResults['error_messages'] as $error)
                                <li>• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

<script>
    // Livewire Event Listener zum Leeren der Input-Felder
    document.addEventListener('livewire:init', () => {
        Livewire.on('input-cleared', () => {
            // Include Filter Input leeren
            const includeInput = document.querySelector('input[wire\\:model="newIncludeFilter"]');
            if (includeInput) {
                includeInput.value = '';
                includeInput.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
            }

            // Exclude Filter Input leeren
            const excludeInput = document.querySelector('input[wire\\:model="newExcludeFilter"]');
            if (excludeInput) {
                excludeInput.value = '';
                excludeInput.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
            }
        });
    });
</script>
