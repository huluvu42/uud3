{{-- resources/views/components/person-details-modal.blade.php --}}

@props(['show' => false, 'person' => null, 'settings' => null, 'currentDay' => 1])

@if ($show && $person)
    <div class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
        <div class="relative top-4 mx-auto mb-4 w-11/12 max-w-4xl rounded-md border bg-white shadow-lg">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-gray-200 p-6">
                <div class="flex items-center space-x-3">
                    <h3 class="text-xl font-bold text-gray-900">
                        {{ $person->full_name }}
                    </h3>
                    <div class="flex space-x-2">
                        @if ($person->isGuest())
                            <span class="rounded bg-blue-100 px-2 py-1 text-sm text-blue-800">👥 Gast</span>
                        @endif
                        @if ($person->can_have_guests)
                            <span class="rounded bg-purple-100 px-2 py-1 text-sm text-purple-800">Host</span>
                        @endif
                    </div>
                </div>
                <button wire:click="closePersonDetailsModal" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="max-h-[calc(100vh-200px)] overflow-y-auto p-6">

                <!-- Person Info Section -->
                <div class="mb-6 rounded-lg bg-gray-50 p-4">
                    <h4 class="mb-3 text-lg font-semibold text-gray-900">📋 Allgemeine Informationen</h4>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <div class="mb-2">
                                <span class="text-sm font-medium text-gray-700">Name:</span>
                                <span class="ml-2 text-sm text-gray-900">{{ $person->full_name }}</span>
                            </div>

                            @if ($person->band)
                                <div class="mb-2">
                                    <span class="text-sm font-medium text-gray-700">🎵 Band:</span>
                                    <span class="ml-2 text-sm text-gray-900">{{ $person->band->band_name }}</span>
                                </div>
                            @endif

                            @if ($person->group)
                                <div class="mb-2">
                                    <span class="text-sm font-medium text-gray-700">👥 Gruppe:</span>
                                    <span class="ml-2 text-sm text-gray-900">{{ $person->group->name }}</span>
                                </div>
                            @endif
                        </div>

                        <div>
                            <!-- Gast-Beziehungen -->
                            @if ($person->isGuest() && $person->responsiblePerson)
                                <div class="mb-2">
                                    <span class="text-sm font-medium text-gray-700">👤 Verantwortlich:</span>
                                    <span
                                        class="ml-2 text-sm text-blue-600">{{ $person->responsiblePerson->full_name }}</span>
                                </div>
                            @endif

                            @if ($person->can_have_guests && $person->responsibleFor->count() > 0)
                                <div class="mb-2">
                                    <span class="text-sm font-medium text-gray-700">👥 Gäste:</span>
                                    <span class="ml-2 text-sm text-purple-600">
                                        {{ $person->responsibleFor->count() }}
                                        {{ $person->responsibleFor->count() === 1 ? 'Gast' : 'Gäste' }}
                                    </span>
                                </div>
                            @endif

                            <!-- Anwesenheitsstatus -->
                            <div class="mb-2">
                                <span class="text-sm font-medium text-gray-700">Status heute:</span>
                                @php
                                    $hasBackstageToday = $person->{"backstage_day_{$currentDay}"};
                                @endphp
                                @if ($hasBackstageToday)
                                    <span
                                        class="{{ $person->present ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} ml-2 inline-flex items-center rounded-full px-2 py-1 text-xs font-medium">
                                        {{ $person->present ? '✓ Anwesend' : '✗ Abwesend' }}
                                    </span>
                                @else
                                    <span
                                        class="ml-2 inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-500">
                                        🚫 Kein Zugang heute
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($person->remarks)
                        <div class="mt-4 rounded-lg bg-yellow-50 p-3">
                            <h5 class="mb-2 font-medium text-yellow-800">💬 Bemerkungen</h5>
                            <p class="text-sm text-yellow-700">{{ $person->remarks }}</p>
                        </div>
                    @endif
                </div>

                <!-- Backstage Access Section -->
                <div class="mb-6 rounded-lg bg-gray-50 p-4">
                    <h4 class="mb-3 text-lg font-semibold text-gray-900">
                        🎪 {{ $settings ? $settings->getBackstageLabel() : 'Backstage' }}-Berechtigung
                    </h4>
                    <div class="grid grid-cols-4 gap-4">
                        @for ($day = 1; $day <= 4; $day++)
                            <div class="text-center">
                                <div class="mb-2 text-sm font-medium text-gray-700">
                                    {{ $settings ? $settings->getDayLabel($day) : "Tag $day" }}
                                    @if ($day == $currentDay)
                                        <span class="ml-1 text-xs text-blue-600">HEUTE</span>
                                    @endif
                                </div>
                                @if ($person->{"backstage_day_$day"})
                                    <div
                                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                                        <span class="text-lg font-bold text-green-600">✓</span>
                                    </div>
                                    <div class="mt-1 text-xs text-green-600">Berechtigt</div>
                                @else
                                    <div
                                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                                        <span class="text-lg font-bold text-red-600">✗</span>
                                    </div>
                                    <div class="mt-1 text-xs text-red-600">Nicht berechtigt</div>
                                @endif
                            </div>
                        @endfor
                    </div>
                </div>

                <!-- Voucher Section -->
                <div class="mb-6 rounded-lg bg-gray-50 p-4">
                    <h4 class="mb-3 text-lg font-semibold text-gray-900">
                        🎫 {{ $settings ? $settings->getVoucherLabel() : 'Voucher' }}-Übersicht
                    </h4>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        @for ($day = 1; $day <= 4; $day++)
                            <div
                                class="{{ $day == $currentDay ? 'bg-blue-50 ring-2 ring-blue-200' : 'bg-white' }} rounded-lg border p-4">
                                <div class="mb-3 text-center">
                                    <div class="font-medium text-gray-900">
                                        {{ $settings ? $settings->getDayLabel($day) : "Tag $day" }}
                                        @if ($day == $currentDay)
                                            <span class="ml-1 text-xs text-blue-600">HEUTE</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Berechtigt:</span>
                                        <span class="font-medium">{{ $person->{"voucher_day_$day"} }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Ausgegeben:</span>
                                        <span
                                            class="font-medium text-green-600">{{ $person->{"voucher_issued_day_$day"} }}</span>
                                    </div>
                                    <div class="flex justify-between border-t border-gray-200 pt-2">
                                        <span class="text-gray-600">Verfügbar:</span>
                                        <span
                                            class="{{ $person->getAvailableVouchersForDay($day) > 0 ? 'text-blue-600' : 'text-gray-400' }} font-bold">
                                            {{ $person->getAvailableVouchersForDay($day) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Voucher Action Button -->
                                @if ($person->getAvailableVouchersForDay($day) > 0 && $settings && $settings->canIssueVouchersForDay($day, $currentDay))
                                    @php
                                        $isSingleMode = $settings->isSingleVoucherMode();
                                        $availableCount = $person->getAvailableVouchersForDay($day);
                                        $buttonText = $isSingleMode ? '1 ausgeben' : 'Alle ausgeben';
                                    @endphp
                                    <button
                                        wire:click="issueVouchersFromModal({{ $person->id }}, {{ $day }})"
                                        wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed"
                                        wire:target="issueVouchersFromModal"
                                        class="{{ $person->isGuest() ? 'bg-blue-400 hover:bg-blue-500' : 'bg-blue-500 hover:bg-blue-600' }} mt-3 w-full rounded px-3 py-2 text-sm text-white disabled:cursor-not-allowed disabled:opacity-50"
                                        title="{{ $isSingleMode ? '1' : $availableCount }} {{ $settings->getVoucherLabel() }} ausgeben">
                                        <span wire:loading.remove wire:target="issueVouchersFromModal">
                                            @if ($person->isGuest())
                                                👥
                                            @endif{{ $buttonText }}
                                        </span>
                                        <span wire:loading wire:target="issueVouchersFromModal">
                                            @if ($person->isGuest())
                                                👥
                                            @endif💳 Ausgeben...
                                        </span>
                                    </button>
                                @elseif($person->getAvailableVouchersForDay($day) > 0)
                                    <div
                                        class="mt-3 w-full rounded bg-gray-200 px-3 py-2 text-center text-sm text-gray-600">
                                        Nicht erlaubt
                                    </div>
                                @endif
                            </div>
                        @endfor
                    </div>
                </div>

                <!-- Kennzeichen Section -->
                @if ($person->hasVehiclePlates())
                    <div class="mb-6 rounded-lg bg-gray-50 p-4">
                        <h4 class="mb-3 text-lg font-semibold text-gray-900">🚗 Kennzeichen</h4>
                        <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                            @foreach ($person->vehiclePlates as $plate)
                                <div
                                    class="rounded bg-white px-3 py-2 text-center font-mono text-sm font-medium shadow-sm">
                                    {{ $plate->license_plate }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-3">
                    <!-- Anwesenheit Toggle -->
                    @php
                        $hasBackstageToday = $person->{"backstage_day_{$currentDay}"};
                    @endphp
                    @if ($hasBackstageToday)
                        @if ($person->isGuest())
                            @php
                                $guestStatus = $this->getGuestStatusForToday($person);
                            @endphp
                            @if ($guestStatus['can_toggle'])
                                <button wire:click="toggleGuestPresenceFromModal({{ $person->id }})"
                                    wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed"
                                    wire:target="toggleGuestPresenceFromModal"
                                    class="{{ $person->present ? 'bg-blue-500 hover:bg-blue-600 text-white' : 'bg-gray-300 hover:bg-gray-400 text-gray-700' }} rounded px-4 py-2 font-medium disabled:cursor-not-allowed disabled:opacity-50">
                                    <span wire:loading.remove wire:target="toggleGuestPresenceFromModal">
                                        👥 {{ $person->present ? 'Als abwesend markieren' : 'Als anwesend markieren' }}
                                    </span>
                                    <span wire:loading wire:target="toggleGuestPresenceFromModal">
                                        👥 Wird gesetzt...
                                    </span>
                                </button>
                            @endif
                        @else
                            <button wire:click="togglePresenceFromModal({{ $person->id }})"
                                wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed"
                                wire:target="togglePresenceFromModal"
                                class="{{ $person->present ? 'bg-green-500 hover:bg-green-600 text-white' : 'bg-gray-300 hover:bg-gray-400 text-gray-700' }} rounded px-4 py-2 font-medium disabled:cursor-not-allowed disabled:opacity-50">
                                <span wire:loading.remove wire:target="togglePresenceFromModal">
                                    {{ $person->present ? 'Als abwesend markieren' : 'Als anwesend markieren' }}
                                </span>
                                <span wire:loading wire:target="togglePresenceFromModal">
                                    Wird gesetzt...
                                </span>
                            </button>
                        @endif
                    @endif

                    <!-- Kennzeichen verwalten -->
                    <button wire:click="showVehiclePlatesFromModal({{ $person->id }})"
                        class="rounded bg-blue-500 px-4 py-2 font-medium text-white hover:bg-blue-600">
                        🚗 Kennzeichen verwalten
                    </button>

                    <!-- Gast hinzufügen (falls möglich) -->
                    @if ($this->canMemberHaveGuest($person))
                        <button wire:click="addGuestFromModal({{ $person->id }})"
                            class="rounded bg-green-500 px-4 py-2 font-medium text-white hover:bg-green-600">
                            👥 Gast hinzufügen
                        </button>
                    @endif

                    <!-- Gäste anzeigen (falls vorhanden) -->
                    @if ($person->can_have_guests && $person->responsibleFor->count() > 0)
                        <button wire:click="showGuestsFromModal({{ $person->id }})"
                            class="rounded bg-purple-500 px-4 py-2 font-medium text-white hover:bg-purple-600">
                            👥 Gäste anzeigen ({{ $person->responsibleFor->count() }})
                        </button>
                    @endif
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="border-t border-gray-200 px-6 py-4">
                <div class="flex justify-end">
                    <button wire:click="closePersonDetailsModal"
                        class="rounded bg-gray-500 px-4 py-2 text-white hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Schließen
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Keyboard Support -->
<script>
    document.addEventListener('livewire:initialized', () => {
        // ESC zum Schließen
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && @this.showPersonDetailsModal) {
                @this.call('closePersonDetailsModal');
            }
        });
    });
</script>
