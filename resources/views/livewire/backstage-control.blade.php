{{-- resources/views/livewire/backstage-control.blade.php - REFACTORED --}}

<div class="container mx-auto px-4 py-8">
    @include('partials.navigation')

    <div class="mx-auto mt-6 max-w-7xl">
        <!-- Flash Messages -->
        @include('components.flash-messages')

        <!-- Header Section -->
        @include('components.backstage.header-section', [
            'search' => $search,
            'bandSearch' => $bandSearch,
            'stages' => $stages,
            'settings' => $settings,
            'currentDay' => $currentDay,
            'voucherAmount' => $voucherAmount,
            'purchaseStageId' => $purchaseStageId,
            'stageFilter' => $stageFilter,
        ])

        <!-- Band Search Results -->
        @if (count($bandSearchResults) > 0)
            @include('components.backstage.band-search-results', [
                'bandSearchResults' => $bandSearchResults,
                'settings' => $settings,
            ])
        @endif

        <!-- Person Search Results -->
        @if (count($searchResults) > 0)
            @include('components.backstage.person-search-results', [
                'searchResults' => $searchResults,
                'settings' => $settings,
                'currentDay' => $currentDay,
            ])
        @elseif($showBandList && $todaysBands->count() > 0)
            <!-- Today's Bands -->
            @include('components.backstage.todays-bands', [
                'todaysBands' => $todaysBands,
                'settings' => $settings,
                'currentDay' => $currentDay,
                'stageFilter' => $stageFilter,
                'stages' => $stages,
                'selectedBand' => $selectedBand,
            ])
        @elseif(!$search && !$bandSearch && !$showBandList)
            <!-- Welcome Message -->
            @include('components.backstage.welcome-message')
        @endif

        <!-- Selected Band from Search -->
        @if ($selectedBandFromSearch)
            @include('components.backstage.selected-band-details', [
                'selectedBandFromSearch' => $selectedBandFromSearch,
                'bandMembers' => $bandMembers,
                'settings' => $settings,
                'currentDay' => $currentDay,
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
            ])
        @endif

        <!-- Selected Person Details (Legacy - wird durch Modal ersetzt) -->
        @if ($selectedPerson)
            @include('components.backstage.selected-person-details', [
                'selectedPerson' => $selectedPerson,
                'bandMembers' => $bandMembers,
                'settings' => $settings,
                'currentDay' => $currentDay,
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
            ])
        @endif

        <!-- Selected Band Details from Band List -->
        @if ($showBandList && $selectedBand)
            @include('components.backstage.selected-band-from-list', [
                'selectedBand' => $selectedBand,
                'settings' => $settings,
                'currentDay' => $currentDay,
            ])
        @endif
    </div>

    <!-- Modals -->
    @include('components.backstage.modals', [
        'showStageModal' => $showStageModal,
        'stages' => $stages,
        'settings' => $settings,
        'currentDay' => $currentDay,
        'voucherAmount' => $voucherAmount,
        'purchaseStageId' => $purchaseStageId,
        'showGuestsModal' => $showGuestsModal,
        'selectedPersonForGuests' => $selectedPersonForGuests,
        'showGuestCreateModal' => $showGuestCreateModal,
        'selectedMemberForGuest' => $selectedMemberForGuest,
        'showGuestDeleteModal' => $showGuestDeleteModal,
        'guestToDelete' => $guestToDelete,
        'showPersonDetailsModal' => $showPersonDetailsModal,
        'selectedPersonForDetails' => $selectedPersonForDetails,
    ])

    <!-- JavaScript -->
    @include('components.backstage.scripts')
</div>
