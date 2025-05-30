{{-- resources/views/livewire/management/person-management.blade.php --}}

<div class="container mx-auto px-4 py-8">
    


    <!-- Navigation -->
    <div class="mb-4">
        @include('partials.navigation')

    </div>

    <div class="container mx-auto px-4 py-8">
        @if (session()->has('message'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('message') }}
            </div>
        @endif

        <!-- Header und Filter -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Personen-Verwaltung</h1>
            <div class="flex items-center space-x-4">
                <!-- Einfacher Toggle für Bandmitglieder -->
                <label class="flex items-center space-x-2">
                    <input type="checkbox" wire:model.live="showBandMembers" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Bandmitglieder anzeigen</span>
                </label>
                
                <button wire:click="createPerson" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    Person hinzufügen
                </button>
            </div>
        </div>

        <!-- Such- und Filterbereich -->
        <div class="bg-white shadow-md rounded-lg p-4 mb-6">
            <div class="flex flex-wrap gap-4 items-center">
                <!-- Suchfeld -->
                <div class="flex-1 min-w-64">
                    <input type="text" wire:model.live="search" placeholder="Person suchen (Vor- oder Nachname)..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <!-- Filter -->
                <div>
                    <select wire:model.live="filterType" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all">Alle Personen</option>
                        <option value="groups">Nur Gruppen-Mitglieder</option>
                        @if($showBandMembers)
                            <option value="bands">Nur Band-Mitglieder</option>
                        @endif
                        <option value="guests">Nur Gäste</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Person erstellen/bearbeiten Modal -->
        @if($showCreateForm || $showEditForm)
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white max-h-screen overflow-y-auto">
                    <div class="mt-3">
                        <h2 class="text-xl font-semibold mb-4">
                            {{ $showCreateForm ? 'Neue Person hinzufügen' : 'Person bearbeiten' }}
                        </h2>
                        
                        <!-- Grunddaten -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Vorname *</label>
                                <input type="text" wire:model="first_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('first_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nachname *</label>
                                <input type="text" wire:model="last_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('last_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Zuordnung -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium mb-3">Zuordnung</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Gruppe</label>
                                    <select wire:model.live="group_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Keine Gruppe</option>
                                        @foreach($groups as $group)
                                            <option value="{{ $group->id }}" 
                                                    data-voucher-1="{{ $group->voucher_day_1 ?? 0 }}"
                                                    data-voucher-2="{{ $group->voucher_day_2 ?? 0 }}"
                                                    data-voucher-3="{{ $group->voucher_day_3 ?? 0 }}"
                                                    data-voucher-4="{{ $group->voucher_day_4 ?? 0 }}"
                                                    data-backstage-1="{{ $group->backstage_day_1 ? 1 : 0 }}"
                                                    data-backstage-2="{{ $group->backstage_day_2 ? 1 : 0 }}"
                                                    data-backstage-3="{{ $group->backstage_day_3 ? 1 : 0 }}"
                                                    data-backstage-4="{{ $group->backstage_day_4 ? 1 : 0 }}">
                                                {{ $group->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('group_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Untergruppe</label>
                                    <select wire:model="subgroup_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" {{ !$group_id ? 'disabled' : '' }}>
                                        <option value="">Keine Untergruppe</option>
                                        @foreach($this->subgroups as $subgroup)
                                            <option value="{{ $subgroup->id }}">{{ $subgroup->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('subgroup_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Band</label>
                                    <select wire:model.live="band_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Keine Band</option>
                                        @foreach($bands as $band)
                                            <option value="{{ $band->id }}">{{ $band->band_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('band_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            
                            <!-- Verantwortliche Person -->
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Verantwortliche Person (optional)</label>
                                <select wire:model="responsible_person_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Keine verantwortliche Person</option>
                                    @foreach($responsiblePersons as $person)
                                        <option value="{{ $person->id }}">{{ $person->full_name }}</option>
                                    @endforeach
                                </select>
                                @error('responsible_person_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-6">
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="present" class="mr-2">
                                <span class="text-sm font-medium text-gray-700">Anwesend</span>
                            </label>
                        </div>

                        <!-- Backstage-Zugang -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium mb-3">Backstage-Zugang</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                @foreach([1,2,3,4] as $day)
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="backstage_day_{{ $day }}" class="mr-2">
                                        <span class="text-sm">Tag {{ $day }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Gutscheine -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium mb-3">Gutscheine</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                @foreach([1,2,3,4] as $day)
                                    <div>
                                        <label class="block text-xs text-gray-600">Tag {{ $day }}</label>
                                        <input type="number" wire:model="voucher_day_{{ $day }}" step="0.1" min="0" max="999.9" 
                                               class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error('voucher_day_' . $day) <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Bemerkungen -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bemerkungen</label>
                            <textarea wire:model="remarks" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-end space-x-2 mt-6">
                            <button wire:click="cancelPersonForm" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Abbrechen
                            </button>
                            @if($showCreateForm)
                                <button wire:click="savePerson(true)" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Speichern & Weiter
                                </button>
                            @endif
                            <button wire:click="{{ $showCreateForm ? 'savePerson(false)' : 'updatePerson' }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                {{ $showCreateForm ? 'Speichern' : 'Aktualisieren' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Personen Liste -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            @if($persons->count() > 0)
                <table class="min-w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Name</th>
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Zuordnung</th>
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Anwesend</th>
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Backstage</th>
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Gutscheine</th>
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Verantwortlich für</th>
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($persons as $person)
                            <tr class="hover:bg-gray-50 {{ $person->isGuest() ? 'bg-blue-50' : '' }}">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">
                                        {{ $person->first_name }} {{ $person->last_name }}
                                        @if($person->isGuest())
                                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded ml-2">Gast</span>
                                        @endif
                                    </div>
                                    @if($person->remarks)
                                        <div class="text-sm text-gray-500">{{ $person->remarks }}</div>
                                    @endif
                                    @if($person->isGuest() && $person->responsiblePerson)
                                        <div class="text-xs text-blue-600">Verantwortlich: {{ $person->responsiblePerson->full_name }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="text-sm">
                                        @if($person->group)
                                            <div class="text-purple-600">📁 {{ $person->group->name }}</div>
                                            @if($person->subgroup)
                                                <div class="text-purple-500 text-xs">{{ $person->subgroup->name }}</div>
                                            @endif
                                        @elseif($person->band)
                                            <div class="text-blue-600">🎵 {{ $person->band->band_name }}</div>
                                        @else
                                            <span class="text-gray-400">Keine Zuordnung</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-1 rounded text-xs {{ $person->present ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $person->present ? 'Ja' : 'Nein' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center space-x-1">
                                        @foreach([1,2,3,4] as $day)
                                            <span class="w-6 h-6 rounded-full text-xs flex items-center justify-center {{ $person->{'backstage_day_' . $day} ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                                                {{ $day }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="text-xs">
                                        @php
                                            $personHasVouchers = false;
                                        @endphp
                                        @foreach([1,2,3,4] as $day)
                                            @if($person->{'voucher_day_' . $day} > 0)
                                                <div>T{{ $day }}: {{ $person->{'voucher_day_' . $day} }}</div>
                                                @php $personHasVouchers = true; @endphp
                                            @endif
                                        @endforeach
                                        @if(!$personHasVouchers)
                                            <span class="text-gray-400">Keine</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="text-xs">
                                        @if($person->responsibleFor->count() > 0)
                                            <div class="text-green-600 font-medium">{{ $person->responsibleFor->count() }} Person(en)</div>
                                            @foreach($person->responsibleFor as $responsibleForPerson)
                                                <div class="text-gray-600">{{ $responsibleForPerson->first_name }} {{ $responsibleForPerson->last_name }}</div>
                                            @endforeach
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center space-x-2">
                                        <button wire:click="editPerson({{ $person->id }})" class="text-blue-600 hover:text-blue-900 text-sm">
                                            Bearbeiten
                                        </button>
                                        <button wire:click="deletePerson({{ $person->id }})" onclick="return confirm('Person wirklich löschen?')" class="text-red-600 hover:text-red-900 text-sm">
                                            Löschen
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div class="px-6 py-4 bg-gray-50">
                    {{ $persons->links() }}
                </div>
            @else
                <div class="p-6 text-center text-gray-500">
                    @if($search)
                        Keine Personen mit "{{ $search }}" gefunden.
                    @else
                        Noch keine Personen angelegt.
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
document.addEventListener('DOMContentLoaded', function() {
    // Event Listener für Gruppen-Auswahl
    document.addEventListener('change', function(e) {
        if (e.target.matches('select[wire\\:model\\.live="group_id"]')) {
            const selectedOption = e.target.selectedOptions[0];
            
            if (selectedOption && selectedOption.value) {
                // Voucher-Werte setzen
                for (let day = 1; day <= 4; day++) {
                    const voucherInput = document.querySelector(`input[wire\\:model="voucher_day_${day}"]`);
                    const voucherValue = selectedOption.getAttribute(`data-voucher-${day}`);
                    
                    if (voucherInput && voucherValue) {
                        voucherInput.value = voucherValue > 0 ? voucherValue : '';
                        // Livewire Event triggern
                        voucherInput.dispatchEvent(new Event('input'));
                    }
                }
                
                // Backstage-Checkboxen setzen
                for (let day = 1; day <= 4; day++) {
                    const backstageCheckbox = document.querySelector(`input[wire\\:model="backstage_day_${day}"]`);
                    const backstageValue = selectedOption.getAttribute(`data-backstage-${day}`);
                    
                    if (backstageCheckbox && backstageValue) {
                        backstageCheckbox.checked = backstageValue === '1';
                        // Livewire Event triggern
                        backstageCheckbox.dispatchEvent(new Event('change'));
                    }
                }
                
                // Kurze Bestätigung anzeigen
                showGroupLoadedMessage(selectedOption.textContent);
            } else {
                // Felder zurücksetzen wenn "Keine Gruppe" gewählt
                resetGroupValues();
            }
        }
    });
});

function showGroupLoadedMessage(groupName) {
    // Temporäre Bestätigung anzeigen
    const existingMessage = document.getElementById('group-loaded-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    const message = document.createElement('div');
    message.id = 'group-loaded-message';
    message.className = 'fixed top-4 right-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-2 rounded shadow-lg z-50';
    message.innerHTML = `✓ Einstellungen von "${groupName}" geladen`;
    
    document.body.appendChild(message);
    
    // Nach 3 Sekunden automatisch entfernen
    setTimeout(() => {
        if (message.parentNode) {
            message.parentNode.removeChild(message);
        }
    }, 3000);
}

function resetGroupValues() {
    // Voucher-Felder leeren
    for (let day = 1; day <= 4; day++) {
        const voucherInput = document.querySelector(`input[wire\\:model="voucher_day_${day}"]`);
        if (voucherInput) {
            voucherInput.value = '';
            voucherInput.dispatchEvent(new Event('input'));
        }
        
        const backstageCheckbox = document.querySelector(`input[wire\\:model="backstage_day_${day}"]`);
        if (backstageCheckbox) {
            backstageCheckbox.checked = false;
            backstageCheckbox.dispatchEvent(new Event('change'));
        }
    }
}

// Livewire Hook für nachgeladene Inhalte
document.addEventListener('livewire:navigated', function() {
    // Event Listeners neu initialisieren falls nötig
});
</script>
</div>