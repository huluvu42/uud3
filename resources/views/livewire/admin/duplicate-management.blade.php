{{-- resources/views/livewire/admin/duplicate-management.blade.php --}}

<div class="container mx-auto px-4 py-8">
    @include('partials.navigation')

    <div class="mx-auto mt-8 max-w-7xl">
        <!-- Header -->
        <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-2xl font-bold">🔍 Duplikat Management</h2>
                <div class="flex items-center space-x-4">
                    <!-- Jahr-Auswahl -->
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700">Jahr:</label>
                        <select wire:model.live="selectedYear"
                            class="rounded border border-gray-300 px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @for ($year = 2020; $year <= now()->year + 1; $year++)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endfor
                        </select>
                    </div>

                    <!-- Aktualisieren Button -->
                    <button wire:click="refreshData" class="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600">
                        🔄 Aktualisieren
                    </button>
                </div>
            </div>

            <!-- Statistiken -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div class="rounded bg-blue-50 p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $stats['total_persons'] }}</div>
                    <div class="text-sm text-blue-700">Personen gesamt</div>
                </div>
                <div class="rounded bg-yellow-50 p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-600">{{ $stats['duplicate_groups'] }}</div>
                    <div class="text-sm text-yellow-700">Duplikat-Gruppen</div>
                </div>
                <div class="rounded bg-orange-50 p-4 text-center">
                    <div class="text-2xl font-bold text-orange-600">{{ $stats['potential_duplicates'] }}</div>
                    <div class="text-sm text-orange-700">Potentielle Duplikate</div>
                </div>
                <div class="rounded bg-red-50 p-4 text-center">
                    <div class="text-2xl font-bold text-red-600">{{ $stats['marked_duplicates'] }}</div>
                    <div class="text-sm text-red-700">Markierte Duplikate</div>
                </div>
            </div>

            <!-- Toggle Button -->
            <div class="mt-4 flex justify-center">
                <button wire:click="toggleShowMarkedDuplicates"
                    class="{{ $showMarkedDuplicates ? 'bg-orange-500 hover:bg-orange-600' : 'bg-green-500 hover:bg-green-600' }} rounded px-6 py-2 text-white">
                    @if ($showMarkedDuplicates)
                        📋 Potentielle Duplikate anzeigen
                    @else
                        🏷️ Markierte Duplikate anzeigen ({{ $stats['marked_duplicates'] }})
                    @endif
                </button>
            </div>
        </div>

        <!-- Flash Messages -->
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

        <!-- Loading Indicator -->
        @if ($isLoading)
            <div class="mb-6 rounded-lg bg-blue-50 p-6 text-center">
                <div class="text-blue-600">Lade Duplikate...</div>
            </div>
        @endif

        @if (!$showMarkedDuplicates)
            <!-- Potentielle Duplikate -->
            @if (count($potentialDuplicates) > 0)
                <div class="space-y-6">
                    @foreach ($potentialDuplicates as $duplicateGroup)
                        <div class="rounded-lg bg-white p-6 shadow-md">
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-orange-600">
                                    🔍 {{ $duplicateGroup['name'] }}
                                    <span class="text-sm text-gray-500">({{ $duplicateGroup['count'] }} Personen
                                        gefunden)</span>
                                </h3>
                                <div class="text-sm text-gray-500">
                                    Gleicher Name erkannt
                                </div>
                            </div>

                            <!-- Aktions-Buttons -->
                            <div class="mb-4 flex space-x-2">
                                @php
                                    // Array-Filter statt Collection-Filter
                                    $activePersons = array_filter($duplicateGroup['persons'], function ($person) {
                                        return !$person['is_duplicate'];
                                    });
                                @endphp

                                @if (count($activePersons) > 1)
                                    @foreach ($activePersons as $person)
                                        <button
                                            wire:click="markAllInGroup({{ json_encode($duplicateGroup['persons']) }}, {{ $person['id'] }})"
                                            class="rounded bg-blue-500 px-3 py-1 text-sm text-white hover:bg-blue-600"
                                            title="Alle anderen als Duplikate markieren, {{ $person['first_name'] }} {{ $person['last_name'] }} (ID: {{ $person['id'] }}) als Original behalten">
                                            🎯 {{ $person['first_name'] }} {{ $person['last_name'] }} (ID:
                                            {{ $person['id'] }}) als Original
                                        </button>
                                    @endforeach
                                @endif
                            </div>

                            <!-- Personen-Tabelle -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                ID</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                Name</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                Band</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                Gruppe</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                Knack ID</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                Status</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                Erstellt</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        @foreach ($duplicateGroup['persons'] as $person)
                                            <tr
                                                class="{{ $person['is_duplicate'] ? 'bg-red-50' : 'hover:bg-gray-50' }}">
                                                <td class="whitespace-nowrap px-6 py-4 font-mono text-sm">
                                                    {{ $person['id'] }}</td>
                                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                                    {{ $person['first_name'] }} {{ $person['last_name'] }}
                                                    @if ($person['present'])
                                                        <span
                                                            class="ml-2 rounded-full bg-green-100 px-2 py-1 text-xs text-green-800">
                                                            ✅ Anwesend
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                                    {{ $person['band_name'] ?? '-' }}
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                                    {{ $person['group_name'] ?? '-' }}
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                    {{ $person['knack_id'] ?? '-' }}
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                                    @if ($person['is_duplicate'])
                                                        <span
                                                            class="rounded-full bg-red-100 px-2 py-1 text-xs text-red-800">
                                                            ❌ Duplikat
                                                        </span>
                                                    @else
                                                        <span
                                                            class="rounded-full bg-green-100 px-2 py-1 text-xs text-green-800">
                                                            ✅ Aktiv
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                    {{ $person['created_at'] ?? '-' }}
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                                    @if ($person['is_duplicate'])
                                                        <button wire:click="unmarkAsDuplicate({{ $person['id'] }})"
                                                            class="mr-2 text-green-600 hover:text-green-900"
                                                            title="Als Original markieren">
                                                            ↶ Wiederherstellen
                                                        </button>
                                                    @else
                                                        <button wire:click="markAsDuplicate({{ $person['id'] }})"
                                                            class="text-red-600 hover:text-red-900"
                                                            title="Als Duplikat markieren">
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
                <div class="rounded-lg bg-white p-8 text-center shadow-md">
                    <div class="mb-2 text-lg font-semibold text-green-600">🎉 Keine Duplikate gefunden!</div>
                    <div class="text-gray-500">Alle Personen in {{ $selectedYear }} haben eindeutige Namen.</div>
                </div>
            @endif
        @else
            <!-- Markierte Duplikate -->
            @if (count($markedDuplicates) > 0)
                <div class="space-y-6">
                    @foreach ($markedDuplicates as $name => $duplicates)
                        <div class="rounded-lg bg-white p-6 shadow-md">
                            <h3 class="mb-4 text-lg font-semibold text-red-600">
                                🏷️ {{ $name }}
                                <span class="text-sm text-gray-500">({{ count($duplicates) }} als Duplikat
                                    markiert)</span>
                            </h3>

                            <div class="space-y-3">
                                @foreach ($duplicates as $person)
                                    <div class="flex items-center justify-between rounded-lg bg-red-50 p-4">
                                        <div class="flex-1">
                                            <div class="font-medium">{{ $person->full_name }} (ID:
                                                {{ $person->id }})</div>
                                            <div class="text-sm text-gray-600">
                                                @if ($person->band)
                                                    Band: {{ $person->band->band_name }}
                                                @endif
                                                @if ($person->group)
                                                    | Gruppe: {{ $person->group->name }}
                                                @endif
                                                @if ($person->knack_id)
                                                    | Knack: {{ $person->knack_id }}
                                                @endif
                                            </div>
                                            <div class="mt-1 text-xs text-gray-500">
                                                Markiert: {{ $person->duplicate_marked_at->format('d.m.Y H:i') }}
                                                @if ($person->duplicateMarkedBy)
                                                    von {{ $person->duplicateMarkedBy->first_name }}
                                                    {{ $person->duplicateMarkedBy->last_name }}
                                                @endif
                                            </div>
                                            @if ($person->duplicate_reason)
                                                <div class="mt-1 text-xs text-orange-600">
                                                    Grund: {{ $person->duplicate_reason }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="ml-4">
                                            <button wire:click="unmarkAsDuplicate({{ $person->id }})"
                                                class="rounded bg-green-500 px-3 py-2 text-sm text-white hover:bg-green-600"
                                                title="Duplikat-Markierung entfernen">
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
                <div class="rounded-lg bg-white p-8 text-center shadow-md">
                    <div class="mb-2 text-lg font-semibold text-gray-500">Keine markierten Duplikate</div>
                    <div class="text-gray-400">Aktuell sind keine Personen als Duplikate markiert.</div>
                </div>
            @endif
        @endif
    </div>
</div>
