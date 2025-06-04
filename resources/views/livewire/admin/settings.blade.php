{{-- resources/views/livewire/admin/settings.blade.php --}}

<div class="container mx-auto px-4 py-8">
    @include('partials.navigation')

    <div class="min-h-screen bg-gray-100 p-4">
        <div class="mx-auto max-w-4xl">

            <!-- Success Messages -->
            @if (session()->has('success'))
                <div class="mb-4 rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Festival Settings -->
            <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
                <h2 class="mb-4 text-lg font-semibold">Festival-Einstellungen {{ $year }}</h2>

                <form wire:submit.prevent="saveSettings">
                    <div class="grid grid-cols-2 gap-6">
                        <!-- Dates -->
                        <div>
                            <h3 class="mb-3 font-medium">Festival-Tage</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Tag 1</label>
                                    <input type="date" wire:model="day_1_date"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('day_1_date')
                                        <span class="text-sm text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Tag 2</label>
                                    <input type="date" wire:model="day_2_date"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('day_2_date')
                                        <span class="text-sm text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Tag 3</label>
                                    <input type="date" wire:model="day_3_date"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('day_3_date')
                                        <span class="text-sm text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Tag 4</label>
                                    <input type="date" wire:model="day_4_date"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('day_4_date')
                                        <span class="text-sm text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Wristband Colors -->
                        <div>
                            <h3 class="mb-3 font-medium">Bändchenfarben</h3>
                            <div class="space-y-4">
                                @foreach ([1, 2, 3, 4] as $day)
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-1">
                                            <label class="mb-1 block text-sm font-medium text-gray-700">Tag
                                                {{ $day }}</label>
                                            <div class="flex items-center space-x-2">
                                                <!-- Color Picker -->
                                                <input type="color"
                                                    wire:model="wristband_color_day_{{ $day }}"
                                                    class="h-10 w-12 cursor-pointer rounded border border-gray-300"
                                                    title="Farbe auswählen">
                                                <!-- Text Input für Farbnamen -->
                                                <input type="text"
                                                    wire:model="wristband_color_day_{{ $day }}"
                                                    class="flex-1 rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    placeholder="z.B. #FF0000 oder Rot">
                                                <!-- Farbvorschau -->
                                                <div class="h-10 w-10 rounded border-2 border-gray-300"
                                                    style="background-color: {{ $this->{'wristband_color_day_' . $day} }}"
                                                    title="Farbvorschau"></div>
                                            </div>
                                            @error('wristband_color_day_' . $day)
                                                <span class="text-sm text-red-500">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Voucher Settings -->
                    <div class="mt-6 border-t border-gray-200 pt-6">
                        <h3 class="mb-4 font-medium">Voucher-Regeln</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Voucher-Ausgabe
                                    erlaubt</label>
                                <select wire:model="voucher_issuance_rule"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="current_day_only">Nur am aktuellen Tag</option>
                                    <option value="current_and_past">Aktueller und vergangene Tage</option>
                                    <option value="all_days">Alle Tage</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">
                                    Bestimmt, für welche Tage Voucher ausgegeben werden können
                                </p>
                                @error('voucher_issuance_rule')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Voucher-Ausgabemodus</label>
                                <select wire:model="voucher_output_mode"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="single">Einzeln (1 Voucher pro Klick)</option>
                                    <option value="all_available">Alle verfügbaren auf einmal</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">
                                    Einzeln: Button zeigt "1 Voucher" und gibt nur einen aus<br>
                                    Alle: Button zeigt "X Voucher" und gibt alle verfügbaren aus
                                </p>
                                @error('voucher_output_mode')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Voucher-Kaufmodus -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Voucher-Kaufmodus</label>
                                <select wire:model="voucher_purchase_mode"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="stage_only">Nur Bühnen-Käufe (bisherige Variante)</option>
                                    <option value="person_only">Nur Person-Käufe (neue Variante)</option>
                                    <option value="both">Beide Varianten verfügbar</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">
                                    <strong>Bühnen-Käufe:</strong> Zentraler Bereich zum Kaufen für alle Bühnen<br>
                                    <strong>Person-Käufe:</strong> Buttons bei jeder Person für individuelle Käufe<br>
                                    <strong>Beide:</strong> Sowohl zentraler Bereich als auch Person-Buttons
                                </p>
                                @error('voucher_purchase_mode')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="rounded bg-blue-500 px-6 py-2 text-white hover:bg-blue-600"
                            wire:loading.attr="disabled" wire:loading.class="opacity-50">
                            <span wire:loading.remove>Einstellungen speichern</span>
                            <span wire:loading>Speichern...</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Day Labels -->
            <div class="rounded-lg bg-white p-6 shadow-md">
                <h2 class="mb-4 text-lg font-semibold">Tag-Bezeichnungen</h2>
                <p class="mb-4 text-sm text-gray-600">Passen Sie die Bezeichnungen für die Festival-Tage und Bereiche
                    an.</p>

                <form wire:submit.prevent="saveDayLabels">
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Tag Labels -->
                        <div>
                            <h3 class="mb-3 font-medium">Tag-Bezeichnungen</h3>
                            <div class="space-y-3">
                                @for ($day = 1; $day <= 4; $day++)
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-gray-700">Tag
                                            {{ $day }}</label>
                                        <input type="text" wire:model="day_{{ $day }}_label"
                                            placeholder="Tag {{ $day }}"
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        @error('day_' . $day . '_label')
                                            <span class="text-sm text-red-500">{{ $message }}</span>
                                        @enderror
                                    </div>
                                @endfor
                            </div>
                        </div>

                        <!-- Area Labels -->
                        <div>
                            <h3 class="mb-3 font-medium">Bereich-Bezeichnungen</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Voucher/Bons</label>
                                    <input type="text" wire:model="voucher_label" placeholder="Voucher/Bons"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('voucher_label')
                                        <span class="text-sm text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label
                                        class="mb-1 block text-sm font-medium text-gray-700">Backstage-Berechtigung</label>
                                    <input type="text" wire:model="backstage_label"
                                        placeholder="Backstage-Berechtigung"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    @error('backstage_label')
                                        <span class="text-sm text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="rounded bg-purple-500 px-6 py-2 text-white hover:bg-purple-600"
                            wire:loading.attr="disabled" wire:loading.class="opacity-50">
                            <span wire:loading.remove">Tag-Labels speichern</span>
                            <span wire:loading>Speichern...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Color Input Synchronisation
        document.addEventListener('DOMContentLoaded', function() {
            // Für jeden Tag die Synchronisation zwischen Color Picker und Text Input einrichten
            for (let day = 1; day <= 4; day++) {
                const colorInput = document.querySelector(
                    `input[wire\\:model="wristband_color_day_${day}"][type="color"]`);
                const textInput = document.querySelector(
                    `input[wire\\:model="wristband_color_day_${day}"][type="text"]`);

                if (colorInput && textInput) {
                    // Wenn Color Picker geändert wird, Text Input aktualisieren
                    colorInput.addEventListener('input', function() {
                        if (this.value.startsWith('#')) {
                            textInput.value = this.value;
                            textInput.dispatchEvent(new Event('input'));
                        }
                    });

                    // Wenn Text Input geändert wird und eine gültige Hex-Farbe ist, Color Picker aktualisieren
                    textInput.addEventListener('input', function() {
                        if (this.value.match(/^#[0-9A-Fa-f]{6}$/)) {
                            colorInput.value = this.value;
                        }
                    });
                }
            }
        });
    </script>
