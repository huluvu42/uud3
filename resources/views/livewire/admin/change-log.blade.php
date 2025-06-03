<div class="container mx-auto px-4 py-8">
    @include('partials.navigation')

    <div class="max-w-7xl mx-auto mt-6">
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

        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">Änderungsprotokoll</h2>
            
            <!-- Filters -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 mb-4">
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Datensatz suchen</label>
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="filterRecord"
                        placeholder="Person, Band, Wert..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tabelle</label>
                    <select 
                        wire:model.live="filterTable"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Alle Tabellen</option>
                        @foreach($availableTables as $table)
                            <option value="{{ $table }}">
                                {{ ucfirst(str_replace('_', ' ', $table)) }}
                            </option>
                        @endforeach
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
                <div class="flex items-end">
                    <button 
                        wire:click="clearFilters"
                        class="w-full px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
                    >
                        Filter zurücksetzen
                    </button>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $changes->total() }}</div>
                    <div class="text-sm text-gray-600">Gesamte Änderungen</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-green-600">
                        {{ \App\Models\ChangeLog::where('action', 'create')->count() }}
                    </div>
                    <div class="text-sm text-gray-600">Erstellt</div>
                </div>
                <div class="bg-yellow-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-600">
                        {{ \App\Models\ChangeLog::where('action', 'update')->count() }}
                    </div>
                    <div class="text-sm text-gray-600">Geändert</div>
                </div>
                <div class="bg-red-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-red-600">
                        {{ \App\Models\ChangeLog::where('action', 'delete')->count() }}
                    </div>
                    <div class="text-sm text-gray-600">Gelöscht</div>
                </div>
            </div>
        </div>

        <!-- Changes Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            @if($changes->count() > 0)
                <div class="overflow-x-auto" style="max-height: calc(100vh - 500px); overflow-y: auto;">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Zeitpunkt
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Benutzer
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Datensatz
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Feld & Änderung
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
                                @php $recordInfo = $this->getRecordInfo($change) @endphp
                                <tr class="hover:bg-gray-50 {{ $change->action === 'revert' ? 'bg-yellow-50' : '' }}">
                                    <!-- Zeitpunkt -->
                                    <td class="px-4 py-4 text-sm text-gray-900">
                                        <div class="font-medium">{{ $change->created_at->format('d.m.Y') }}</div>
                                        <div class="text-xs text-gray-500">{{ $change->created_at->format('H:i:s') }}</div>
                                    </td>

                                    <!-- Benutzer -->
                                    <td class="px-4 py-4 text-sm">
                                        @if($change->user)
                                            <div class="font-medium">{{ $change->user->first_name }} {{ $change->user->last_name }}</div>
                                            <div class="text-xs text-gray-500">{{ $change->user->username }}</div>
                                        @else
                                            <span class="text-gray-400">System</span>
                                        @endif
                                    </td>

                                    <!-- Datensatz Information -->
                                    <td class="px-4 py-4 text-sm">
                                        <div class="flex items-center space-x-2">
                                            @if($recordInfo)
                                                <span class="text-lg">{{ $recordInfo['icon'] }}</span>
                                                <div>
                                                    <div class="font-medium text-gray-900">{{ $recordInfo['title'] }}</div>
                                                    @if($recordInfo['subtitle'])
                                                        <div class="text-xs text-gray-500">{{ $recordInfo['subtitle'] }}</div>
                                                    @endif
                                                    <div class="text-xs text-gray-400">
                                                        {{ ucfirst(str_replace('_', ' ', $change->table_name)) }} #{{ $change->record_id }}
                                                    </div>
                                                </div>
                                            @else
                                                <div>
                                                    <div class="font-medium text-gray-900">ID: {{ $change->record_id }}</div>
                                                    <div class="text-xs text-gray-500">
                                                        {{ ucfirst(str_replace('_', ' ', $change->table_name)) }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Feld & Änderung -->
                                    <td class="px-4 py-4 text-sm">
                                        <div class="space-y-2">
                                            <div class="font-medium text-gray-900">{{ $change->field_name }}</div>
                                            
                                            @if($change->action === 'create')
                                                <div class="flex items-center space-x-2">
                                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">
                                                        Erstellt: {{ Str::limit($change->new_value ?? 'NULL', 30) }}
                                                    </span>
                                                </div>
                                            @elseif($change->action === 'delete')
                                                <div class="flex items-center space-x-2">
                                                    <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded">
                                                        Gelöscht: {{ Str::limit($change->old_value ?? 'NULL', 30) }}
                                                    </span>
                                                </div>
                                            @else
                                                <div class="space-y-1">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="text-xs text-gray-500 w-8">Alt:</span>
                                                        <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded flex-1 truncate">
                                                            {{ $change->old_value ?? 'NULL' }}
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center space-x-2">
                                                        <span class="text-xs text-gray-500 w-8">Neu:</span>
                                                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded flex-1 truncate">
                                                            {{ $change->new_value ?? 'NULL' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Aktion -->
                                    <td class="px-4 py-4 text-sm">
                                        <span class="px-2 py-1 text-xs rounded-full font-medium
                                            {{ $change->action === 'create' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $change->action === 'update' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $change->action === 'delete' ? 'bg-red-100 text-red-800' : '' }}
                                            {{ $change->action === 'revert' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        ">
                                            {{ ucfirst($change->action) }}
                                        </span>
                                    </td>

                                    <!-- Aktionen -->
                                    @if($canReset)
                                        <td class="px-4 py-4 text-sm">
                                            @if($change->action === 'update')
                                                <button 
                                                    wire:click="resetChanges({{ $change->id }})"
                                                    wire:confirm="Diese Änderung wirklich rückgängig machen? Das Feld '{{ $change->field_name }}' wird auf den Wert '{{ $change->old_value }}' zurückgesetzt."
                                                    class="px-3 py-1 text-xs bg-orange-500 text-white rounded hover:bg-orange-600 transition-colors"
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
                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
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