{{-- ============================================================================ --}}
{{-- resources/views/livewire/admin/band-registration-dashboard.blade.php --}}
{{-- Vollständiges Dashboard mit Statistiken und Übersicht --}}
{{-- ============================================================================ --}}

<div class="space-y-6">
    <!-- Header -->
    <div class="rounded-lg bg-white p-6 shadow">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Band-Registrierung Dashboard</h2>
                <p class="mt-1 text-gray-600">Übersicht über alle Registrierungsaktivitäten</p>
            </div>
            <button wire:click="$refresh"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                    </path>
                </svg>
                Aktualisieren
            </button>
        </div>
    </div>

    <!-- Haupt-Statistiken -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg bg-white p-6 shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-blue-500">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="truncate text-sm font-medium text-gray-500">Bands gesamt</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['total_bands']) }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-green-500">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="truncate text-sm font-medium text-gray-500">Abgeschlossen</dt>
                        <dd class="text-lg font-medium text-gray-900">
                            {{ number_format($stats['completed_total']) }}
                            <span class="text-sm text-green-600">({{ $stats['completion_rate'] }}%)</span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-yellow-500">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="truncate text-sm font-medium text-gray-500">Ausstehend</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['pending']) }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-purple-500">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="truncate text-sm font-medium text-gray-500">Erwartete Personen</dt>
                        <dd class="text-lg font-medium text-gray-900">
                            {{ number_format($stats['total_expected_members']) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Detaillierte Statistiken -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Aktivitäts-Übersicht -->
        <div class="rounded-lg bg-white p-6 shadow">
            <h3 class="mb-4 text-lg font-medium text-gray-900">Registrierungs-Aktivität</h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between rounded-lg bg-blue-50 px-4 py-3">
                    <div class="flex items-center">
                        <div class="mr-3 h-2 w-2 rounded-full bg-blue-500"></div>
                        <span class="text-sm font-medium text-gray-700">Heute abgeschlossen</span>
                    </div>
                    <span class="text-lg font-bold text-blue-600">{{ $stats['completed_today'] }}</span>
                </div>

                <div class="flex items-center justify-between rounded-lg bg-green-50 px-4 py-3">
                    <div class="flex items-center">
                        <div class="mr-3 h-2 w-2 rounded-full bg-green-500"></div>
                        <span class="text-sm font-medium text-gray-700">Diese Woche</span>
                    </div>
                    <span class="text-lg font-bold text-green-600">{{ $stats['completed_this_week'] }}</span>
                </div>

                <div class="flex items-center justify-between rounded-lg bg-purple-50 px-4 py-3">
                    <div class="flex items-center">
                        <div class="mr-3 h-2 w-2 rounded-full bg-purple-500"></div>
                        <span class="text-sm font-medium text-gray-700">Ø Mitglieder/Band</span>
                    </div>
                    <span class="text-lg font-bold text-purple-600">{{ $stats['avg_members_per_band'] }}</span>
                </div>

                <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
                    <div class="flex items-center">
                        <div class="mr-3 h-2 w-2 rounded-full bg-gray-500"></div>
                        <span class="text-sm font-medium text-gray-700">Fahrzeuge registriert</span>
                    </div>
                    <span class="text-lg font-bold text-gray-600">{{ $stats['total_vehicles'] }}</span>
                </div>

                @if ($stats['needs_reminder'] > 0)
                    <div
                        class="flex items-center justify-between rounded-lg border border-orange-200 bg-orange-50 px-4 py-3">
                        <div class="flex items-center">
                            <div class="mr-3 h-2 w-2 rounded-full bg-orange-500"></div>
                            <span class="text-sm font-medium text-gray-700">Erinnerungen nötig</span>
                        </div>
                        <span class="text-lg font-bold text-orange-600">{{ $stats['needs_reminder'] }}</span>
                    </div>
                @endif

                @if ($stats['expired'] > 0)
                    <div
                        class="flex items-center justify-between rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                        <div class="flex items-center">
                            <div class="mr-3 h-2 w-2 rounded-full bg-red-500"></div>
                            <span class="text-sm font-medium text-gray-700">Abgelaufene Tokens</span>
                        </div>
                        <span class="text-lg font-bold text-red-600">{{ $stats['expired'] }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Registrierungs-Chart (vereinfacht) -->
        <div class="rounded-lg bg-white p-6 shadow">
            <h3 class="mb-4 text-lg font-medium text-gray-900">Registrierungen (letzte 30 Tage)</h3>

            @if (count($chartData) > 0)
                <div class="h-64">
                    <div class="flex h-48 items-end justify-between space-x-1 border-b border-gray-200 pb-2">
                        @php
                            $maxCount = max(array_column($chartData, 'count'));
                            $maxCount = $maxCount > 0 ? $maxCount : 1; // Verhindere Division durch 0
                        @endphp

                        @foreach ($chartData as $day)
                            <div class="group relative flex flex-1 flex-col items-center">
                                <div class="w-full rounded-t bg-gradient-to-t from-blue-500 to-blue-300 transition-colors duration-200 hover:from-blue-600 hover:to-blue-400"
                                    style="height: {{ ($day['count'] / $maxCount) * 100 }}%; min-height: {{ $day['count'] > 0 ? '4px' : '0' }};">
                                </div>

                                <!-- Tooltip -->
                                <div
                                    class="absolute bottom-full z-10 mb-2 whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                    {{ $day['count'] }} Registrierung{{ $day['count'] !== 1 ? 'en' : '' }}
                                    <div
                                        class="absolute left-1/2 top-full -translate-x-1/2 transform border-2 border-transparent border-t-gray-900">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- X-Achse Labels -->
                    <div class="mt-2 flex h-12 items-center justify-between space-x-1">
                        @foreach ($chartData as $index => $day)
                            @if ($index % 5 === 0)
                                {{-- Nur jeden 5. Tag anzeigen --}}
                                <div class="flex-1 text-center">
                                    <div class="text-xs text-gray-500">
                                        {{ date('d.m', strtotime($day['date'])) }}
                                    </div>
                                </div>
                            @else
                                <div class="flex-1"></div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @else
                <div class="flex h-64 items-center justify-center text-gray-500">
                    <div class="text-center">
                        <svg class="mx-auto mb-4 h-16 w-16 text-gray-300" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                        <p class="mb-2 text-lg font-medium">Noch keine Registrierungsdaten</p>
                        <p class="text-sm">Sobald Bands ihre Registrierung abschließen, werden hier Statistiken
                            angezeigt.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Email-Aktivität -->
    <div class="rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">Email-Aktivität</h3>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="rounded-lg bg-blue-50 p-4 text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $stats['with_manager_data'] }}</div>
                <div class="text-sm text-blue-800">Mit Manager-Email</div>
            </div>
            <div class="rounded-lg bg-green-50 p-4 text-center">
                <div class="text-2xl font-bold text-green-600">{{ $stats['links_sent'] }}</div>
                <div class="text-sm text-green-800">Links versendet</div>
            </div>
            <div class="rounded-lg bg-orange-50 p-4 text-center">
                <div class="text-2xl font-bold text-orange-600">{{ $stats['needs_reminder'] }}</div>
                <div class="text-sm text-orange-800">Erinnerungen nötig</div>
            </div>
            <div class="rounded-lg bg-gray-50 p-4 text-center">
                <div class="text-2xl font-bold text-gray-600">
                    {{ $stats['total_bands'] - $stats['with_manager_data'] }}
                </div>
                <div class="text-sm text-gray-800">Ohne Email</div>
            </div>
        </div>
    </div>

    <!-- Letzte Registrierungen -->
    <div class="rounded-lg bg-white shadow">
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Letzte Registrierungen</h3>
                <a href="{{ route('admin.band-registration-links') }}"
                    class="text-sm text-blue-600 hover:text-blue-800">
                    Alle anzeigen →
                </a>
            </div>
        </div>

        @if (count($recentRegistrations) > 0)
            <div class="divide-y divide-gray-200">
                @foreach ($recentRegistrations as $registration)
                    <div class="px-6 py-4 transition-colors duration-150 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="h-2 w-2 flex-shrink-0 rounded-full bg-green-400"></div>
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-medium text-gray-900">
                                        {{ $registration['band_name'] }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <span class="inline-flex items-center">
                                            🎤 {{ $registration['stage_name'] }}
                                        </span>
                                        <span class="mx-2">•</span>
                                        <span class="inline-flex items-center">
                                            👥 {{ $registration['travel_party'] }} Mitglieder
                                        </span>
                                        @if ($registration['manager_name'])
                                            <span class="mx-2">•</span>
                                            <span class="inline-flex items-center">
                                                👤 {{ $registration['manager_name'] }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <div class="text-sm text-gray-900">
                                    {{ $registration['completed_at']->diffForHumans() }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $registration['completed_at']->format('d.m.Y H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="px-6 py-12 text-center text-gray-500">
                <svg class="mx-auto mb-4 h-12 w-12 text-gray-300" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                    </path>
                </svg>
                <div class="mb-2 text-lg font-medium">Noch keine Registrierungen</div>
                <div class="text-sm">Die ersten abgeschlossenen Registrierungen werden hier angezeigt.</div>
            </div>
        @endif
    </div>

    <!-- Überfällige Registrierungen -->
    @if (count($overdueRegistrations) > 0)
        <div class="rounded-lg border-l-4 border-red-400 bg-white shadow">
            <div class="border-b border-gray-200 bg-red-50 px-6 py-4">
                <div class="flex items-center">
                    <svg class="mr-2 h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                        </path>
                    </svg>
                    <h3 class="text-lg font-medium text-red-800">Überfällige Registrierungen</h3>
                </div>
                <p class="mt-1 text-sm text-red-700">Diese Bands haben vor mehr als 14 Tagen einen Link erhalten</p>
            </div>

            <div class="divide-y divide-gray-200">
                @foreach ($overdueRegistrations as $overdue)
                    <div class="px-6 py-4 transition-colors duration-150 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="h-2 w-2 flex-shrink-0 rounded-full bg-red-400"></div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $overdue['band_name'] }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <span>{{ $overdue['stage_name'] }}</span>
                                        @if ($overdue['manager_name'])
                                            <span class="mx-2">•</span>
                                            <span>{{ $overdue['manager_name'] }}</span>
                                        @endif
                                        @if ($overdue['manager_email'])
                                            <span class="mx-2">•</span>
                                            <span class="font-mono text-xs">{{ $overdue['manager_email'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <div class="text-sm font-medium text-red-600">
                                    {{ $overdue['days_overdue'] }} Tage überfällig
                                </div>
                                <div class="text-xs text-gray-500">
                                    Link gesendet: {{ $overdue['sent_at']->format('d.m.Y H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">
                        {{ count($overdueRegistrations) }} überfällige
                        Registrierung{{ count($overdueRegistrations) !== 1 ? 'en' : '' }}
                    </span>
                    <a href="{{ route('admin.band-registration-links', ['filterStatus' => 'needs_reminder']) }}"
                        class="text-sm font-medium text-red-600 hover:text-red-800">
                        Erinnerungen senden →
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- Quick Actions -->
    <div class="rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">Schnellaktionen</h3>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <a href="{{ route('admin.band-registration-links') }}"
                class="flex items-center rounded-lg border border-gray-300 p-4 transition-colors duration-150 hover:bg-gray-50">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                        </path>
                    </svg>
                </div>
                <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">Links verwalten</div>
                    <div class="text-xs text-gray-500">Registrierungslinks generieren und versenden</div>
                </div>
            </a>

            <a href="{{ route('admin.band-manager-import') }}"
                class="flex items-center rounded-lg border border-gray-300 p-4 transition-colors duration-150 hover:bg-gray-50">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10">
                        </path>
                    </svg>
                </div>
                <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">Daten importieren</div>
                    <div class="text-xs text-gray-500">Manager-Kontaktdaten per CSV/Excel</div>
                </div>
            </a>

            <a href="{{ route('admin.bands') }}"
                class="flex items-center rounded-lg border border-gray-300 p-4 transition-colors duration-150 hover:bg-gray-50">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                        </path>
                    </svg>
                </div>
                <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">Band-Verwaltung</div>
                    <div class="text-xs text-gray-500">Alle Bands und Details verwalten</div>
                </div>
            </a>
        </div>
    </div>

    <!-- System-Status -->
    <div class="rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">System-Status</h3>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="flex items-center rounded-lg bg-green-50 p-3">
                <div class="flex-shrink-0">
                    <div class="h-2 w-2 rounded-full bg-green-500"></div>
                </div>
                <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">Datenbank</div>
                    <div class="text-xs text-green-600">Online</div>
                </div>
            </div>

            <div class="flex items-center rounded-lg bg-green-50 p-3">
                <div class="flex-shrink-0">
                    <div class="h-2 w-2 rounded-full bg-green-500"></div>
                </div>
                <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">Email-System</div>
                    <div class="text-xs text-green-600">Aktiv</div>
                </div>
            </div>

            <div class="flex items-center rounded-lg bg-green-50 p-3">
                <div class="flex-shrink-0">
                    <div class="h-2 w-2 rounded-full bg-green-500"></div>
                </div>
                <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">Queue-System</div>
                    <div class="text-xs text-green-600">Läuft</div>
                </div>
            </div>

            <div class="flex items-center rounded-lg bg-blue-50 p-3">
                <div class="flex-shrink-0">
                    <div class="h-2 w-2 rounded-full bg-blue-500"></div>
                </div>
                <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">Letztes Update</div>
                    <div class="text-xs text-blue-600">{{ now()->format('H:i') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
