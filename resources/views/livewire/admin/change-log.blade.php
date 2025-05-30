<!-- resources/views/livewire/admin/change-log.blade.php -->
<div class="min-h-screen bg-gray-100 p-4">
    <div class="max-w-7xl mx-auto">
        <!-- Header with Navigation -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-800">Änderungsprotokoll</h1>
                <div class="flex space-x-4">
                    <!-- Admin Navigation -->
                    <a href="{{ route('admin.users') }}" 
                       class="px-3 py-2 rounded text-blue-600 hover:text-blue-800 hover:bg-blue-50">
                        Benutzer
                    </a>
                    <a href="{{ route('admin.settings') }}" 
                       class="px-3 py-2 rounded text-blue-600 hover:text-blue-800 hover:bg-blue-50">
                        Einstellungen
                    </a>
                    <a href="{{ route('admin.changelog') }}" 
                       class="px-3 py-2 rounded bg-blue-100 text-blue-800 font-medium">
                        Protokoll
                    </a>
                    <span class="text-gray-300">|</span>
                    <a href="{{ route('home') }}" class="text-green-600 hover:text-green-800">← Hauptseite</a>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('success') || session()->has('error'))
            <div class="mb-4">
                @if (session()->has('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session()->has('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        {{ session('error') }}
                    </div>
                @endif
            </div>
        @endif

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">Filter</h2>
                <button 
                    wire:click="clearFilters"
                    class="px-3 py-1 text-sm bg-gray-500 text-white rounded hover:bg-gray-600"
                >
                    Filter zurücksetzen
                </button>
            </div>
            
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Benutzer suchen</label>
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="filterUser"
                        placeholder="Name oder Benutzername..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tabelle</label>
                    <select 
                        wire:model.live="filterTable"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Alle Tabellen</option>
                        @if(isset($availableTables))
                            @foreach($availableTables as $table)
                                <option value="{{ $table }}">
                                    {{ ucfirst(str_replace('_', ' ', $table)) }}
                                </option>
                            @endforeach
                        @else
                            <option value="persons">Personen</option>
                            <option value="bands">Bands</option>
                            <option value="groups">Gruppen</option>
                            <option value="stages">Bühnen</option>
                            <option value="users">Benutzer</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Aktion</label>
                    <select 
                        wire:model.live="filterAction"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Alle Aktionen</option>
                        <option value="create">Erstellt</option>
                        <option value="update">Geändert</option>
                        <option value="delete">Gelöscht</option>
                        <option value="revert">Rückgängig gemacht</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="text-2xl font-bold text-blue-600">{{ $changes->total() }}</div>
                <div class="text-sm text-gray-600">Gesamte Änderungen</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="text-2xl font-bold text-green-600">
                    {{ \App\Models\ChangeLog::where('action', 'create')->count() }}
                </div>
                <div class="text-sm text-gray-600">Erstellt</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="text-2xl font-bold text-yellow-600">
                    {{ \App\Models\ChangeLog::where('action', 'update')->count() }}
                </div>
                <div class="text-sm text-gray-600">Geändert</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="text-2xl font-bold text-red-600">
                    {{ \App\Models\ChangeLog::where('action', 'delete')->count() }}
                </div>
                <div class="text-sm text-gray-600">Gelöscht</div>
            </div>
        </div>

        <!-- Changes Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            @if($changes->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Zeitpunkt
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Benutzer
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tabelle
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Datensatz ID
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Feld
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Änderung
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aktion
                                </th>
                                @if($canReset)
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Aktionen
                                    </th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($changes as $change)
                                <tr class="hover:bg-gray-50 {{ $change->action === 'revert' ? 'bg-yellow-50' : '' }}">
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div>{{ $change->created_at->format('d.m.Y') }}</div>
                                        <div class="text-xs text-gray-500">{{ $change->created_at->format('H:i:s') }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($change->user)
                                            <div class="font-medium">{{ $change->user->first_name }} {{ $change->user->last_name }}</div>
                                            <div class="text-xs text-gray-500">{{ $change->user->username }}</div>
                                        @else
                                            <span class="text-gray-400">System</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded">
                                            {{ ucfirst(str_replace('_', ' ', $change->table_name)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        #{{ $change->record_id }}
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium">
                                        {{ $change->field_name }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($change->action !== 'create' && $change->action !== 'delete')
                                            <div class="space-y-1">
                                                <div class="flex items-center">
                                                    <span class="text-xs text-gray-500 mr-2">Alt:</span>
                                                    <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded max-w-xs truncate">
                                                        {{ $change->old_value ?? 'NULL' }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center">
                                                    <span class="text-xs text-gray-500 mr-2">Neu:</span>
                                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded max-w-xs truncate">
                                                        {{ $change->new_value ?? 'NULL' }}
                                                    </span>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-500">
                                                {{ $change->action === 'create' ? 'Datensatz erstellt' : 'Datensatz gelöscht' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 text-xs rounded-full font-medium
                                            {{ $change->action === 'create' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $change->action === 'update' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $change->action === 'delete' ? 'bg-red-100 text-red-800' : '' }}
                                            {{ $change->action === 'revert' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        ">
                                            {{ ucfirst($change->action) }}
                                        </span>
                                    </td>
                                    @if($canReset)
                                        <td class="px-4 py-3 text-sm">
                                            @if($change->action === 'update')
                                                <button 
                                                    wire:click="resetChanges({{ $change->id }})"
                                                    wire:confirm="Diese Änderung wirklich rückgängig machen? Das Feld '{{ $change->field_name }}' wird auf den Wert '{{ $change->old_value }}' zurückgesetzt."
                                                    class="px-2 py-1 text-xs bg-orange-500 text-white rounded hover:bg-orange-600 transition-colors"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                >
                                                    <span wire:loading.remove wire:target="resetChanges({{ $change->id }})">Rückgängig</span>
                                                    <span wire:loading wire:target="resetChanges({{ $change->id }})">...</span>
                                                </button>
                                            @else
                                                <span class="text-xs text-gray-400">-</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $changes->links() }}
                </div>
            @else
                <div class="p-8 text-center text-gray-500">
                    <div class="mb-4">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="text-lg mb-2">Keine Änderungen gefunden</div>
                    <div class="text-sm">
                        @if($filterUser || $filterTable || $filterAction)
                            Versuchen Sie, die Filter zu ändern oder zurückzusetzen.
                        @else
                            Sobald Änderungen vorgenommen werden, erscheinen sie hier.
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>