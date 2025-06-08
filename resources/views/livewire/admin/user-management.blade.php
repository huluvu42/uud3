{{-- resources/views/livewire/admin/user-management.blade.php --}}

<div class="container mx-auto px-4 py-8">
    @include('partials.navigation')

    <div class="mx-auto mt-6 max-w-7xl">
        <!-- Flash Messages -->
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

        <!-- Header-Bereich -->
        <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
            <!-- Zwei-Spalten Layout -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                <!-- Benutzersuche -->
                <div>
                    <h3 class="mb-4 text-lg font-semibold">Benutzersuche</h3>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Vorname, Nachname oder Benutzername..."
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Aktionen -->
                <div>
                    <h3 class="mb-4 text-lg font-semibold">Aktionen</h3>
                    <button wire:click="createUser"
                        class="w-full rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600">
                        Neuer Benutzer
                    </button>
                </div>
            </div>
        </div>

        <!-- Benutzerliste -->
        @if (count($searchResults) > 0)
            <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
                <h3 class="mb-4 text-lg font-semibold">Benutzer ({{ count($searchResults) }} gefunden)</h3>
                <div class="space-y-3" style="max-height: calc(100vh - 400px); overflow-y: auto; min-height: 400px;">
                    @foreach ($searchResults as $user)
                        <div class="{{ $user->isProtectedAdmin() ? 'border-l-4 border-l-yellow-500 bg-yellow-50' : '' }} rounded-lg border border-gray-200 p-4 hover:bg-gray-50"
                            wire:key="user-{{ $user->id }}-{{ $loop->index }}">

                            <div class="grid grid-cols-1 gap-4 xl:grid-cols-7">

                                <!-- Benutzer Info -->
                                <div class="xl:col-span-2">
                                    <div class="mb-1 flex items-center text-lg font-medium">
                                        {{ $user->first_name }} {{ $user->last_name }}
                                        @if ($user->isProtectedAdmin())
                                            <span
                                                class="ml-2 rounded-full bg-yellow-100 px-2 py-1 text-xs text-yellow-800">
                                                🛡️ Geschützt
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        👤 {{ $user->username }}
                                    </div>
                                </div>

                                <!-- Administrator -->
                                <div class="xl:col-span-1">
                                    <div class="mb-2 text-sm font-medium text-gray-700">Administrator</div>
                                    <div class="flex justify-center">
                                        @if ($user->is_admin)
                                            <div
                                                class="flex h-5 w-5 items-center justify-center rounded-full bg-green-100">
                                                <span class="text-xs font-bold text-green-600">✓</span>
                                            </div>
                                        @else
                                            <div
                                                class="flex h-5 w-5 items-center justify-center rounded-full bg-red-100">
                                                <span class="text-xs font-bold text-red-600">✗</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Verwaltung -->
                                <div class="xl:col-span-1">
                                    <div class="mb-2 text-sm font-medium text-gray-700">Verwaltung</div>
                                    <div class="flex justify-center">
                                        @if ($user->can_manage)
                                            <div
                                                class="flex h-5 w-5 items-center justify-center rounded-full bg-green-100">
                                                <span class="text-xs font-bold text-green-600">✓</span>
                                            </div>
                                        @else
                                            <div
                                                class="flex h-5 w-5 items-center justify-center rounded-full bg-red-100">
                                                <span class="text-xs font-bold text-red-600">✗</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Änderungen zurücksetzen -->
                                <div class="xl:col-span-1">
                                    <div class="mb-2 text-sm font-medium text-gray-700">Zurücksetzen</div>
                                    <div class="flex justify-center">
                                        @if ($user->can_reset_changes)
                                            <div
                                                class="flex h-5 w-5 items-center justify-center rounded-full bg-green-100">
                                                <span class="text-xs font-bold text-green-600">✓</span>
                                            </div>
                                        @else
                                            <div
                                                class="flex h-5 w-5 items-center justify-center rounded-full bg-red-100">
                                                <span class="text-xs font-bold text-red-600">✗</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="xl:col-span-1">
                                    <div class="mb-2 text-sm font-medium text-gray-700">Status</div>
                                    <div
                                        class="w-full rounded bg-green-100 px-3 py-2 text-center text-sm font-medium text-green-800">
                                        Aktiv
                                    </div>
                                </div>

                                <!-- Aktionen -->
                                <div class="xl:col-span-1">
                                    <div class="mb-2 text-sm font-medium text-gray-700">Aktionen</div>
                                    <div class="space-y-2">
                                        <button wire:click="selectUserForEdit({{ $user->id }})"
                                            class="w-full rounded bg-blue-500 px-3 py-1 text-xs text-white hover:bg-blue-600"
                                            wire:key="edit-btn-{{ $user->id }}-{{ $loop->index }}">
                                            Bearbeiten
                                        </button>

                                        @if ($user->canBeDeleted() && $user->id !== auth()->id())
                                            <button wire:click="deleteUser({{ $user->id }})"
                                                wire:confirm="Benutzer '{{ $user->username }}' wirklich löschen?"
                                                class="w-full rounded bg-red-500 px-3 py-1 text-xs text-white hover:bg-red-600"
                                                wire:key="delete-btn-{{ $user->id }}-{{ $loop->index }}">
                                                Löschen
                                            </button>
                                        @else
                                            <div
                                                class="w-full rounded bg-gray-100 px-3 py-1 text-center text-xs text-gray-500">
                                                @if ($user->isProtectedAdmin())
                                                    🛡️ Geschützt
                                                @elseif($user->id === auth()->id())
                                                    Eigener Account
                                                @else
                                                    Nicht löschbar
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif(!$search)
            <!-- Welcome Message -->
            <div class="rounded-lg bg-white p-8 text-center shadow-md">
                <h3 class="mb-4 text-xl font-semibold text-gray-600">Benutzerverwaltung</h3>
                <p class="mb-6 text-gray-500">Verwalten Sie hier alle Systembenutzer und deren Berechtigungen.</p>
                <div class="grid grid-cols-1 gap-4 text-sm text-gray-400 md:grid-cols-3">
                    <div class="rounded bg-gray-50 p-4">
                        <h4 class="mb-2 font-medium text-gray-600">👤 Benutzer verwalten</h4>
                        <p>Erstellen, bearbeiten und löschen Sie Benutzerkonten</p>
                    </div>
                    <div class="rounded bg-gray-50 p-4">
                        <h4 class="mb-2 font-medium text-gray-600">🔑 Berechtigungen</h4>
                        <p>Verwalten Sie Administrator- und Zurücksetz-Rechte</p>
                    </div>
                    <div class="rounded bg-gray-50 p-4">
                        <h4 class="mb-2 font-medium text-gray-600">📋 Verwaltungsrechte</h4>
                        <p>Vergeben Sie Zugriffsrechte für Gruppen & Bühnen</p>
                    </div>
                </div>
            </div>
        @else
            <!-- No Search Results -->
            <div class="rounded-lg bg-white p-8 text-center shadow-md">
                <h3 class="mb-4 text-xl font-semibold text-gray-600">Keine Benutzer gefunden</h3>
                <p class="text-gray-500">Für "{{ $search }}" wurden keine Benutzer gefunden.</p>
            </div>
        @endif
    </div>

    <!-- User Modal -->
    @if ($showModal)
        <div class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
            <div class="relative top-20 mx-auto w-11/12 rounded-md border bg-white p-5 shadow-lg md:w-1/2 lg:w-1/3">
                <div class="mt-3">
                    <h3 class="mb-6 text-lg font-medium">
                        {{ $editingUser ? 'Benutzer bearbeiten' : 'Neuer Benutzer' }}
                        @if ($editingUser && $editingUser->isProtectedAdmin())
                            <span class="ml-2 rounded-full bg-yellow-100 px-2 py-1 text-xs text-yellow-800">
                                🛡️ Geschützter Admin
                            </span>
                        @endif
                    </h3>

                    <form wire:submit.prevent="saveUser">
                        <div class="space-y-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Benutzername</label>
                                <input type="text" wire:model="username"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    {{ $editingUser && $editingUser->isProtectedAdmin() ? 'readonly' : '' }} required>
                                @if ($editingUser && $editingUser->isProtectedAdmin())
                                    <p class="mt-1 text-xs text-yellow-600">Der Benutzername des Admin-Accounts kann
                                        nicht geändert werden.</p>
                                @endif
                                @error('username')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700">Vorname</label>
                                    <input type="text" wire:model="first_name"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required>
                                    @error('first_name')
                                        <span class="text-sm text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700">Nachname</label>
                                    <input type="text" wire:model="last_name"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required>
                                    @error('last_name')
                                        <span class="text-sm text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">
                                    Passwort {{ $editingUser ? '(leer lassen für keine Änderung)' : '' }}
                                </label>
                                <input type="password" wire:model="password"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    {{ $editingUser ? '' : 'required' }}>
                                @error('password')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="space-y-3 border-t border-gray-200 pt-4">
                                <h4 class="font-medium text-gray-700">Berechtigungen</h4>

                                <div class="space-y-2">
                                    <label
                                        class="{{ $is_admin ? 'border-blue-500 bg-blue-50' : '' }} flex cursor-pointer items-center rounded-lg border p-3 hover:bg-gray-50">
                                        <input type="checkbox" wire:model="is_admin"
                                            class="mr-3 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                            {{ $editingUser && $editingUser->isProtectedAdmin() ? 'disabled' : '' }}>
                                        <div>
                                            <div class="font-medium">Administrator</div>
                                            <div class="text-sm text-gray-500">
                                                Vollzugriff auf alle Funktionen des Systems
                                                @if ($editingUser && $editingUser->isProtectedAdmin())
                                                    <br><span class="text-yellow-600">⚠️ Der Admin-Account muss
                                                        Administrator bleiben</span>
                                                @endif
                                            </div>
                                        </div>
                                    </label>

                                    <label
                                        class="{{ $can_manage ? 'border-orange-500 bg-orange-50' : '' }} flex cursor-pointer items-center rounded-lg border p-3 hover:bg-gray-50">
                                        <input type="checkbox" wire:model="can_manage"
                                            class="mr-3 rounded border-gray-300 text-orange-600 shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50">
                                        <div>
                                            <div class="font-medium">Verwaltung</div>
                                            <div class="text-sm text-gray-500">Kann Gruppen, Bühnen und verwandte
                                                Einstellungen verwalten</div>
                                        </div>
                                    </label>

                                    <label
                                        class="{{ $can_reset_changes ? 'border-blue-500 bg-blue-50' : '' }} flex cursor-pointer items-center rounded-lg border p-3 hover:bg-gray-50">
                                        <input type="checkbox" wire:model="can_reset_changes"
                                            class="mr-3 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <div>
                                            <div class="font-medium">Änderungen zurücksetzen</div>
                                            <div class="text-sm text-gray-500">Kann Personendaten und Voucher-Status
                                                zurücksetzen</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-4 border-t border-gray-200 pt-4">
                            <button type="button" wire:click="closeModal"
                                class="rounded border border-gray-300 px-4 py-2 text-gray-600 hover:bg-gray-50">
                                Abbrechen
                            </button>
                            <button type="submit" class="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600"
                                wire:loading.attr="disabled" wire:loading.class="opacity-50">
                                <span wire:loading.remove>{{ $editingUser ? 'Aktualisieren' : 'Erstellen' }}</span>
                                <span wire:loading>Speichern...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
