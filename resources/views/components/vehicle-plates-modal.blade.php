{{-- resources/views/components/vehicle-plates-modal.blade.php --}}

<!-- Kennzeichen Modal -->
@if ($showVehiclePlatesModal && $selectedPersonForPlates)
    <div class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
        <div class="relative top-10 mx-auto w-11/12 max-w-2xl rounded-md border bg-white p-6 shadow-lg">
            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-xl font-semibold text-gray-900">
                    🚗 Kennzeichen von {{ $selectedPersonForPlates->full_name }}
                </h3>
                <button wire:click="closeVehiclePlatesModal" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Flash Messages im Modal -->
            @if (session()->has('success'))
                <div class="mb-4 rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session()->has('error'))
                <div class="mb-4 rounded border border-red-400 bg-red-100 px-4 py-3 text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Neues Kennzeichen hinzufügen / Bearbeiten -->
            <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <h4 class="mb-3 font-medium text-gray-900">
                    {{ $editingPlateId ? 'Kennzeichen bearbeiten' : 'Neues Kennzeichen hinzufügen' }}
                </h4>

                <div class="flex gap-3">
                    <div class="flex-1">
                        <input type="text" wire:model.live="newLicensePlate"
                            placeholder="z.B. AB-CD 123 oder AB CD 123"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-center font-mono uppercase focus:outline-none focus:ring-2 focus:ring-blue-500"
                            maxlength="20" style="letter-spacing: 1px;">
                        @error('newLicensePlate')
                            <span class="text-sm text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex gap-2">
                        @if ($editingPlateId)
                            <button wire:click="updateVehiclePlate"
                                class="rounded bg-green-500 px-4 py-2 text-white hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500"
                                {{ !$newLicensePlate ? 'disabled' : '' }}>
                                Aktualisieren
                            </button>
                            <button wire:click="cancelPlateEdit"
                                class="rounded bg-gray-500 px-4 py-2 text-white hover:bg-gray-600">
                                Abbrechen
                            </button>
                        @else
                            <button wire:click="addVehiclePlate"
                                class="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                {{ !$newLicensePlate ? 'disabled' : '' }}>
                                Hinzufügen
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Limit-Anzeige -->
                <div class="mt-2 text-xs text-gray-500">
                    {{ $selectedPersonForPlates->vehiclePlates->count() }}/3 Kennzeichen verwendet
                </div>
            </div>

            <!-- Bestehende Kennzeichen -->
            <div class="mb-4">
                <h4 class="mb-3 font-medium text-gray-900">Eingetragene Kennzeichen</h4>

                @if ($selectedPersonForPlates->vehiclePlates->count() > 0)
                    <div class="space-y-2">
                        @foreach ($selectedPersonForPlates->vehiclePlates as $plate)
                            <div
                                class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-3">
                                <div class="flex items-center">
                                    <div class="mr-3 flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">
                                        <span class="text-sm">🚗</span>
                                    </div>
                                    <div>
                                        <div class="font-mono text-lg font-medium tracking-wider text-gray-900">
                                            {{ $plate->license_plate }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Eingetragen: {{ $plate->created_at->format('d.m.Y H:i') }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    @if ($editingPlateId !== $plate->id)
                                        <button wire:click="editVehiclePlate({{ $plate->id }})"
                                            class="rounded bg-yellow-500 px-3 py-1 text-xs text-white hover:bg-yellow-600">
                                            Bearbeiten
                                        </button>
                                    @endif

                                    <button wire:click="deleteVehiclePlate({{ $plate->id }})"
                                        wire:confirm="Kennzeichen '{{ $plate->license_plate }}' wirklich löschen?"
                                        class="rounded bg-red-500 px-3 py-1 text-xs text-white hover:bg-red-600">
                                        Löschen
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center">
                        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                            <span class="text-2xl">🚗</span>
                        </div>
                        <h3 class="mb-2 text-sm font-medium text-gray-900">Keine Kennzeichen eingetragen</h3>
                        <p class="text-xs text-gray-500">
                            Fügen Sie das erste Kennzeichen für {{ $selectedPersonForPlates->full_name }} hinzu.
                        </p>
                    </div>
                @endif
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end border-t border-gray-200 pt-4">
                <button wire:click="closeVehiclePlatesModal"
                    class="rounded bg-gray-500 px-4 py-2 text-white hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Schließen
                </button>
            </div>
        </div>
    </div>
@endif
