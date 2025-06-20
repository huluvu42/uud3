{{-- ============================================================================ --}}
{{-- resources/views/livewire/admin/band-registration-links.blade.php --}}
{{-- Admin Interface für Link-Management --}}
{{-- ============================================================================ --}}

<div class="space-y-6">
    <!-- Header mit Statistiken -->
    <div class="rounded-lg bg-white p-6 shadow">
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900">Band-Registrierungslinks</h2>
            <button wire:click="$refresh" class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                    </path>
                </svg>
            </button>
        </div>

        <!-- Statistik-Cards -->
        <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-8">
            <div class="rounded-lg bg-blue-50 p-4">
                <div class="text-2xl font-bold text-blue-600">{{ $stats['total'] }}</div>
                <div class="text-xs text-gray-600">Bands gesamt</div>
            </div>
            <div class="rounded-lg bg-green-50 p-4">
                <div class="text-2xl font-bold text-green-600">{{ $stats['with_manager_email'] }}</div>
                <div class="text-xs text-gray-600">Mit Email</div>
            </div>
            <div class="rounded-lg bg-purple-50 p-4">
                <div class="text-2xl font-bold text-purple-600">{{ $stats['tokens_generated'] }}</div>
                <div class="text-xs text-gray-600">Links generiert</div>
            </div>
            <div class="rounded-lg bg-emerald-50 p-4">
                <div class="text-2xl font-bold text-emerald-600">{{ $stats['completed'] }}</div>
                <div class="text-xs text-gray-600">Abgeschlossen</div>
            </div>
            <div class="rounded-lg bg-yellow-50 p-4">
                <div class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</div>
                <div class="text-xs text-gray-600">Ausstehend</div>
            </div>
            <div class="rounded-lg bg-orange-50 p-4">
                <div class="text-2xl font-bold text-orange-600">{{ $stats['needs_reminder'] }}</div>
                <div class="text-xs text-gray-600">Erinnerung</div>
            </div>
            <div class="rounded-lg bg-red-50 p-4">
                <div class="text-2xl font-bold text-red-600">{{ $stats['no_email'] }}</div>
                <div class="text-xs text-gray-600">Ohne Email</div>
            </div>
            <div class="rounded-lg bg-indigo-50 p-4">
                <div class="text-2xl font-bold text-indigo-600">{{ $stats['completion_rate'] }}%</div>
                <div class="text-xs text-gray-600">Erfolgsrate</div>
            </div>
        </div>
    </div>

    <!-- Filter und Aktionen -->
    <div class="rounded-lg bg-white p-6 shadow">
        <div class="mb-6 flex flex-col space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
            <!-- Suchfeld -->
            <div class="max-w-md flex-1">
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Band, Manager oder Email suchen..."
                        class="w-full rounded-md border border-gray-300 py-2 pl-10 pr-4 focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="flex space-x-3">
                <select wire:model.live="filterStatus" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="all">Alle anzeigen</option>
                    <option value="completed">Abgeschlossen</option>
                    <option value="pending">Ausstehend</option>
                    <option value="needs_reminder">Erinnerung nötig</option>
                    <option value="no_email">Ohne Email</option>
                </select>
            </div>
        </div>

        <!-- Bulk-Aktionen -->
        <div class="mb-6 flex flex-wrap gap-3">
            <button wire:click="generateTokensAndSendEmails" @if (empty($selectedBands)) disabled @endif
                class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>
                Links generieren & Emails senden ({{ count($selectedBands) }})
            </button>

            <button wire:click="sendReminders"
                class="inline-flex items-center rounded-md bg-orange-600 px-4 py-2 text-white hover:bg-orange-700">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-5 5v-5zM4 7h16M4 12h16M4 17h5"></path>
                </svg>
                Erinnerungen senden ({{ $stats['needs_reminder'] }})
            </button>
        </div>

        <!-- Benachrichtigungen -->
        @if (session()->has('message'))
            <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-green-700">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                {{ session('error') }}
            </div>
        @endif
    </div>

    <!-- Bands-Tabelle -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            <input type="checkbox"
                                @click="$event.target.checked ? $wire.selectedBands = @js($bands->pluck('id')->toArray()) : $wire.selectedBands = []"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Band
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            Manager</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            Email-Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            Registrierung</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            Aktionen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($bands as $band)
                        <tr class="hover:bg-gray-50" wire:key="band-{{ $band->id }}">
                            <td class="whitespace-nowrap px-6 py-4">
                                <input type="checkbox" wire:model="selectedBands" value="{{ $band->id }}"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $band->band_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $band->stage->name ?? 'Keine Bühne' }}</div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if ($band->manager_full_name)
                                    <div class="text-sm">
                                        <div class="font-medium text-gray-900">{{ $band->manager_full_name }}</div>
                                        <div class="text-gray-500">{{ $band->manager_email }}</div>
                                        @if ($band->manager_phone)
                                            <div class="text-gray-500">📞 {{ $band->manager_phone }}</div>
                                        @endif
                                    </div>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                        Keine Manager-Daten
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if ($band->registration_link_sent_at)
                                    <div class="text-sm">
                                        <span
                                            class="mb-1 inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                            ✅ Gesendet
                                        </span>
                                        <div class="text-gray-500">
                                            {{ $band->registration_link_sent_at->format('d.m.Y H:i') }}</div>
                                        @if ($band->registration_reminder_sent_at)
                                            <div class="text-xs text-orange-600">🔔 Erinnerung:
                                                {{ $band->registration_reminder_sent_at->format('d.m.Y') }}</div>
                                        @elseif($band->needsReminder())
                                            <div class="text-xs text-orange-500">⏰ Erinnerung nötig</div>
                                        @endif
                                    </div>
                                @elseif($band->manager_email)
                                    <span
                                        class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">
                                        📧 Bereit zum Senden
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                        ❌ Keine Email
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if ($band->registration_completed)
                                    <div class="text-sm">
                                        <span
                                            class="mb-1 inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                            ✅ Abgeschlossen
                                        </span>
                                        @if ($band->travel_party)
                                            <div class="text-gray-500">{{ $band->travel_party }} Mitglieder</div>
                                        @endif
                                    </div>
                                @elseif($band->registration_token)
                                    <div class="text-sm">
                                        <span
                                            class="mb-1 inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                            🔗 Link aktiv
                                        </span>
                                        <div class="text-gray-500">Läuft ab:
                                            {{ $band->registration_token_expires_at->format('d.m.Y') }}</div>
                                        @if ($band->isRegistrationExpired())
                                            <div class="text-xs text-red-500">⚠️ Abgelaufen</div>
                                        @endif
                                    </div>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                                        ❌ Nicht gestartet
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    @if ($band->registration_token && !$band->registration_completed)
                                        <button wire:click="copyLink({{ $band->id }})"
                                            class="text-xs text-blue-600 hover:text-blue-900">
                                            📋 Link
                                        </button>
                                        @if ($band->manager_email)
                                            <button wire:click="sendEmail({{ $band->id }})"
                                                class="text-xs text-green-600 hover:text-green-900">
                                                📧 Email
                                            </button>
                                        @endif
                                    @endif

                                    @if ($band->registration_token)
                                        <button wire:click="deleteToken({{ $band->id }})"
                                            onclick="return confirm('Token wirklich löschen?')"
                                            class="text-xs text-red-600 hover:text-red-900">
                                            🗑️ Token
                                        </button>
                                    @endif

                                    @if ($band->registration_completed)
                                        <button wire:click="resetRegistration({{ $band->id }})"
                                            onclick="return confirm('Registrierung wirklich zurücksetzen?')"
                                            class="text-xs text-orange-600 hover:text-orange-900">
                                            🔄 Reset
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="mb-2 text-lg font-medium">Keine Bands gefunden</div>
                                <div class="text-sm">Versuchen Sie einen anderen Filter oder Suchbegriff.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', function() {
        Livewire.on('copy-to-clipboard', function(event) {
            navigator.clipboard.writeText(event.text).then(function() {
                // Toast-Benachrichtigung
                const toast = document.createElement('div');
                toast.className =
                    'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-md shadow-lg z-50';
                toast.textContent = 'Link wurde in die Zwischenablage kopiert!';
                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.remove();
                }, 3000);
            });
        });
    });
</script>
