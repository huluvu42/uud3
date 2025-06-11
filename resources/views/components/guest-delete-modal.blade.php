{{-- resources/views/components/guest-delete-modal.blade.php --}}

@props(['show' => false, 'guest' => null])

@if ($show && $guest)
    <div class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
        <div
            class="relative top-1/2 mx-auto w-11/12 max-w-md -translate-y-1/2 transform rounded-md border bg-white p-5 shadow-lg">
            <div class="mt-3">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-red-600">
                        🗑️ Gast löschen
                    </h3>
                    <button wire:click="closeGuestDeleteModal"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Warning Icon und Message -->
                <div class="mb-4 flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-lg font-medium text-gray-900">
                            Gast wirklich löschen?
                        </h4>
                        <p class="mt-1 text-sm text-gray-600">
                            Möchten Sie den Gast <strong>{{ $guest->full_name }}</strong> wirklich dauerhaft löschen?
                        </p>
                    </div>
                </div>

                <!-- Gast-Info -->
                <div class="mb-4 rounded-lg bg-blue-50 p-3">
                    <div class="text-sm text-blue-800">
                        <div class="mb-1">
                            <strong>👥 Gast:</strong> {{ $guest->full_name }}
                        </div>
                        @if ($guest->responsiblePerson)
                            <div class="mb-1">
                                <strong>👤 Verantwortlich:</strong> {{ $guest->responsiblePerson->full_name }}
                            </div>
                        @endif
                        @if ($guest->band)
                            <div class="mb-1">
                                <strong>🎵 Band:</strong> {{ $guest->band->band_name }}
                            </div>
                        @endif
                        @if ($guest->present)
                            <div class="text-sm text-orange-600">
                                <strong>⚠️ Hinweis:</strong> Gast ist aktuell als anwesend markiert
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Warning Message -->
                <div class="mb-4 rounded-lg bg-yellow-50 p-3">
                    <div class="text-sm text-yellow-800">
                        <strong>⚠️ Achtung:</strong> Diese Aktion kann nicht rückgängig gemacht werden.
                        Alle Daten des Gastes gehen verloren.
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3">
                    <button type="button" wire:click="closeGuestDeleteModal"
                        class="rounded bg-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Abbrechen
                    </button>
                    <button type="button" wire:click="confirmDeleteGuest" wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed" wire:target="confirmDeleteGuest"
                        class="rounded bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading.remove wire:target="confirmDeleteGuest">
                            🗑️ Gast löschen
                        </span>
                        <span wire:loading wire:target="confirmDeleteGuest">
                            🗑️ Lösche...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Keyboard Support -->
<script>
    document.addEventListener('livewire:initialized', () => {
        // ESC zum Schließen
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && @this.showGuestDeleteModal) {
                @this.call('closeGuestDeleteModal');
            }
        });
    });
</script>
