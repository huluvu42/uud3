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