{{-- resources/views/components/backstage/voucher-purchase-section.blade.php --}}

<div>
    <h3 class="mb-3 text-lg font-semibold">Bonkauf</h3>
    @if ($canShowStagePurchase)
        <div class="space-y-2">
            <div class="flex gap-1">
                <button wire:click="initiatePurchase(0.5)"
                    class="{{ $voucherAmount == 0.5 ? 'ring-2 ring-blue-300' : '' }} flex-1 rounded bg-blue-500 px-2 py-2 text-sm text-white hover:bg-blue-600">
                    0.5
                </button>
                <button wire:click="initiatePurchase(1.0)"
                    class="{{ $voucherAmount == 1.0 ? 'ring-2 ring-blue-300' : '' }} flex-1 rounded bg-blue-500 px-2 py-2 text-sm text-white hover:bg-blue-600">
                    1.0
                </button>
                @if ($purchaseStageId)
                    <button wire:click="resetStageSelection"
                        class="rounded bg-gray-400 px-2 py-2 text-sm text-white hover:bg-gray-500"
                        title="Bühnen-Auswahl zurücksetzen">
                        ↻
                    </button>
                @endif
            </div>
        </div>
    @endif

    <!-- Verkaufszahlen anzeigen -->
    @if ($purchaseStageId)
        @php
            $selectedStageObj = $stages->find($purchaseStageId);
        @endphp
        @if ($selectedStageObj)
            @php $soldToday = $this->getSoldVouchersForStage($purchaseStageId, $currentDay); @endphp
            <div class="mt-2 flex items-center justify-between gap-2 rounded border bg-blue-50 p-2 text-xs text-gray-600">
                <div>
                    <strong>{{ $selectedStageObj->name }}</strong><br>
                    Heute: {{ $soldToday }} {{ $settings ? $settings->getVoucherLabel() : 'Bons' }}
                </div>
                <button wire:click="resetStageSelection"
                    class="rounded bg-gray-400 px-2 py-2 text-sm text-white hover:bg-gray-500"
                    title="Bühnen-Auswahl zurücksetzen">
                    ↻
                </button>
            </div>
        @endif
    @endif
</div>
