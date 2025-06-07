{{-- resources/views/livewire/backstage-control.blade.php --}}

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

        <!-- Header-Bereich mit 4 Spalten -->
        <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
            <!-- Vier-Spalten Layout -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">

                <!-- Personensuche -->
                <div>
                    <h3 class="mb-3 text-lg font-semibold">Personensuche</h3>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Vorname, Nachname..."
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">

                    @if ($search)
                        <div class="mt-2">
                            <button wire:click="clearAllSearches"
                                class="text-xs text-gray-500 underline hover:text-gray-700">
                                🗑️ Alle Suchen zurücksetzen
                            </button>
                        </div>
                    @endif
                </div>

                <!-- NEU: Band-Suche -->
                <div>
                    <h3 class="mb-3 text-lg font-semibold">Band-Suche</h3>
                    <input type="text" wire:model.live.debounce.300ms="bandSearch" placeholder="Bandname..."
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">

                    @if ($bandSearch)
                        <div class="mt-2">
                            <button wire:click="clearAllSearches"
                                class="text-xs text-gray-500 underline hover:text-gray-700">
                                🗑️ Alle Suchen zurücksetzen
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Bonkauf -->
                <div>
                    <h3 class="mb-3 text-lg font-semibold">Bonkauf</h3>
                    @if ($this->canShowStagePurchase())
                        <div class="space-y-2">
                            <div class="flex gap-1">
                                <button wire:click="initiatePurchase(0.5)"
                                    class="{{ $voucherAmount == 0.5 ? 'ring-2 ring-blue-300' : '' }} flex-1 rounded bg-blue-500 px-2 py-2 text-sm text-white hover:bg-blue-600">
                                    0.5
                                </button>
                                <button wire:click="initiatePurchase(1.0)"
                                    class="{{ $voucherAmount == 1.0 ? 'ring-2 ring-blue-300' : '' }} flex-1 rounded bg-blue-500 px-2 py-2 text-sm text-white hover:bg-blue-600">
                                    1.0
                                </button>
                                @if ($purchaseStageId)
                                    <button wire:click="resetStageSelection"
                                        class="rounded bg-gray-400 px-2 py-2 text-sm text-white hover:bg-gray-500"
                                        title="Bühnen-Auswahl zurücksetzen">
                                        ↻
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Verkaufszahlen anzeigen -->
                    @if ($purchaseStageId)
                        @php
                            $selectedStageObj = $stages->find($purchaseStageId);
                        @endphp
                        @if ($selectedStageObj)
                            @php $soldToday = $this->getSoldVouchersForStage($purchaseStageId, $currentDay); @endphp
                            <div class="mt-2 rounded border bg-blue-50 p-2 text-xs text-gray-600">
                                <strong>{{ $selectedStageObj->name }}</strong><br>
                                Heute: {{ $soldToday }} {{ $settings ? $settings->getVoucherLabel() : 'Bons' }}
                            </div>
                        @endif
                    @endif
                </div>

                <!-- Bands des Tages -->
                <div>
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Bands des Tages</h3>
                        <select wire:model.live="stageFilter"
                            class="rounded border border-gray-300 px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all">Alle</option>
                            @foreach ($stages as $stage)
                                <option value="{{ $stage->id }}">{{ Str::limit($stage->name, 8) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button wire:click="showTodaysBands"
                        class="w-full rounded bg-purple-500 px-3 py-2 text-sm text-white hover:bg-purple-600">
                        {{ $settings ? $settings->getDayLabel($currentDay) : "Tag $currentDay" }}
                    </button>
                </div>
            </div>
        </div>

        <!-- NEU: Band-Suchergebnisse -->
        @if (count($bandSearchResults) > 0)
            <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
                <h3 class="mb-4 text-lg font-semibold">Band-Suchergebnisse ({{ count($bandSearchResults) }} Bands)</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
                    style="max-height: calc(100vh - 400px); overflow-y: auto;">
                    @foreach ($bandSearchResults as $band)
                        <div wire:click="selectBandFromSearch({{ $band->id }})"
                            class="cursor-pointer rounded-lg border border-gray-200 p-4 hover:border-purple-300 hover:bg-gray-50"
                            wire:key="band-search-{{ $band->id }}">

                            <!-- NEU: Status-Badge oben rechts -->
                            @php $bandStatus = $this->getBandStatusForToday($band) @endphp
                            <div class="mb-2 flex items-start justify-between">
                                <div class="text-lg font-medium text-purple-700">{{ $band->band_name }}</div>
                                <span
                                    class="{{ $bandStatus['class'] }} rounded-full border px-3 py-1 text-sm font-medium">
                                    {{ $bandStatus['text'] }}
                                </span>
                            </div>

                            <div class="mb-2 text-sm text-gray-600">
                                🎪 Bühne: {{ $band->stage->name ?? 'Keine Bühne' }}
                            </div>

                            <div class="text-sm text-gray-600">
                                👥 {{ $band->members->count() }} Mitglieder
                                @if ($band->all_present)
                                    <span class="ml-2 rounded bg-green-100 px-2 py-0.5 text-xs text-green-800">Alle
                                        da</span>
                                @endif
                            </div>

                            <!-- Performance Tage -->
                            <div class="mt-2 space-y-1">
                                @for ($day = 1; $day <= 4; $day++)
                                    @if ($band->{"plays_day_$day"})
                                        @php
                                            $performanceTime = $band->getFormattedPerformanceTimeForDay($day);
                                            $latestArrival = null;

                                            if ($performanceTime && $settings) {
                                                $arrivalMinutes = $settings->getLatestArrivalTimeMinutes();
                                                try {
                                                    $performanceDateTime = \Carbon\Carbon::createFromFormat(
                                                        'H:i',
                                                        $performanceTime,
                                                    );
                                                    $latestArrivalTime = $performanceDateTime->subMinutes(
                                                        $arrivalMinutes,
                                                    );
                                                    $latestArrival = $latestArrivalTime->format('H:i');
                                                } catch (\Exception $e) {
                                                    $latestArrival = null;
                                                }
                                            }
                                        @endphp

                                        <div class="flex items-center space-x-2">
                                            <span class="rounded bg-purple-100 px-2 py-1 text-xs text-purple-700">
                                                {{ $settings ? $settings->getDayLabel($day) : "T$day" }}
                                            </span>

                                            @if ($latestArrival)
                                                <span class="text-xs text-orange-600" title="Späteste Ankunftszeit">
                                                    bis {{ $latestArrival }}
                                                </span>
                                            @endif

                                            @if ($performanceTime)
                                                <span class="text-xs text-green-600" title="Auftrittszeit">
                                                    🎤 {{ $performanceTime }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                @endfor
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Personen-Suchergebnisse (wie bisher, aber konditioniert) -->
        @if (count($searchResults) > 0)
            <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
                <h3 class="mb-4 text-lg font-semibold">Personen-Suchergebnisse ({{ count($searchResults) }} Personen)
                </h3>
                <!-- Automatische Höhenanpassung mit max-height basierend auf viewport -->
                <div class="space-y-3" style="max-height: calc(100vh - 400px); overflow-y: auto; min-height: 400px;">
                    @foreach ($searchResults as $person)
                        <div class="rounded-lg border border-gray-200 p-4 hover:bg-gray-50"
                            wire:key="person-{{ $person->id }}-{{ $person->voucher_issued_day_1 }}-{{ $person->voucher_issued_day_2 }}-{{ $person->voucher_issued_day_3 }}-{{ $person->voucher_issued_day_4 }}-{{ $person->present ? 'present' : 'absent' }}">

                            <div class="grid grid-cols-1 gap-4 xl:grid-cols-6">

                                <!-- Person Info -->
                                <div class="xl:col-span-1">
                                    <div wire:click="selectPerson({{ $person->id }})"
                                        class="mb-1 cursor-pointer text-lg font-medium hover:text-blue-600">
                                        <div class="flex items-center gap-1">
                                            <span>{{ $person->first_name }} {{ $person->last_name }}</span>
                                        </div>

                                        <!-- NEU: Badges in separater Zeile -->
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            @if ($person->isGuest())
                                                <span
                                                    class="rounded bg-blue-100 px-2 py-1 text-xs text-blue-800">Gast</span>
                                            @endif
                                            @if ($person->can_have_guests)
                                                <span
                                                    class="rounded bg-purple-100 px-2 py-1 text-xs text-purple-800">kann
                                                    Gäste haben</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($person->band)
                                        <div class="mb-1 flex items-center text-sm text-gray-600">
                                            <span class="mr-1 w-4 text-center">🎵</span>
                                            <span>{{ $person->band->band_name }}</span>
                                        </div>
                                        <!-- NEU: Zur Band Button direkt unter dem Bandnamen -->
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

                                    <!-- NEU: Gast-Informationen -->
                                    @if ($person->isGuest() && $person->responsiblePerson)
                                        <div class="mb-1 text-xs text-blue-600">
                                            <span class="whitespace-nowrap rounded bg-blue-50 px-2 py-1">
                                                Verantwortlich: {{ $person->responsiblePerson->full_name }}
                                            </span>
                                        </div>
                                    @endif

                                    <!-- NEU: Anzahl Gäste (klickbar) -->
                                    @if ($person->can_have_guests && $person->responsibleFor->count() > 0)
                                        <button wire:click="showGuests({{ $person->id }})"
                                            class="mb-1 inline-flex items-center rounded bg-purple-50 px-2 py-1 text-xs text-purple-700 hover:bg-purple-100"
                                            title="Gäste anzeigen">
                                            👥 {{ $person->responsibleFor->count() }}
                                            {{ $person->responsibleFor->count() === 1 ? 'Gast' : 'Gäste' }}
                                        </button>
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
                                    <button wire:click="togglePresence({{ $person->id }})"
                                        class="{{ $person->present ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-700' }} w-full rounded px-3 py-2 text-sm font-medium"
                                        title="{{ $person->present ? 'Als abwesend markieren' : 'Als anwesend markieren' }}">
                                        {{ $person->present ? 'Anwesend' : 'Abwesend' }}
                                    </button>
                                    <!-- Kennzeichen Button -->
                                    <button wire:click="showVehiclePlates({{ $person->id }})"
                                        class="{{ $person->hasVehiclePlates() ? 'bg-blue-500 text-white' : 'bg-gray-400 text-white' }} w-full rounded px-3 py-1 text-xs font-medium hover:opacity-80"
                                        title="Kennzeichen verwalten">
                                        🚗
                                        {{ $person->vehiclePlates->count() > 0 ? $person->vehiclePlates->count() : '' }}
                                        Kennzeichen
                                    </button>
                                    {{-- NEU: Kennzeichen direkt anzeigen --}}
                                    @if ($person->hasVehiclePlates())
                                        <div class="w-full rounded bg-gray-50 px-2 py-1 text-xs">
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
                                            $nextAvailableDay = $this->getNextAvailableVoucherDay($person);
                                            $isSingleMode = $settings && $settings->isSingleVoucherMode();
                                            $voucherLabel = $settings ? $settings->getVoucherLabel() : 'Voucher';
                                        @endphp

                                        @if ($nextAvailableDay)
                                            @php
                                                $availableCount = $person->getAvailableVouchersForDay(
                                                    $nextAvailableDay,
                                                );
                                                $buttonText = $isSingleMode
                                                    ? "1 $voucherLabel"
                                                    : "$availableCount $voucherLabel";
                                                $dayLabel = $settings
                                                    ? $settings->getDayLabel($nextAvailableDay)
                                                    : "Tag $nextAvailableDay";
                                            @endphp

                                            <button
                                                wire:click="issueVouchers({{ $person->id }}, {{ $nextAvailableDay }})"
                                                class="w-full rounded bg-blue-500 px-3 py-2 text-sm text-white hover:bg-blue-600"
                                                title="{{ $isSingleMode ? '1' : 'Alle verfügbaren' }} {{ $voucherLabel }} für {{ $dayLabel }} ausgeben">
                                                {{ $buttonText }}
                                            </button>

                                            @if ($nextAvailableDay != $currentDay)
                                                <div class="text-center text-xs text-orange-600">
                                                    ({{ $dayLabel }})
                                                </div>
                                            @endif
                                        @else
                                            <div
                                                class="w-full rounded bg-gray-100 px-3 py-2 text-center text-sm text-gray-500">
                                                Keine {{ $voucherLabel }}
                                            </div>
                                        @endif

                                        <!-- NEU: Person-basierte Voucher-Käufe -->
                                        @if ($this->canShowPersonPurchase())
                                            <div class="mt-2 border-t pt-2">
                                                <div class="mb-1 text-xs text-gray-500">Bon kaufen:</div>
                                                <div class="flex gap-1">
                                                    <button
                                                        wire:click="purchasePersonVoucher({{ $person->id }}, 0.5)"
                                                        class="flex-1 rounded bg-green-500 px-2 py-1 text-xs text-white hover:bg-green-600"
                                                        title="0.5 Bon für {{ $person->full_name }} kaufen">
                                                        0.5
                                                    </button>
                                                    <button
                                                        wire:click="purchasePersonVoucher({{ $person->id }}, 1.0)"
                                                        class="flex-1 rounded bg-green-500 px-2 py-1 text-xs text-white hover:bg-green-600"
                                                        title="1.0 Bon für {{ $person->full_name }} kaufen">
                                                        1.0
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
        @elseif($showBandList && $todaysBands->count() > 0)
            <!-- Today's Bands -->
            <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
                <h3 class="mb-4 text-lg font-semibold">
                    Bands für {{ $settings ? $settings->getDayLabel($currentDay) : "Tag $currentDay" }}
                    @if ($stageFilter !== 'all')
                        @php $selectedStageObj = $stages->find($stageFilter) @endphp
                        - {{ $selectedStageObj->name }}
                    @endif
                    ({{ $todaysBands->count() }} Bands)
                </h3>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
                    style="max-height: calc(100vh - 400px); overflow-y: auto;">
                    @foreach ($todaysBands as $band)
                        <div wire:click="selectBand({{ $band->id }})"
                            class="{{ $selectedBand && $selectedBand->id === $band->id ? 'ring-2 ring-blue-500 bg-blue-50' : '' }} cursor-pointer rounded-lg border border-gray-200 p-4 hover:bg-gray-50">

                            <!-- NEU: Status-Badge oben rechts -->
                            @php $bandStatus = $this->getBandStatusForToday($band) @endphp
                            <div class="mb-2 flex items-start justify-between">
                                <div class="text-lg font-medium">{{ $band->band_name }}</div>
                                <span
                                    class="{{ $bandStatus['class'] }} rounded-full border px-3 py-1 text-sm font-medium">
                                    {{ $bandStatus['text'] }}
                                </span>
                            </div>

                            <div class="mb-2 text-sm text-gray-600">
                                Bühne: {{ $band->stage->name ?? 'Keine Bühne' }}
                            </div>
                            <div class="text-sm text-gray-600">
                                {{ $band->members->count() }} Mitglieder
                                @if ($band->all_present)
                                    <span class="ml-2 rounded bg-green-100 px-2 py-0.5 text-xs text-green-800">Alle
                                        da</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif(!$search && !$bandSearch && !$showBandList)
            <!-- Welcome Message -->
            <div class="rounded-lg bg-white p-8 text-center shadow-md">
                <h3 class="mb-4 text-xl font-semibold text-gray-600">Willkommen zur Festival Backstage Kontrolle</h3>
                <p class="mb-6 text-gray-500">Verwenden Sie die Funktionen oben, um Personen oder Bands zu verwalten.
                </p>
                <div class="grid grid-cols-1 gap-4 text-sm text-gray-400 md:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded bg-gray-50 p-4">
                        <h4 class="mb-2 font-medium text-gray-600">🔍 Personensuche</h4>
                        <p>Suchen Sie nach Vor-, Nachname</p>
                    </div>
                    <div class="rounded bg-gray-50 p-4">
                        <h4 class="mb-2 font-medium text-gray-600">🎵 Band-Suche</h4>
                        <p>Suchen Sie nach Bandnamen</p>
                    </div>
                    <div class="rounded bg-gray-50 p-4">
                        <h4 class="mb-2 font-medium text-gray-600">🎫 Bonkauf</h4>
                        <p>Kaufen Sie 0.5 oder 1.0 Bons</p>
                    </div>
                    <div class="rounded bg-gray-50 p-4">
                        <h4 class="mb-2 font-medium text-gray-600">🎪 Bands verwalten</h4>
                        <p>Zeigen Sie alle Bands des Tages an</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- NEU: Ausgewählte Band aus Suche -->
        @if ($selectedBandFromSearch)
            <div class="mb-6 rounded-lg border-l-4 border-purple-500 bg-white p-6 shadow-md">
                <div class="mb-6 flex items-start justify-between">
                    <div class="flex-1">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-xl font-bold text-purple-700">
                                🎵 {{ $selectedBandFromSearch->band_name }}
                            </h3>

                            <!-- NEU: Großer Status-Badge -->
                            @php $bandStatus = $this->getBandStatusForToday($selectedBandFromSearch) @endphp
                            <div class="{{ $bandStatus['class'] }} rounded-lg border-2 px-4 py-2 text-lg font-bold">
                                {{ $bandStatus['text'] }}
                            </div>
                        </div>

                        @if ($selectedBandFromSearch->stage)
                            <p class="text-gray-600">🎪 Bühne: {{ $selectedBandFromSearch->stage->name }}</p>
                        @endif

                        <!-- Performance Tage mit Zeiten -->
                        <div class="mt-2 space-y-2">
                            @for ($day = 1; $day <= 4; $day++)
                                @if ($selectedBandFromSearch->{"plays_day_$day"})
                                    @php
                                        $performanceTime = $selectedBandFromSearch->getFormattedPerformanceTimeForDay(
                                            $day,
                                        );
                                        $latestArrival = null;

                                        if ($performanceTime && $settings) {
                                            $arrivalMinutes = $settings->getLatestArrivalTimeMinutes();
                                            try {
                                                $performanceDateTime = \Carbon\Carbon::createFromFormat(
                                                    'H:i',
                                                    $performanceTime,
                                                );
                                                $latestArrivalTime = $performanceDateTime->subMinutes($arrivalMinutes);
                                                $latestArrival = $latestArrivalTime->format('H:i');
                                            } catch (\Exception $e) {
                                                // Fallback wenn Zeitformat nicht passt
                                                $latestArrival = null;
                                            }
                                        }
                                    @endphp

                                    <div class="flex items-center space-x-3">
                                        <span
                                            class="{{ $day == $currentDay ? 'ring-2 ring-purple-300' : '' }} rounded bg-purple-100 px-3 py-1 text-sm font-medium text-purple-700">
                                            {{ $settings ? $settings->getDayLabel($day) : "Tag $day" }}
                                            @if ($day == $currentDay)
                                                <span class="ml-1 text-xs">HEUTE</span>
                                            @endif
                                        </span>

                                        @if ($latestArrival)
                                            <span class="text-sm text-orange-600" title="Späteste Ankunftszeit">
                                                🕐 bis {{ $latestArrival }}
                                            </span>
                                        @endif

                                        @if ($performanceTime)
                                            <span class="text-sm text-green-600" title="Auftrittszeit">
                                                🎤 {{ $performanceTime }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            @endfor
                        </div>
                    </div>

                    @if ($selectedBandFromSearch->all_present)
                        <span class="rounded-full bg-green-100 px-3 py-1 text-sm text-green-800">Alle anwesend</span>
                    @endif
                </div>

                <!-- Band Members -->
                @if (count($bandMembers) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-purple-50">
                                <tr>
                                    <th wire:click="sortBy('first_name')"
                                        class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase text-purple-700 hover:bg-purple-100">
                                        Vorname
                                        @if ($sortBy === 'first_name')
                                            <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </th>
                                    <th wire:click="sortBy('last_name')"
                                        class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase text-purple-700 hover:bg-purple-100">
                                        Nachname
                                        @if ($sortBy === 'last_name')
                                            <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-purple-700">
                                        Anwesend</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-purple-700">
                                        Kennzeichen</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-purple-700">
                                        {{ $settings ? $settings->getVoucherLabel() : 'Voucher' }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-purple-700">
                                        Aktionen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($bandMembers as $member)
                                    <tr class="hover:bg-purple-50">
                                        <td class="px-4 py-3 text-sm">{{ $member->first_name }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $member->last_name }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <button wire:click="togglePresence({{ $member->id }})"
                                                class="{{ $member->present ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-700' }} rounded px-2 py-1 text-xs">
                                                {{ $member->present ? 'Ja' : 'Nein' }}
                                            </button>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if ($member->hasVehiclePlates())
                                                <button wire:click="showVehiclePlates({{ $member->id }})"
                                                    class="rounded bg-blue-500 px-2 py-1 text-xs text-white hover:bg-blue-600"
                                                    title="Kennzeichen verwalten">
                                                    🚗 {{ $member->vehiclePlates->count() }}
                                                </button>
                                                <div class="mt-1 text-xs text-gray-600">
                                                    @foreach ($member->vehiclePlates as $plate)
                                                        <div class="font-mono">{{ $plate->license_plate }}</div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <button wire:click="showVehiclePlates({{ $member->id }})"
                                                    class="rounded bg-gray-400 px-2 py-1 text-xs text-white hover:bg-gray-500"
                                                    title="Kennzeichen hinzufügen">
                                                    🚗 +
                                                </button>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            {{ $member->{"voucher_day_$currentDay"} }}/{{ $member->{"voucher_issued_day_$currentDay"} }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="flex space-x-2">
                                                @php
                                                    $nextAvailableDay = $this->getNextAvailableVoucherDay($member);
                                                    $isSingleMode = $settings && $settings->isSingleVoucherMode();
                                                    $voucherLabel = $settings
                                                        ? $settings->getVoucherLabel()
                                                        : 'Voucher';
                                                @endphp

                                                @if ($nextAvailableDay)
                                                    @php
                                                        $availableCount = $member->getAvailableVouchersForDay(
                                                            $nextAvailableDay,
                                                        );
                                                        $buttonText = $isSingleMode ? 'Ausgeben' : 'Alle';
                                                    @endphp
                                                    <button
                                                        wire:click="issueVouchers({{ $member->id }}, {{ $nextAvailableDay }})"
                                                        class="rounded bg-blue-500 px-2 py-1 text-xs text-white hover:bg-blue-600"
                                                        title="{{ $isSingleMode ? '1' : $availableCount }} {{ $voucherLabel }} ausgeben">
                                                        {{ $buttonText }}
                                                    </button>
                                                @endif

                                                <button wire:click="selectPerson({{ $member->id }})"
                                                    class="rounded bg-gray-500 px-2 py-1 text-xs text-white hover:bg-gray-600">
                                                    Details
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-8 text-center text-gray-500">
                        <p>Diese Band hat noch keine Mitglieder zugeordnet.</p>
                    </div>
                @endif
            </div>
        @endif

        <!-- Selected Person Details (wie bisher) -->
        @if ($selectedPerson)
            <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
                <div class="mb-6 flex items-start justify-between">
                    <div>
                        <h3 class="text-xl font-bold">
                            {{ $selectedPerson->full_name }}
                            @if ($selectedPerson->isGuest())
                                <span class="ml-2 rounded bg-blue-100 px-2 py-1 text-sm text-blue-800">Gast</span>
                            @endif
                            @if ($selectedPerson->can_have_guests)
                                <span class="ml-2 rounded bg-purple-100 px-2 py-1 text-sm text-purple-800">Host</span>
                            @endif
                        </h3>

                        @if ($selectedPerson->band)
                            <p class="text-gray-600">Band: {{ $selectedPerson->band->band_name }}</p>
                        @endif
                        @if ($selectedPerson->group)
                            <p class="text-gray-600">Gruppe: {{ $selectedPerson->group->name }}</p>
                        @endif

                        <!-- NEU: Gast-Beziehungen -->
                        @if ($selectedPerson->isGuest() && $selectedPerson->responsiblePerson)
                            <p class="text-blue-600">
                                <strong>Verantwortliche Person:</strong>
                                {{ $selectedPerson->responsiblePerson->full_name }}
                            </p>
                        @endif

                        @if ($selectedPerson->can_have_guests && $selectedPerson->responsibleFor->count() > 0)
                            <p class="text-purple-600">
                                <strong>Verantwortlich für:</strong>
                                <button wire:click="showGuests({{ $selectedPerson->id }})"
                                    class="underline hover:no-underline">
                                    {{ $selectedPerson->responsibleFor->count() }}
                                    {{ $selectedPerson->responsibleFor->count() === 1 ? 'Gast' : 'Gäste' }}
                                </button>
                            </p>
                        @endif
                    </div>
                    <button wire:click="togglePresence({{ $selectedPerson->id }})"
                        class="{{ $selectedPerson->present ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-700' }} rounded px-4 py-2 font-medium">
                        {{ $selectedPerson->present ? 'Anwesend' : 'Nicht anwesend' }}
                    </button>
                </div>

                <!-- Voucher Information -->
                <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                    @for ($day = 1; $day <= 4; $day++)
                        <div class="rounded-lg bg-gray-50 p-4">
                            <div class="mb-2 font-medium">{{ $settings ? $settings->getDayLabel($day) : "Tag $day" }}
                            </div>
                            <div class="space-y-1 text-sm">
                                <div>Berechtigt: <span
                                        class="font-medium">{{ $selectedPerson->{"voucher_day_$day"} }}</span></div>
                                <div>Ausgegeben: <span
                                        class="font-medium">{{ $selectedPerson->{"voucher_issued_day_$day"} }}</span>
                                </div>
                            </div>
                            @if (
                                $selectedPerson->getAvailableVouchersForDay($day) > 0 &&
                                    $settings &&
                                    $settings->canIssueVouchersForDay($day, $currentDay))
                                @php
                                    $isSingleMode = $settings->isSingleVoucherMode();
                                    $availableCount = $selectedPerson->getAvailableVouchersForDay($day);
                                    $buttonText = $isSingleMode ? '1 ausgeben' : 'Alle ausgeben';
                                @endphp
                                <button wire:click="issueVouchers({{ $selectedPerson->id }}, {{ $day }})"
                                    class="mt-2 w-full rounded bg-blue-500 px-3 py-1 text-sm text-white hover:bg-blue-600"
                                    title="{{ $isSingleMode ? '1' : $availableCount }} {{ $settings ? $settings->getVoucherLabel() : 'Voucher' }} ausgeben">
                                    {{ $buttonText }}
                                </button>
                            @elseif($selectedPerson->getAvailableVouchersForDay($day) > 0)
                                <div class="mt-2 rounded bg-gray-200 px-3 py-1 text-center text-sm text-gray-600">
                                    Nicht erlaubt
                                </div>
                            @endif
                        </div>
                    @endfor
                </div>

                @if ($selectedPerson->remarks)
                    <div class="rounded-lg bg-yellow-50 p-4">
                        <h4 class="mb-2 font-medium text-yellow-800">Bemerkung</h4>
                        <p class="text-yellow-700">{{ $selectedPerson->remarks }}</p>
                    </div>
                @endif
            </div>

            <!-- Band Members -->
            @if (count($bandMembers) > 0)
                <div class="rounded-lg bg-white p-6 shadow-md">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Bandmitglieder: {{ $selectedPerson->band->band_name }}</h3>
                        @if ($selectedPerson->band->all_present)
                            <span class="rounded-full bg-green-100 px-3 py-1 text-sm text-green-800">Alle
                                anwesend</span>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th wire:click="sortBy('first_name')"
                                        class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 hover:bg-gray-100">
                                        Vorname
                                        @if ($sortBy === 'first_name')
                                            <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </th>
                                    <th wire:click="sortBy('last_name')"
                                        class="cursor-pointer px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 hover:bg-gray-100">
                                        Nachname
                                        @if ($sortBy === 'last_name')
                                            <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                        @endif
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Anwesend</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Kennzeichen</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        {{ $settings ? $settings->getVoucherLabel() : 'Voucher' }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Aktionen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($bandMembers as $member)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm">{{ $member->first_name }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $member->last_name }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <button wire:click="togglePresence({{ $member->id }})"
                                                class="{{ $member->present ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-700' }} rounded px-2 py-1 text-xs">
                                                {{ $member->present ? 'Ja' : 'Nein' }}
                                            </button>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if ($member->hasVehiclePlates())
                                                <button wire:click="showVehiclePlates({{ $member->id }})"
                                                    class="rounded bg-blue-500 px-2 py-1 text-xs text-white hover:bg-blue-600"
                                                    title="Kennzeichen verwalten">
                                                    🚗 {{ $member->vehiclePlates->count() }}
                                                </button>
                                                <div class="mt-1 text-xs text-gray-600">
                                                    @foreach ($member->vehiclePlates as $plate)
                                                        <div class="font-mono">{{ $plate->license_plate }}</div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <button wire:click="showVehiclePlates({{ $member->id }})"
                                                    class="rounded bg-gray-400 px-2 py-1 text-xs text-white hover:bg-gray-500"
                                                    title="Kennzeichen hinzufügen">
                                                    🚗 +
                                                </button>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            {{ $member->{"voucher_day_$currentDay"} }}/{{ $member->{"voucher_issued_day_$currentDay"} }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="flex space-x-2">
                                                @php
                                                    $nextAvailableDay = $this->getNextAvailableVoucherDay($member);
                                                    $isSingleMode = $settings && $settings->isSingleVoucherMode();
                                                    $voucherLabel = $settings
                                                        ? $settings->getVoucherLabel()
                                                        : 'Voucher';
                                                @endphp

                                                @if ($nextAvailableDay)
                                                    @php
                                                        $availableCount = $member->getAvailableVouchersForDay(
                                                            $nextAvailableDay,
                                                        );
                                                        $buttonText = $isSingleMode ? 'Ausgeben' : 'Alle';
                                                    @endphp
                                                    <button
                                                        wire:click="issueVouchers({{ $member->id }}, {{ $nextAvailableDay }})"
                                                        class="rounded bg-blue-500 px-2 py-1 text-xs text-white hover:bg-blue-600"
                                                        title="{{ $isSingleMode ? '1' : $availableCount }} {{ $voucherLabel }} ausgeben">
                                                        {{ $buttonText }}
                                                    </button>
                                                @endif

                                                <button wire:click="selectPerson({{ $member->id }})"
                                                    class="rounded bg-gray-500 px-2 py-1 text-xs text-white hover:bg-gray-600">
                                                    Details
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif

        <!-- Selected Band Details (from band list) -->
        @if ($showBandList && $selectedBand)
            <div class="rounded-lg bg-white p-6 shadow-md">
                <h3 class="mb-4 text-lg font-semibold">{{ $selectedBand->band_name }} - Mitglieder
                    ({{ $selectedBand->members->count() }})</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Anwesend
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                    {{ $settings ? $settings->getVoucherLabel() : 'Voucher' }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Aktionen
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($selectedBand->members as $member)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium">{{ $member->full_name }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <button wire:click="togglePresence({{ $member->id }})"
                                            class="{{ $member->present ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-700' }} rounded px-2 py-1 text-xs">
                                            {{ $member->present ? 'Ja' : 'Nein' }}
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ $member->{"voucher_day_$currentDay"} }}/{{ $member->{"voucher_issued_day_$currentDay"} }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <button wire:click="selectPerson({{ $member->id }})"
                                            class="rounded bg-blue-500 px-2 py-1 text-xs text-white hover:bg-blue-600">
                                            Details
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <!-- Stage Selection Modal -->
    @if ($showStageModal)
        <div class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
            <div class="relative top-20 mx-auto w-11/12 rounded-md border bg-white p-5 shadow-lg md:w-1/2">
                <div class="mt-3">
                    <h3 class="mb-4 text-lg font-medium">Bühne für {{ $voucherAmount }} Bon auswählen</h3>

                    <!-- Bühnen-Auswahl mit Verkaufszahlen -->
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

    <!-- Gäste Modal für BackstageControl -->
    @if ($showGuestsModal && $selectedPersonForGuests)
        <div class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
            <div
                class="relative top-4 mx-auto max-h-screen w-11/12 max-w-6xl overflow-y-auto rounded-md border bg-white p-6 shadow-lg">
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
                    </div>
                </div>

                <!-- Kompakte Gäste-Liste für BackstageControl -->
                @if ($selectedPersonForGuests->responsibleFor->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Name
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Backstage</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        {{ $settings ? $settings->getVoucherLabel() : 'Voucher' }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                        Aktionen</th>
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
                                            <button wire:click="togglePresence({{ $guest->id }})"
                                                class="{{ $guest->present ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }} inline-flex items-center rounded-full px-3 py-1 text-xs font-medium transition-colors duration-200"
                                                title="Klicken um Status zu ändern">
                                                {{ $guest->present ? '✓ Anwesend' : '✗ Abwesend' }}
                                            </button>
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
                                                    $voucherLabel = $settings
                                                        ? $settings->getVoucherLabel()
                                                        : 'Voucher';
                                                @endphp

                                                @if ($nextAvailableDay)
                                                    <button
                                                        wire:click="issueVouchers({{ $guest->id }}, {{ $nextAvailableDay }})"
                                                        class="rounded bg-blue-500 px-2 py-1 text-xs text-white hover:bg-blue-600"
                                                        title="{{ $isSingleMode ? '1' : 'Alle verfügbaren' }} {{ $voucherLabel }} ausgeben">
                                                        {{ $isSingleMode ? '1' : 'Alle' }}
                                                    </button>
                                                @endif

                                                <button wire:click="selectPerson({{ $guest->id }})"
                                                    class="rounded bg-gray-500 px-2 py-1 text-xs text-white hover:bg-gray-600">
                                                    Details
                                                </button>
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

    @include('components.vehicle-plates-modal')
</div>

@script
    <script>
        $wire.on('search-cleared', () => {
            const searchInput = document.querySelector('input[wire\\:model\\.live\\.debounce\\.300ms="search"]');
            const bandSearchInput = document.querySelector(
                'input[wire\\:model\\.live\\.debounce\\.300ms="bandSearch"]');

            if (searchInput) {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
            }

            if (bandSearchInput) {
                bandSearchInput.value = '';
                bandSearchInput.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
            }
        });

        $wire.on('refresh-component', () => {
            // Force Livewire to completely re-render the component
            window.Livewire.find($wire.__instance.id).call('$refresh');
        });

        // LocalStorage für Bühnenauswahl
        window.addEventListener('DOMContentLoaded', function() {
            // Beim Laden der Seite gespeicherte Bühne laden
            const savedStageId = localStorage.getItem('backstage_purchase_stage_id');
            if (savedStageId && savedStageId !== 'null') {
                $wire.set('purchaseStageId', parseInt(savedStageId));
            }
        });

        // Livewire Event Listener für Stage-Änderungen
        $wire.on('stage-selected', (stageId) => {
            if (stageId && stageId !== null) {
                localStorage.setItem('backstage_purchase_stage_id', stageId);
            } else {
                localStorage.removeItem('backstage_purchase_stage_id');
            }
        });
    </script>
@endscript
