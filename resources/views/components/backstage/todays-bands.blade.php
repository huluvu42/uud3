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
            <div wire:click="selectBandFromSearch({{ $band->id }})" wire:loading.class="pointer-events-none opacity-75"
                wire:target="selectBand"
                class="{{ $selectedBand && $selectedBand->id === $band->id ? 'ring-2 ring-blue-500 bg-blue-50' : '' }} cursor-pointer rounded-lg border border-gray-200 p-4 hover:bg-gray-50">

                @php $bandStatus = $this->getBandStatusForToday($band) @endphp
                <div class="mb-2 flex items-start justify-between">
                    <div class="text-lg font-medium">{{ $band->band_name }}</div>
                    <span class="{{ $bandStatus['class'] }} rounded-full border px-3 py-1 text-sm font-medium">
                        {{ $bandStatus['text'] }}
                    </span>
                </div>

                <div class="mb-2 text-sm text-gray-600">
                    Bühne: {{ $band->stage->name ?? 'Keine Bühne' }}
                </div>
                <div class="text-sm text-gray-600">
                    {{ $band->members->count() }} Mitglieder
                    @if ($band->all_present)
                        <span class="ml-2 rounded bg-green-100 px-2 py-0.5 text-xs text-green-800">Alle da</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
