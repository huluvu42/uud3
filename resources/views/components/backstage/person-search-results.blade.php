<div class="mb-6 rounded-lg bg-white p-6 shadow-md">
    <h3 class="mb-4 text-lg font-semibold">Personen-Suchergebnisse ({{ count($searchResults) }} Personen)</h3>
    <div class="space-y-3" style="max-height: calc(100vh - 400px); overflow-y: auto; min-height: 400px;">
        @foreach ($searchResults as $person)
            <div class="rounded-lg border border-gray-200 p-4 hover:bg-gray-50"
                wire:key="search-person-{{ $person->id }}">
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-6">

                    <!-- Person Info -->
                    <div class="xl:col-span-1">
                        <div wire:click="selectPersonForDetails({{ $person->id }})"
                            class="mb-1 cursor-pointer text-lg font-medium hover:text-blue-600">
                            <div class="flex items-center gap-1">
                                <span>{{ $person->first_name }} {{ $person->last_name }}</span>
                            </div>

                            <!-- Badges -->
                            <div class="mt-1 flex flex-wrap gap-1">
                                @if ($person->isGuest())
                                    <span class="rounded bg-blue-100 px-2 py-1 text-xs text-blue-800">👥 Gast</span>
                                @endif
                                @if ($person->can_have_guests)
                                    <span class="rounded bg-purple-100 px-2 py-1 text-xs text-purple-800">Host</span>
                                @endif
                            </div>
                        </div>

                        <!-- Band/Gruppe Info -->
                        @if ($person->band)
                            <div class="mb-1 flex items-center text-sm text-gray-600">
                                <span class="mr-1 w-4 text-center">🎵</span>
                                <span>{{ $person->band->band_name }}</span>
                            </div>
                            <button wire:click="goToBand({{ $person->id }})"
                                class="mb-1 inline-flex items-center rounded bg-purple-50 px-2 py-1 text-xs text-purple-700 hover:bg-purple-100"
                                title="Zur Band '{{ $person->band->band_name }}' wechseln">
                                🎵 Zur Band
                            </button>
                        @endif

                        @if ($person->group)
                            <div class="mb-1 flex items-center text-sm text-gray-500">
                                <span class="mr-1 w-4 text-center">👥</span>
                                <span>{{ $person->group->name }}</span>
                            </div>
                        @endif

                        <!-- Gast-Beziehungen -->
                        @if ($person->isGuest() && $person->responsiblePerson)
                            <div class="mb-1 text-xs text-blue-600">
                                <span class="whitespace-nowrap rounded bg-blue-50 px-2 py-1">
                                    👤 Verantwortlich: {{ $person->responsiblePerson->full_name }}
                                </span>
                            </div>
                        @endif

                        <!-- Anzahl Gäste oder Gast-Button -->
                        @if ($person->can_have_guests)
                            @php $guestCount = $person->responsibleFor->count(); @endphp
                            @if ($guestCount > 0)
                                <button wire:click="showGuests({{ $person->id }})"
                                    class="mb-1 inline-flex items-center rounded bg-purple-50 px-2 py-1 text-xs text-purple-700 hover:bg-purple-100"
                                    title="Gäste anzeigen">
                                    👥 {{ $guestCount }} {{ $guestCount === 1 ? 'Gast' : 'Gäste' }}
                                </button>
                            @elseif ($person->band && $this->canMemberHaveGuest($person))
                                <button wire:click="addGuestForMember({{ $person->id }})"
                                    class="mb-1 inline-flex items-center rounded bg-green-50 px-2 py-1 text-xs text-green-700 hover:bg-green-100"
                                    title="Gast hinzufügen">
                                    👥 + Gast hinzufügen
                                </button>
                            @endif
                        @endif

                        @if ($person->remarks)
                            <div class="mt-1 text-xs text-blue-600">
                                <span class="rounded bg-blue-50 px-2 py-1 text-blue-800">
                                    {{ Str::limit($person->remarks, 20) }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Bändchen -->
                    <div class="xl:col-span-1">
                        <div class="flex flex-col items-center">
                            <div class="mb-2 text-sm font-medium text-gray-700">Bändchen</div>

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
                                        <div class="flex h-5 w-5 items-center justify-center rounded-full bg-green-100">
                                            <span class="text-xs font-bold text-green-600">✓</span>
                                        </div>
                                    @else
                                        <div class="flex h-5 w-5 items-center justify-center rounded-full bg-red-100">
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
                                        @if ($day == $currentDay)
                                            <div class="text-green-600">
                                                {{ $person->{"voucher_issued_day_$day"} }}</div>
                                        @else
                                            <div class="text-gray-400">
                                                {{ $person->{"voucher_issued_day_$day"} }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="xl:col-span-1">
                        <div class="mb-2 text-sm font-medium text-gray-700">Status</div>

                        @php
                            $hasBackstageToday = $person->{"backstage_day_{$currentDay}"};
                        @endphp

                        @if ($hasBackstageToday)
                            @if ($person->isGuest())
                                @php
                                    $guestStatus = $this->getGuestStatusForToday($person);
                                @endphp
                                @if ($guestStatus['can_toggle'])
                                    <button wire:click="toggleGuestPresence({{ $person->id }})"
                                        wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed"
                                        wire:target="toggleGuestPresence"
                                        class="{{ $person->present ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-700' }} w-full rounded px-3 py-2 text-sm font-medium transition-opacity hover:opacity-80 disabled:cursor-not-allowed"
                                        title="{{ $person->present ? 'Gast als abwesend markieren' : 'Gast als anwesend markieren' }}">
                                        <span wire:loading.remove wire:target="toggleGuestPresence">
                                            {{ $person->present ? 'Anwesend' : 'Abwesend' }}
                                        </span>
                                        <span wire:loading wire:target="toggleGuestPresence">
                                            👥 Wird gesetzt...
                                        </span>
                                    </button>
                                @else
                                    <div class="w-full cursor-not-allowed rounded bg-gray-200 px-3 py-2 text-center text-sm text-gray-500"
                                        title="👥 Gast: {{ $guestStatus['reason'] }}">
                                        👥 {{ $guestStatus['reason'] }}
                                    </div>
                                @endif
                            @else
                                <button wire:click="togglePresence({{ $person->id }})" wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50 cursor-not-allowed" wire:target="togglePresence"
                                    class="{{ $person->present ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-700' }} w-full rounded px-3 py-2 text-sm font-medium transition-opacity hover:opacity-80 disabled:cursor-not-allowed"
                                    title="{{ $person->present ? 'Als abwesend markieren' : 'Als anwesend markieren' }}">
                                    <span wire:loading.remove wire:target="togglePresence">
                                        {{ $person->present ? 'Anwesend' : 'Abwesend' }}
                                    </span>
                                    <span wire:loading wire:target="togglePresence">
                                        Wird gesetzt...
                                    </span>
                                </button>
                            @endif
                        @else
                            <div class="w-full cursor-not-allowed rounded bg-gray-200 px-3 py-2 text-center text-sm text-gray-500"
                                title="Person hat heute keinen Backstage-Zugang">
                                🚫 Kein Zugang
                            </div>
                        @endif

                        <!-- Kennzeichen Button -->
                        <button wire:click="showVehiclePlates({{ $person->id }})"
                            class="{{ $person->hasVehiclePlates() ? 'bg-blue-500 text-white' : 'bg-gray-400 text-white' }} mt-2 w-full rounded px-3 py-1 text-xs font-medium hover:opacity-80"
                            title="Kennzeichen verwalten">
                            🚗 {{ $person->vehiclePlates->count() > 0 ? $person->vehiclePlates->count() : '' }}
                            Kennzeichen
                        </button>

                        @if ($person->hasVehiclePlates())
                            <div class="mt-1 w-full rounded bg-gray-50 px-2 py-1 text-xs">
                                @foreach ($person->vehiclePlates as $plate)
                                    <div class="text-center font-mono text-gray-700"
                                        title="Kennzeichen: {{ $plate->license_plate }}">
                                        {{ $plate->license_plate }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Aktionen -->
                    <div class="xl:col-span-1">
                        <div class="mb-2 text-sm font-medium text-gray-700">Aktionen</div>
                        <div class="space-y-2">
                            @php
                                $freshPerson = \App\Models\Person::find($person->id);
                                $nextAvailableDay = $this->getNextAvailableVoucherDay($freshPerson);
                                $isSingleMode = $settings && $settings->isSingleVoucherMode();
                                $voucherLabel = $settings ? $settings->getVoucherLabel() : 'Voucher';
                                $freeVoucherLabel = 'Frei ' . $voucherLabel;
                            @endphp

                            @if ($nextAvailableDay)
                                @php
                                    $availableCount = $freshPerson->getAvailableVouchersForDay($nextAvailableDay);
                                    $buttonText = $isSingleMode
                                        ? "1 $freeVoucherLabel"
                                        : "$availableCount $freeVoucherLabel";
                                    $dayLabel = $settings
                                        ? $settings->getDayLabel($nextAvailableDay)
                                        : "Tag $nextAvailableDay";
                                @endphp

                                <button wire:click="issueVouchers({{ $freshPerson->id }}, {{ $nextAvailableDay }})"
                                    wire:loading.attr="disabled" wire:loading.class="opacity-50"
                                    wire:target="issueVouchers"
                                    wire:key="voucher-btn-{{ $freshPerson->id }}-{{ $availableCount }}-{{ now()->timestamp }}"
                                    class="{{ $person->isGuest() ? 'bg-blue-400 hover:bg-blue-500' : 'bg-blue-500 hover:bg-blue-600' }} w-full rounded px-3 py-2 text-sm text-white disabled:cursor-not-allowed disabled:opacity-50"
                                    title="{{ $isSingleMode ? '1' : 'Alle verfügbaren' }} {{ $freeVoucherLabel }} für {{ $dayLabel }} ausgeben"
                                    data-person-id="{{ $freshPerson->id }}">

                                    <span wire:loading.remove wire:target="issueVouchers">
                                        @if ($person->isGuest())
                                            👥
                                        @endif{{ $buttonText }}
                                    </span>
                                    <span wire:loading wire:target="issueVouchers">
                                        @if ($person->isGuest())
                                            👥
                                        @endif💳 Ausgeben...
                                    </span>
                                </button>

                                @if ($nextAvailableDay != $currentDay)
                                    <div class="text-center text-xs text-orange-600">
                                        ({{ $dayLabel }})
                                    </div>
                                @endif
                            @else
                                <div class="w-full rounded bg-gray-100 px-3 py-2 text-center text-sm text-gray-500">
                                    @if ($person->isGuest())
                                        👥
                                    @endif Keine {{ $freeVoucherLabel }}
                                </div>
                            @endif

                            <!-- Person-basierte Voucher-Käufe -->
                            @if ($this->canShowPersonPurchase())
                                <div class="mt-2 border-t pt-2">
                                    <div class="mb-1 text-xs text-gray-500">Bon kaufen:</div>
                                    <div class="flex gap-1">
                                        <button wire:click="purchasePersonVoucher({{ $person->id }}, 0.5)"
                                            class="flex-1 rounded bg-green-500 px-2 py-1 text-xs text-white hover:bg-green-600"
                                            title="0.5 Bon für {{ $person->full_name }} kaufen">
                                            halber Bon
                                        </button>
                                        <button wire:click="purchasePersonVoucher({{ $person->id }}, 1.0)"
                                            class="flex-1 rounded bg-green-500 px-2 py-1 text-xs text-white hover:bg-green-600"
                                            title="1.0 Bon für {{ $person->full_name }} kaufen">
                                            ganzer Bon
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
