{{-- resources/views/livewire/management/band-management.blade.php - PERFORMANCE OPTIMIERT --}}

<div class="container mx-auto px-4 py-8">
    <!-- Navigation -->
    <div class="mb-4">
        @include('partials.navigation')
    </div>

    <div class="mx-auto max-w-7xl">
        <!-- Flash Messages -->
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
        <!-- ===== MITGLIEDER-ANSICHT ===== -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">{{ $selectedBand->band_name }} - Mitglieder</h1>
                <div class="mt-2 flex items-center space-x-4 text-sm text-gray-600">
                    <span>{{ $selectedBand->stage->name ?? 'Keine Bühne' }}</span>
                    <span class="{{ $selectedBand->all_present ? 'text-green-600' : 'text-red-600' }}">
                        {{ $selectedBand->all_present ? '✅ Alle anwesend' : '❌ Nicht alle anwesend' }}
                    </span>
                </div>
            </div>
            <div class="flex space-x-2">
                <button wire:click="addMember({{ $selectedBand->id }})"
                    class="rounded bg-green-500 px-4 py-2 font-bold text-white transition-colors duration-200 hover:bg-green-700">
                    Mitglied hinzufügen
                </button>
                <button wire:click="backToBandList"
                    class="rounded bg-gray-500 px-4 py-2 font-bold text-white transition-colors duration-200 hover:bg-gray-700">
                    Zurück zu Bands
                </button>
            </div>
        </div>

        <!-- Band Info Dashboard -->
        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="rounded-lg bg-white p-4 shadow-md">
                <div class="text-2xl font-bold text-blue-600">{{ $selectedBand->members->count() }}</div>
                <div class="text-sm text-gray-600">Mitglieder gesamt</div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-md">
                <div class="text-2xl font-bold text-green-600">
                    {{ $selectedBand->members->where('present', true)->count() }}
                </div>
                <div class="text-sm text-gray-600">Anwesend</div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-md">
                <div class="text-2xl font-bold text-purple-600">{{ $selectedBand->vehiclePlates->count() }}</div>
                <div class="text-sm text-gray-600">Fahrzeuge</div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-md">
                <div class="text-2xl font-bold text-orange-600">
                    {{ $selectedBand->members->where('responsible_person_id', '!=', null)->count() }}
                </div>
                <div class="text-sm text-gray-600">Gäste</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-6 rounded-lg bg-white p-4 shadow-md">
            <h3 class="mb-3 text-lg font-semibold">🚀 Schnell-Aktionen</h3>
            <div class="flex flex-wrap gap-2">
                <button wire:click="markAllMembersPresent({{ $selectedBand->id }})"
                    wire:confirm="Alle Mitglieder als anwesend markieren?"
                    class="rounded bg-green-500 px-3 py-1 text-sm text-white hover:bg-green-600">
                    ✅ Alle anwesend
                </button>
                <button wire:click="markAllMembersAbsent({{ $selectedBand->id }})"
                    wire:confirm="Alle Mitglieder als abwesend markieren?"
                    class="rounded bg-red-500 px-3 py-1 text-sm text-white hover:bg-red-600">
                    ❌ Alle abwesend
                </button>
                <button wire:click="recalculateVouchersForBand({{ $selectedBand->id }})"
                    wire:confirm="Voucher für alle Mitglieder neu berechnen?"
                    class="rounded bg-blue-500 px-3 py-1 text-sm text-white hover:bg-blue-600">
                    💰 Voucher neu berechnen
                </button>
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

                        <!-- Keyboard Shortcuts Hint -->
                        <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <div class="text-sm text-gray-600">
                                <span class="font-medium">⌨️ Tastaturkürzel:</span>
                                <kbd class="ml-2 rounded border bg-white px-2 py-1 text-xs">Enter</kbd> =
                                {{ $showMemberForm ? 'Hinzufügen' : 'Aktualisieren' }}
                                <span class="mx-2">•</span>
                                <kbd class="rounded border bg-white px-2 py-1 text-xs">Esc</kbd> = Abbrechen
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Vorname *</label>
                                <input type="text" wire:model="first_name"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Max">
                                @error('first_name')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Nachname *</label>
                                <input type="text" wire:model="last_name"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Mustermann">
                                @error('last_name')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <label
                                class="{{ $present ? 'border-green-500 bg-green-50' : '' }} flex cursor-pointer items-center rounded-lg border p-3 transition-colors duration-200 hover:bg-gray-50">
                                <input type="checkbox" wire:model="present"
                                    class="mr-3 rounded border-gray-300 text-green-600">
                                <div>
                                    <div class="font-medium">✅ Anwesend</div>
                                    <div class="text-sm text-gray-500">Mitglied ist derzeit beim Festival anwesend</div>
                                </div>
                            </label>
                        </div>

                        <!-- Automatische Backstage-Anzeige -->
                        <div class="mt-4">
                            <h4 class="mb-2 text-sm font-medium text-gray-700">🎫 Backstage-Zugang (automatisch gesetzt)
                            </h4>
                            <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                                @foreach ([1, 2, 3, 4] as $day)
                                    <div
                                        class="{{ $this->{'backstage_day_' . $day} ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-gray-50' }} flex items-center justify-center rounded-lg border p-2">
                                        <span
                                            class="{{ $this->{'backstage_day_' . $day} ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-600' }} mr-2 flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold">
                                            {{ $day }}
                                        </span>
                                        <span class="text-sm font-medium">Tag {{ $day }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Automatische Voucher-Anzeige -->
                        @php
                            $totalVouchers =
                                (float) ($this->voucher_day_1 ?: 0) +
                                (float) ($this->voucher_day_2 ?: 0) +
                                (float) ($this->voucher_day_3 ?: 0) +
                                (float) ($this->voucher_day_4 ?: 0);
                        @endphp
                        @if ($totalVouchers > 0)
                            <div class="mt-4">
                                <h4 class="mb-2 text-sm font-medium text-gray-700">💰 Gutscheine (automatisch gesetzt)
                                </h4>
                                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                                    @foreach ([1, 2, 3, 4] as $day)
                                        @if ($this->{'voucher_day_' . $day})
                                            <div class="rounded-lg border border-green-200 bg-green-50 p-3">
                                                <div class="text-xs text-gray-600">Tag {{ $day }}</div>
                                                <div class="font-bold text-green-800">
                                                    ($this->{'voucher_day_' . $day})
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                                <div class="mt-2 text-right text-sm text-gray-600">
                                    Gesamt: <span class="font-bold text-green-600">($totalVouchers)</span>
                                </div>
                            </div>
                        @endif

                        <div class="mt-4">
                            <label class="mb-1 block text-sm font-medium text-gray-700">💬 Bemerkungen</label>
                            <textarea wire:model="remarks" rows="2"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Optionale Bemerkungen zum Mitglied..."></textarea>
                        </div>

                        <div class="mt-6 flex justify-end space-x-2">
                            <button wire:click="cancelMemberForm"
                                class="rounded bg-gray-500 px-4 py-2 font-bold text-white transition-colors duration-200 hover:bg-gray-700">
                                Abbrechen
                            </button>
                            <button wire:click="{{ $showMemberForm ? 'saveMember' : 'updateMember' }}"
                                class="rounded bg-green-500 px-4 py-2 font-bold text-white transition-colors duration-200 hover:bg-green-700">
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
                            👥 Gast hinzufügen für {{ $selectedMember->first_name ?? '' }}
                            {{ $selectedMember->last_name ?? '' }}
                        </h2>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Vorname *</label>
                                <input type="text" wire:model="guest_first_name"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Anna">
                                @error('guest_first_name')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Nachname *</label>
                                <input type="text" wire:model="guest_last_name"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Muster">
                                @error('guest_last_name')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        @if ($selectedBand && $selectedBand->stage && $selectedBand->stage->guest_allowed)
                            <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-3">
                                <div class="flex items-center">
                                    <span class="mr-2 text-green-600">✅</span>
                                    <div>
                                        <p class="font-medium text-green-800">Gäste erlaubt</p>
                                        <p class="text-sm text-green-700">
                                            Gast erhält automatisch Backstage-Zugang an den Auftrittstagen der Band.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="mt-4 rounded-lg border border-yellow-200 bg-yellow-50 p-3">
                                <div class="flex items-center">
                                    <span class="mr-2 text-yellow-600">⚠️</span>
                                    <div>
                                        <p class="font-medium text-yellow-800">Eingeschränkte Berechtigung</p>
                                        <p class="text-sm text-yellow-700">
                                            Diese Bühne erlaubt keine Gäste. Der Gast wird ohne Backstage-Zugang
                                            angelegt.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="mt-6 flex justify-end space-x-2">
                            <button wire:click="cancelGuestForm"
                                class="rounded bg-gray-500 px-4 py-2 font-bold text-white transition-colors duration-200 hover:bg-gray-700">
                                Abbrechen
                            </button>
                            <button wire:click="saveGuest"
                                class="rounded bg-green-500 px-4 py-2 font-bold text-white transition-colors duration-200 hover:bg-green-700">
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
                        <h3 class="mb-4 text-lg font-medium">🚗 Neues KFZ-Kennzeichen hinzufügen</h3>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Kennzeichen *</label>
                            <input type="text" wire:model="license_plate" placeholder="z.B. AB-CD 123"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                style="text-transform: uppercase;">
                            @error('license_plate')
                                <span class="text-sm text-red-500">{{ $message }}</span>
                            @enderror
                            <div class="mt-1 text-xs text-gray-500">
                                💡 Tipp: Das Kennzeichen wird automatisch in Großbuchstaben umgewandelt
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end space-x-2">
                            <button wire:click="cancelVehicleForm"
                                class="rounded bg-gray-500 px-4 py-2 font-bold text-white transition-colors duration-200 hover:bg-gray-700">
                                Abbrechen
                            </button>
                            <button wire:click="saveVehicle"
                                class="rounded bg-green-500 px-4 py-2 font-bold text-white transition-colors duration-200 hover:bg-green-700">
                                Hinzufügen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- KFZ-Kennzeichen Section -->
        <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-semibold">🚗 KFZ-Kennzeichen ({{ $selectedBand->vehiclePlates->count() }})
                </h2>
                <button wire:click="addVehicle({{ $selectedBand->id }})"
                    class="rounded bg-blue-500 px-4 py-2 font-bold text-white transition-colors duration-200 hover:bg-blue-700">
                    ➕ Kennzeichen hinzufügen
                </button>
            </div>

            @if ($selectedBand->vehiclePlates->count() > 0)
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($selectedBand->vehiclePlates as $vehicle)
                        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4 transition-colors duration-200 hover:bg-gray-100"
                            wire:key="vehicle-{{ $vehicle->id }}-{{ $loop->index }}">
                            <div class="flex items-center">
                                <span class="mr-2 text-blue-600">🚗</span>
                                <span class="font-mono text-lg font-bold">{{ $vehicle->license_plate }}</span>
                            </div>
                            <button wire:click="deleteVehicle({{ $vehicle->id }})"
                                wire:confirm="Kennzeichen '{{ $vehicle->license_plate }}' wirklich löschen?"
                                class="text-sm text-red-600 transition-colors duration-200 hover:text-red-900"
                                title="Kennzeichen löschen">
                                🗑️
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center text-gray-500">
                    <div class="mb-2 text-4xl">🚗</div>
                    <p>Noch keine KFZ-Kennzeichen hinzugefügt.</p>
                </div>
            @endif
        </div>

        <!-- Mitglieder Liste -->
        <div class="rounded-lg bg-white p-6 shadow-md">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-semibold">👥 Mitglieder ({{ $selectedBand->members->count() }})</h2>
                <!-- Member Search -->
                <div class="relative">
                    <input type="text" placeholder="Mitglieder durchsuchen..."
                        class="w-64 rounded-md border border-gray-300 px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <span class="absolute right-3 top-2.5 text-gray-400">🔍</span>
                </div>
            </div>

            @if ($selectedBand->members->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                    Name</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">
                                    Typ</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">
                                    Anwesend</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">
                                    Backstage</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">
                                    Gutscheine</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">
                                    KFZ</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">
                                    Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($selectedBand->members->sortBy(function ($member) {
        return $member->isGuest() ? 1 : 0;
    }) as $member)
                                <tr class="{{ $member->isGuest() ? 'bg-blue-50' : '' }} transition-colors duration-150 hover:bg-gray-50"
                                    wire:key="member-{{ $member->id }}-{{ $loop->index }}">
                                    <td class="px-4 py-4">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <div
                                                    class="{{ $member->isGuest() ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-600' }} flex h-10 w-10 items-center justify-center rounded-full">
                                                    {{ $member->isGuest() ? '👥' : '🎵' }}
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="font-medium text-gray-900">
                                                    {{ $member->first_name }} {{ $member->last_name }}
                                                    @if ($member->isGuest())
                                                        <span
                                                            class="ml-2 rounded bg-blue-100 px-2 py-1 text-xs text-blue-800">👥
                                                            Gast</span>
                                                    @endif
                                                </div>
                                                @if ($member->remarks)
                                                    <div class="text-sm text-gray-500">💬
                                                        {{ Str::limit($member->remarks, 50) }}</div>
                                                @endif

                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span
                                            class="{{ $member->isGuest() ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }} inline-flex items-center rounded-full px-3 py-1 text-xs font-medium">
                                            {{ $member->isGuest() ? '👥 Gast' : '🎵 Mitglied' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <button wire:click="toggleMemberPresence({{ $member->id }})"
                                            class="{{ $member->present ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }} inline-flex items-center rounded-full px-3 py-1 text-xs font-medium transition-colors duration-200"
                                            title="Klicken um Status zu ändern">
                                            {{ $member->present ? '✅ Ja' : '❌ Nein' }}
                                        </button>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <div class="flex justify-center space-x-1">
                                            @foreach ([1, 2, 3, 4] as $day)
                                                <span
                                                    class="{{ $member->{'backstage_day_' . $day} ? 'bg-blue-500 text-white shadow-md' : 'bg-gray-200 text-gray-600' }} flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold transition-colors duration-200"
                                                    title="Tag {{ $day }}: {{ $member->{'backstage_day_' . $day} ? 'Berechtigt' : 'Nicht berechtigt' }}">
                                                    {{ $day }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <div class="text-xs">
                                            @php
                                                $memberHasVouchers = false;
                                                $totalMemberVouchers = 0;
                                            @endphp
                                            @foreach ([1, 2, 3, 4] as $day)
                                                @if ($member->{'voucher_day_' . $day} > 0)
                                                    <div class="mb-1">
                                                        <span
                                                            class="inline-flex items-center rounded bg-green-100 px-2 py-1 text-xs text-green-800">
                                                            {{ number_format($member->{'voucher_day_' . $day}, 0) }}
                                                        </span>
                                                    </div>
                                                    @php
                                                        $memberHasVouchers = true;
                                                        $totalMemberVouchers +=
                                                            (float) $member->{'voucher_day_' . $day};
                                                    @endphp
                                                @endif
                                            @endforeach
                                            @if (!$memberHasVouchers)
                                                <span class="text-gray-400">💸 Keine</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <!-- KFZ-Kennzeichen anzeigen (VEREINFACHT) -->
                                        <div class="text-xs">
                                            @if ($member->vehiclePlates && $member->vehiclePlates->count() > 0)
                                                <div class="space-y-1">
                                                    @foreach ($member->vehiclePlates as $plate)
                                                        <span
                                                            class="inline-block rounded bg-blue-50 px-2 py-1 font-mono text-xs text-blue-800">
                                                            {{ $plate->license_plate }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-gray-400">🚗 Keine</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <div class="flex justify-center space-x-1">
                                            @if (!$member->isGuest())
                                                <!-- KFZ-Kennzeichen Button (VEREINFACHT - verwendet bestehende Component) -->
                                                <button wire:click="showVehiclePlates({{ $member->id }})"
                                                    class="rounded bg-gray-500 px-2 py-1 text-xs text-white transition-colors duration-200 hover:bg-gray-600"
                                                    title="KFZ-Kennzeichen verwalten">
                                                    🚗
                                                </button>

                                                @if (!$member->guest && $selectedBand->stage && $selectedBand->stage->guest_allowed)
                                                    <button wire:click="addGuest({{ $member->id }})"
                                                        class="rounded bg-purple-500 px-2 py-1 text-xs text-white transition-colors duration-200 hover:bg-purple-600"
                                                        title="Gast hinzufügen">
                                                        👥 Gast +
                                                    </button>
                                                @elseif($member->guest)
                                                    <span
                                                        class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600">👥
                                                        Hat Gast</span>
                                                @else
                                                    <span class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-400"
                                                        title="Bühne erlaubt keine Gäste">🚫 Keine Gäste</span>
                                                @endif
                                                <button wire:click="editMember({{ $member->id }})"
                                                    class="rounded bg-blue-500 px-2 py-1 text-xs text-white transition-colors duration-200 hover:bg-blue-600"
                                                    title="Mitglied bearbeiten">
                                                    ✏️
                                                </button>
                                                <button wire:click="deleteMember({{ $member->id }})"
                                                    wire:confirm="Mitglied '{{ $member->first_name }} {{ $member->last_name }}' wirklich löschen?"
                                                    class="rounded bg-red-500 px-2 py-1 text-xs text-white transition-colors duration-200 hover:bg-red-600"
                                                    title="Mitglied löschen">
                                                    🗑️
                                                </button>
                                            @else
                                                <span class="text-xs text-gray-500">Gast von:
                                                    {{ $member->responsiblePerson ? $member->responsiblePerson->first_name : 'Unbekannt' }}</span>
                                                <button wire:click="deleteMember({{ $member->id }})"
                                                    wire:confirm="Gast '{{ $member->first_name }} {{ $member->last_name }}' wirklich löschen?"
                                                    class="ml-2 rounded bg-red-500 px-2 py-1 text-xs text-white transition-colors duration-200 hover:bg-red-600"
                                                    title="Gast löschen">
                                                    🗑️
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
                <div class="py-12 text-center">
                    <div class="mb-4 text-6xl">👥</div>
                    <h3 class="mb-2 text-lg font-medium text-gray-900">Noch keine Mitglieder</h3>
                    <p class="mb-4 text-gray-500">Fügen Sie das erste Mitglied für diese Band hinzu.</p>
                    <button wire:click="addMember({{ $selectedBand->id }})"
                        class="rounded bg-green-500 px-4 py-2 font-bold text-white transition-colors duration-200 hover:bg-green-700">
                        ➕ Erstes Mitglied hinzufügen
                    </button>
                </div>
            @endif
        </div>
    @else
        <!-- ===== BAND-LISTEN-ANSICHT ===== -->

        <!-- Header Actions -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Band-Verwaltung</h1>
                <p class="mt-2 text-gray-600">Jahr {{ date('Y') }} • {{ $bands->total() ?? 0 }} Bands gefunden
                </p>
            </div>
            <button wire:click="createBand" wire:loading.attr="disabled" wire:loading.class="opacity-50"
                wire:target="createBand"
                class="rounded bg-green-500 px-6 py-3 font-bold text-white transition-colors duration-200 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                <span wire:loading.remove wire:target="createBand">Neue Band</span>
                <span wire:loading wire:target="createBand">⏳ Erstelle...</span>
            </button>
        </div>

        <!-- Advanced Search & Filters -->
        <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <!-- Search Field -->
                <div class="lg:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Band suchen</label>
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.300ms="search" wire:focus="focusSearch"
                            placeholder="Bandname oder Bühne eingeben..."
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 pr-12 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            id="search-input" autocomplete="off" ondblclick="@this.call('clearSearch')">

                        <!-- Search Actions -->
                        <div class="absolute right-2 top-1/2 flex -translate-y-1/2 space-x-1">
                            @if ($search)
                                <button type="button" wire:click="clearSearch"
                                    class="text-gray-400 transition-colors duration-200 hover:text-gray-600"
                                    title="Suche löschen (oder Doppelklick auf Feld)">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            @else
                                <span class="text-gray-400">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </span>
                            @endif
                        </div>
                    </div>
                    @if ($search)
                        <div class="mt-2 text-sm text-gray-600">
                            💡 <strong>Tipp:</strong> ESC drücken oder Doppelklick zum Leeren •
                            {{ $bands->total() ?? 0 }} Ergebnisse für "{{ $search }}"
                        </div>
                    @endif
                </div>

                <!-- Quick Actions -->
                <div>
                    <div class="space-y-2">
                        <button wire:click="createBand"
                            class="w-full rounded bg-green-500 px-4 py-2 text-sm text-white transition-colors duration-200 hover:bg-green-600">
                            Neue Band erstellen
                        </button>
                        <!-- Future: Export/Import buttons -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div wire:loading wire:target="updatedSearch,createBand,editBand,deleteBand,showMembers"
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
                <span class="text-blue-700">Verarbeite Anfrage...</span>
            </div>
        </div>

        <!-- Band erstellen/bearbeiten Modal -->
        @if ($showCreateForm || $showEditForm)
            <div class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
                <div
                    class="relative top-20 mx-auto w-11/12 rounded-md border bg-white p-6 shadow-lg md:w-2/3 lg:w-1/2">
                    <div class="mt-3">
                        <h2 class="mb-6 text-2xl font-semibold">
                            {{ $showCreateForm ? '🎵 Neue Band erstellen' : '✏️ Band bearbeiten' }}
                        </h2>

                        <!-- Keyboard Shortcuts -->
                        <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="text-sm text-gray-600">
                                <span class="font-medium">⌨️ Tastaturkürzel:</span>
                                <kbd class="ml-2 rounded border bg-white px-2 py-1 text-xs">Enter</kbd> =
                                {{ $showCreateForm ? 'Erstellen' : 'Aktualisieren' }}
                                <span class="mx-2">•</span>
                                <kbd class="rounded border bg-white px-2 py-1 text-xs">Esc</kbd> = Abbrechen
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-gray-700">Band Name *</label>
                                <input type="text" wire:model="band_name"
                                    class="w-full rounded-md border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="z.B. The Awesome Rockers">
                                @error('band_name')
                                    <span class="mt-1 text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Bühne *</label>
                                <select wire:model="stage_id"
                                    class="w-full rounded-md border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">🎭 Bühne wählen</option>
                                    @foreach ($stages as $stage)
                                        <option value="{{ $stage->id }}">
                                            {{ $stage->name }}
                                            @if ($stage->max_bands < 999)
                                                (max. {{ $stage->max_bands }} Bands)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('stage_id')
                                    <span class="mt-1 text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Reisekosten</label>
                                <div class="relative">
                                    <input type="number" wire:model="travel_costs" step="0.01" min="0"
                                        max="99999.99"
                                        class="w-full rounded-md border border-gray-300 px-4 py-3 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="0.00">
                                    <span class="absolute right-3 top-3 text-gray-500">€</span>
                                </div>
                                @error('travel_costs')
                                    <span class="mt-1 text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Jahr Info -->
                        <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <div class="flex items-center">
                                <span class="mr-2 text-blue-600">📅</span>
                                <div>
                                    <p class="font-medium text-blue-800">Jahr: {{ date('Y') }}</p>
                                    <p class="text-sm text-blue-700">Wird automatisch für das aktuelle Jahr erstellt
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Spieltage -->
                        <div class="mt-6">
                            <h4 class="mb-4 text-lg font-medium text-gray-700">🗓️ Spieltage auswählen</h4>
                            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                                @foreach ([1, 2, 3, 4] as $day)
                                    <label
                                        class="{{ $this->{'plays_day_' . $day} ? 'border-blue-500 bg-blue-50' : '' }} flex cursor-pointer items-center rounded-lg border p-4 transition-colors duration-200 hover:bg-gray-50">
                                        <input type="checkbox" wire:model="plays_day_{{ $day }}"
                                            class="mr-3 rounded border-gray-300 text-blue-600">
                                        <div class="text-center">
                                            <div class="font-bold text-gray-900">Tag {{ $day }}</div>
                                            <div class="text-xs text-gray-500">Festival Tag {{ $day }}</div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @if (!($plays_day_1 || $plays_day_2 || $plays_day_3 || $plays_day_4))
                                <div class="mt-2 text-sm text-amber-600">
                                    ⚠️ Mindestens ein Spieltag muss ausgewählt werden
                                </div>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="mt-8 flex justify-end space-x-4 border-t border-gray-200 pt-6">
                            <button wire:click="cancelBandForm"
                                class="rounded border border-gray-300 px-6 py-3 text-gray-600 transition-colors duration-200 hover:bg-gray-50">
                                ❌ Abbrechen
                            </button>
                            <button wire:click="{{ $showCreateForm ? 'saveBand' : 'updateBand' }}"
                                class="rounded bg-green-500 px-6 py-3 font-bold text-white transition-colors duration-200 hover:bg-green-700">
                                {{ $showCreateForm ? '🎵 Band erstellen' : '💾 Änderungen speichern' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Bands Liste -->
        <div class="overflow-hidden rounded-lg bg-white shadow-md">
            @if ($bands->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-6 py-4 text-left text-sm font-medium text-gray-700">Band</th>
                                <th class="px-6 py-4 text-center text-sm font-medium text-gray-700">Bühne</th>
                                <th class="px-6 py-4 text-center text-sm font-medium text-gray-700">Spieltage</th>
                                <th class="px-6 py-4 text-center text-sm font-medium text-gray-700">Mitglieder</th>
                                <th class="px-6 py-4 text-center text-sm font-medium text-gray-700">Status</th>
                                <th class="px-6 py-4 text-center text-sm font-medium text-gray-700">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($bands as $band)
                                <tr class="transition-colors duration-150 hover:bg-gray-50"
                                    wire:key="band-{{ $band->id }}-{{ $loop->index }}">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="ml-4">
                                                <div class="font-medium text-gray-900">{{ $band->band_name }}</div>
                                                <div class="text-sm text-gray-500">
                                                    @if ($band->travel_costs)
                                                        • 💰 {{ number_format($band->travel_costs, 2) }}€
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="inline-flex items-center rounded-full bg-purple-100 px-3 py-1 text-sm text-purple-800">
                                            {{ $band->stage->name ?? 'Keine Bühne' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center space-x-1">
                                            @foreach ([1, 2, 3, 4] as $day)
                                                <span
                                                    class="{{ $band->{'plays_day_' . $day} ? 'bg-blue-500 text-white shadow-md' : 'bg-gray-200 text-gray-600' }} flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold transition-colors duration-200"
                                                    title="Tag {{ $day }}: {{ $band->{'plays_day_' . $day} ? 'Spielt' : 'Spielt nicht' }}">
                                                    {{ $day }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button wire:click="showMembers({{ $band->id }})"
                                            wire:loading.attr="disabled" wire:loading.class="opacity-50"
                                            wire:target="showMembers"
                                            class="group inline-flex items-center rounded-lg bg-green-100 px-3 py-2 text-sm font-medium text-green-800 transition-colors duration-200 hover:bg-green-200">
                                            <span wire:loading.remove wire:target="showMembers">
                                                👥 {{ $band->total_members_count ?? $band->members->count() }}
                                                @if (isset($band->present_members_count))
                                                    <span class="ml-1 text-xs text-green-600">
                                                        ({{ $band->present_members_count }} ✅)
                                                    </span>
                                                @endif
                                            </span>
                                            <span wire:loading wire:target="showMembers" class="flex items-center">
                                                <svg class="mr-2 h-4 w-4 animate-spin"
                                                    xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                                        stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                    </path>
                                                </svg>
                                                Lade...
                                            </span>
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="{{ $band->all_present ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} inline-flex items-center rounded-full px-3 py-1 text-xs font-medium">
                                            {{ $band->all_present ? '✅ Komplett' : 'Unvollständig' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center space-x-2">
                                            <button wire:click="showMembers({{ $band->id }})"
                                                wire:loading.attr="disabled" wire:loading.class="opacity-50"
                                                wire:target="showMembers"
                                                class="rounded bg-green-500 px-3 py-1 text-xs text-white transition-colors duration-200 hover:bg-green-600"
                                                title="Mitglieder verwalten">
                                                Mitglieder
                                            </button>
                                            <button wire:click="editBand({{ $band->id }})"
                                                wire:loading.attr="disabled" wire:loading.class="opacity-50"
                                                wire:target="editBand"
                                                class="rounded bg-blue-500 px-3 py-1 text-xs text-white transition-colors duration-200 hover:bg-blue-600"
                                                title="Band bearbeiten">
                                                ✏️
                                            </button>
                                            <button wire:click="deleteBand({{ $band->id }})"
                                                wire:confirm="Band '{{ $band->band_name }}' wirklich löschen? Alle Mitglieder werden ebenfalls gelöscht!"
                                                wire:loading.attr="disabled" wire:loading.class="opacity-50"
                                                wire:target="deleteBand"
                                                class="rounded bg-red-500 px-3 py-1 text-xs text-white transition-colors duration-200 hover:bg-red-600"
                                                title="Band löschen">
                                                🗑️
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">
                    {{ $bands->links() }}
                </div>
            @else
                <div class="p-12 text-center">
                    @if ($search)
                        <div class="mb-4 text-6xl">🔍</div>
                        <h3 class="mb-2 text-xl font-medium text-gray-900">Keine Bands gefunden</h3>
                        <p class="mb-4 text-gray-500">Für "{{ $search }}" wurden keine Bands gefunden.</p>
                        <button wire:click="clearSearch"
                            class="rounded bg-blue-500 px-4 py-2 text-white transition-colors duration-200 hover:bg-blue-600">
                            🔍 Suche zurücksetzen
                        </button>
                    @else
                        <div class="mb-4 text-6xl">🎵</div>
                        <h3 class="mb-2 text-xl font-medium text-gray-900">Noch keine Bands</h3>
                        <p class="mb-6 text-gray-500">Erstellen Sie die erste Band für {{ date('Y') }}.</p>
                        <button wire:click="createBand"
                            class="rounded bg-green-500 px-6 py-3 font-bold text-white transition-colors duration-200 hover:bg-green-700">
                            🎵 Erste Band erstellen
                        </button>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <!-- Optimiertes JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ===== SEARCH FUNCTIONALITY =====
            const searchInput = document.getElementById('search-input');

            if (searchInput) {
                // ESC zum Löschen der Suche
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && this.value.trim() !== '') {
                        e.preventDefault();
                        @this.call('clearSearch');
                        showTemporaryMessage('🔍 Suche mit ESC geleert', 'info');
                    }
                });

                // Auto-Uppercase für Kennzeichen
                const licensePlateInput = document.querySelector('input[wire\\:model="license_plate"]');
                if (licensePlateInput) {
                    licensePlateInput.addEventListener('input', function(e) {
                        e.target.value = e.target.value.toUpperCase();
                    });
                }
            }

            // ===== KEYBOARD SHORTCUTS =====
            document.addEventListener('keydown', function(e) {
                const modal = document.querySelector('.fixed.inset-0.bg-gray-600');
                if (!modal) return;

                // Enter: Speichern/Aktualisieren (außer in Textarea)
                if (e.key === 'Enter' && !e.shiftKey && e.target.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    const saveButtons = [
                        'button[wire\\:click="saveBand"]',
                        'button[wire\\:click="updateBand"]',
                        'button[wire\\:click="saveMember"]',
                        'button[wire\\:click="updateMember"]',
                        'button[wire\\:click="saveVehicle"]',
                        'button[wire\\:click="saveGuest"]'
                    ];

                    for (const selector of saveButtons) {
                        const btn = document.querySelector(selector);
                        if (btn && !btn.disabled) {
                            btn.click();
                            break;
                        }
                    }
                    return;
                }

                // Escape: Modal schließen
                if (e.key === 'Escape') {
                    e.preventDefault();
                    const cancelButtons = [
                        'button[wire\\:click="cancelBandForm"]',
                        'button[wire\\:click="cancelMemberForm"]',
                        'button[wire\\:click="cancelVehicleForm"]',
                        'button[wire\\:click="cancelGuestForm"]'
                    ];

                    for (const selector of cancelButtons) {
                        const btn = document.querySelector(selector);
                        if (btn) {
                            btn.click();
                            break;
                        }
                    }
                }
            });

            // ===== LOADING STATES =====
            window.addEventListener('livewire:request', () => {
                document.body.style.cursor = 'wait';
            });

            window.addEventListener('livewire:response', () => {
                document.body.style.cursor = 'default';
            });

            // ===== FORM ENHANCEMENTS =====

            // Auto-focus für Formulare
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        // Modal wurde geöffnet
                        const modal = document.querySelector('.fixed.inset-0.bg-gray-600');
                        if (modal) {
                            setTimeout(() => {
                                const firstInput = modal.querySelector(
                                    'input[type="text"]:not([readonly]):not([disabled])'
                                );
                                if (firstInput) {
                                    firstInput.focus();
                                }
                            }, 100);
                        }
                    }
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            // ===== TOOLTIPS & HELP =====

            // Tooltip für Spieltage
            document.addEventListener('mouseover', function(e) {
                if (e.target.matches('.rounded-full[title]')) {
                    // Native tooltip ist ausreichend, aber könnte erweitert werden
                }
            });

            // ===== PERFORMANCE MONITORING (Development) =====
            @if (app()->environment('local'))
                let requestCount = 0;
                window.addEventListener('livewire:request', () => {
                    requestCount++;
                    console.log(`🔄 Livewire Request #${requestCount}`);
                });

                window.addEventListener('livewire:response', () => {
                    console.log(`✅ Livewire Response #${requestCount} completed`);
                });
            @endif
        });

        // ===== UTILITY FUNCTIONS =====

        /**
         * Zeigt temporäre Nachrichten mit verschiedenen Typen
         */
        function showTemporaryMessage(message, type = 'info', duration = 2000) {
            const colors = {
                'info': 'bg-blue-100 border-blue-400 text-blue-700',
                'success': 'bg-green-100 border-green-400 text-green-700',
                'warning': 'bg-yellow-100 border-yellow-400 text-yellow-700',
                'error': 'bg-red-100 border-red-400 text-red-700'
            };

            // Vorherige Nachricht entfernen
            const existingMessage = document.getElementById('temp-message');
            if (existingMessage) existingMessage.remove();

            // Neue Nachricht erstellen
            const messageDiv = document.createElement('div');
            messageDiv.id = 'temp-message';
            messageDiv.className =
                `fixed top-4 right-4 ${colors[type]} px-4 py-3 rounded border shadow-lg z-50 opacity-90 transform transition-all duration-300`;
            messageDiv.innerHTML = `
                <div class="flex items-center">
                    <span class="mr-2">${getIconForType(type)}</span>
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(messageDiv);

            // Einblend-Animation
            setTimeout(() => {
                messageDiv.classList.add('translate-x-0');
            }, 10);

            // Automatisches Entfernen
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.style.opacity = '0';
                    messageDiv.style.transform = 'translateX(100%)';
                    setTimeout(() => messageDiv.remove(), 300);
                }
            }, duration);
        }

        /**
         * Icons für verschiedene Nachrichtentypen
         */
        function getIconForType(type) {
            const icons = {
                'info': 'ℹ️',
                'success': '✅',
                'warning': '⚠️',
                'error': '❌'
            };
            return icons[type] || icons['info'];
        }

        /**
         * Debounce-Funktion für Performance
         */
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        /**
         * Copy to Clipboard mit Feedback
         */
        function copyToClipboard(text, successMessage = 'In Zwischenablage kopiert!') {
            navigator.clipboard.writeText(text).then(() => {
                showTemporaryMessage(successMessage, 'success');
            }).catch(() => {
                showTemporaryMessage('Kopieren fehlgeschlagen', 'error');
            });
        }

        // ===== GLOBAL SHORTCUTS =====
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K für Search Focus
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.getElementById('search-input');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }

            // Ctrl/Cmd + N für neue Band (nur auf Hauptseite)
            if ((e.ctrlKey || e.metaKey) && e.key === 'n' && !document.querySelector(
                    '.fixed.inset-0.bg-gray-600')) {
                e.preventDefault();
                const createButton = document.querySelector('button[wire\\:click="createBand"]');
                if (createButton && !createButton.disabled) {
                    createButton.click();
                }
            }
        });

        // ===== ACCESSIBILITY ENHANCEMENTS =====

        // Focus-Management für Modals
        let lastFocusedElement = null;

        document.addEventListener('focusin', function(e) {
            if (!e.target.closest('.fixed.inset-0')) {
                lastFocusedElement = e.target;
            }
        });

        // Modal Focus Trap
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                const modal = document.querySelector('.fixed.inset-0.bg-gray-600');
                if (modal) {
                    const focusableElements = modal.querySelectorAll(
                        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                    );
                    const firstElement = focusableElements[0];
                    const lastElement = focusableElements[focusableElements.length - 1];

                    if (e.shiftKey && document.activeElement === firstElement) {
                        e.preventDefault();
                        lastElement.focus();
                    } else if (!e.shiftKey && document.activeElement === lastElement) {
                        e.preventDefault();
                        firstElement.focus();
                    }
                }
            }
        });

        // ===== LIVEWIRE EVENT LISTENERS =====

        window.addEventListener('livewire:response', () => {
            // Nach Livewire-Update wieder auf letztes Element fokussieren
            if (lastFocusedElement && !document.querySelector('.fixed.inset-0.bg-gray-600')) {
                setTimeout(() => {
                    if (lastFocusedElement && document.contains(lastFocusedElement)) {
                        lastFocusedElement.focus();
                    }
                }, 100);
            }
        });

        // ===== DEVELOPMENT HELPERS =====
        @if (app()->environment('local'))
            // Console Commands für Development
            window.bandManagement = {
                clearCache: () => fetch('/artisan/cache:clear'),
                showStats: () => console.table(@json($statistics ?? [])),
                debugMode: false,
                toggleDebug: function() {
                    this.debugMode = !this.debugMode;
                    console.log('Debug Mode:', this.debugMode ? 'ON' : 'OFF');
                }
            };

            console.log('🎵 Band Management loaded. Try: bandManagement.showStats()');
        @endif
    </script>
    @include('components.vehicle-plates-modal')
</div>
