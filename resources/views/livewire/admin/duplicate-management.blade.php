{{-- resources/views/livewire/admin/duplicate-management.blade.php --}}

<div class="container mx-auto px-4 py-8">
    @include('partials.navigation')

    <div class="max-w-7xl mx-auto">
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

        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Duplikat-Verwaltung</h2>
                <div class="flex items-center space-x-4">
                    <select 
                        wire:model="selectedYear"
                        class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        @for($year = now()->year; $year >= 2020; $year--)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endfor
                    </select>
                    <button 
                        wire:click="refreshData"
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="refreshData">🔄 Aktualisieren</span>
                        <span wire:loading wire:target="refreshData">Lädt...</span>
                    </button>
                </div>
            </div>

            <!-- Statistiken -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="text-center p-4 bg-blue-50 rounded">
                    <div class="text-2xl font-bold text-blue-600">{{ $stats['total_persons'] }}</div>
                    <div class="text-sm text-gray-600">Gesamte Personen</div>
                </div>
                <div class="text-center p-4 bg-yellow-50 rounded">
                    <div class="text-2xl font-bold text-yellow-600">{{ $stats['duplicate_groups'] }}</div>
                    <div class="text-sm text-gray-600">Duplikat-Gruppen</div>
                </div>
                <div class="text-center p-4 bg-orange-50 rounded">
                    <div class="text-2xl font-bold text-orange-600">{{ $stats['potential_duplicates'] }}</div>
                    <div class="text-sm text-gray-600">Potentielle Duplikate</div>
                </div>
                <div class="text-center p-4 bg-red-50 rounded">
                    <div class="text-2xl font-bold text-red-600">{{ $stats['marked_duplicates'] }}</div>
                    <div class="text-sm text-gray-600">Markierte Duplikate</div>
                </div>
            </div>

            <!-- Tab-Navigation -->
            <div class="flex space-x-4 border-b">
                <button 
                    wire:click="$set('showMarkedDuplicates', false)"
                    class="px-4 py-2 {{ !$showMarkedDuplicates ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500' }}"
                >
                    📋 Potentielle Duplikate ({{ $stats['duplicate_groups'] }})
                </button>
                <button 
                    wire:click="$set('showMarkedDuplicates', true)"
                    class="px-4 py-2 {{ $showMarkedDuplicates ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500' }}"
                >
                    🏷️ Markierte Duplikate ({{ $stats['marked_duplicates'] }})
                </button>
            </div>
        </div>

        @if($isLoading)
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <div class="text-gray-500">Lade Duplikate...</div>
            </div>
        @elseif(!$showMarkedDuplicates)
            <!-- Potentielle Duplikate -->
            @if(count($potentialDuplicates) > 0)
                <div class="space-y-6">
                    @foreach($potentialDuplicates as $group)
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-red-600">
                                    🔍 {{ $group['name'] }} 
                                    <span class="text-sm text-gray-500">({{ $group['count'] }} Personen gefunden)</span>
                                </h3>
                                <div class="text-sm text-gray-500">
                                    Bitte wählen Sie die Originale aus und markieren Sie die Duplikate
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Band</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gruppe</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bemerkung</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Knack ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($group['persons'] as $person)
                                            <tr class="{{ $person->is_duplicate ? 'bg-red-50' : 'hover:bg-gray-50' }}">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono">{{ $person->id }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    {{ $person->first_name }} {{ $person->last_name }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $person->band->band_name ?? '-' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $person->group->name ?? '-' }}
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900 max-w-xs">
                                                    @if($person->remarks)
                                                        <span class="px-2 py-1 text-xs bg-blue-50 text-blue-800 rounded truncate block">
                                                            {{ Str::limit($person->remarks, 30) }}
                                                        </span>
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $person->knack_id ?? '-' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    @if($person->is_duplicate)
                                                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                                            ❌ Duplikat
                                                        </span>
                                                    @else
                                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                                            ✅ Aktiv
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    @if($person->is_duplicate)
                                                        <button 
                                                            wire:click="unmarkAsDuplicate({{ $person->id }})"
                                                            class="text-green-600 hover:text-green-900 mr-2"
                                                            title="Als Original markieren"
                                                        >
                                                            ↶ Wiederherstellen
                                                        </button>
                                                    @else
                                                        <button 
                                                            wire:click="markAsDuplicate({{ $person->id }})"
                                                            class="text-red-600 hover:text-red-900"
                                                            title="Als Duplikat markieren"
                                                        >
                                                            🏷️ Markieren
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-lg shadow-md p-8 text-center">
                    <div class="text-green-600 text-lg font-semibold mb-2">🎉 Keine Duplikate gefunden!</div>
                    <div class="text-gray-500">Alle Personen in {{ $selectedYear }} haben eindeutige Namen.</div>
                </div>
            @endif

        @else
            <!-- Markierte Duplikate -->
            @if(count($markedDuplicates) > 0)
                <div class="space-y-6">
                    @foreach($markedDuplicates as $name => $duplicates)
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <h3 class="text-lg font-semibold text-red-600 mb-4">
                                🏷️ {{ $name }} 
                                <span class="text-sm text-gray-500">({{ count($duplicates) }} als Duplikat markiert)</span>
                            </h3>

                            <div class="space-y-3">
                                @foreach($duplicates as $person)
                                    <div class="flex justify-between items-center p-4 bg-red-50 rounded-lg">
                                        <div class="flex-1">
                                            <div class="font-medium">{{ $person->full_name }} (ID: {{ $person->id }})</div>
                                            <div class="text-sm text-gray-600">
                                                @if($person->band)
                                                    Band: {{ $person->band->band_name }}
                                                @endif
                                                @if($person->group)
                                                    | Gruppe: {{ $person->group->name }}
                                                @endif
                                                @if($person->knack_id)
                                                    | Knack: {{ $person->knack_id }}
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                Markiert: {{ $person->duplicate_marked_at->format('d.m.Y H:i') }}
                                                @if($person->duplicateMarkedBy)
                                                    von {{ $person->duplicateMarkedBy->first_name }} {{ $person->duplicateMarkedBy->last_name }}
                                                @endif
                                            </div>
                                            @if($person->duplicate_reason)
                                                <div class="text-xs text-orange-600 mt-1">
                                                    Grund: {{ $person->duplicate_reason }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="ml-4">
                                            <button 
                                                wire:click="unmarkAsDuplicate({{ $person->id }})"
                                                class="px-3 py-2 bg-green-500 text-white text-sm rounded hover:bg-green-600"
                                                title="Duplikat-Markierung entfernen"
                                            >
                                                ↶ Wiederherstellen
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-lg shadow-md p-8 text-center">
                    <div class="text-gray-500 text-lg font-semibold mb-2">Keine markierten Duplikate</div>
                    <div class="text-gray-400">Aktuell sind keine Personen als Duplikate markiert.</div>
                </div>
            @endif
        @endif
    </div>
</div>