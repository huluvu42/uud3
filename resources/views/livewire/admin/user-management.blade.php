{{-- resources/views/livewire/admin/user-management.blade.php --}}

<div class="container mx-auto px-4 py-8">
    @include('partials.navigation')

    <div class="max-w-7xl mx-auto">
        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <!-- Header-Bereich -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <!-- Zwei-Spalten Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Benutzersuche -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Benutzersuche</h3>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Vorname, Nachname oder Benutzername..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Aktionen -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Aktionen</h3>
                    <button wire:click="createUser"
                        class="w-full px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Neuer Benutzer
                    </button>
                </div>
            </div>
        </div>

        <!-- Benutzerliste -->
        @if (count($searchResults) > 0)
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Benutzer ({{ count($searchResults) }} gefunden)</h3>
                <div class="space-y-3" style="max-height: calc(100vh - 400px); overflow-y: auto; min-height: 400px;">
                    @foreach ($searchResults as $user)
                        <div class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50"
                            wire:key="user-{{ $user->id }}-{{ $loop->index }}">

                            <div class="grid grid-cols-1 xl:grid-cols-6 gap-4">

                                <!-- Benutzer Info -->
                                <div class="xl:col-span-2">
                                    <div class="font-medium text-lg mb-1">
                                        {{ $user->first_name }} {{ $user->last_name }}
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        👤 {{ $user->username }}
                                    </div>
                                </div>

                                <!-- Administrator -->
                                <div class="xl:col-span-1">
                                    <div class="text-sm font-medium text-gray-700 mb-2">Administrator</div>
                                    <div class="flex justify-center">
                                        @if ($user->is_admin)
                                            <div
                                                class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center">
                                                <span class="text-green-600 font-bold text-xs">✓</span>
                                            </div>
                                        @else
                                            <div
                                                class="w-5 h-5 bg-red-100 rounded-full flex items-center justify-center">
                                                <span class="text-red-600 font-bold text-xs">✗</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Änderungen zurücksetzen -->
                                <div class="xl:col-span-1">
                                    <div class="text-sm font-medium text-gray-700 mb-2">Zurücksetzen</div>
                                    <div class="flex justify-center">
                                        @if ($user->can_reset_changes)
                                            <div
                                                class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center">
                                                <span class="text-green-600 font-bold text-xs">✓</span>
                                            </div>
                                        @else
                                            <div
                                                class="w-5 h-5 bg-red-100 rounded-full flex items-center justify-center">
                                                <span class="text-red-600 font-bold text-xs">✗</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="xl:col-span-1">
                                    <div class="text-sm font-medium text-gray-700 mb-2">Status</div>
                                    <div
                                        class="w-full px-3 py-2 rounded text-sm font-medium text-center bg-green-100 text-green-800">
                                        Aktiv
                                    </div>
                                </div>

                                <!-- Aktionen -->
                                <div class="xl:col-span-1">
                                    <div class="text-sm font-medium text-gray-700 mb-2">Aktionen</div>
                                    <div class="space-y-2">
                                        <button wire:click="selectUserForEdit({{ $user->id }})"
                                            class="w-full px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600"
                                            wire:key="edit-btn-{{ $user->id }}-{{ $loop->index }}">
                                            Bearbeiten
                                        </button>

                                        @if ($user->id !== auth()->id())
                                            <button wire:click="deleteUser({{ $user->id }})"
                                                wire:confirm="Benutzer '{{ $user->username }}' wirklich löschen?"
                                                class="w-full px-3 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600"
                                                wire:key="delete-btn-{{ $user->id }}-{{ $loop->index }}">
                                                Löschen
                                            </button>
                                        @else
                                            <div
                                                class="w-full px-3 py-1 bg-gray-100 text-gray-500 text-xs rounded text-center">
                                                Eigener Account
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
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <h3 class="text-xl font-semibold text-gray-600 mb-4">Benutzerverwaltung</h3>
                <p class="text-gray-500 mb-6">Verwalten Sie hier alle Systembenutzer und deren Berechtigungen.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-400">
                    <div class="p-4 bg-gray-50 rounded">
                        <h4 class="font-medium text-gray-600 mb-2">👤 Benutzer verwalten</h4>
                        <p>Erstellen, bearbeiten und löschen Sie Benutzerkonten</p>
                    </div>
                    <div class="p-4 bg-gray-50 rounded">
                        <h4 class="font-medium text-gray-600 mb-2">🔑 Berechtigungen</h4>
                        <p>Verwalten Sie Administrator- und Zurücksetz-Rechte</p>
                    </div>
                </div>
            </div>
        @else
            <!-- No Search Results -->
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <h3 class="text-xl font-semibold text-gray-600 mb-4">Keine Benutzer gefunden</h3>
                <p class="text-gray-500">Für "{{ $search }}" wurden keine Benutzer gefunden.</p>
            </div>
        @endif
    </div>

    <!-- User Modal -->
    @if ($showModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium mb-6">
                        {{ $editingUser ? 'Benutzer bearbeiten' : 'Neuer Benutzer' }}
                    </h3>

                    <form wire:submit.prevent="saveUser">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Benutzername</label>
                                <input type="text" wire:model="username"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required>
                                @error('username')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Vorname</label>
                                    <input type="text" wire:model="first_name"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required>
                                    @error('first_name')
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nachname</label>
                                    <input type="text" wire:model="last_name"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required>
                                    @error('last_name')
                                        <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Passwort {{ $editingUser ? '(leer lassen für keine Änderung)' : '' }}
                                </label>
                                <input type="password" wire:model="password"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    {{ $editingUser ? '' : 'required' }}>
                                @error('password')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="space-y-3 pt-4 border-t border-gray-200">
                                <h4 class="font-medium text-gray-700">Berechtigungen</h4>

                                <div class="space-y-2">
                                    <label
                                        class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer {{ $is_admin ? 'border-blue-500 bg-blue-50' : '' }}">
                                        <input type="checkbox" wire:model="is_admin"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 mr-3">
                                        <div>
                                            <div class="font-medium">Administrator</div>
                                            <div class="text-sm text-gray-500">Vollzugriff auf alle Funktionen des
                                                Systems</div>
                                        </div>
                                    </label>

                                    <label
                                        class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer {{ $can_reset_changes ? 'border-blue-500 bg-blue-50' : '' }}">
                                        <input type="checkbox" wire:model="can_reset_changes"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 mr-3">
                                        <div>
                                            <div class="font-medium">Änderungen zurücksetzen</div>
                                            <div class="text-sm text-gray-500">Kann Personendaten und Voucher-Status
                                                zurücksetzen</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4 mt-6 pt-4 border-t border-gray-200">
                            <button type="button" wire:click="closeModal"
                                class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
                                Abbrechen
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
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
