{{-- resources/views/livewire/management/band-management.blade.php --}}

<div class="container mx-auto px-4 py-8">
    <!-- Navigation -->
    <div class="mb-4">
        @include('partials.navigation')

    </div>
    <div class="max-w-7xl mx-auto">
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif
    </div>
    @if($selectedBand)
        <!-- Mitglieder-Ansicht -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">{{ $selectedBand->band_name }} - Mitglieder</h1>
            <div class="space-x-2">
                <button wire:click="addMember({{ $selectedBand->id }})" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    Mitglied hinzufügen
                </button>
                <button wire:click="backToBandList" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Zurück zu Bands
                </button>
            </div>
        </div>

        <!-- Band Info -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <strong>Bühne:</strong> {{ $selectedBand->stage->name ?? 'Keine Bühne' }}
                </div>
                <div>
                    <strong>Jahr:</strong> {{ $selectedBand->year }}
                </div>
                <div>
                    <strong>Alle anwesend:</strong> 
                    <span class="px-2 py-1 rounded text-sm {{ $selectedBand->all_present ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $selectedBand->all_present ? 'Ja' : 'Nein' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Mitglied hinzufügen/bearbeiten Modal -->
        @if($showMemberForm || $showEditMemberForm)
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <h2 class="text-xl font-semibold mb-4">
                            {{ $showMemberForm ? 'Neues Mitglied hinzufügen' : 'Mitglied bearbeiten' }}
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Vorname</label>
                                <input type="text" wire:model="first_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('first_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nachname</label>
                                <input type="text" wire:model="last_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('last_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="present" class="mr-2">
                                <span class="text-sm font-medium text-gray-700">Anwesend</span>
                            </label>
                        </div>

                        <!-- Automatische Backstage-Anzeige (nicht editierbar) -->
                        <div class="mt-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Backstage-Zugang (automatisch gesetzt)</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                @foreach([1,2,3,4] as $day)
                                    <div class="flex items-center">
                                        <span class="w-6 h-6 rounded-full text-xs flex items-center justify-center mr-2 {{ $this->{'backstage_day_' . $day} ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                                            {{ $day }}
                                        </span>
                                        <span class="text-sm">Tag {{ $day }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Automatische Voucher-Anzeige (nur relevante Tage) -->
                        @php
                            $hasVouchers = $this->voucher_day_1 || $this->voucher_day_2 || $this->voucher_day_3 || $this->voucher_day_4;
                        @endphp
                        @if($hasVouchers)
                            <div class="mt-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Gutscheine (automatisch gesetzt)</h4>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    @foreach([1,2,3,4] as $day)
                                        @if($this->{'voucher_day_' . $day})
                                            <div class="bg-green-50 p-2 rounded">
                                                <div class="text-xs text-gray-600">Tag {{ $day }}</div>
                                                <div class="font-medium text-green-800">{{ $this->{'voucher_day_' . $day} }} Gutscheine</div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bemerkungen</label>
                            <textarea wire:model="remarks" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>

                        <div class="flex justify-end space-x-2 mt-6">
                            <button wire:click="cancelMemberForm" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Abbrechen
                            </button>
                            <button wire:click="{{ $showMemberForm ? 'saveMember' : 'updateMember' }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                {{ $showMemberForm ? 'Hinzufügen' : 'Aktualisieren' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Gast hinzufügen Modal -->
        @if($showGuestForm)
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <h2 class="text-xl font-semibold mb-4">
                            Gast hinzufügen für {{ $selectedMember->first_name ?? '' }} {{ $selectedMember->last_name ?? '' }}
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Vorname</label>
                                <input type="text" wire:model="guest_first_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('guest_first_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nachname</label>
                                <input type="text" wire:model="guest_last_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('guest_last_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        @if($selectedBand && $selectedBand->stage && $selectedBand->stage->guest_allowed)
                            <div class="mt-4 p-3 bg-green-50 rounded-lg">
                                <p class="text-sm text-green-800">
                                    <strong>Info:</strong> Gäste erhalten automatisch Backstage-Zugang an den Auftrittstagen der Band.
                                </p>
                            </div>
                        @else
                            <div class="mt-4 p-3 bg-yellow-50 rounded-lg">
                                <p class="text-sm text-yellow-800">
                                    <strong>Hinweis:</strong> Diese Bühne erlaubt keine Gäste. Der Gast wird ohne Backstage-Zugang angelegt.
                                </p>
                            </div>
                        @endif

                        <div class="flex justify-end space-x-2 mt-6">
                            <button wire:click="cancelGuestForm" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Abbrechen
                            </button>
                            <button wire:click="saveGuest" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Gast hinzufügen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- KFZ-Kennzeichen Modal -->
        @if($showVehicleForm)
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/3 shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <h3 class="text-lg font-medium mb-4">Neues KFZ-Kennzeichen hinzufügen</h3>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kennzeichen</label>
                            <input type="text" wire:model="license_plate" placeholder="z.B. AB-CD 123" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('license_plate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex justify-end space-x-2 mt-6">
                            <button wire:click="cancelVehicleForm" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Abbrechen
                            </button>
                            <button wire:click="saveVehicle" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Hinzufügen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- KFZ-Kennzeichen -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">KFZ-Kennzeichen ({{ $selectedBand->vehiclePlates->count() }})</h2>
                <button wire:click="addVehicle({{ $selectedBand->id }})" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Kennzeichen hinzufügen
                </button>
            </div>

            @if($selectedBand->vehiclePlates->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($selectedBand->vehiclePlates as $vehicle)
                        <div class="bg-gray-50 p-4 rounded-lg flex justify-between items-center">
                            <span class="font-mono text-lg">{{ $vehicle->license_plate }}</span>
                            <button wire:click="deleteVehicle({{ $vehicle->id }})" onclick="return confirm('Kennzeichen wirklich löschen?')" class="text-red-600 hover:text-red-900 text-sm">
                                Löschen
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-center py-4">Noch keine KFZ-Kennzeichen hinzugefügt.</p>
            @endif
        </div>

        <!-- Mitglieder Liste -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Mitglieder ({{ $selectedBand->members->count() }})</h2>
            
            @if($selectedBand->members->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Name</th>
                                <th class="px-4 py-2 text-center text-sm font-medium text-gray-700">Typ</th>
                                <th class="px-4 py-2 text-center text-sm font-medium text-gray-700">Anwesend</th>
                                <th class="px-4 py-2 text-center text-sm font-medium text-gray-700">Backstage</th>
                                <th class="px-4 py-2 text-center text-sm font-medium text-gray-700">Gutscheine</th>
                                <th class="px-4 py-2 text-center text-sm font-medium text-gray-700">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($selectedBand->members->sortBy(function($member) { return $member->isGuest() ? 1 : 0; }) as $member)
                                <tr class="hover:bg-gray-50 {{ $member->isGuest() ? 'bg-blue-50' : '' }}">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">
                                            {{ $member->first_name }} {{ $member->last_name }}
                                            @if($member->isGuest())
                                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded ml-2">Gast</span>
                                            @endif
                                        </div>
                                        @if($member->remarks)
                                            <div class="text-sm text-gray-500">{{ $member->remarks }}</div>
                                        @endif
                                        @if($member->isGuest() && $member->hostMember)
                                            <div class="text-xs text-blue-600">Gast von: {{ $member->hostMember->first_name }} {{ $member->hostMember->last_name }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 rounded text-xs {{ $member->isGuest() ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $member->isGuest() ? 'Gast' : 'Mitglied' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 rounded text-xs {{ $member->present ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $member->present ? 'Ja' : 'Nein' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center space-x-1">
                                            @foreach([1,2,3,4] as $day)
                                                <span class="w-6 h-6 rounded-full text-xs flex items-center justify-center {{ $member->{'backstage_day_' . $day} ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                                                    {{ $day }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="text-xs">
                                            @php
                                                $memberHasVouchers = false;
                                            @endphp
                                            @foreach([1,2,3,4] as $day)
                                                @if($member->{'voucher_day_' . $day} > 0)
                                                    <div>T{{ $day }}: {{ $member->{'voucher_day_' . $day} }}</div>
                                                    @php $memberHasVouchers = true; @endphp
                                                @endif
                                            @endforeach
                                            @if(!$memberHasVouchers)
                                                <span class="text-gray-400">Keine</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center space-x-2">
                                            @if(!$member->isGuest())
                                                @if(!$member->guest)
                                                    <button wire:click="addGuest({{ $member->id }})" class="text-green-600 hover:text-green-900 text-sm">
                                                        Gast hinzufügen
                                                    </button>
                                                @else
                                                    <span class="text-gray-400 text-sm">Hat Gast</span>
                                                @endif
                                                <button wire:click="editMember({{ $member->id }})" class="text-blue-600 hover:text-blue-900 text-sm">
                                                    Bearbeiten
                                                </button>
                                                <button wire:click="deleteMember({{ $member->id }})" onclick="return confirm('Mitglied wirklich löschen?')" class="text-red-600 hover:text-red-900 text-sm">
                                                    Löschen
                                                </button>
                                            @else
                                                <button wire:click="deleteGuest({{ $member->id }})" onclick="return confirm('Gast wirklich löschen?')" class="text-red-600 hover:text-red-900 text-sm">
                                                    Gast entfernen
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 text-center py-8">Noch keine Mitglieder hinzugefügt.</p>
            @endif
        </div>

    @else
        <!-- Band-Listen-Ansicht -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Band-Verwaltung</h1>
            <button wire:click="createBand" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                Neue Band
            </button>
        </div>

        <!-- Suchfeld -->
        <div class="mb-4">
            <input type="text" wire:model.live="search" placeholder="Band suchen..." class="w-full max-w-md px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <!-- Band erstellen/bearbeiten Modal -->
        @if($showCreateForm || $showEditForm)
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <h2 class="text-xl font-semibold mb-4">
                            {{ $showCreateForm ? 'Neue Band erstellen' : 'Band bearbeiten' }}
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Band Name</label>
                                <input type="text" wire:model="band_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('band_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bühne</label>
                                <select wire:model="stage_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Bühne wählen</option>
                                    @foreach($stages as $stage)
                                        <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                    @endforeach
                                </select>
                                @error('stage_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Reisekosten</label>
                                <input type="number" wire:model="travel_costs" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('travel_costs') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-600">
                                <strong>Jahr:</strong> {{ date('Y') }} (automatisch gesetzt)
                            </p>
                        </div>

                        <div class="mt-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Spieltage</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                @foreach([1,2,3,4] as $day)
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="plays_day_{{ $day }}" class="mr-2">
                                        <span class="text-sm">Tag {{ $day }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex justify-end space-x-2 mt-6">
                            <button wire:click="cancelBandForm" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Abbrechen
                            </button>
                            <button wire:click="{{ $showCreateForm ? 'saveBand' : 'updateBand' }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                {{ $showCreateForm ? 'Erstellen' : 'Aktualisieren' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Bands Liste -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            @if($bands->count() > 0)
                <table class="min-w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Band Name</th>
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Bühne</th>
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Spieltage</th>
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Mitglieder</th>
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Alle anwesend</th>
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($bands as $band)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $band->band_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $band->year }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-sm text-gray-900">{{ $band->stage->name ?? 'Keine Bühne' }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center space-x-1">
                                        @foreach([1,2,3,4] as $day)
                                            <span class="w-6 h-6 rounded-full text-xs flex items-center justify-center {{ $band->{'plays_day_' . $day} ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                                                {{ $day }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button wire:click="showMembers({{ $band->id }})" class="text-blue-600 hover:text-blue-900 font-medium">
                                        {{ $band->members->count() }} Mitglieder
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-1 rounded text-xs {{ $band->all_present ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $band->all_present ? 'Ja' : 'Nein' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center space-x-2">
                                        <button wire:click="showMembers({{ $band->id }})" class="text-green-600 hover:text-green-900 text-sm">
                                            Mitglieder
                                        </button>
                                        <button wire:click="editBand({{ $band->id }})" class="text-blue-600 hover:text-blue-900 text-sm">
                                            Bearbeiten
                                        </button>
                                        <button wire:click="deleteBand({{ $band->id }})" onclick="return confirm('Band wirklich löschen?')" class="text-red-600 hover:text-red-900 text-sm">
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
                    {{ $bands->links() }}
                </div>
            @else
                <div class="p-6 text-center text-gray-500">
                    Keine Bands gefunden.
                </div>
            @endif
        </div>
    @endif
</div>