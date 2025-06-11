<div class="mb-6 rounded-lg bg-white p-6 shadow-md">
    <h3 class="mb-4 text-lg font-semibold">Band-Suchergebnisse ({{ count($bandSearchResults) }} Bands)</h3>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
        style="max-height: calc(100vh - 400px); overflow-y: auto;">
        @foreach ($bandSearchResults as $band)
            <div wire:click="selectBandFromSearch({{ $band->id }})"
                wire:loading.class="pointer-events-none opacity-75" wire:target="selectBandFromSearch"
                class="cursor-pointer rounded-lg border border-gray-200 p-4 hover:border-purple-300 hover:bg-gray-50"
                wire:key="band-search-{{ $band->id }}">

                @php $bandStatus = $this->getBandStatusForToday($band) @endphp
                <div class="mb-2 flex items-start justify-between">
                    <div class="text-lg font-medium text-purple-700">{{ $band->band_name }}</div>
                    <span class="{{ $bandStatus['class'] }} rounded-full border px-3 py-1 text-sm font-medium">
                        {{ $bandStatus['text'] }}
                    </span>
                </div>

                <div class="mb-2 text-sm text-gray-600">
                    🎪 Bühne: {{ $band->stage->name ?? 'Keine Bühne' }}
                </div>

                <div class="text-sm text-gray-600">
                    👥 {{ $band->members->count() }} Mitglieder
                    @if ($band->all_present)
                        <span class="ml-2 rounded bg-green-100 px-2 py-0.5 text-xs text-green-800">Alle da</span>
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
                                        $latestArrivalTime = $performanceDateTime->subMinutes($arrivalMinutes);
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
