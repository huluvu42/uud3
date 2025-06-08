{{-- resources/views/livewire/management/band-management.blade.php --}}

<div class="container mx-auto px-4 py-8">
    <!-- Navigation -->
    <div class="mb-4">
        @include('partials.navigation')

    </div>
    <div class="mx-auto max-w-7xl">
        @if (session()->has('message'))
            <div class="mb-4 rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-4 rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700">
                {{ session('error') }}
            </div>
        @endif
    </div>
    @if ($selectedBand)
        <!-- Mitglieder-Ansicht -->
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-3xl font-bold text-gray-800">{{ $selectedBand->band_name }} - Mitglieder</h1>
            <div class="space-x-2">
                <button wire:click="addMember({{ $selectedBand->id }})"
                    class="rounded bg-green-500 px-4 py-2 font-bold text-white hover:bg-green-700">
                    Mitglied hinzufügen
                </button>
                <button wire:click="backToBandList"
                    class="rounded bg-gray-500 px-4 py-2 font-bold text-white hover:bg-gray-700">
                    Zurück zu Bands
                </button>
            </div>
        </div>

        <!-- Band Info -->
        <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <strong>Bühne:</strong> {{ $selectedBand->stage->name ?? 'Keine Bühne' }}
                </div>
                <div>
                    <strong>Jahr:</strong> {{ $selectedBand->year }}
                </div>
                <div>
                    <strong>Alle anwesend:</strong>
                    <span
                        class="{{ $selectedBand->all_present ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} rounded px-2 py-1 text-sm">
                        {{ $selectedBand->all_present ? 'Ja' : 'Nein' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Mitglied hinzufügen/bearbeiten Modal -->
        @if ($showMemberForm || $showEditMemberForm)
            <div class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
                <div class="relative top-20 mx-auto w-11/12 rounded-md border bg-white p-5 shadow-lg md:w-1/2">
                    <div class="mt-3">
                        <h2 class="mb-4 text-xl font-semibold">
                            {{ $showMemberForm ? 'Neues Mitglied hinzufügen' : 'Mitglied bearbeiten' }}
                        </h2>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Vorname</label>
                                <input type="text" wire:model="first_name"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('first_name')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Nachname</label>
                                <input type="text" wire:model="last_name"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('last_name')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
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
                            <h4 class="mb-2 text-sm font-medium text-gray-700">Backstage-Zugang (automatisch gesetzt)
                            </h4>
                            <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                                @foreach ([1, 2, 3, 4] as $day)
                                    <div class="flex items-center">
                                        <span
                                            class="{{ $this->{'backstage_day_' . $day} ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-600' }} mr-2 flex h-6 w-6 items-center justify-center rounded-full text-xs">
                                            {{ $day }}
                                        </span>
                                        <span class="text-sm">Tag {{ $day }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Automatische Voucher-Anzeige (nur relevante Tage) -->
                        @php
                            $hasVouchers =
                                $this->voucher_day_1 ||
                                $this->voucher_day_2 ||
                                $this->voucher_day_3 ||
                                $this->voucher_day_4;
                        @endphp
                        @if ($hasVouchers)
                            <div class="mt-4">
                                <h4 class="mb-2 text-sm font-medium text-gray-700">Gutscheine (automatisch gesetzt)</h4>
                                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                                    @foreach ([1, 2, 3, 4] as $day)
                                        @if ($this->{'voucher_day_' . $day})
                                            <div class="rounded bg-green-50 p-2">
                                                <div class="text-xs text-gray-600">Tag {{ $day }}</div>
                                                <div class="font-medium text-green-800">
                                                    {{ $this->{'voucher_day_' . $day} }} Gutscheine</div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="mt-4">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Bemerkungen</label>
                            <textarea wire:model="remarks" rows="2"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>

                        <div class="mt-6 flex justify-end space-x-2">
                            <button wire:click="cancelMemberForm"
                                class="rounded bg-gray-500 px-4 py-2 font-bold text-white hover:bg-gray-700">
                                Abbrechen
                            </button>
                            <button wire:click="{{ $showMemberForm ? 'saveMember' : 'updateMember' }}"
                                class="rounded bg-green-500 px-4 py-2 font-bold text-white hover:bg-green-700">
                                {{ $showMemberForm ? 'Hinzufügen' : 'Aktualisieren' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Gast hinzufügen Modal -->
        @if ($showGuestForm)
            <div class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
                <div class="relative top-20 mx-auto w-11/12 rounded-md border bg-white p-5 shadow-lg md:w-1/2">
                    <div class="mt-3">
                        <h2 class="mb-4 text-xl font-semibold">
                            Gast hinzufügen für {{ $selectedMember->first_name ?? '' }}
                            {{ $selectedMember->last_name ?? '' }}
                        </h2>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Vorname</label>
                                <input type="text" wire:model="guest_first_name"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('guest_first_name')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Nachname</label>
                                <input type="text" wire:model="guest_last_name"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('guest_last_name')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        @if ($selectedBand && $selectedBand->stage && $selectedBand->stage->guest_allowed)
                            <div class="mt-4 rounded-lg bg-green-50 p-3">
                                <p class="text-sm text-green-800">
                                    <strong>Info:</strong> Gäste erhalten automatisch Backstage-Zugang an den
                                    Auftrittstagen der Band.
                                </p>
                            </div>
                        @else
                            <div class="mt-4 rounded-lg bg-yellow-50 p-3">
                                <p class="text-sm text-yellow-800">
                                    <strong>Hinweis:</strong> Diese Bühne erlaubt keine Gäste. Der Gast wird ohne
                                    Backstage-Zugang angelegt.
                                </p>
                            </div>
                        @endif

                        <div class="mt-6 flex justify-end space-x-2">
                            <button wire:click="cancelGuestForm"
                                class="rounded bg-gray-500 px-4 py-2 font-bold text-white hover:bg-gray-700">
                                Abbrechen
                            </button>
                            <button wire:click="saveGuest"
                                class="rounded bg-green-500 px-4 py-2 font-bold text-white hover:bg-green-700">
                                Gast hinzufügen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- KFZ-Kennzeichen Modal -->
        @if ($showVehicleForm)
            <div class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
                <div class="relative top-20 mx-auto w-11/12 rounded-md border bg-white p-5 shadow-lg md:w-1/3">
                    <div class="mt-3">
                        <h3 class="mb-4 text-lg font-medium">Neues KFZ-Kennzeichen hinzufügen</h3>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Kennzeichen</label>
                            <input type="text" wire:model="license_plate" placeholder="z.B. AB-CD 123"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('license_plate')
                                <span class="text-sm text-red-500">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mt-6 flex justify-end space-x-2">
                            <button wire:click="cancelVehicleForm"
                                class="rounded bg-gray-500 px-4 py-2 font-bold text-white hover:bg-gray-700">
                                Abbrechen
                            </button>
                            <button wire:click="saveVehicle"
                                class="rounded bg-green-500 px-4 py-2 font-bold text-white hover:bg-green-700">
                                Hinzufügen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- KFZ-Kennzeichen -->
        <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-semibold">KFZ-Kennzeichen ({{ $selectedBand->vehiclePlates->count() }})</h2>
                <button wire:click="addVehicle({{ $selectedBand->id }})"
                    class="rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700">
                    Kennzeichen hinzufügen
                </button>
            </div>

            @if ($selectedBand->vehiclePlates->count() > 0)
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    @foreach ($selectedBand->vehiclePlates as $vehicle)
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 p-4">
                            <span class="font-mono text-lg">{{ $vehicle->license_plate }}</span>
                            <button wire:click="deleteVehicle({{ $vehicle->id }})"
                                onclick="return confirm('Kennzeichen wirklich löschen?')"
                                class="text-sm text-red-600 hover:text-red-900">
                                Löschen
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="py-4 text-center text-gray-500">Noch keine KFZ-Kennzeichen hinzugefügt.</p>
            @endif
        </div>

        <!-- Mitglieder Liste -->
        <div class="rounded-lg bg-white p-6 shadow-md">
            <h2 class="mb-4 text-xl font-semibold">Mitglieder ({{ $selectedBand->members->count() }})</h2>

            @if ($selectedBand->members->count() > 0)
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
                            @foreach ($selectedBand->members->sortBy(function ($member) {
        return $member->isGuest() ? 1 : 0;
    }) as $member)
                                <tr class="{{ $member->isGuest() ? 'bg-blue-50' : '' }} hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">
                                            {{ $member->first_name }} {{ $member->last_name }}
                                            @if ($member->isGuest())
                                                <span
                                                    class="ml-2 rounded bg-blue-100 px-2 py-1 text-xs text-blue-800">Gast</span>
                                            @endif
                                        </div>
                                        @if ($member->remarks)
                                            <div class="text-sm text-gray-500">{{ $member->remarks }}</div>
                                        @endif
                                        @if ($member->isGuest() && $member->hostMember)
                                            <div class="text-xs text-blue-600">Gast von:
                                                {{ $member->hostMember->first_name }}
                                                {{ $member->hostMember->last_name }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            class="{{ $member->isGuest() ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }} rounded px-2 py-1 text-xs">
                                            {{ $member->isGuest() ? 'Gast' : 'Mitglied' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            class="{{ $member->present ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} rounded px-2 py-1 text-xs">
                                            {{ $member->present ? 'Ja' : 'Nein' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center space-x-1">
                                            @foreach ([1, 2, 3, 4] as $day)
                                                <span
                                                    class="{{ $member->{'backstage_day_' . $day} ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-600' }} flex h-6 w-6 items-center justify-center rounded-full text-xs">
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
                                            @foreach ([1, 2, 3, 4] as $day)
                                                @if ($member->{'voucher_day_' . $day} > 0)
                                                    <div>T{{ $day }}: {{ $member->{'voucher_day_' . $day} }}
                                                    </div>
                                                    @php $memberHasVouchers = true; @endphp
                                                @endif
                                            @endforeach
                                            @if (!$memberHasVouchers)
                                                <span class="text-gray-400">Keine</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center space-x-2">
                                            @if (!$member->isGuest())
                                                @if (!$member->guest)
                                                    <button wire:click="addGuest({{ $member->id }})"
                                                        class="text-sm text-green-600 hover:text-green-900">
                                                        Gast hinzufügen
                                                    </button>
                                                @else
                                                    <span class="text-sm text-gray-400">Hat Gast</span>
                                                @endif
                                                <button wire:click="editMember({{ $member->id }})"
                                                    class="text-sm text-blue-600 hover:text-blue-900">
                                                    Bearbeiten
                                                </button>
                                                <button wire:click="deleteMember({{ $member->id }})"
                                                    onclick="return confirm('Mitglied wirklich löschen?')"
                                                    class="text-sm text-red-600 hover:text-red-900">
                                                    Löschen
                                                </button>
                                            @else
                                                <button wire:click="deleteGuest({{ $member->id }})"
                                                    onclick="return confirm('Gast wirklich löschen?')"
                                                    class="text-sm text-red-600 hover:text-red-900">
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
                <p class="py-8 text-center text-gray-500">Noch keine Mitglieder hinzugefügt.</p>
            @endif
        </div>
    @else
        <!-- Band-Listen-Ansicht -->
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-3xl font-bold text-gray-800">Band-Verwaltung</h1>
            <button wire:click="createBand" wire:loading.attr="disabled" wire:loading.class="opacity-50"
                wire:target="createBand"
                class="rounded bg-green-500 px-4 py-2 font-bold text-white transition-colors duration-200 hover:bg-green-700">
                <span wire:loading.remove wire:target="createBand">Neue Band</span>
                <span wire:loading wire:target="createBand">Lade...</span>
            </button>
        </div>

        <!-- Suchfeld -->
        <div class="mb-4">
            <div class="relative max-w-md">
                <input type="text" wire:model.live.debounce.300ms="search" wire:focus="focusSearch"
                    placeholder="Band suchen..."
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    id="search-input" autocomplete="off" ondblclick="@this.call('clearSearch')">

                <!-- Clear Button -->
                @if ($search)
                    <button type="button" wire:click="clearSearch"
                        class="absolute right-2 top-1/2 -translate-y-1/2 transform text-gray-400 transition-colors duration-200 hover:text-gray-600"
                        title="Suche löschen">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>
        </div>
        <!-- Loading Indicator für Suchergebnisse -->
        <div wire:loading wire:target="updatedSearch"
            class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-center">
            <div class="flex items-center justify-center space-x-2">
                <svg class="h-5 w-5 animate-spin text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span class="text-blue-700">Suche läuft...</span>
            </div>
        </div>
        <!-- Band erstellen/bearbeiten Modal -->
        @if ($showCreateForm || $showEditForm)
            <div class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
                <div class="relative top-20 mx-auto w-11/12 rounded-md border bg-white p-5 shadow-lg md:w-2/3">
                    <div class="mt-3">
                        <h2 class="mb-4 text-xl font-semibold">
                            {{ $showCreateForm ? 'Neue Band erstellen' : 'Band bearbeiten' }}
                        </h2>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Band Name</label>
                                <input type="text" wire:model="band_name"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('band_name')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Bühne</label>
                                <select wire:model="stage_id"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Bühne wählen</option>
                                    @foreach ($stages as $stage)
                                        <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                    @endforeach
                                </select>
                                @error('stage_id')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Reisekosten</label>
                                <input type="number" wire:model="travel_costs" step="0.01" min="0"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('travel_costs')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4 rounded-lg bg-gray-50 p-3">
                            <p class="text-sm text-gray-600">
                                <strong>Jahr:</strong> {{ date('Y') }} (automatisch gesetzt)
                            </p>
                        </div>

                        <div class="mt-4">
                            <h4 class="mb-2 text-sm font-medium text-gray-700">Spieltage</h4>
                            <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                                @foreach ([1, 2, 3, 4] as $day)
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="plays_day_{{ $day }}"
                                            class="mr-2">
                                        <span class="text-sm">Tag {{ $day }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-2">
                            <button wire:click="cancelBandForm"
                                class="rounded bg-gray-500 px-4 py-2 font-bold text-white hover:bg-gray-700">
                                Abbrechen
                            </button>
                            <button wire:click="{{ $showCreateForm ? 'saveBand' : 'updateBand' }}"
                                class="rounded bg-green-500 px-4 py-2 font-bold text-white hover:bg-green-700">
                                {{ $showCreateForm ? 'Erstellen' : 'Aktualisieren' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Bands Liste -->
        <div class="overflow-hidden rounded-lg bg-white shadow-md">
            @if ($bands->count() > 0)
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
                        @foreach ($bands as $band)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $band->band_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $band->year }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="text-sm text-gray-900">{{ $band->stage->name ?? 'Keine Bühne' }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center space-x-1">
                                        @foreach ([1, 2, 3, 4] as $day)
                                            <span
                                                class="{{ $band->{'plays_day_' . $day} ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-600' }} flex h-6 w-6 items-center justify-center rounded-full text-xs">
                                                {{ $day }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button wire:click="showMembers({{ $band->id }})"
                                        class="font-medium text-blue-600 hover:text-blue-900">
                                        {{ $band->members->count() }} Mitglieder
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="{{ $band->all_present ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} rounded px-2 py-1 text-xs">
                                        {{ $band->all_present ? 'Ja' : 'Nein' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button wire:click="showMembers({{ $band->id }})"
                                        wire:loading.attr="disabled" wire:loading.class="opacity-50"
                                        wire:target="showMembers"
                                        class="text-sm text-green-600 transition-colors duration-200 hover:text-green-900">
                                        <span wire:loading.remove wire:target="showMembers">Mitglieder</span>
                                        <span wire:loading wire:target="showMembers">...</span>
                                    </button>
                                    <button wire:click="editBand({{ $band->id }})" wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50" wire:target="editBand"
                                        class="text-sm text-blue-600 transition-colors duration-200 hover:text-blue-900">
                                        <span wire:loading.remove wire:target="editBand">Bearbeiten</span>
                                        <span wire:loading wire:target="editBand">...</span>
                                    </button>
                                    <button wire:click="deleteBand({{ $band->id }})"
                                        wire:confirm="Band wirklich löschen?" wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50" wire:target="deleteBand"
                                        class="text-sm text-red-600 transition-colors duration-200 hover:text-red-900">
                                        <span wire:loading.remove wire:target="deleteBand">Löschen</span>
                                        <span wire:loading wire:target="deleteBand">...</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="bg-gray-50 px-6 py-4">
                    {{ $bands->links() }}
                </div>
            @else
                <div class="p-6 text-center text-gray-500">
                    Keine Bands gefunden.
                </div>
            @endif
        </div>
    @endif
    <!-- Optimiertes JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Suchfeld-Funktionalität
            const searchInput = document.getElementById('search-input');

            if (searchInput) {
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && this.value.trim() !== '') {
                        e.preventDefault();
                        @this.call('clearSearch');
                        showTemporaryMessage('🔍 Suche mit ESC geleert', 'info');
                    }
                });
            }

            // Livewire Loading States
            window.addEventListener('livewire:request', () => {
                document.body.style.cursor = 'wait';
            });

            window.addEventListener('livewire:response', () => {
                document.body.style.cursor = 'default';
            });
        });

        // Hilfsfunktion für temporäre Nachrichten
        function showTemporaryMessage(message, type = 'info') {
            const colors = {
                'info': 'bg-blue-100 border-blue-400 text-blue-700',
                'success': 'bg-green-100 border-green-400 text-green-700',
                'warning': 'bg-yellow-100 border-yellow-400 text-yellow-700',
                'error': 'bg-red-100 border-red-400 text-red-700'
            };

            const existingMessage = document.getElementById('temp-message');
            if (existingMessage) existingMessage.remove();

            const messageDiv = document.createElement('div');
            messageDiv.id = 'temp-message';
            messageDiv.className = `fixed top-4 right-4 ${colors[type]} px-4 py-2 rounded border shadow-lg z-50 opacity-90`;
            messageDiv.textContent = message;

            document.body.appendChild(messageDiv);

            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.style.opacity = '0';
                    setTimeout(() => messageDiv.remove(), 300);
                }
            }, 2000);
        }
    </script>
</div>
