{{-- resources/views/components/backstage/header-section.blade.php --}}

<div class="mb-6 rounded-lg bg-white p-6 shadow-md">
    <!-- Vier-Spalten Layout -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">

        <!-- Personensuche -->
        <div>
            <h3 class="mb-3 text-lg font-semibold">Personensuche</h3>
            <div class="relative">
                <input type="text" wire:model.live.debounce.500ms="search" wire:focus="focusSearch"
                    placeholder="Vorname, Nachname, KFZ ..."
                    class="w-full rounded-md border border-gray-300 px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    id="search-input" autocomplete="off" ondblclick="@this.call('clearSearch')">

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

            @if ($search || $bandSearch)
                <div class="mt-2">
                    <button wire:click="clearAllSearches" class="text-xs text-gray-500 underline hover:text-gray-700">
                        🗑️ Alle Suchen zurücksetzen
                    </button>
                </div>
            @endif
        </div>

        <!-- Band-Suche -->
        <div>
            <h3 class="mb-3 text-lg font-semibold">Band-Suche</h3>
            <div class="relative">
                <input type="text" wire:model.live.debounce.500ms="bandSearch" wire:focus="focusBandSearch"
                    placeholder="Bandname..."
                    class="w-full rounded-md border border-gray-300 px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-purple-500"
                    id="band-search-input" autocomplete="off" ondblclick="@this.call('clearBandSearch')">

                @if ($bandSearch)
                    <button type="button" wire:click="clearBandSearch"
                        class="absolute right-2 top-1/2 -translate-y-1/2 transform text-gray-400 transition-colors duration-200 hover:text-gray-600"
                        title="Band-Suche löschen">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>
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
                    <div
                        class="mt-2 flex items-center justify-between gap-2 rounded border bg-blue-50 p-2 text-xs text-gray-600">
                        <div>
                            <strong>{{ $selectedStageObj->name }}</strong><br>
                            Heute: {{ $soldToday }} {{ $settings ? $settings->getVoucherLabel() : 'Bons' }}
                        </div>
                        <button wire:click="resetStageSelection"
                            class="rounded bg-gray-400 px-2 py-2 text-sm text-white hover:bg-gray-500"
                            title="Bühnen-Auswahl zurücksetzen">
                            ↻
                        </button>
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
