{{-- resources/views/livewire/admin/knack-import.blade.php --}}

<div class="container mx-auto px-4 py-8">
    @include('partials.navigation')

    <div class="max-w-6xl mx-auto">
        <!-- Success/Error Messages -->
        @if (session()->has('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-bold mb-6">Knack Daten Import</h2>

            <!-- Knack API Konfiguration -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold mb-4">API Konfiguration</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Application ID</label>
                        <input 
                            type="text" 
                            wire:model="appId"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Knack App ID"
                        >
                        <p class="text-xs text-gray-500 mt-1">
                            💡 Wird automatisch vom ausgewählten Object übernommen, falls dort eingetragen
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                        <input 
                            type="password" 
                            wire:model="apiKey"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="API Key"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Knack Object</label>
                        <select 
                            wire:model="selectedKnackObjectId"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Object auswählen...</option>
                            @foreach($knackObjects as $knackObject)
                                <option value="{{ $knackObject->id }}">
                                    {{ $knackObject->name }} ({{ $knackObject->object_key }})
                                    @if($knackObject->app_id) - Custom App ID @endif
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            Keine Objects verfügbar? 
                            <a href="#" onclick="alert('Knack Objects können über die Datenbank oder einen separaten Admin-Bereich verwaltet werden.')" class="text-blue-500 hover:underline">
                                Objects verwalten
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Filter Konfiguration -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold mb-4">Filter Einstellungen</h3>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-blue-800">
                        <strong>Optional:</strong> Filter können leer gelassen werden um alle Datensätze zu importieren.
                        <br><strong>Wichtig:</strong> Fügen Sie jeden Filter einzeln hinzu! Nicht mehrere Filter gleichzeitig in ein Feld eingeben.
                    </p>
                </div>
                
                <!-- Include Filter -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Include Filter - <span class="text-gray-500">(optional)</span>
                        <br><span class="text-xs text-gray-500">Jobliste muss einen dieser Werte enthalten</span>
                    </label>
                    <div class="flex gap-2 mb-2">
                        <input 
                            type="text" 
                            wire:model="newIncludeFilter"
                            wire:keydown.enter="addIncludeFilter"
                            wire:key="include-filter-input"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Einen Filter eingeben (z.B. Mitarbeiter) und auf 'Hinzufügen' klicken"
                        >
                        <button 
                            wire:click="addIncludeFilter"
                            class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
                        >
                            Hinzufügen
                        </button>
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded p-2 mb-3">
                        <p class="text-xs text-yellow-800">
                            💡 <strong>Tipp:</strong> Fügen Sie jeden Begriff einzeln hinzu (z.B. erst "Mitarbeiter", dann "Backstage"). 
                            Geben Sie nicht "Mitarbeiter, Backstage" in ein Feld ein!
                        </p>
                    </div>
                    
                    @if(count($includeFilters) > 0)
                        <div class="flex flex-wrap gap-2 mb-2">
                            @foreach($includeFilters as $index => $filter)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-800">
                                    {{ $filter }}
                                    <button 
                                        wire:click="removeIncludeFilter({{ $index }})"
                                        class="ml-2 text-green-600 hover:text-green-800"
                                        title="Filter entfernen"
                                    >
                                        ×
                                    </button>
                                </span>
                            @endforeach
                        </div>
                        <p class="text-xs text-green-600 font-medium">
                            ✓ {{ count($includeFilters) }} Include-Filter aktiv: 
                            Jobliste muss mindestens einen der obigen Begriffe enthalten
                        </p>
                    @else
                        <p class="text-xs text-gray-500 italic">Keine Include-Filter gesetzt - alle Datensätze werden berücksichtigt</p>
                    @endif
                </div>

                <!-- Exclude Filter -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Exclude Filter - <span class="text-gray-500">(optional)</span>
                        <br><span class="text-xs text-gray-500">Jobliste darf diese Werte NICHT enthalten</span>
                    </label>
                    <div class="flex gap-2 mb-2">
                        <input 
                            type="text" 
                            wire:model="newExcludeFilter"
                            wire:keydown.enter="addExcludeFilter"
                            wire:key="exclude-filter-input"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Einen Filter eingeben (z.B. Abbau) und auf 'Hinzufügen' klicken"
                        >
                        <button 
                            wire:click="addExcludeFilter"
                            class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
                        >
                            Hinzufügen
                        </button>
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded p-2 mb-3">
                        <p class="text-xs text-yellow-800">
                            💡 <strong>Tipp:</strong> Fügen Sie jeden Begriff einzeln hinzu (z.B. erst "Abbau", dann "extern"). 
                            Geben Sie nicht "Abbau, extern" in ein Feld ein!
                        </p>
                    </div>
                    
                    @if(count($excludeFilters) > 0)
                        <div class="flex flex-wrap gap-2 mb-2">
                            @foreach($excludeFilters as $index => $filter)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-red-100 text-red-800">
                                    {{ $filter }}
                                    <button 
                                        wire:click="removeExcludeFilter({{ $index }})"
                                        class="ml-2 text-red-600 hover:text-red-800"
                                        title="Filter entfernen"
                                    >
                                        ×
                                    </button>
                                </span>
                            @endforeach
                        </div>
                        <p class="text-xs text-red-600 font-medium">
                            ✓ {{ count($excludeFilters) }} Exclude-Filter aktiv: 
                            Datensätze mit diesen Begriffen werden ausgeschlossen
                        </p>
                    @else
                        <p class="text-xs text-gray-500 italic">Keine Exclude-Filter gesetzt - keine Datensätze werden ausgeschlossen</p>
                    @endif
                </div>

                <!-- Filter-Zusammenfassung -->
                @if(count($includeFilters) > 0 || count($excludeFilters) > 0)
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">📋 Filter-Zusammenfassung:</h4>
                        <ul class="text-xs text-gray-600 space-y-1">
                            @if(count($includeFilters) > 0)
                                <li>✅ <strong>Include:</strong> Jobliste muss enthalten: {{ implode(' ODER ', $includeFilters) }}</li>
                            @endif
                            @if(count($excludeFilters) > 0)
                                <li>❌ <strong>Exclude:</strong> Jobliste darf NICHT enthalten: {{ implode(' UND NICHT ', $excludeFilters) }}</li>
                            @endif
                        </ul>
                    </div>
                @endif
            </div>

            <!-- Import Einstellungen -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold mb-4">Import Einstellungen</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gruppe</label>
                        <select 
                            wire:model="selectedGroupId"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Gruppe auswählen...</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jahr</label>
                        <input 
                            type="number" 
                            wire:model="year"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            min="2020"
                            max="2030"
                        >
                    </div>
                </div>
            </div>

            <!-- Aktionen -->
            <div class="flex gap-4 mb-6">
                <button 
                    wire:click="loadPreview"
                    wire:loading.attr="disabled"
                    class="px-6 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="loadPreview">Vorschau laden</span>
                    <span wire:loading wire:target="loadPreview">Lade...</span>
                </button>

                @if($showPreview && count($previewData) > 0)
                    <button 
                        wire:click="executeImport"
                        wire:loading.attr="disabled"
                        class="px-6 py-2 bg-green-500 text-white rounded hover:bg-green-600 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="executeImport">Import starten</span>
                        <span wire:loading wire:target="executeImport">Importiere...</span>
                    </button>

                    <button 
                        wire:click="resetImport"
                        class="px-6 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
                    >
                        Zurücksetzen
                    </button>
                @endif
            </div>
        </div>

        <!-- Vorschau -->
        @if($showPreview && count($previewData) > 0)
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Vorschau ({{ count($previewData) }} Datensätze)</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Knack ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Original</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vorname</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nachname</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jobliste/Bemerkung</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($previewData as $data)
                                <tr class="{{ $data['exists'] ? 'bg-yellow-50' : '' }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-xs font-mono">{{ $data['knack_id'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $data['kontakt'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ $data['first_name'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ $data['last_name'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 py-1 text-xs bg-blue-50 text-blue-800 rounded">
                                            {{ $data['jobliste'] }}
                                        </span>
                                        <div class="text-xs text-gray-500 mt-1">→ wird als Bemerkung gespeichert</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($data['exists'])
                                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800" title="{{ $data['exists_reason'] ?? 'Bereits vorhanden' }}">
                                                Bereits vorhanden
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                                Wird importiert
                                            </span>
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
        @if($importResults)
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">Import Ergebnisse</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="text-center p-4 bg-green-50 rounded">
                        <div class="text-2xl font-bold text-green-600">{{ $importResults['imported'] }}</div>
                        <div class="text-sm text-gray-600">Importiert</div>
                    </div>
                    <div class="text-center p-4 bg-yellow-50 rounded">
                        <div class="text-2xl font-bold text-yellow-600">{{ $importResults['skipped'] }}</div>
                        <div class="text-sm text-gray-600">Übersprungen</div>
                    </div>
                    <div class="text-center p-4 bg-red-50 rounded">
                        <div class="text-2xl font-bold text-red-600">{{ $importResults['errors'] }}</div>
                        <div class="text-sm text-gray-600">Fehler</div>
                    </div>
                </div>

                @if(isset($importResults['error_messages']) && count($importResults['error_messages']) > 0)
                    <div class="mt-4">
                        <h4 class="font-medium text-red-600 mb-2">Fehlermeldungen:</h4>
                        <ul class="text-sm text-red-600 space-y-1">
                            @foreach($importResults['error_messages'] as $error)
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
                includeInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            
            // Exclude Filter Input leeren
            const excludeInput = document.querySelector('input[wire\\:model="newExcludeFilter"]');
            if (excludeInput) {
                excludeInput.value = '';
                excludeInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    });
</script>