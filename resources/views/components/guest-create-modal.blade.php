{{-- resources/views/components/guest-create-modal.blade.php --}}

@props(['show' => false, 'selectedMember' => null, 'settings' => null])

@if ($show && $selectedMember)
    <div class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
        <div class="relative top-20 mx-auto w-11/12 rounded-md border bg-white p-5 shadow-lg md:w-1/3">
            <div class="mt-3">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-medium">
                        Gast für {{ $selectedMember->full_name }} hinzufügen
                    </h3>
                    <button wire:click="closeGuestCreateModal"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mb-4 rounded-lg bg-blue-50 p-3">
                    <div class="text-sm text-blue-800">
                        <div class="mb-1">
                            <strong>Band:</strong> {{ $selectedMember->band->band_name }}
                        </div>
                        <div class="mb-1">
                            <strong>Bühne:</strong> {{ $selectedMember->band->stage->name }}
                        </div>
                        <div class="text-xs text-blue-600">
                            <strong>Backstage-Zugang:</strong> Nur an Spieltagen der Band
                        </div>
                    </div>
                </div>

                <form wire:submit.prevent="saveGuest">
                    <div class="mb-4">
                        <label for="guest_first_name" class="block text-sm font-medium text-gray-700">
                            Vorname *
                        </label>
                        <input type="text" wire:model="guest_first_name" id="guest_first_name"
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required autocomplete="given-name">
                        @error('guest_first_name')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="guest_last_name" class="block text-sm font-medium text-gray-700">
                            Nachname *
                        </label>
                        <input type="text" wire:model="guest_last_name" id="guest_last_name"
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required autocomplete="family-name">
                        @error('guest_last_name')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Automatische Berechtigung Anzeige -->
                    <div class="mb-4 rounded-lg bg-gray-50 p-3">
                        <h4 class="mb-2 text-sm font-medium text-gray-700">Automatische Berechtigung:</h4>
                        <div class="grid grid-cols-4 gap-2 text-xs">
                            @for ($day = 1; $day <= 4; $day++)
                                <div class="text-center">
                                    <div class="mb-1 font-medium">
                                        {{ $settings ? $settings->getDayLabel($day) : "T$day" }}
                                    </div>
                                    @if ($selectedMember->band->{"plays_day_$day"})
                                        <div class="rounded bg-green-100 px-2 py-1 text-green-800">
                                            ✓ Backstage
                                        </div>
                                    @else
                                        <div class="rounded bg-gray-100 px-2 py-1 text-gray-500">
                                            ✗ Kein Zugang
                                        </div>
                                    @endif
                                </div>
                            @endfor
                        </div>

                        <div class="mt-2 text-xs text-gray-600">
                            <strong>Hinweis:</strong> Gast kann nur an Tagen anwesend gesetzt werden, an denen die Band
                            spielt.
                        </div>
                    </div>

                    <!-- Form Buttons -->
                    <div class="flex justify-end space-x-2">
                        <button type="button" wire:click="closeGuestCreateModal"
                            class="rounded bg-gray-500 px-4 py-2 font-bold text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Abbrechen
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed" wire:target="saveGuest"
                            class="rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-50">
                            <span wire:loading.remove wire:target="saveGuest">
                                👥 Gast hinzufügen
                            </span>
                            <span wire:loading wire:target="saveGuest">
                                👥 Erstelle Gast...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

{{-- Keyboard Shortcuts für bessere UX --}}
<script>
    document.addEventListener('livewire:initialized', () => {
        // ESC zum Schließen
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && @this.showGuestCreateModal) {
                @this.call('closeGuestCreateModal');
            }
        });

        // Auto-Focus auf erstes Eingabefeld
        Livewire.hook('morph.updated', ({
            el,
            component
        }) => {
            if (@this.showGuestCreateModal) {
                setTimeout(() => {
                    const firstInput = document.getElementById('guest_first_name');
                    if (firstInput) {
                        firstInput.focus();
                    }
                }, 100);
            }
        });
    });
</script>
