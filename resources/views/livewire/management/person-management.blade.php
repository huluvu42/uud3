{{-- resources/views/livewire/management/person-management.blade.php --}}

<div class="container mx-auto px-4 py-8">
    @include('partials.navigation')

    <div class="mx-auto mt-6 max-w-7xl">
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

        <!-- Header-Bereich -->
        <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
            <!-- Drei-Spalten Layout -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                <!-- Personensuche -->
                <div>
                    <h3 class="mb-4 text-lg font-semibold">Personensuche</h3>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Vorname oder Nachname..."
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Filter -->
                <div>
                    <h3 class="mb-4 text-lg font-semibold">Filter & Optionen</h3>
                    <div class="space-y-2">
                        <select wire:model.live="filterType"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all">Alle Personen</option>
                            <option value="groups">Nur Gruppen-Mitglieder</option>
                            @if ($showBandMembers)
                                <option value="bands">Nur Band-Mitglieder</option>
                            @endif
                            <option value="guests">Nur Gäste</option>
                        </select>

                        <label class="flex items-center space-x-2">
                            <input type="checkbox" wire:model.live="showBandMembers"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Bandmitglieder anzeigen</span>
                        </label>
                    </div>
                </div>

                <!-- Aktionen -->
                <div>
                    <h3 class="mb-4 text-lg font-semibold">Aktionen</h3>
                    <button wire:click="createPerson"
                        class="w-full rounded bg-green-500 px-4 py-2 text-white hover:bg-green-600">
                        Person hinzufügen
                    </button>
                </div>
            </div>
        </div>

        <!-- Personenliste -->
        @if ($persons && count($persons) > 0)
            <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
                <h3 class="mb-4 text-lg font-semibold">Personen ({{ count($persons) }} gefunden)</h3>
                <div class="space-y-3" style="max-height: calc(100vh - 400px); overflow-y: auto; min-height: 400px;">
                    @foreach ($persons as $person)
                        <div class="{{ $person->isGuest() ? 'bg-blue-50 border-blue-200' : '' }} rounded-lg border border-gray-200 p-4 hover:bg-gray-50"
                            wire:key="person-{{ $person->id }}-{{ $loop->index }}">

                            <div class="grid grid-cols-1 gap-4 xl:grid-cols-7">

                                <!-- Person Info -->
                                <div class="xl:col-span-1">
                                    <div class="mb-1 text-lg font-medium">
                                        {{ $person->first_name }} {{ $person->last_name }}
                                    </div>
                                    <!-- Badges in separater Zeile -->
                                    <div class="mb-2 flex flex-wrap gap-1">
                                        @if ($person->isGuest())
                                            <span
                                                class="rounded bg-blue-100 px-2 py-1 text-xs text-blue-800">Gast</span>
                                        @endif
                                        @if ($person->can_have_guests)
                                            <span class="rounded bg-purple-100 px-2 py-1 text-xs text-purple-800">kann
                                                Gäste haben</span>
                                        @endif
                                    </div>
                                    @if ($person->group)
                                        <div class="mb-1 text-sm text-purple-600">📁 {{ $person->group->name }}</div>
                                        @if ($person->subgroup)
                                            <div class="text-xs text-purple-500">{{ $person->subgroup->name }}</div>
                                        @endif
                                    @elseif($person->band)
                                        <div class="mb-1 text-sm text-blue-600">🎵 {{ $person->band->band_name }}</div>
                                    @endif
                                    @if ($person->isGuest() && $person->responsiblePerson)
                                        <div class="text-xs text-blue-600">Verantwortlich:
                                            {{ $person->responsiblePerson->full_name }}</div>
                                    @endif
                                    @if ($person->remarks)
                                        <div class="mt-1 text-xs text-blue-600">
                                            <span class="rounded bg-blue-50 px-2 py-1 text-blue-800">
                                                {{ Str::limit($person->remarks, 20) }}
                                            </span>
                                        </div>
                                    @endif

                                    {{-- Kennzeichen anzeigen --}}
                                    @if ($person->hasVehiclePlates())
                                        <div class="mt-1 text-xs text-green-600">
                                            <span class="rounded bg-green-50 px-2 py-1 text-green-800"
                                                title="{{ $person->vehiclePlatesString }}">
                                                🚗 {{ $person->vehiclePlates->count() }} Kennzeichen
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Bändchen -->
                                <div class="xl:col-span-1">
                                    <div class="mb-2 text-sm font-medium text-gray-700">Bändchen</div>
                                    <div class="flex justify-center">
                                        @php $wristbandColor = $this->getWristbandColorForPerson($person) @endphp
                                        @if ($wristbandColor && $this->hasAnyBackstageAccess($person))
                                            <div class="h-8 w-8 rounded border-2 border-gray-300 shadow-sm"
                                                style="background-color: {{ $wristbandColor }}"
                                                title="Bändchenfarbe: {{ $wristbandColor }}"></div>
                                        @else
                                            <div
                                                class="flex h-8 w-8 items-center justify-center rounded border-2 border-gray-300 bg-gray-100">
                                                <span class="text-xs text-gray-400">✗</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Backstage-Berechtigung -->
                                <div class="xl:col-span-1">
                                    <div class="mb-2 text-sm font-medium text-gray-700">
                                        {{ $settings ? $settings->getBackstageLabel() : 'Backstage' }}</div>
                                    <div class="flex space-x-1">
                                        @for ($day = 1; $day <= 4; $day++)
                                            <div class="text-center">
                                                <div class="mb-1 text-xs text-gray-500">
                                                    {{ $settings ? $settings->getDayLabel($day) : "T$day" }}</div>
                                                @if ($person->{"backstage_day_$day"})
                                                    <div
                                                        class="flex h-5 w-5 items-center justify-center rounded-full bg-green-100">
                                                        <span class="text-xs font-bold text-green-600">✓</span>
                                                    </div>
                                                @else
                                                    <div
                                                        class="flex h-5 w-5 items-center justify-center rounded-full bg-red-100">
                                                        <span class="text-xs font-bold text-red-600">✗</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endfor
                                    </div>
                                </div>

                                <!-- Voucher-Übersicht -->
                                <div class="xl:col-span-1">
                                    <div class="mb-2 text-sm font-medium text-gray-700">
                                        {{ $settings ? $settings->getVoucherLabel() : 'Voucher' }}</div>
                                    <div class="flex space-x-1">
                                        @for ($day = 1; $day <= 4; $day++)
                                            <div class="text-center">
                                                <div class="mb-1 text-xs text-gray-500">
                                                    {{ $settings ? $settings->getDayLabel($day) : "T$day" }}</div>
                                                <div class="rounded border bg-gray-50 px-1 py-1 text-xs">
                                                    <div class="font-medium text-blue-600">
                                                        {{ $person->{"voucher_day_$day"} }}</div>
                                                </div>
                                            </div>
                                        @endfor
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="xl:col-span-1">
                                    <div class="mb-2 text-sm font-medium text-gray-700">Status</div>
                                    <div
                                        class="{{ $person->present ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} w-full rounded px-3 py-2 text-center text-sm font-medium">
                                        {{ $person->present ? 'Anwesend' : 'Abwesend' }}
                                    </div>
                                </div>

                                <!-- Verantwortlich für -->
                                <div class="xl:col-span-1">
                                    <div class="mb-2 text-sm font-medium text-gray-700">Verantwortlich</div>
                                    <div class="text-xs">
                                        @if ($person->responsibleFor->count() > 0)
                                            <button wire:click="showGuests({{ $person->id }})"
                                                class="inline-flex items-center rounded-lg bg-purple-600 px-3 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-purple-700 focus:ring-4 focus:ring-purple-300"
                                                title="Gäste anzeigen">
                                                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M17 20h5v-2a3 3 0 00-5.196-2.196M17 20H7m10 0v-2c0-5.523-4.477-10-10-10S-3 12.477-3 18v2m20 0H7m0 0H2v-2a3 3 0 015.196-2.196M7 20v-2m5-10a3 3 0 100-6 3 3 0 000 6z" />
                                                </svg>
                                                {{ $person->responsibleFor->count() }}
                                                {{ $person->responsibleFor->count() === 1 ? 'Gast' : 'Gäste' }}
                                            </button>
                                        @else
                                            <span
                                                class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-2 text-sm text-gray-500">
                                                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728" />
                                                </svg>
                                                Keine Gäste
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Aktionen -->
                                <div class="xl:col-span-1">
                                    <div class="mb-2 text-sm font-medium text-gray-700">Aktionen</div>
                                    <div class="space-y-2">
                                        <button wire:click="editPerson({{ $person->id }})"
                                            class="w-full rounded bg-blue-500 px-3 py-1 text-xs text-white hover:bg-blue-600"
                                            wire:key="edit-btn-{{ $person->id }}-{{ $loop->index }}">
                                            Bearbeiten
                                        </button>

                                        <button wire:click="deletePerson({{ $person->id }})"
                                            wire:confirm="Person '{{ $person->full_name }}' wirklich löschen?"
                                            class="w-full rounded bg-red-500 px-3 py-1 text-xs text-white hover:bg-red-600"
                                            wire:key="delete-btn-{{ $person->id }}-{{ $loop->index }}">
                                            Löschen
                                        </button>

                                        {{-- Kennzeichen Button --}}
                                        <button wire:click="showVehiclePlates({{ $person->id }})"
                                            class="{{ $person->hasVehiclePlates() ? 'bg-green-500' : 'bg-gray-500' }} w-full rounded px-3 py-1 text-xs text-white hover:opacity-80"
                                            title="Kennzeichen verwalten">
                                            🚗
                                            {{ $person->vehiclePlates->count() > 0 ? $person->vehiclePlates->count() : '' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if ($persons instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                    <div class="mt-4">
                        {{ $persons->links() }}
                    </div>
                @endif
            </div>
        @elseif(!$search)
            <!-- Welcome Message -->
            <div class="rounded-lg bg-white p-8 text-center shadow-md">
                <h3 class="mb-4 text-xl font-semibold text-gray-600">Personen-Verwaltung</h3>
                <p class="mb-6 text-gray-500">Verwalten Sie hier alle Personen, Gruppen und Bandmitglieder.</p>
                <div class="grid grid-cols-1 gap-4 text-sm text-gray-400 md:grid-cols-3">
                    <div class="rounded bg-gray-50 p-4">
                        <h4 class="mb-2 font-medium text-gray-600">👥 Personen verwalten</h4>
                        <p>Erstellen, bearbeiten und organisieren Sie alle Personen</p>
                    </div>
                    <div class="rounded bg-gray-50 p-4">
                        <h4 class="mb-2 font-medium text-gray-600">🎫 Voucher & Backstage</h4>
                        <p>Verwalten Sie Berechtigungen und Voucher für alle Tage</p>
                    </div>
                    <div class="rounded bg-gray-50 p-4">
                        <h4 class="mb-2 font-medium text-gray-600">🎵 Gruppen & Bands</h4>
                        <p>Zuordnung zu Gruppen, Bands und verantwortlichen Personen</p>
                    </div>
                </div>
            </div>
        @else
            <!-- No Search Results -->
            <div class="rounded-lg bg-white p-8 text-center shadow-md">
                <h3 class="mb-4 text-xl font-semibold text-gray-600">Keine Personen gefunden</h3>
                <p class="text-gray-500">Für "{{ $search }}" wurden keine Personen gefunden.</p>
            </div>
        @endif
    </div>

    <!-- Person erstellen/bearbeiten Modal -->
    @if ($showCreateForm || $showEditForm)
        <div class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
            <div
                class="relative top-10 mx-auto max-h-screen w-11/12 w-11/12 max-w-6xl overflow-y-auto rounded-md border bg-white p-5 shadow-lg md:w-3/4 lg:w-2/3">
                <div class="mt-3">
                    <h2 class="mb-6 text-xl font-semibold">
                        {{ $showCreateForm ? 'Neue Person hinzufügen' : 'Person bearbeiten' }}
                    </h2>
                    <!-- NEU: Keyboard Shortcuts Hint -->
                    <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-3">
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">⌨️ Tastaturkürzel:</span>
                            <span class="ml-2">
                                @if ($showCreateForm)
                                    <kbd class="rounded border bg-white px-2 py-1 text-xs">Shift + Enter</kbd> =
                                    Speichern & Weiter
                                    <span class="mx-2">•</span>
                                @endif
                                <kbd class="rounded border bg-white px-2 py-1 text-xs">Enter</kbd> =
                                {{ $showCreateForm ? 'Speichern' : 'Aktualisieren' }}
                                <span class="mx-2">•</span>
                                <kbd class="rounded border bg-white px-2 py-1 text-xs">Esc</kbd> = Abbrechen
                            </span>
                        </div>
                    </div>

                    <!-- Grunddaten -->
                    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Vorname *</label>
                            <input type="text" wire:model="first_name"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('first_name')
                                <span class="text-sm text-red-500">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Nachname *</label>
                            <input type="text" wire:model="last_name"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('last_name')
                                <span class="text-sm text-red-500">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Zuordnung -->
                    <div class="mb-6">
                        <h3 class="mb-3 text-lg font-medium">Zuordnung</h3>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Gruppe</label>
                                <select wire:model.live="group_id"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Keine Gruppe</option>
                                    @foreach ($groups as $group)
                                        <option value="{{ $group->id }}"
                                            data-voucher-1="{{ $group->voucher_day_1 ?? 0 }}"
                                            data-voucher-2="{{ $group->voucher_day_2 ?? 0 }}"
                                            data-voucher-3="{{ $group->voucher_day_3 ?? 0 }}"
                                            data-voucher-4="{{ $group->voucher_day_4 ?? 0 }}"
                                            data-backstage-1="{{ $group->backstage_day_1 ? 1 : 0 }}"
                                            data-backstage-2="{{ $group->backstage_day_2 ? 1 : 0 }}"
                                            data-backstage-3="{{ $group->backstage_day_3 ? 1 : 0 }}"
                                            data-backstage-4="{{ $group->backstage_day_4 ? 1 : 0 }}"
                                            data-can-have-guests="{{ $group->can_have_guests ? 1 : 0 }}">
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('group_id')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Untergruppe</label>
                                <select wire:model="subgroup_id"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    {{ !$group_id ? 'disabled' : '' }}>
                                    <option value="">Keine Untergruppe</option>
                                    @foreach ($this->subgroups as $subgroup)
                                        <option value="{{ $subgroup->id }}">{{ $subgroup->name }}</option>
                                    @endforeach
                                </select>
                                @error('subgroup_id')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Band</label>
                                <select wire:model.live="band_id"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Keine Band</option>
                                    @foreach ($bands as $band)
                                        <option value="{{ $band->id }}">{{ $band->band_name }}</option>
                                    @endforeach
                                </select>
                                @error('band_id')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Verantwortliche Person -->
                        <div class="mt-4">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Verantwortliche Person (für
                                Gäste)</label>
                            <select wire:model="responsible_person_id"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Keine verantwortliche Person</option>
                                @foreach ($responsiblePersons as $person)
                                    <option value="{{ $person->id }}">{{ $person->full_name }}</option>
                                @endforeach
                            </select>
                            @error('responsible_person_id')
                                <span class="text-sm text-red-500">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="mb-6">
                        <label
                            class="{{ $present ? 'border-green-500 bg-green-50' : '' }} flex cursor-pointer items-center rounded-lg border p-3 hover:bg-gray-50">
                            <input type="checkbox" wire:model="present"
                                class="mr-3 rounded border-gray-300 text-green-600 shadow-sm focus:border-green-300 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                            <div>
                                <div class="font-medium">Anwesend</div>
                                <div class="text-sm text-gray-500">Person ist derzeit beim Festival anwesend</div>
                            </div>
                        </label>

                        <!-- NEU: Kann Gäste haben -->
                        <label
                            class="{{ $can_have_guests ? 'border-purple-500 bg-purple-50' : '' }} flex cursor-pointer items-center rounded-lg border p-3 hover:bg-gray-50">
                            <input type="checkbox" wire:model="can_have_guests"
                                class="mr-3 rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200 focus:ring-opacity-50">
                            <div>
                                <div class="font-medium">Kann Gäste haben</div>
                                <div class="text-sm text-gray-500">Person darf als verantwortliche Person für Gäste
                                    eingetragen werden</div>
                            </div>
                        </label>
                    </div>

                    <!-- Backstage-Zugang -->
                    <div class="mb-6">
                        <h3 class="mb-3 text-lg font-medium">
                            {{ $settings ? $settings->getBackstageLabel() : 'Backstage-Zugang' }}</h3>
                        <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                            @foreach ([1, 2, 3, 4] as $day)
                                <label
                                    class="{{ $this->{'backstage_day_' . $day} ? 'border-blue-500 bg-blue-50' : '' }} flex cursor-pointer items-center rounded-lg border p-3 hover:bg-gray-50">
                                    <input type="checkbox" wire:model="backstage_day_{{ $day }}"
                                        class="mr-2 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span
                                        class="text-sm font-medium">{{ $settings ? $settings->getDayLabel($day) : "Tag $day" }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Gutscheine -->
                    <div class="mb-6">
                        <h3 class="mb-3 text-lg font-medium">
                            {{ $settings ? $settings->getVoucherLabel() : 'Gutscheine' }}</h3>
                        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                            @foreach ([1, 2, 3, 4] as $day)
                                <div>
                                    <label
                                        class="mb-1 block text-sm font-medium text-gray-700">{{ $settings ? $settings->getDayLabel($day) : "Tag $day" }}</label>
                                    <input type="number" wire:model="voucher_day_{{ $day }}"
                                        step="0.1" min="0" max="999.9"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="0.0">
                                    @error('voucher_day_' . $day)
                                        <span class="text-sm text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Bemerkungen -->
                    <div class="mb-6">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Bemerkungen</label>
                        <textarea wire:model="remarks" rows="3"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Optionale Bemerkungen zur Person..."></textarea>
                        @error('remarks')
                            <span class="text-sm text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Buttons -->
                    <div class="mt-6 flex justify-end space-x-4 border-t border-gray-200 pt-4">
                        <button wire:click="cancelPersonForm"
                            class="rounded border border-gray-300 px-4 py-2 text-gray-600 hover:bg-gray-50">
                            Abbrechen
                        </button>
                        @if ($showCreateForm)
                            <button wire:click="savePerson(true)"
                                class="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600">
                                Speichern & Weiter
                            </button>
                        @endif
                        <button wire:click="{{ $showCreateForm ? 'savePerson(false)' : 'updatePerson' }}"
                            class="rounded bg-green-500 px-4 py-2 text-white hover:bg-green-600"
                            wire:loading.attr="disabled" wire:loading.class="opacity-50">
                            <span wire:loading.remove">{{ $showCreateForm ? 'Speichern' : 'Aktualisieren' }}</span>
                            <span wire:loading>{{ $showCreateForm ? 'Speichern...' : 'Aktualisieren...' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <!-- Gäste Modal -->
    @if ($showGuestsModal && $selectedPersonForGuests)
        <div class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
            <div
                class="relative top-4 mx-auto max-h-screen w-11/12 max-w-7xl overflow-y-auto rounded-md border bg-white p-6 shadow-lg">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-xl font-semibold text-gray-900">
                        Gäste von {{ $selectedPersonForGuests->full_name }}
                    </h3>
                    <button wire:click="closeGuestsModal"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Flash Messages im Modal -->
                @if (session()->has('success'))
                    <div class="mb-4 rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Gäste-Übersicht -->
                <div class="mb-4 rounded-lg border border-purple-200 bg-purple-50 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-purple-900">
                                {{ $selectedPersonForGuests->responsibleFor->count() }}
                                {{ $selectedPersonForGuests->responsibleFor->count() === 1 ? 'Gast' : 'Gäste' }}
                                insgesamt
                            </h4>
                            <p class="text-sm text-purple-700">
                                Verantwortliche Person: {{ $selectedPersonForGuests->full_name }}
                                @if ($selectedPersonForGuests->group)
                                    ({{ $selectedPersonForGuests->group->name }})
                                @elseif($selectedPersonForGuests->band)
                                    ({{ $selectedPersonForGuests->band->band_name }})
                                @endif
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-purple-600">
                                @if ($selectedPersonForGuests->can_have_guests)
                                    <span
                                        class="inline-flex items-center rounded bg-purple-100 px-2 py-1 text-xs text-purple-800">
                                        ✓ Darf Gäste haben
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gäste-Liste -->
                @if ($selectedPersonForGuests->responsibleFor->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Name</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Status</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Backstage</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Voucher</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Bemerkungen</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Aktionen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($selectedPersonForGuests->responsibleFor as $guest)
                                    <tr class="hover:bg-gray-50">
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="h-8 w-8 flex-shrink-0">
                                                    <div
                                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">
                                                        <svg class="h-4 w-4 text-blue-600" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $guest->first_name }} {{ $guest->last_name }}
                                                    </div>
                                                    <div class="text-sm text-blue-600">
                                                        <span
                                                            class="inline-flex items-center rounded bg-blue-100 px-2 py-1 text-xs text-blue-800">
                                                            👥 Gast
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <button wire:click="toggleGuestPresence({{ $guest->id }})"
                                                class="{{ $guest->present ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }} inline-flex items-center rounded-full px-3 py-1 text-xs font-medium transition-colors duration-200"
                                                title="Klicken um Status zu ändern">
                                                {{ $guest->present ? '✓ Anwesend' : '✗ Abwesend' }}
                                            </button>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="flex space-x-1">
                                                @for ($day = 1; $day <= 4; $day++)
                                                    <span
                                                        class="{{ $guest->{"backstage_day_$day"} ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400' }} flex h-5 w-5 items-center justify-center rounded-full text-xs">
                                                        {{ $day }}
                                                    </span>
                                                @endfor
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                @php
                                                    $totalVouchers =
                                                        $guest->voucher_day_1 +
                                                        $guest->voucher_day_2 +
                                                        $guest->voucher_day_3 +
                                                        $guest->voucher_day_4;
                                                @endphp
                                                @if ($totalVouchers > 0)
                                                    <span class="font-medium">{{ $totalVouchers }}€</span>
                                                    <div class="text-xs text-gray-500">
                                                        {{ $guest->voucher_day_1 }}/{{ $guest->voucher_day_2 }}/{{ $guest->voucher_day_3 }}/{{ $guest->voucher_day_4 }}
                                                    </div>
                                                @else
                                                    <span class="text-gray-400">Keine Voucher</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($guest->remarks)
                                                <div class="max-w-xs truncate text-sm text-gray-900"
                                                    title="{{ $guest->remarks }}">
                                                    {{ $guest->remarks }}
                                                </div>
                                            @else
                                                <span class="text-sm text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                                            <button wire:click="editPerson({{ $guest->id }})"
                                                class="font-medium text-blue-600 hover:text-blue-800">
                                                Bearbeiten
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.196-2.196M17 20H7m10 0v-2c0-5.523-4.477-10-10-10S-3 12.477-3 18v2m20 0H7m0 0H2v-2a3 3 0 015.196-2.196M7 20v-2m5-10a3 3 0 100-6 3 3 0 000 6z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Gäste</h3>
                        <p class="mt-1 text-sm text-gray-500">Diese Person hat noch keine Gäste zugeordnet.</p>
                    </div>
                @endif

                <!-- Modal Footer -->
                <div class="mt-6 flex justify-end">
                    <button wire:click="closeGuestsModal"
                        class="rounded bg-gray-500 px-4 py-2 text-white hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Schließen
                    </button>
                </div>
            </div>
        </div>
    @endif
    @include('components.vehicle-plates-modal')
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Event Listener für Gruppen-Auswahl
        document.addEventListener('change', function(e) {
            if (e.target.matches('select[wire\\:model\\.live="group_id"]')) {
                const selectedOption = e.target.selectedOptions[0];

                if (selectedOption && selectedOption.value) {
                    // Voucher-Werte setzen
                    for (let day = 1; day <= 4; day++) {
                        const voucherInput = document.querySelector(
                            `input[wire\\:model="voucher_day_${day}"]`);
                        const voucherValue = selectedOption.getAttribute(`data-voucher-${day}`);

                        if (voucherInput && voucherValue) {
                            voucherInput.value = voucherValue > 0 ? voucherValue : '';
                            // Livewire Event triggern
                            voucherInput.dispatchEvent(new Event('input'));
                        }
                    }

                    // Backstage-Checkboxen setzen
                    for (let day = 1; day <= 4; day++) {
                        const backstageCheckbox = document.querySelector(
                            `input[wire\\:model="backstage_day_${day}"]`);
                        const backstageValue = selectedOption.getAttribute(`data-backstage-${day}`);

                        if (backstageCheckbox && backstageValue) {
                            backstageCheckbox.checked = backstageValue === '1';
                            // Livewire Event triggern
                            backstageCheckbox.dispatchEvent(new Event('change'));
                        }
                    }

                    // NEU: "Kann Gäste haben" Checkbox setzen
                    const canHaveGuestsCheckbox = document.querySelector(
                        'input[wire\\:model="can_have_guests"]');
                    const canHaveGuestsValue = selectedOption.getAttribute('data-can-have-guests');

                    if (canHaveGuestsCheckbox && canHaveGuestsValue) {
                        canHaveGuestsCheckbox.checked = canHaveGuestsValue === '1';
                        canHaveGuestsCheckbox.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    }

                    // Kurze Bestätigung anzeigen
                    showGroupLoadedMessage(selectedOption.textContent);
                } else {
                    // Felder zurücksetzen wenn "Keine Gruppe" gewählt
                    resetGroupValues();
                }
            }
        });

        // NEU: Event Listener für das Leeren der Name-Felder
        window.addEventListener('clearNameFields', function(e) {
            clearNameFields();
        });

        // NEU: Keyboard Shortcuts für Modal
        document.addEventListener('keydown', function(e) {
            // Nur aktiv wenn Modal geöffnet ist
            const modal = document.querySelector('.fixed.inset-0.bg-gray-600');
            if (!modal) return;

            // Shift + Enter: Speichern & Weiter (nur im Create-Modus)
            if (e.shiftKey && e.key === 'Enter') {
                e.preventDefault();
                const saveAndContinueBtn = document.querySelector(
                    'button[wire\\:click="savePerson(true)"]');
                if (saveAndContinueBtn && !saveAndContinueBtn.disabled) {
                    saveAndContinueBtn.click();
                    showKeyboardShortcutMessage('Shift + Enter: Speichern & Weiter');
                }
                return;
            }

            // Enter (ohne Shift): Speichern/Aktualisieren
            if (e.key === 'Enter' && !e.shiftKey) {
                // Nicht triggern wenn in textarea
                if (e.target.tagName === 'TEXTAREA') return;

                e.preventDefault();
                const saveBtn = document.querySelector(
                    'button[wire\\:click="savePerson(false)"], button[wire\\:click="updatePerson"]');
                if (saveBtn && !saveBtn.disabled) {
                    saveBtn.click();
                    showKeyboardShortcutMessage('Enter: Gespeichert');
                }
                return;
            }

            // Escape: Modal schließen
            if (e.key === 'Escape') {
                e.preventDefault();
                const cancelBtn = document.querySelector('button[wire\\:click="cancelPersonForm"]');
                if (cancelBtn) {
                    cancelBtn.click();
                    showKeyboardShortcutMessage('Escape: Abgebrochen');
                }
                return;
            }
        });
    });

    // NEU: Funktion zum Anzeigen von Keyboard Shortcut Feedback
    function showKeyboardShortcutMessage(message) {
        const existingMessage = document.getElementById('keyboard-shortcut-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        const messageDiv = document.createElement('div');
        messageDiv.id = 'keyboard-shortcut-message';
        messageDiv.className = 'fixed top-4 left-4 bg-gray-800 text-white px-4 py-2 rounded shadow-lg z-50 opacity-90';
        messageDiv.innerHTML = `⌨️ ${message}`;

        document.body.appendChild(messageDiv);

        // Nach 2 Sekunden entfernen
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, 2000);
    }

    // NEU: Funktion zum Leeren der Name-Felder
    function clearNameFields() {
        const firstNameInput = document.querySelector('input[wire\\:model="first_name"]');
        const lastNameInput = document.querySelector('input[wire\\:model="last_name"]');
        const presentCheckbox = document.querySelector('input[wire\\:model="present"]');

        if (firstNameInput) {
            firstNameInput.value = '';
            firstNameInput.dispatchEvent(new Event('input', {
                bubbles: true
            }));
        }

        if (lastNameInput) {
            lastNameInput.value = '';
            lastNameInput.dispatchEvent(new Event('input', {
                bubbles: true
            }));
        }

        if (presentCheckbox) {
            presentCheckbox.checked = false;
            presentCheckbox.dispatchEvent(new Event('change', {
                bubbles: true
            }));
        }

        // Fokus auf Vorname-Feld setzen für bessere UX
        if (firstNameInput) {
            setTimeout(() => firstNameInput.focus(), 100);
        }
    }

    function showGroupLoadedMessage(groupName) {
        // Temporäre Bestätigung anzeigen
        const existingMessage = document.getElementById('group-loaded-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        const message = document.createElement('div');
        message.id = 'group-loaded-message';
        message.className =
            'fixed top-4 right-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-2 rounded shadow-lg z-50';
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

        // NEU: "Kann Gäste haben" zurücksetzen
        const canHaveGuestsCheckbox = document.querySelector('input[wire\\:model="can_have_guests"]');
        if (canHaveGuestsCheckbox) {
            canHaveGuestsCheckbox.checked = false;
            canHaveGuestsCheckbox.dispatchEvent(new Event('change', {
                bubbles: true
            }));
        }
    }

    // Livewire Hook für nachgeladene Inhalte
    document.addEventListener('livewire:navigated', function() {
        // Event Listeners neu initialisieren falls nötig
    });
</script>
