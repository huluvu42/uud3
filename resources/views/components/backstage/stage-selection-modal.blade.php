{{-- resources/views/components/backstage/stage-selection-modal.blade.php --}}

<div class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
    <div class="relative top-20 mx-auto w-11/12 rounded-md border bg-white p-5 shadow-lg md:w-1/2">
        <div class="mt-3">
            <h3 class="mb-4 text-lg font-medium">Bühne für {{ $voucherAmount }} Bon auswählen</h3>

            <!-- Bühnen-Auswahl mit Verkaufszahlen -->
            <div class="mb-4 space-y-2">
                @foreach ($stages as $stage)
                    @php $soldToday = $this->getSoldVouchersForStage($stage->id, $currentDay) @endphp
                    <label class="{{ $purchaseStageId == $stage->id ? 'border-blue-500 bg-blue-50' : '' }} flex cursor-pointer items-center rounded-lg border p-3 hover:bg-gray-50">
                        <input type="radio" wire:model.live="purchaseStageId" value="{{ $stage->id }}" class="mr-3">
                        <div class="flex-1">
                            <div class="font-medium">{{ $stage->name }}</div>
                            <div class="text-sm text-gray-500">
                                Heute verkauft: {{ $soldToday }} {{ $settings ? $settings->getVoucherLabel() : 'Bons' }}
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="flex justify-end space-x-2">
                <button wire:click="cancelStageSelection"
                    class="rounded bg-gray-500 px-4 py-2 font-bold text-white hover:bg-gray-700">
                    Abbrechen
                </button>
                <button wire:click="purchaseVouchers"
                    class="rounded bg-green-500 px-4 py-2 font-bold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                    {{ !$purchaseStageId ? 'disabled' : '' }}>
                    {{ $voucherAmount }} Bon kaufen
                </button>
            </div>
        </div>
    </div>
</div>
