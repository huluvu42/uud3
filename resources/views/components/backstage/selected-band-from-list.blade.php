{{-- resources/views/components/backstage/selected-band-from-list.blade.php --}}
{{-- Detailansicht für eine ausgewählte Band aus der Tagesliste --}}

<div class="mb-6 rounded-lg border-l-4 border-blue-500 bg-white p-6 shadow-md">
    <div class="mb-6 flex items-start justify-between">
        <div class="flex-1">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-xl font-bold text-blue-700">
                    🎵 {{ $selectedBand->band_name }}
                </h3>

                @php $bandStatus = $this->getBandStatusForToday($selectedBand) @endphp
                <div class="{{ $bandStatus['class'] }} rounded-lg border-2 px-4 py-2 text-lg font-bold">
                    {{ $bandStatus['text'] }}
                </div>
            </div>

            @if ($selectedBand->stage)
                <p class="text-gray-600 mb-2">🎪 Bühne: {{ $selectedBand->stage->name }}</p>
            @endif

            {{-- Performance-Tage anzeigen --}}
            <div class="mb-3 flex flex-wrap gap-2">
                @for ($day = 1; $day <= 4; $day++)
                    @if ($selectedBand->{"plays_day_$day"})
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium
                            {{ $day == $currentDay ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $settings ? $settings->getDayLabel($day) : "Tag $day" }}
                        </span>
                    @endif
                @endfor
            </div>
        </div>

        <div class="flex flex-col items-end space-y-2">
            @if ($selectedBand->all_present)
                <span class="rounded-full bg-green-100 px-3 py-1 text-sm text-green-800">Alle anwesend</span>
            @endif

            {{-- Close Button --}}
            <button wire:click="clearSelectedBand" 
                class="rounded bg-gray-500 px-3 py-1 text-sm text-white hover:bg-gray-600"
                title="Auswahl aufheben">
                ✕ Schließen
            </button>
        </div>
    </div>

    {{-- Band-Mitglieder laden und anzeigen --}}
    @if ($selectedBand->members && count($selectedBand->members) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-blue-700">
                            Mitglied / Gast
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-blue-700">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-blue-700">Aktionen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach ($selectedBand->members as $member)
                        <tr class="hover:bg-blue-50">
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium text-gray-900">
                                    {{ $member->first_name }} {{ $member->last_name }}
                                </div>
                                
                                {{-- Gast-Info falls vorhanden --}}
                                @php $memberGuest = $this->getMemberGuest($member); @endphp
                                @if ($memberGuest)
                                    <div class="mt-1 flex items-center justify-between text-xs text-blue-600">
                                        <span>👥 Gast: {{ $memberGuest->first_name }} {{ $memberGuest->last_name }}</span>
                                        <button wire:click="showGuestDeleteModal({{ $memberGuest->id }})"
                                            class="ml-2 text-red-500 hover:text-red-700" title="Gast entfernen">
                                            🗑️
                                        </button>
                                    </div>
                                @endif

                                {{-- Fahrzeug-Info falls vorhanden --}}
                                @if ($member->vehiclePlates && count($member->vehiclePlates) > 0)
                                    <div class="mt-1 text-xs text-green-600">
                                        🚗 {{ $member->vehiclePlates->pluck('plate')->join(', ') }}
                                    </div>
                                @endif
                            </td>
                            
                            <td class="px-4 py-3 text-sm">
                                {{-- Anwesenheits-Status --}}
                                @if ($member->{"present_day_$currentDay"})
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                        ✓ Anwesend
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                        ✗ Nicht da
                                    </span>
                                @endif

                                {{-- Wristband-Status --}}
                                @if ($member->wristband_issued)
                                    <div class="mt-1">
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                            🎫 Armband
                                        </span>
                                    </div>
                                @endif
                            </td>
                            
                            <td class="px-4 py-3 text-sm">
                                <div class="flex flex-col space-y-2">
                                    {{-- Anwesenheit togglen --}}
                                    @if ($member->{"present_day_$currentDay"})
                                        <button wire:click="markAbsent({{ $member->id }})"
                                            class="w-full rounded bg-red-500 px-3 py-1 text-xs text-white hover:bg-red-600">
                                            Als Abwesend markieren
                                        </button>
                                    @else
                                        <button wire:click="markPresent({{ $member->id }})"
                                            class="w-full rounded bg-green-500 px-3 py-1 text-xs text-white hover:bg-green-600">
                                            Als Anwesend markieren
                                        </button>
                                    @endif

                                    {{-- Wristband --}}
                                    @if (!$member->wristband_issued)
                                        <button wire:click="issueWristband({{ $member->id }})"
                                            class="w-full rounded bg-blue-500 px-3 py-1 text-xs text-white hover:bg-blue-600">
                                            Armband ausgeben
                                        </button>
                                    @endif

                                    {{-- Voucher --}}
                                    @php
                                        $remainingVouchers = $this->getRemainingVouchers($member->id, $currentDay);
                                    @endphp
                                    @if ($remainingVouchers > 0)
                                        <button wire:click="issueVouchers({{ $member->id }}, {{ $currentDay }})"
                                            class="w-full rounded bg-purple-500 px-3 py-1 text-xs text-white hover:bg-purple-600">
                                            {{ $settings ? $settings->getVoucherLabel() : 'Voucher' }} ({{ $remainingVouchers }})
                                        </button>
                                    @endif

                                    {{-- Gast hinzufügen --}}
                                    @if (!$memberGuest)
                                        <button wire:click="showGuestCreateModal({{ $member->id }})"
                                            class="w-full rounded bg-indigo-500 px-3 py-1 text-xs text-white hover:bg-indigo-600">
                                            Gast hinzufügen
                                        </button>
                                    @endif

                                    {{-- Person Details --}}
                                    <button wire:click="showPersonDetailsModal({{ $member->id }})"
                                        class="w-full rounded bg-gray-500 px-3 py-1 text-xs text-white hover:bg-gray-600">
                                        Details ansehen
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Band-Aktionen --}}
        <div class="mt-6 flex flex-wrap gap-3">
            <button wire:click="markAllPresent({{ $selectedBand->id }})"
                class="rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                Alle als anwesend markieren
            </button>
            
            <button wire:click="issueAllWristbands({{ $selectedBand->id }})"
                class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                Alle Armbänder ausgeben
            </button>

            @if ($settings && $selectedBand->stage)
                <button wire:click="openStageModal({{ $selectedBand->stage->id }})"
                    class="rounded bg-purple-600 px-4 py-2 text-white hover:bg-purple-700">
                    {{ $settings->getVoucherLabel() }} für Bühne
                </button>
            @endif
        </div>

    @else
        <div class="text-center py-8 text-gray-500">
            <p>Keine Mitglieder gefunden oder Band-Daten noch nicht vollständig geladen.</p>
            <button wire:click="refreshBandData({{ $selectedBand->id }})"
                class="mt-2 rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600">
                Daten neu laden
            </button>
        </div>
    @endif
</div>
