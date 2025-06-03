{{-- resources/views/livewire/admin/knack-objects-management.blade.php --}}

<div class="container mx-auto px-4 py-8">
    @include('partials.navigation')

    <div class="max-w-6xl mx-auto">
        <!-- Success/Error Messages -->
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

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Knack Objects Verwaltung</h2>
                <button 
                    wire:click="openCreateModal"
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                >
                    ➕ Neues Object
                </button>
            </div>

            <!-- Objects Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Object Key</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">App ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Beschreibung</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($knackObjects as $knackObject)
                            <tr class="{{ !$knackObject->active ? 'bg-gray-50 opacity-75' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-gray-900">{{ $knackObject->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <code class="px-2 py-1 bg-gray-100 text-sm rounded">{{ $knackObject->object_key }}</code>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $knackObject->app_id ?: 'Standard' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ Str::limit($knackObject->description, 50) ?: '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button 
                                        wire:click="toggleActive({{ $knackObject->id }})"
                                        class="px-2 py-1 text-xs rounded-full {{ $knackObject->active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}"
                                    >
                                        {{ $knackObject->active ? 'Aktiv' : 'Inaktiv' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button 
                                        wire:click="openEditModal({{ $knackObject->id }})"
                                        class="text-blue-600 hover:text-blue-900"
                                    >
                                        ✏️ Bearbeiten
                                    </button>
                                    <button 
                                        wire:click="delete({{ $knackObject->id }})"
                                        onclick="return confirm('Sind Sie sicher, dass Sie dieses Object löschen möchten?')"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        🗑️ Löschen
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    Keine Knack Objects vorhanden. 
                                    <button 
                                        wire:click="openCreateModal"
                                        class="text-blue-500 hover:underline"
                                    >
                                        Erstellen Sie das erste Object
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        @if($showModal)
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <h3 class="text-lg font-medium mb-4">
                            {{ $editingId ? 'Knack Object bearbeiten' : 'Neues Knack Object' }}
                        </h3>
                        
                        <form wire:submit.prevent="save">
                            <div class="space-y-4">
                                <!-- Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Name <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        wire:model="name"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="z.B. Mitarbeiter 2024"
                                    >
                                    @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Object Key -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Object Key <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        wire:model="object_key"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="z.B. object_1"
                                    >
                                    <p class="text-xs text-gray-500 mt-1">
                                        Den Object Key findest du in der Knack Builder URL oder API Dokumentation
                                    </p>
                                    @error('object_key') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- App ID -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        App ID <span class="text-gray-400">(optional)</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        wire:model="app_id"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="Leer lassen für Standard App-ID"
                                    >
                                    <p class="text-xs text-gray-500 mt-1">
                                        Falls leer, wird die Standard App-ID aus den Einstellungen verwendet
                                    </p>
                                    @error('app_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Description -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Beschreibung <span class="text-gray-400">(optional)</span>
                                    </label>
                                    <textarea 
                                        wire:model="description"
                                        rows="3"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="Beschreibung dieses Knack Objects..."
                                    ></textarea>
                                    @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Active -->
                                <div class="flex items-center">
                                    <input 
                                        type="checkbox" 
                                        wire:model="active"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    >
                                    <label class="ml-2 block text-sm text-gray-900">
                                        Aktiv (Object im Import-Dropdown anzeigen)
                                    </label>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-2 mt-6">
                                <button 
                                    type="button"
                                    wire:click="closeModal"
                                    class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
                                >
                                    Abbrechen
                                </button>
                                <button 
                                    type="submit"
                                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                                >
                                    {{ $editingId ? 'Aktualisieren' : 'Erstellen' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>