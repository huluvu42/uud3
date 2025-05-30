<!-- resources/views/livewire/admin/user-management.blade.php -->
<div class="min-h-screen bg-gray-100 p-4">
    <div class="max-w-6xl mx-auto">
        <!-- Header with Navigation -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-800">Benutzerverwaltung</h1>
                <div class="flex space-x-4">
                    <!-- Admin Navigation -->
                    <a href="{{ route('admin.users') }}" 
                       class="px-3 py-2 rounded bg-blue-100 text-blue-800 font-medium">
                        Benutzer
                    </a>
                    <a href="{{ route('admin.settings') }}" 
                       class="px-3 py-2 rounded text-blue-600 hover:text-blue-800 hover:bg-blue-50">
                        Einstellungen
                    </a>
                    <a href="{{ route('admin.changelog') }}" 
                       class="px-3 py-2 rounded text-blue-600 hover:text-blue-800 hover:bg-blue-50">
                        Protokoll
                    </a>
                    <span class="text-gray-300">|</span>
                    <a href="{{ route('home') }}" class="text-green-600 hover:text-green-800">← Hauptseite</a>
                    
                    <button 
                        wire:click="createUser"
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                    >
                        Neuer Benutzer
                    </button>
                </div>
            </div>
        </div>

        <!-- Success Messages -->
        @if (session()->has('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Benutzername</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Admin</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Änderungen zurücksetzen</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($users as $user)
                        <tr>
                            <td class="px-6 py-4 text-sm">{{ $user->username }}</td>
                            <td class="px-6 py-4 text-sm">{{ $user->first_name }} {{ $user->last_name }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 text-xs rounded {{ $user->is_admin ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $user->is_admin ? 'Ja' : 'Nein' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 text-xs rounded {{ $user->can_reset_changes ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $user->can_reset_changes ? 'Ja' : 'Nein' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm space-x-2">
                                <button 
                                    wire:click="editUser({{ $user->id }})"
                                    class="text-blue-600 hover:text-blue-800"
                                >
                                    Bearbeiten
                                </button>
                                @if($user->id !== auth()->id())
                                    <button 
                                        wire:click="deleteUser({{ $user->id }})"
                                        wire:confirm="Benutzer wirklich löschen?"
                                        class="text-red-600 hover:text-red-800"
                                    >
                                        Löschen
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- User Modal -->
        @if($showModal)
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center">
                <div class="bg-white p-6 rounded-lg shadow-xl w-96">
                    <h3 class="text-lg font-bold mb-4">
                        {{ $editingUser ? 'Benutzer bearbeiten' : 'Neuer Benutzer' }}
                    </h3>
                    
                    <form wire:submit.prevent="saveUser">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Benutzername</label>
                            <input 
                                type="text" 
                                wire:model="username"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required
                            >
                            @error('username') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Vorname</label>
                            <input 
                                type="text" 
                                wire:model="first_name"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required
                            >
                            @error('first_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nachname</label>
                            <input 
                                type="text" 
                                wire:model="last_name"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required
                            >
                            @error('last_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Passwort {{ $editingUser ? '(leer lassen für keine Änderung)' : '' }}
                            </label>
                            <input 
                                type="password" 
                                wire:model="password"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                {{ $editingUser ? '' : 'required' }}
                            >
                            @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    wire:model="is_admin"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                >
                                <span class="ml-2 text-sm text-gray-700">Administrator</span>
                            </label>
                        </div>

                        <div class="mb-6">
                            <label class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    wire:model="can_reset_changes"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                >
                                <span class="ml-2 text-sm text-gray-700">Änderungen zurücksetzen</span>
                            </label>
                        </div>

                        <div class="flex justify-end space-x-4">
                            <button 
                                type="button"
                                wire:click="closeModal"
                                class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50"
                            >
                                Abbrechen
                            </button>
                            <button 
                                type="submit"
                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                            >
                                <span wire:loading.remove>Speichern</span>
                                <span wire:loading>Speichern...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>