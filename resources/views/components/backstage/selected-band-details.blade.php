<div class="mb-6 rounded-lg border-l-4 border-purple-500 bg-white p-6 shadow-md">
    <div class="mb-6 flex items-start justify-between">
        <div class="flex-1">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-xl font-bold text-purple-700">
                    🎵 {{ $selectedBandFromSearch->band_name }}
                </h3>

                @php $bandStatus = $this->getBandStatusForToday($selectedBandFromSearch) @endphp
                <div class="{{ $bandStatus['class'] }} rounded-lg border-2 px-4 py-2 text-lg font-bold">
                    {{ $bandStatus['text'] }}
                </div>
            </div>

            @if ($selectedBandFromSearch->stage)
                <p class="text-gray-600">🎪 Bühne: {{ $selectedBandFromSearch->stage->name }}</p>
            @endif
        </div>

        @if ($selectedBandFromSearch->all_present)
            <span class="rounded-full bg-green-100 px-3 py-1 text-sm text-green-800">Alle anwesend</span>
        @endif
    </div>

    <!-- Band Members Table (vereinfacht) -->
    @if (count($bandMembers) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-purple-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-purple-700">
                            Mitglied / Gast
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-purple-700">Anwesend</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-purple-700">Aktionen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach ($bandMembers as $member)
                        <tr class="hover:bg-purple-50">
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium text-gray-900">{{ $member->first_name }}
                                    {{ $member->last_name }}</div>

                                @php $memberGuest = $this->getMemberGuest($member); @endphp
                                @if ($memberGuest)
                                    <div class="mt-1 flex items-center justify-between text-xs text-blue-600">
                                        <span>👥 Gast: {{ $memberGuest->first_name }}
                                            {{ $memberGuest->last_name }}</span>
                                        <button wire:click="deleteGuestWithConfirmation({{ $memberGuest->id }})"
                                            class="ml-2 text-red-600 hover:text-red-800" title="Gast löschen">✕</button>
                                    </div>
                                @elseif ($this->canMemberHaveGuest($member))
                                    <button wire:click="addGuestForMember({{ $member->id }})"
                                        class="mt-1 inline-flex items-center rounded bg-blue-50 px-2 py-1 text-xs text-blue-700 hover:bg-blue-100"
                                        title="Gast hinzufügen">
                                        👥 Gast hinzufügen
                                    </button>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-sm">
                                @php $hasBackstageToday = $member->{"backstage_day_{$currentDay}"}; @endphp
                                @if ($hasBackstageToday)
                                    <button wire:click="togglePresence({{ $member->id }})"
                                        class="{{ $member->present ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-700' }} rounded px-2 py-1 text-xs hover:opacity-80">
                                        {{ $member->present ? 'Ja' : 'Nein' }}
                                    </button>
                                @else
                                    <span
                                        class="inline-block cursor-not-allowed rounded bg-gray-200 px-2 py-1 text-xs text-gray-500">
                                        🚫 Kein Zugang
                                    </span>
                                @endif

                                @if ($memberGuest)
                                    @php $guestStatus = $this->getGuestStatusForToday($memberGuest); @endphp
                                    <div class="mt-1">
                                        @if ($guestStatus['can_toggle'])
                                            <button wire:click="toggleGuestPresence({{ $memberGuest->id }})"
                                                class="{{ $memberGuest->present ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-700' }} rounded px-2 py-1 text-xs hover:opacity-80">
                                                👥 {{ $memberGuest->present ? 'Ja' : 'Nein' }}
                                            </button>
                                        @else
                                            <span
                                                class="inline-block cursor-not-allowed rounded bg-gray-200 px-2 py-1 text-xs text-gray-500">
                                                👥 🚫
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-sm">
                                <div class="flex flex-col space-y-2">
                                    <div class="flex space-x-2">
                                        <button wire:click="selectPersonForDetails({{ $member->id }})"
                                            class="rounded bg-gray-500 px-2 py-1 text-xs text-white hover:bg-gray-600">
                                            Details
                                        </button>
                                    </div>

                                    @if ($memberGuest)
                                        <div class="flex space-x-2 border-t border-blue-100 pt-1">
                                            <button wire:click="selectPersonForDetails({{ $memberGuest->id }})"
                                                class="rounded bg-gray-400 px-2 py-1 text-xs text-white hover:bg-gray-500">
                                                👥 Details
                                            </button>
                                        </div>
                                    @endif
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
