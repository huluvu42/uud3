{{-- resources/views/components/backstage/modals.blade.php - VOLLSTÄNDIGE KORRIGIERTE VERSION --}}

<!-- Stage Selection Modal -->
@if ($showStageModal)
    <div class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
        <div class="relative top-20 mx-auto w-11/12 rounded-md border bg-white p-5 shadow-lg md:w-1/2">
            <div class="mt-3">
                <h3 class="mb-4 text-lg font-medium">Bühne für {{ $voucherAmount }} Bon auswählen</h3>

                <div class="mb-4 space-y-2">
                    @foreach ($stages as $stage)
                        @php $soldToday = $this->getSoldVouchersForStage($stage->id, $currentDay) @endphp
                        <label
                            class="{{ $purchaseStageId == $stage->id ? 'border-blue-500 bg-blue-50' : '' }} flex cursor-pointer items-center rounded-lg border p-3 hover:bg-gray-50">
                            <input type="radio" wire:model.live="purchaseStageId" value="{{ $stage->id }}"
                                class="mr-3">
                            <div class="flex-1">
                                <div class="font-medium">{{ $stage->name }}</div>
                                <div class="text-sm text-gray-500">
                                    Heute verkauft: {{ $soldToday }}
                                    {{ $settings ? $settings->getVoucherLabel() : 'Bons' }}
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="flex justify-end space-x-2">
                    <button wire:click="cancelStageSelection"
                        class="rounded bg-gray-500 px-4 py-2 font-bold text-white hover:bg-gray-700">
                        Abbrechen
                    </button>
                    <button wire:click="purchaseVouchers"
                        class="rounded bg-green-500 px-4 py-2 font-bold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                        {{ !$purchaseStageId ? 'disabled' : '' }}>
                        {{ $voucherAmount }} Bon kaufen
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Guests Modal -->
@if ($showGuestsModal && $selectedPersonForGuests)
    <div class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
        <div
            class="relative top-4 mx-auto max-h-screen w-11/12 max-w-6xl overflow-y-auto rounded-md border bg-white p-6 shadow-lg">
            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-xl font-semibold text-gray-900">
                    Gäste von {{ $selectedPersonForGuests->full_name }}
                </h3>
                <button wire:click="closeGuestsModal" class="text-gray-400 hover:text-gray-600 focus:outline-none">
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
                </div>
            </div>

            <!-- Kompakte Gäste-Liste -->
            @if ($selectedPersonForGuests->responsibleFor->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Gruppe/Band
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Backstage
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                    {{ $settings ? $settings->getVoucherLabel() : 'Voucher' }}
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Aktionen
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($selectedPersonForGuests->responsibleFor as $guest)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $guest->first_name }} {{ $guest->last_name }}
                                        </div>
                                        <div class="text-sm text-blue-600">
                                            <span
                                                class="inline-flex items-center rounded bg-blue-100 px-2 py-1 text-xs text-blue-800">
                                                👥 Gast
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            // Prüfen ob es ein Gast von einem Bandmitglied ist
                                            $isGuestOfBandMember = $this->isGuestOfBandMember($guest);
                                        @endphp

                                        @if ($isGuestOfBandMember)
                                            {{-- Gast von Bandmitglied - Spalte leer lassen --}}
                                            <span class="text-sm text-gray-400">—</span>
                                        @else
                                            {{-- Gast von Nicht-Bandmitglied - Gruppe des GASTES anzeigen --}}
                                            @if ($guest->group)
                                                <div class="text-sm">
                                                    <span
                                                        class="font-medium text-purple-600">{{ $guest->group->name }}</span>
                                                    <div class="text-xs text-gray-500">Gruppe</div>
                                                </div>
                                            @elseif ($guest->subgroup)
                                                <div class="text-sm">
                                                    <span
                                                        class="font-medium text-indigo-600">{{ $guest->subgroup->name }}</span>
                                                    <div class="text-xs text-gray-500">Subgruppe</div>
                                                </div>
                                            @else
                                                <div class="text-sm">
                                                    <span class="font-medium text-gray-600">Einzelgast</span>
                                                    <div class="text-xs text-gray-500">Ohne Gruppe</div>
                                                </div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            // KORRIGIERTE LOGIK: Verwendet nur noch getGuestStatusForToday
                                            $guestStatus = $this->getGuestStatusForToday($guest);
                                        @endphp

                                        @if ($guestStatus['can_toggle'])
                                            <button wire:click="toggleGuestPresence({{ $guest->id }})"
                                                class="{{ $guest->present ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }} inline-flex items-center rounded-full px-3 py-1 text-xs font-medium transition-colors duration-200"
                                                title="Klicken um Status zu ändern">
                                                {{ $guest->present ? '✓ Anwesend' : '✗ Abwesend' }}
                                            </button>
                                        @else
                                            <span
                                                class="inline-flex cursor-not-allowed items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-500"
                                                title="{{ $guestStatus['reason'] }}">
                                                🚫 {{ $guestStatus['reason'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex space-x-1">
                                            @for ($day = 1; $day <= 4; $day++)
                                                <span
                                                    class="{{ $guest->{"backstage_day_$day"} ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400' }} flex h-4 w-4 items-center justify-center rounded-full text-xs">
                                                    {{ $day }}
                                                </span>
                                            @endfor
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm">
                                            @php
                                                $nextAvailableDay = $this->getNextAvailableVoucherDay($guest);
                                            @endphp
                                            @if ($nextAvailableDay)
                                                <span
                                                    class="font-medium text-blue-600">{{ $guest->{"voucher_day_$nextAvailableDay"} }}</span>
                                                <span
                                                    class="text-gray-500">/{{ $guest->{"voucher_issued_day_$currentDay"} }}</span>
                                            @else
                                                <span class="text-gray-400">Keine</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex space-x-2">
                                            @php
                                                $nextAvailableDay = $this->getNextAvailableVoucherDay($guest);
                                                $isSingleMode = $settings && $settings->isSingleVoucherMode();
                                                $voucherLabel = $settings ? $settings->getVoucherLabel() : 'Voucher';
                                            @endphp

                                            @if ($nextAvailableDay)
                                                <button
                                                    wire:click="issueVouchers({{ $guest->id }}, {{ $nextAvailableDay }})"
                                                    class="rounded bg-blue-500 px-2 py-1 text-xs text-white hover:bg-blue-600"
                                                    title="{{ $isSingleMode ? '1' : 'Alle verfügbaren' }} {{ $voucherLabel }} ausgeben">
                                                    👥 {{ $isSingleMode ? '1' : 'Alle' }}
                                                </button>
                                            @endif

                                            <button wire:click="selectPersonForDetails({{ $guest->id }})"
                                                class="rounded bg-gray-500 px-2 py-1 text-xs text-white hover:bg-gray-600">
                                                Details
                                            </button>

                                            {{-- LÖSCHEN-BUTTON NUR BEI GÄSTEN VON BANDMITGLIEDERN --}}
                                            @if ($this->isGuestOfBandMember($guest))
                                                <button wire:click="deleteGuestWithConfirmation({{ $guest->id }})"
                                                    class="rounded bg-red-500 px-2 py-1 text-xs text-white hover:bg-red-600"
                                                    title="Gast löschen">
                                                    ✕
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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

<!-- Vehicle Plates Modal -->
@include('components.vehicle-plates-modal')

<!-- Guest Create Modal -->
@include('components.guest-create-modal', [
    'show' => $showGuestCreateModal,
    'selectedMember' => $selectedMemberForGuest,
    'settings' => $settings,
])

<!-- Guest Delete Confirmation Modal -->
@include('components.guest-delete-modal', [
    'show' => $showGuestDeleteModal,
    'guest' => $guestToDelete,
])

<!-- Person Details Modal -->
@include('components.person-details-modal', [
    'show' => $showPersonDetailsModal,
    'person' => $selectedPersonForDetails,
    'settings' => $settings,
    'currentDay' => $currentDay,
])
