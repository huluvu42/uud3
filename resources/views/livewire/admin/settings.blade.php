{{-- resources/views/livewire/admin/settings.blade.php --}}

<div class="container mx-auto px-4 py-8">
   @include('partials.navigation')

    <div class="min-h-screen bg-gray-100 p-4">
    <div class="max-w-4xl mx-auto">
         
        <!-- Success Messages -->
        @if (session()->has('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <!-- Festival Settings -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Festival-Einstellungen {{ $year }}</h2>
            
            <form wire:submit.prevent="saveSettings">
                <div class="grid grid-cols-2 gap-6">
                    <!-- Dates -->
                    <div>
                        <h3 class="font-medium mb-3">Festival-Tage</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tag 1</label>
                                <input 
                                    type="date" 
                                    wire:model="day_1_date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                @error('day_1_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tag 2</label>
                                <input 
                                    type="date" 
                                    wire:model="day_2_date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                @error('day_2_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tag 3</label>
                                <input 
                                    type="date" 
                                    wire:model="day_3_date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                @error('day_3_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tag 4</label>
                                <input 
                                    type="date" 
                                    wire:model="day_4_date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                @error('day_4_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Wristband Colors -->
                    <div>
                        <h3 class="font-medium mb-3">Bändchenfarben</h3>
                        <div class="space-y-4">
                            @foreach([1,2,3,4] as $day)
                                <div class="flex items-center space-x-3">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tag {{ $day }}</label>
                                        <div class="flex items-center space-x-2">
                                            <!-- Color Picker -->
                                            <input 
                                                type="color" 
                                                wire:model="wristband_color_day_{{ $day }}"
                                                class="w-12 h-10 border border-gray-300 rounded cursor-pointer"
                                                title="Farbe auswählen"
                                            >
                                            <!-- Text Input für Farbnamen -->
                                            <input 
                                                type="text" 
                                                wire:model="wristband_color_day_{{ $day }}"
                                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                placeholder="z.B. #FF0000 oder Rot"
                                            >
                                            <!-- Farbvorschau -->
                                            <div 
                                                class="w-10 h-10 rounded border-2 border-gray-300"
                                                style="background-color: {{ $this->{'wristband_color_day_' . $day} }}"
                                                title="Farbvorschau"
                                            ></div>
                                        </div>
                                        @error('wristband_color_day_' . $day) <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Voucher Settings -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="font-medium mb-4">Voucher-Regeln</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Voucher-Ausgabe erlaubt</label>
                            <select 
                                wire:model="voucher_issuance_rule"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="current_day_only">Nur am aktuellen Tag</option>
                                <option value="current_and_past">Aktueller und vergangene Tage</option>
                                <option value="all_days">Alle Tage</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">
                                Bestimmt, für welche Tage Voucher ausgegeben werden können
                            </p>
                            @error('voucher_issuance_rule') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Voucher-Ausgabemodus</label>
                            <select 
                                wire:model="voucher_output_mode"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="single">Einzeln (1 Voucher pro Klick)</option>
                                <option value="all_available">Alle verfügbaren auf einmal</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">
                                Einzeln: Button zeigt "1 Voucher" und gibt nur einen aus<br>
                                Alle: Button zeigt "X Voucher" und gibt alle verfügbaren aus
                            </p>
                            @error('voucher_output_mode') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <button 
                        type="submit"
                        class="px-6 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                    >
                        <span wire:loading.remove>Einstellungen speichern</span>
                        <span wire:loading>Speichern...</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Field Labels -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Feldbezeichnungen</h2>
            <p class="text-sm text-gray-600 mb-4">Hier können Sie die Bezeichnungen für verschiedene Felder anpassen.</p>
            
            <form wire:submit.prevent="saveLabels">
                <div class="grid grid-cols-2 gap-4">
                    @foreach([
                        'first_name' => 'Vorname',
                        'last_name' => 'Nachname',
                        'band_name' => 'Bandname',
                        'present' => 'Anwesend',
                        'voucher' => 'Verzehrbon',
                        'remarks' => 'Bemerkung',
                        'group' => 'Gruppe',
                        'stage' => 'Bühne'
                    ] as $key => $defaultLabel)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $defaultLabel }}</label>
                            <input 
                                type="text" 
                                value="{{ $fieldLabels[$key] ?? $defaultLabel }}"
                                wire:change="updateFieldLabel('{{ $key }}', $event.target.value)"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    <button 
                        type="submit"
                        class="px-6 py-2 bg-green-500 text-white rounded hover:bg-green-600"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                    >
                        <span wire:loading.remove>Labels speichern</span>
                        <span wire:loading>Speichern...</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Day Labels -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold mb-4">Tag-Bezeichnungen</h2>
            <p class="text-sm text-gray-600 mb-4">Passen Sie die Bezeichnungen für die Festival-Tage und Bereiche an.</p>
            
            <form wire:submit.prevent="saveDayLabels">
                <div class="grid grid-cols-2 gap-4">
                    <!-- Tag Labels -->
                    <div>
                        <h3 class="font-medium mb-3">Tag-Bezeichnungen</h3>
                        <div class="space-y-3">
                            @for($day = 1; $day <= 4; $day++)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tag {{ $day }}</label>
                                    <input 
                                        type="text" 
                                        wire:model="day_{{ $day }}_label"
                                        placeholder="Tag {{ $day }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                    @error('day_' . $day . '_label') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            @endfor
                        </div>
                    </div>

                    <!-- Area Labels -->
                    <div>
                        <h3 class="font-medium mb-3">Bereich-Bezeichnungen</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Voucher/Bons</label>
                                <input 
                                    type="text" 
                                    wire:model="voucher_label"
                                    placeholder="Voucher/Bons"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                @error('voucher_label') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Backstage-Berechtigung</label>
                                <input 
                                    type="text" 
                                    wire:model="backstage_label"
                                    placeholder="Backstage-Berechtigung"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                @error('backstage_label') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <button 
                        type="submit"
                        class="px-6 py-2 bg-purple-500 text-white rounded hover:bg-purple-600"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                    >
                        <span wire:loading.remove">Tag-Labels speichern</span>
                        <span wire:loading>Speichern...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let selectedDay = 1; // Standard-Tag für Farbauswahl

