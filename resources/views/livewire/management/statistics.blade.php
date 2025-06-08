{{-- resources/views/livewire/management/statistics.blade.php --}}

<div class="container mx-auto px-4 py-8">
    @include('partials.navigation')

    <div class="mx-auto mt-6 max-w-7xl">
        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="mb-4 rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <!-- Header & Filters -->
        <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800">📊 {{ $settings->voucher_label ?? 'Voucher' }} Statistiken
                    {{ $year }}</h2>
                <p class="text-gray-600">Übersicht über freie und gekaufte
                    {{ strtolower($settings->voucher_label ?? 'Voucher') }}</p>
            </div>

            <!-- Filter Row -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
                <!-- Name Search -->
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Personensuche</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Vor- oder Nachname..."
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Group Filter -->
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Gruppe</label>
                    <select wire:model.live="selectedGroupId"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Alle Gruppen</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filters -->
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Filter</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" wire:model.live="showOnlyWithVouchers"
                                class="mr-2 rounded border-gray-300 text-blue-600">
                            <span class="text-sm">Nur mit freien
                                {{ strtolower($settings->voucher_label ?? 'Voucher') }}</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" wire:model.live="showOnlyWithPurchases"
                                class="mr-2 rounded border-gray-300 text-blue-600">
                            <span class="text-sm">Nur mit Käufen</span>
                        </label>
                    </div>
                </div>

                <!-- Actions -->
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Aktionen</label>
                    <button wire:click="exportCsv"
                        class="w-full rounded bg-green-500 px-4 py-2 text-white hover:bg-green-600">
                        📊 CSV Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        @if (count($statistics) > 0)
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <div class="text-2xl font-bold text-blue-700">{{ $summary['total_persons'] }}</div>
                    <div class="text-sm text-blue-600">Personen gefunden</div>
                </div>
                <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                    <div class="text-2xl font-bold text-green-700">
                        {{ number_format($summary['grand_total_issued'], 1) }}</div>
                    <div class="text-sm text-green-600">{{ $settings->voucher_label ?? 'Voucher' }} frei</div>
                </div>
                <div class="rounded-lg border border-purple-200 bg-purple-50 p-4">
                    <div class="text-2xl font-bold text-purple-700">
                        {{ number_format($summary['grand_total_purchased'], 1) }}</div>
                    <div class="text-sm text-purple-600">{{ $settings->voucher_label ?? 'Voucher' }} gekauft</div>
                </div>
                <div class="rounded-lg border border-orange-200 bg-orange-50 p-4">
                    <div class="text-2xl font-bold text-orange-700">{{ $summary['persons_with_vouchers'] }}</div>
                    <div class="text-sm text-orange-600">Personen mit freien
                        {{ strtolower($settings->voucher_label ?? 'Voucher') }}</div>
                </div>
            </div>

            <!-- Day Summary with Stage Breakdown -->
            <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
                <h3 class="mb-4 text-lg font-semibold">Tagesübersicht</h3>

                <!-- Gesamtübersicht -->
                <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
                    @for ($day = 1; $day <= 4; $day++)
                        <div class="rounded border border-gray-200 p-4">
                            <h4 class="mb-2 font-medium text-gray-700">
                                {{ $settings->{"day_{$day}_label"} ?? "Tag {$day}" }}</h4>
                            <div class="space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <span>Frei:</span>
                                    <span
                                        class="font-medium text-green-600">{{ number_format($summary['total_issued']["day_{$day}"], 1) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Gekauft:</span>
                                    <span
                                        class="font-medium text-purple-600">{{ number_format($summary['total_purchased']["day_{$day}"], 1) }}</span>
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>

                <!-- Bühnen-spezifische Käufe -->
                @if (isset($summary['stage_statistics']) && count($summary['stage_statistics']) > 0)
                    <div class="border-t border-gray-200 pt-4">
                        <h4 class="text-md mb-3 font-medium text-gray-700">
                            {{ $settings->voucher_label ?? 'Voucher' }}-Käufe nach Bühne</h4>
                        <div class="space-y-3">
                            @foreach ($summary['stage_statistics'] as $stageStat)
                                <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                                    <div class="mb-2 flex items-center justify-between">
                                        <h5 class="font-medium text-gray-800">🎭 {{ $stageStat['stage']->name }}</h5>
                                        <span class="text-sm font-medium text-purple-600">
                                            Gesamt: {{ number_format($stageStat['total'], 1) }}
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-4 gap-2 text-sm">
                                        @for ($day = 1; $day <= 4; $day++)
                                            <div class="text-center">
                                                <div class="text-xs text-gray-500">
                                                    {{ $settings->{"day_{$day}_label"} ?? "Tag {$day}" }}</div>
                                                <div class="font-medium text-purple-600">
                                                    {{ number_format($stageStat['total_purchased']["day_{$day}"], 1) }}
                                                </div>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Statistics Table -->
            <div class="rounded-lg bg-white shadow-md">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold">Detailstatistiken ({{ count($statistics) }} Personen)</h3>
                </div>

                <div class="overflow-x-auto">
                    <div style="max-height: 600px; overflow-y: auto;">
                        <table class="min-w-full">
                            <thead class="sticky top-0 bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Person
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Gruppe / Band
                                    </th>
                                    @for ($day = 1; $day <= 4; $day++)
                                        <th
                                            class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                            {{ $settings->{"day_{$day}_label"} ?? "Tag {$day}" }}
                                        </th>
                                    @endfor
                                    <th
                                        class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Gesamt
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($statistics as $stat)
                                    <tr class="hover:bg-gray-50">
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $stat['person']->full_name }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                {{ $stat['person']->group->name ?? '-' }}
                                            </div>
                                            @if ($stat['person']->band)
                                                <div class="text-xs text-gray-500">
                                                    {{ $stat['person']->band->band_name }}
                                                </div>
                                            @endif
                                        </td>
                                        @for ($day = 1; $day <= 4; $day++)
                                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                                <div class="space-y-1">
                                                    <div class="text-sm">
                                                        <span class="inline-block w-12 font-medium text-green-600">
                                                            {{ number_format($stat['vouchers_issued']["day_{$day}"], 1) }}
                                                        </span>
                                                        <span class="text-gray-400">/</span>
                                                        <span class="inline-block w-12 font-medium text-purple-600">
                                                            {{ number_format($stat['vouchers_purchased']["day_{$day}"], 1) }}
                                                        </span>
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        F / K
                                                    </div>
                                                </div>
                                            </td>
                                        @endfor
                                        <td class="whitespace-nowrap px-6 py-4 text-center">
                                            <div class="space-y-1">
                                                <div class="text-sm font-medium">
                                                    <span
                                                        class="text-green-600">{{ number_format($stat['total_issued'], 1) }}</span>
                                                    <span class="text-gray-400">/</span>
                                                    <span
                                                        class="text-purple-600">{{ number_format($stat['total_purchased'], 1) }}</span>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    F / K
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <!-- No Results -->
            <div class="rounded-lg bg-white p-8 text-center shadow-md">
                <div class="mb-4 text-6xl text-gray-400">📊</div>
                <h3 class="mb-4 text-xl font-semibold text-gray-600">Keine Statistiken gefunden</h3>
                <p class="text-gray-500">
                    @if ($search || $selectedGroupId)
                        Für die gewählten Filter wurden keine Personen gefunden.
                    @else
                        Es sind noch keine Personen für {{ $year }} vorhanden.
                    @endif
                </p>
            </div>
        @endif
    </div>
</div>
