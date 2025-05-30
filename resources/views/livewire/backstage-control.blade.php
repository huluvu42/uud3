{{-- resources/views/livewire/backstage-control.blade.php --}}

<div>
    @include('partials.navigation')

    <div class="container mx-auto px-4 py-8">
        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <div class="container mx-auto" >
            <!-- Search and Actions -->
            <div class="flex flex-col lg:flex-row bg-white rounded-lg px-4 py-8 mb-6">
                <div class="w-full lg:w-1/2 p-4">
                <!-- Search -->
                    <h2 class="text-lg font-semibold mb-4">Personensuche</h2>
                    <!-- Suchfeld -->
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search"
                        placeholder="Vorname, Nachname oder Bandname..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
                <div class="w-full lg:w-1/4 p-4">
                    <!-- Voucher-Buttons -->

                    <button 
                        wire:click="initiatePurchase(0.5)"
                        class="flex-1 px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 {{ $voucherAmount == 0.5 ? 'ring-2 ring-blue-300' : '' }}"
                    >
                        0.5 Bon
                    </button>
                    <button 
                        wire:click="initiatePurchase(1.0)"
                        class="flex-1 px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 {{ $voucherAmount == 1.0 ? 'ring-2 ring-blue-300' : '' }}"
                    >
                        1 Bon
                    </button>
                    @if($purchaseStageId)
                                <button 
                                    wire:click="resetStageSelection"
                                    class="px-3 py-2 bg-gray-400 text-white rounded hover:bg-gray-500"
                                    title="Bühnen-Auswahl zurücksetzen"
                                >
                                    Reset
                                </button>
                    @endif
                                <!-- Aktuelle Bühnen-Auswahl anzeigen -->
                        @if($purchaseStageId)
                            @php $selectedStageObj = $stages->find($purchaseStageId) @endphp
                            <div class="text-sm text-gray-600 bg-gray-50 p-2 rounded">
                                <strong>Ausgewählte Bühne:</strong> {{ $selectedStageObj->name }}
                            </div>
                        @endif
                </div>
                <div class="w-full lg:w-1/4 p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold">Bands des Tages</h2>
                        <div class="flex space-x-2">
                            <select 
                                wire:model.live="stageFilter"
                                class="px-3 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="all">Alle Bühnen</option>
                                @foreach($stages as $stage)
                                    <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <button 
                        wire:click="showTodaysBands"
                        class="w-full px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600"
                    >
                        Bands für {{ $settings ? $settings->getDayLabel($currentDay) : "Tag $currentDay" }} anzeigen
                    </button>
                </div>
            </div>

            <!-- Results and Details -->
            
                <!-- Search Results / Band List (Full Width) -->
                @if(count($searchResults) > 0)
                    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                        <h2 class="text-lg font-semibold mb-4">Suchergebnisse</h2>
                        <div class="space-y-2 max-h-80 overflow-y-auto">
                            @foreach($searchResults as $person)
                                <div class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50" 
                                     wire:key="person-{{ $person->id }}-{{ $person->voucher_issued_day_1 }}-{{ $person->voucher_issued_day_2 }}-{{ $person->voucher_issued_day_3 }}-{{ $person->voucher_issued_day_4 }}-{{ $person->present ? 'present' : 'absent' }}">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <!-- Name und Gruppe/Band -->
                                            <div 
                                                wire:click="selectPerson({{ $person->id }})"
                                                class="font-medium text-lg cursor-pointer hover:text-blue-600 mb-1"
                                            >
                                                {{ $person->first_name }} {{ $person->last_name }}
                                            </div>
                                            @if($person->band)
                                                <div class="text-sm text-gray-600 mb-2">🎵 Band: {{ $person->band->band_name }}</div>
                                            @endif
                                            @if($person->group)
                                                <div class="text-sm text-gray-500 mb-2">👥 Gruppe: {{ $person->group->name }}</div>
                                            @endif
                                        </div>
                                        
                                        <!-- Backstage-Berechtigung für alle Tage -->
                                        <div class="mx-4">
                                            <div class="text-sm font-medium text-gray-700 mb-1">{{ $settings ? $settings->getBackstageLabel() : 'Backstage-Berechtigung' }}</div>
                                            <div class="flex space-x-2">
                                                @for($day = 1; $day <= 4; $day++)
                                                    <div class="text-center">
                                                        <div class="text-sm text-gray-500 mb-1">{{ $settings ? $settings->getDayLabel($day) : "Tag $day" }}</div>
                                                        @if($person->{"backstage_day_$day"})
                                                            <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center">
                                                                <span class="text-green-600 font-bold text-sm">✓</span>
                                                            </div>
                                                        @else
                                                            <div class="w-6 h-6 bg-red-100 rounded-full flex items-center justify-center">
                                                                <span class="text-red-600 font-bold text-sm">✗</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endfor
                                            </div>
                                        </div>
                                        
                                        <!-- Voucher-Übersicht für alle Tage -->
                                        <div class="mx-4">
                                            <div class="text-sm font-medium text-gray-700 mb-1">{{ $settings ? $settings->getVoucherLabel() : 'Voucher/Bons' }}</div>
                                            <div class="flex space-x-2">
                                                @for($day = 1; $day <= 4; $day++)
                                                    <div class="text-center">
                                                        <div class="text-sm text-gray-500 mb-1">{{ $settings ? $settings->getDayLabel($day) : "Tag $day" }}</div>
                                                        <div class="text-sm bg-gray-50 px-2 py-1 rounded">
                                                            <div class="font-medium text-blue-600">{{ $person->{"voucher_day_$day"} }}</div>
                                                            @if($day == $currentDay)
                                                                <div class="text-green-600 text-xs">{{ $person->{"voucher_issued_day_$day"} }} heute</div>
                                                            @else
                                                                <div class="text-gray-400 text-xs">{{ $person->{"voucher_issued_day_$day"} }} ausg.</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endfor
                                            </div>
                                        </div>
                                        
                                        <!-- Aktionen -->
                                        <div class="flex flex-col space-y-2 ml-4">
                                            <!-- Anwesenheit Toggle -->
                                            <button 
                                                wire:click="togglePresence({{ $person->id }})"
                                                class="px-3 py-2 rounded text-sm font-medium {{ $person->present ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-700' }}"
                                                title="{{ $person->present ? 'Als abwesend markieren' : 'Als anwesend markieren' }}"
                                            >
                                                {{ $person->present ? 'Anwesend' : 'Abwesend' }}
                                            </button>
                                            
                                            <!-- Voucher ausgeben - berücksichtigt Settings -->
                                            @php
                                                $nextAvailableDay = $this->getNextAvailableVoucherDay($person);
                                                $isSingleMode = $settings && $settings->isSingleVoucherMode();
                                                $voucherLabel = $settings ? $settings->getVoucherLabel() : 'Voucher';
                                            @endphp
                                            
                                            @if($nextAvailableDay)
                                                @php
                                                    $availableCount = $person->getAvailableVouchersForDay($nextAvailableDay);
                                                    $buttonText = $isSingleMode ? "1 $voucherLabel" : "$availableCount $voucherLabel";
                                                    $dayLabel = $settings ? $settings->getDayLabel($nextAvailableDay) : "Tag $nextAvailableDay";
                                                @endphp
                                                
                                                <button 
                                                    wire:click="issueVouchers({{ $person->id }}, {{ $nextAvailableDay }})"
                                                    class="px-3 py-2 bg-blue-500 text-white text-sm rounded hover:bg-blue-600"
                                                    title="{{ $isSingleMode ? '1' : 'Alle verfügbaren' }} {{ $voucherLabel }} für {{ $dayLabel }} ausgeben"
                                                >
                                                    {{ $buttonText }}
                                                </button>
                                                
                                                @if($nextAvailableDay != $currentDay)
                                                    <span class="text-xs text-orange-600 text-center">
                                                        ({{ $dayLabel }})
                                                    </span>
                                                @endif
                                            @else
                                                <span class="px-3 py-2 bg-gray-100 text-gray-500 text-sm rounded text-center">
                                                    Keine {{ $voucherLabel }}
                                                </span>
                                            @endif
                                            
                                            <!-- Details Button -->
                                            <button 
                                                wire:click="selectPerson({{ $person->id }})"
                                                class="px-3 py-1 bg-gray-500 text-white text-xs rounded hover:bg-gray-600"
                                            >
                                                Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                @elseif($showBandList && $todaysBands->count() > 0)
                    <!-- Today's Bands in the same area -->
                    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                        <h2 class="text-lg font-semibold mb-4">
                            Bands für {{ $settings ? $settings->getDayLabel($currentDay) : "Tag $currentDay" }}
                            @if($stageFilter !== 'all')
                                @php $selectedStageObj = $stages->find($stageFilter) @endphp
                                - {{ $selectedStageObj->name }}
                            @endif
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($todaysBands as $band)
                                <div 
                                    wire:click="selectBand({{ $band->id }})"
                                    class="p-4 border border-gray-200 rounded cursor-pointer hover:bg-gray-50 {{ $selectedBand && $selectedBand->id === $band->id ? 'ring-2 ring-blue-500' : '' }}"
                                >
                                    <div class="font-medium text-lg">{{ $band->band_name }}</div>
                                    <div class="text-sm text-gray-600 mb-2">
                                        Bühne: {{ $band->stage->name ?? 'Keine Bühne' }}
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        {{ $band->members->count() }} Mitglieder
                                        @if($band->all_present)
                                            <span class="ml-2 px-2 py-0.5 bg-green-100 text-green-800 rounded text-xs">Alle da</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Selected Person Details -->
                @if($selectedPerson)
                    <!-- Selected Person Info -->
                    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <h2 class="text-xl font-bold">{{ $selectedPerson->full_name }}</h2>
                                @if($selectedPerson->band)
                                    <p class="text-gray-600">Band: {{ $selectedPerson->band->band_name }}</p>
                                @endif
                                @if($selectedPerson->group)
                                    <p class="text-gray-600">Gruppe: {{ $selectedPerson->group->name }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <button 
                                    wire:click="togglePresence({{ $selectedPerson->id }})"
                                    class="px-4 py-2 rounded font-medium {{ $selectedPerson->present ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-700' }}"
                                >
                                    {{ $selectedPerson->present ? 'Anwesend' : 'Nicht anwesend' }}
                                </button>
                            </div>
                        </div>

                        <!-- Voucher Information -->
                        <div class="grid grid-cols-4 gap-4 text-sm">
                            @for($day = 1; $day <= 4; $day++)
                                <div class="bg-gray-50 p-3 rounded">
                                    <div class="font-medium mb-1">{{ $settings ? $settings->getDayLabel($day) : "Tag $day" }}</div>
                                    <div>Berechtigt: {{ $selectedPerson->{"voucher_day_$day"} }}</div>
                                    <div>Ausgegeben: {{ $selectedPerson->{"voucher_issued_day_$day"} }}</div>
                                    @if($selectedPerson->getAvailableVouchersForDay($day) > 0 && $settings && $settings->canIssueVouchersForDay($day, $currentDay))
                                        @php
                                            $isSingleMode = $settings->isSingleVoucherMode();
                                            $availableCount = $selectedPerson->getAvailableVouchersForDay($day);
                                            $buttonText = $isSingleMode ? '1 ausgeben' : 'Alle ausgeben';
                                        @endphp
                                        <button 
                                            wire:click="issueVouchers({{ $selectedPerson->id }}, {{ $day }})"
                                            class="mt-2 px-2 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600"
                                            title="{{ $isSingleMode ? '1' : $availableCount }} {{ $settings ? $settings->getVoucherLabel() : 'Voucher' }} ausgeben"
                                        >
                                            {{ $buttonText }}
                                        </button>
                                    @elseif($selectedPerson->getAvailableVouchersForDay($day) > 0)
                                        <div class="mt-2 px-2 py-1 bg-gray-200 text-gray-600 text-xs rounded text-center">
                                            Nicht erlaubt
                                        </div>
                                    @endif
                                </div>
                            @endfor
                        </div>

                        @if($selectedPerson->remarks)
                            <div class="mt-4 p-3 bg-yellow-50 rounded">
                                <strong>Bemerkung:</strong> {{ $selectedPerson->remarks }}
                            </div>
                        @endif
                    </div>

                    <!-- Band Members (if person is in a band) -->
                    @if(count($bandMembers) > 0)
                        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold">Bandmitglieder: {{ $selectedPerson->band->band_name }}</h3>
                                @if($selectedPerson->band->all_present)
                                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Alle anwesend</span>
                                @endif
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th 
                                                wire:click="sortBy('first_name')"
                                                class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100"
                                            >
                                                Vorname
                                                @if($sortBy === 'first_name')
                                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                                @endif
                                            </th>
                                            <th 
                                                wire:click="sortBy('last_name')"
                                                class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100"
                                            >
                                                Nachname
                                                @if($sortBy === 'last_name')
                                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                                @endif
                                            </th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Anwesend</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $settings ? $settings->getVoucherLabel() : 'Voucher' }} {{ $settings ? $settings->getDayLabel($currentDay) : "Tag $currentDay" }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($bandMembers as $member)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-3 py-2 text-sm">{{ $member->first_name }}</td>
                                                <td class="px-3 py-2 text-sm">{{ $member->last_name }}</td>
                                                <td class="px-3 py-2 text-sm">
                                                    <button 
                                                        wire:click="togglePresence({{ $member->id }})"
                                                        class="px-2 py-1 rounded text-xs {{ $member->present ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-700' }}"
                                                    >
                                                        {{ $member->present ? 'Ja' : 'Nein' }}
                                                    </button>
                                                </td>
                                                <td class="px-3 py-2 text-sm">
                                                    <div class="flex items-center space-x-2">
                                                        <span>{{ $member->{"voucher_day_$currentDay"} }}/{{ $member->{"voucher_issued_day_$currentDay"} }}</span>
                                                        @php
                                                            $nextAvailableDay = $this->getNextAvailableVoucherDay($member);
                                                            $isSingleMode = $settings && $settings->isSingleVoucherMode();
                                                            $voucherLabel = $settings ? $settings->getVoucherLabel() : 'Voucher';
                                                        @endphp
                                                        
                                                        @if($nextAvailableDay)
                                                            @php
                                                                $availableCount = $member->getAvailableVouchersForDay($nextAvailableDay);
                                                                $buttonText = $isSingleMode ? 'Ausgeben' : 'Alle';
                                                            @endphp
                                                            <button 
                                                                wire:click="issueVouchers({{ $member->id }}, {{ $nextAvailableDay }})"
                                                                class="px-2 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600"
                                                                title="{{ $isSingleMode ? '1' : $availableCount }} {{ $voucherLabel }} ausgeben"
                                                            >
                                                                {{ $buttonText }}
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                @elseif($showBandList && $selectedBand)
                    <!-- Selected Band Details -->
                    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                        <h3 class="text-lg font-semibold mb-4">{{ $selectedBand->band_name }} - Mitglieder</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Anwesend</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $settings ? $settings->getVoucherLabel() : 'Voucher' }}</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($selectedBand->members as $member)
                                        <tr>
                                            <td class="px-3 py-2 text-sm">{{ $member->full_name }}</td>
                                            <td class="px-3 py-2 text-sm">
                                                <button 
                                                    wire:click="togglePresence({{ $member->id }})"
                                                    class="px-2 py-1 rounded text-xs {{ $member->present ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-700' }}"
                                                >
                                                    {{ $member->present ? 'Ja' : 'Nein' }}
                                                </button>
                                            </td>
                                            <td class="px-3 py-2 text-sm">
                                                {{ $member->{"voucher_day_$currentDay"} }}/{{ $member->{"voucher_issued_day_$currentDay"} }}
                                            </td>
                                            <td class="px-3 py-2 text-sm">
                                                <button 
                                                    wire:click="selectPerson({{ $member->id }})"
                                                    class="px-2 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600"
                                                >
                                                    Details
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                @elseif(!$search)
                    <!-- Welcome Message -->
                    <div class="bg-white rounded-lg shadow-md p-8 text-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-600 mb-4">Festival Backstage Kontrolle</h2>
                        <p class="text-gray-500 mb-6">Verwenden Sie die Suche links, um Personen zu finden und zu verwalten.</p>
                        <div class="text-sm text-gray-400">
                            <p>• Suchen Sie nach Vor-, Nachname oder Bandname</p>
                            <p>• Markieren Sie Personen als anwesend</p>
                            <p>• Geben Sie Verzehrbons aus</p>
                            <p>• Verwalten Sie Bandmitglieder</p>
                        </div>
                    </div>
                @endif
            
        </div>

        <!-- Stage Selection Modal -->
        @if($showStageModal)
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/3 shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <h3 class="text-lg font-medium mb-4">Bühne für {{ $voucherAmount }} Bon auswählen</h3>
                        <select 
                            wire:model="purchaseStageId"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Bühne auswählen...</option>
                            @foreach($stages as $stage)
                                <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                            @endforeach
                        </select>
                        
                        <div class="flex justify-end space-x-2">
                            <button 
                                wire:click="$set('showStageModal', false)"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Abbrechen
                            </button>
                            <button 
                                wire:click="purchaseVouchers"
                                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                                {{ !$purchaseStageId ? 'disabled' : '' }}
                            >
                                {{ $voucherAmount }} Bon kaufen
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@script
<script>
$wire.on('search-cleared', () => {
    const searchInput = document.querySelector('input[wire\\:model\\.live\\.debounce\\.300ms="search"]');
    if (searchInput) {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
});

$wire.on('refresh-component', () => {
    // Force Livewire to completely re-render the component
    window.Livewire.find($wire.__instance.id).call('$refresh');
});
</script>
@endscript