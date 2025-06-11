{{-- resources/views/components/backstage/person-info-section.blade.php --}}

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
        @elseif ($this->canMemberHaveGuest($person))
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