// Funktion zum Setzen der ausgewählten Farbe
function setSelectedDayColor(hex, name) {
    // Alle Tage durchgehen und den ersten sichtbaren Tag finden
    for (let day = 1; day <= 4; day++) {
        const colorInput = document.querySelector(`input[wire\\:model="wristband_color_day_${day}"][type="color"]`);
        const textInput = document.querySelector(`input[wire\\:model="wristband_color_day_${day}"][type="text"]`);
        
        if (colorInput && textInput) {
            // Wenn der Benutzer auf eine Farbe klickt, fragen welcher Tag
            const dayChoice = prompt(`Für welchen Tag soll die Farbe "${name}" gesetzt werden? (1-4)`, day);
            
            if (dayChoice && dayChoice >= 1 && dayChoice <= 4) {
                const targetColorInput = document.querySelector(`input[wire\\:model="wristband_color_day_${dayChoice}"][type="color"]`);
                const targetTextInput = document.querySelector(`input[wire\\:model="wristband_color_day_${dayChoice}"][type="text"]`);
                
                if (targetColorInput && targetTextInput) {
                    targetColorInput.value = hex;
                    targetTextInput.value = name;
                    
                    // Livewire Events triggern
                    targetColorInput.dispatchEvent(new Event('input'));
                    targetTextInput.dispatchEvent(new Event('input'));
                }
            }
            break;
        }
    }
}

// Color Input Synchronisation
document.addEventListener('DOMContentLoaded', function() {
    // Für jeden Tag die Synchronisation zwischen Color Picker und Text Input einrichten
    for (let day = 1; day <= 4; day++) {
        const colorInput = document.querySelector(`input[wire\\:model="wristband_color_day_${day}"][type="color"]`);
        const textInput = document.querySelector(`input[wire\\:model="wristband_color_day_${day}"][type="text"]`);
        
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