{{-- resources/views/livewire/admin/knack-objects-management.blade.php --}}

<div class="container mx-auto px-4 py-8">
    @include('partials.navigation')

    <div class="mx-auto max-w-7xl">
        <!-- Success/Error Messages -->
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

        <div class="rounded-lg bg-white p-6 shadow-md">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-2xl font-bold">Knack Objects Verwaltung</h2>
                <button wire:click="openCreateModal" class="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600">
                    ➕ Neues Object
                </button>
            </div>

            <!-- Objects Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Object Key</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">App ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">API Key</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Beschreibung
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($knackObjects as $knackObject)
                            <tr class="{{ !$knackObject->active ? 'bg-gray-50 opacity-75' : '' }}">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $knackObject->name }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <code
                                        class="rounded bg-gray-100 px-2 py-1 text-sm">{{ $knackObject->object_key }}</code>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ $knackObject->app_id ?: '-' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    <span class="rounded bg-blue-50 px-2 py-1 font-mono text-xs text-blue-800">
                                        {{ $knackObject->getMaskedApiKey() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ Str::limit($knackObject->description, 50) ?: '-' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="{{ $knackObject->status_color }} rounded-full px-2 py-1 text-xs">
                                        {{ $knackObject->status_text }}
                                    </span>
                                </td>
                                <td class="space-x-2 whitespace-nowrap px-6 py-4 text-sm font-medium">
                                    @if ($knackObject->isComplete())
                                        <button wire:click="testConnection({{ $knackObject->id }})"
                                            class="text-green-600 hover:text-green-900" title="Verbindung testen">
                                            🔍 Test
                                        </button>
                                    @endif
                                    <button wire:click="openEditModal({{ $knackObject->id }})"
                                        class="text-blue-600 hover:text-blue-900">
                                        ✏️ Bearbeiten
                                    </button>
                                    <button wire:click="delete({{ $knackObject->id }})"
                                        onclick="return confirm('Sind Sie sicher, dass Sie dieses Object löschen möchten?')"
                                        class="text-red-600 hover:text-red-900">
                                        🗑️ Löschen
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                    Keine Knack Objects vorhanden.
                                    <button wire:click="openCreateModal" class="text-blue-500 hover:underline">
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
        @if ($showModal)
            <div class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50">
                <div class="relative top-20 mx-auto w-11/12 rounded-md border bg-white p-5 shadow-lg md:w-1/2">
                    <div class="mt-3">
                        <h3 class="mb-4 text-lg font-medium">
                            {{ $editingId ? 'Knack Object bearbeiten' : 'Neues Knack Object' }}
                        </h3>

                        <form wire:submit.prevent="save">
                            <div class="space-y-4">
                                <!-- Name -->
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">
                                        Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" wire:model="name"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="z.B. Mitarbeiter 2024">
                                    @error('name')
                                        <span class="text-sm text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Object Key -->
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">
                                        Object Key <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" wire:model="object_key"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="z.B. object_1">
                                    <p class="mt-1 text-xs text-gray-500">
                                        Den Object Key findest du in der Knack Builder URL oder API Dokumentation
                                    </p>
                                    @error('object_key')
                                        <span class="text-sm text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- App ID -->
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">
                                        App ID <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" wire:model="app_id"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="z.B. 5f1234567890abcdef123456">
                                    <p class="mt-1 text-xs text-gray-500">
                                        Die Knack Application ID findest du in der URL des Knack Builders
                                    </p>
                                    @error('app_id')
                                        <span class="text-sm text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- API Key -->
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">
                                        API Key <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="{{ $showApiKey ? 'text' : 'password' }}" wire:model="api_key"
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="API Key eingeben...">
                                        <button type="button" wire:click="toggleShowApiKey"
                                            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600">
                                            @if ($showApiKey)
                                                👁️
                                            @else
                                                🙈
                                            @endif
                                        </button>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Den API Key findest du in den Knack Account-Einstellungen unter "API & Code"
                                    </p>
                                    @error('api_key')
                                        <span class="text-sm text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Description -->
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">
                                        Beschreibung <span class="text-gray-400">(optional)</span>
                                    </label>
                                    <textarea wire:model="description" rows="3"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="Beschreibung dieses Knack Objects..."></textarea>
                                    @error('description')
                                        <span class="text-sm text-red-500">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Active -->
                                <div class="flex items-center">
                                    <input type="checkbox" wire:model="active"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <label class="ml-2 block text-sm text-gray-900">
                                        Aktiv (Object im Import-Dropdown anzeigen)
                                    </label>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end space-x-2">
                                <button type="button" wire:click="closeModal"
                                    class="rounded bg-gray-500 px-4 py-2 text-white hover:bg-gray-600">
                                    Abbrechen
                                </button>
                                <button type="submit"
                                    class="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600">
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
