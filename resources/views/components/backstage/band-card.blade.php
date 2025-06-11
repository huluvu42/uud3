{{-- resources/views/components/backstage/band-card.blade.php --}}

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
                @include('components.backstage.performance-day-info', [
                    'band' => $band,
                    'day' => $day,
                    'settings' => $settings
                ])
            @endif
        @endfor
    </div>
</div>
