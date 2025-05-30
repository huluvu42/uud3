<div class="container mx-auto px-4 py-8">
    


    <!-- Navigation -->
    <div class="mb-4">
        @include('partials.navigation')

    </div>
    <div class="max-w-7xl mx-auto">
        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <!-- Simple Tab Navigation -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-6">
                    <button onclick="showTab('groups')" id="tab-groups"
                            class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-purple-500 text-purple-600">
                        Gruppen ({{ $groups->count() }})
                    </button>
                    <button onclick="showTab('subgroups')" id="tab-subgroups"
                            class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Untergruppen ({{ $subgroups->count() }})
                    </button>
                    <button onclick="showTab('stages')" id="tab-stages"
                            class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Bühnen ({{ $stages->count() }})
                    </button>
                </nav>
            </div>

            <!-- Groups Tab -->
            <div id="content-groups" class="tab-content p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold">Gruppen verwalten</h2>
                    <button wire:click="createGroup" 
                            onmousedown="setActiveTab('groups')"
                            class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600">
                        Neue Gruppe
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Backstage-Tage</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Voucher/Tag</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Untergruppen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($groups as $group)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium">{{ $group->name }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="flex space-x-1">
                                            @for($day = 1; $day <= 4; $day++)
                                                <span class="px-2 py-1 text-xs rounded {{ $group->{"backstage_day_$day"} ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500' }}">
                                                    {{ $day }}
                                                </span>
                                            @endfor
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        {{ $group->voucher_day_1 }}/{{ $group->voucher_day_2 }}/{{ $group->voucher_day_3 }}/{{ $group->voucher_day_4 }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                                            {{ $group->subgroups->count() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm space-x-2">
                                        <button wire:click="editGroup({{ $group->id }})" 
                                                onclick="setActiveTab('groups')"
                                                class="text-blue-600 hover:text-blue-800">
                                            Bearbeiten
                                        </button>
                                        <button wire:click="deleteGroup({{ $group->id }})" 
                                                wire:confirm="Gruppe '{{ $group->name }}' wirklich löschen?"
                                                onclick="setActiveTab('groups')"
                                                class="text-red-600 hover:text-red-800">
                                            Löschen
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        Noch keine Gruppen angelegt. Klicken Sie auf "Neue Gruppe" um zu beginnen.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Subgroups Tab -->
            <div id="content-subgroups" class="tab-content p-6" style="display: none;">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold">Untergruppen verwalten</h2>
                    <button wire:click="createSubgroup" 
                            onmousedown="setActiveTab('subgroups')"
                            class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600">
                        Neue Untergruppe
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hauptgruppe</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($subgroups as $subgroup)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium">{{ $subgroup->name }}</td>
                                    <td class="px-6 py-4 text-sm">{{ $subgroup->group->name }}</td>
                                    <td class="px-6 py-4 text-sm space-x-2">
                                        <button wire:click="editSubgroup({{ $subgroup->id }})" 
                                                onclick="setActiveTab('subgroups')"
                                                class="text-blue-600 hover:text-blue-800">
                                            Bearbeiten
                                        </button>
                                        <button wire:click="deleteSubgroup({{ $subgroup->id }})" 
                                                wire:confirm="Untergruppe '{{ $subgroup->name }}' wirklich löschen?"
                                                onclick="setActiveTab('subgroups')"
                                                class="text-red-600 hover:text-red-800">
                                            Löschen
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                                        Noch keine Untergruppen angelegt.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stages Tab -->
            <div id="content-stages" class="tab-content p-6" style="display: none;">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold">Bühnen verwalten</h2>
                    <button wire:click="createStage" 
                            onmousedown="setActiveTab('stages')"
                            class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600">
                        Neue Bühne
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Anwesenheit</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gast erlaubt</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Voucher/Tag</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($stages as $stage)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium">{{ $stage->name }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-2 py-1 text-xs rounded {{ $stage->presence_days === 'all_days' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ $stage->presence_days === 'all_days' ? 'Alle Tage' : 'Nur Auftrittstag' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-2 py-1 text-xs rounded {{ $stage->guest_allowed ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500' }}">
                                            {{ $stage->guest_allowed ? 'Ja' : 'Nein' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">{{ $stage->vouchers_on_performance_day }}</td>
                                    <td class="px-6 py-4 text-sm space-x-2">
                                        <button wire:click="editStage({{ $stage->id }})" 
                                                onclick="setActiveTab('stages')"
                                                class="text-blue-600 hover:text-blue-800">
                                            Bearbeiten
                                        </button>
                                        <button wire:click="deleteStage({{ $stage->id }})" 
                                                wire:confirm="Bühne '{{ $stage->name }}' wirklich löschen?"
                                                onclick="setActiveTab('stages')"
                                                class="text-red-600 hover:text-red-800">
                                            Löschen
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        Noch keine Bühnen angelegt.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Group Modal -->
        @if($showGroupModal)
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
                <div class="bg-white p-6 rounded-lg shadow-xl w-96 max-w-md max-h-screen overflow-y-auto">
                    <h3 class="text-lg font-bold mb-4">{{ $editingGroup ? 'Gruppe bearbeiten' : 'Neue Gruppe' }}</h3>
                    
                    <form wire:submit.prevent="saveGroup">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Gruppenname</label>
                            <input type="text" wire:model="group_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                            @error('group_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Backstage-Berechtigung</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="group_backstage_day_1" class="rounded">
                                    <span class="ml-2 text-sm">Tag 1</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="group_backstage_day_2" class="rounded">
                                    <span class="ml-2 text-sm">Tag 2</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="group_backstage_day_3" class="rounded">
                                    <span class="ml-2 text-sm">Tag 3</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="group_backstage_day_4" class="rounded">
                                    <span class="ml-2 text-sm">Tag 4</span>
                                </label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Voucher pro Tag</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-xs text-gray-500">Tag 1</label>
                                    <input type="number" step="0.1" wire:model="group_voucher_day_1" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Tag 2</label>
                                    <input type="number" step="0.1" wire:model="group_voucher_day_2" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Tag 3</label>
                                    <input type="number" step="0.1" wire:model="group_voucher_day_3" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Tag 4</label>
                                    <input type="number" step="0.1" wire:model="group_voucher_day_4" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Bemerkungen</label>
                            <textarea wire:model="group_remarks" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
                        </div>

                        <div class="flex justify-end space-x-4">
                            <button type="button" wire:click="closeGroupModal" class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
                                Abbrechen
                            </button>
                            <button type="submit" class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600">
                                Speichern
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- Subgroup Modal -->
        @if($showSubgroupModal)
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
                <div class="bg-white p-6 rounded-lg shadow-xl w-96">
                    <h3 class="text-lg font-bold mb-4">{{ $editingSubgroup ? 'Untergruppe bearbeiten' : 'Neue Untergruppe' }}</h3>
                    
                    <form wire:submit.prevent="saveSubgroup">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                            <input type="text" wire:model="subgroup_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                            @error('subgroup_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Hauptgruppe</label>
                            <select wire:model="subgroup_group_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                                <option value="">Bitte wählen...</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                            @error('subgroup_group_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end space-x-4">
                            <button type="button" wire:click="closeSubgroupModal" class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
                                Abbrechen
                            </button>
                            <button type="submit" class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600">
                                Speichern
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- Stage Modal -->
        @if($showStageModal)
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
                <div class="bg-white p-6 rounded-lg shadow-xl w-96">
                    <h3 class="text-lg font-bold mb-4">{{ $editingStage ? 'Bühne bearbeiten' : 'Neue Bühne' }}</h3>
                    
                    <form wire:submit.prevent="saveStage">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Bühnenname</label>
                            <input type="text" wire:model="stage_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                            @error('stage_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Anwesenheitsberechtigung</label>
                            <select wire:model="stage_presence_days" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="performance_day">Nur am Auftrittstag</option>
                                <option value="all_days">Alle Festival-Tage</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="stage_guest_allowed" class="rounded">
                                <span class="ml-2 text-sm">Gast erlaubt</span>
                            </label>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Voucher am Auftrittstag</label>
                            <input type="number" step="0.1" wire:model="stage_vouchers_on_performance_day" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            @error('stage_vouchers_on_performance_day') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex justify-end space-x-4">
                            <button type="button" wire:click="closeStageModal" class="px-4 py-2 text-gray-600 border border-gray-300 rounded hover:bg-gray-50">
                                Abbrechen
                            </button>
                            <button type="submit" class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600">
                                Speichern
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
// Sehr einfaches und robustes Tab-Management
let activeTab = localStorage.getItem('groupManagementTab') || 'groups';

function showTab(tab) {
    console.log('Showing tab:', tab);
    
    // Alle Inhalte verstecken
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    
    // Alle Tab-Buttons zurücksetzen
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.className = 'tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
    });
    
    // Aktiven Inhalt anzeigen
    const contentEl = document.getElementById('content-' + tab);
    if (contentEl) {
        contentEl.style.display = 'block';
    }
    
    // Aktiven Tab-Button highlighten
    const tabEl = document.getElementById('tab-' + tab);
    if (tabEl) {
        tabEl.className = 'tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-purple-500 text-purple-600';
    }
    
    // Tab speichern
    localStorage.setItem('groupManagementTab', tab);
    activeTab = tab;
}

function setActiveTab(tab) {
    console.log('Setting active tab to:', tab);
    localStorage.setItem('groupManagementTab', tab);
    activeTab = tab;
}

// Initial tab anzeigen
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, showing initial tab:', activeTab);
    setTimeout(() => showTab(activeTab), 200);
});

// HAUPTFUNKTION: Nach JEDEM Livewire-Update den Tab wiederherstellen
function restoreActiveTab() {
    const savedTab = localStorage.getItem('groupManagementTab') || 'groups';
    console.log('Restoring tab:', savedTab);
    setTimeout(() => showTab(savedTab), 50);
}

// Mehrere Event-Listener für maximale Abdeckung
document.addEventListener('livewire:navigated', restoreActiveTab);
document.addEventListener('livewire:load', restoreActiveTab);

// Backup: Regelmäßig prüfen und korrigieren
setInterval(() => {
    const savedTab = localStorage.getItem('groupManagementTab') || 'groups';
    const currentVisibleTab = getCurrentVisibleTab();
    
    if (savedTab !== currentVisibleTab) {
        console.log('Tab mismatch detected! Saved:', savedTab, 'Visible:', currentVisibleTab);
        showTab(savedTab);
    }
}, 1000);

// Hilfsfunktion: Welcher Tab ist gerade sichtbar?
function getCurrentVisibleTab() {
    if (document.getElementById('content-groups').style.display !== 'none') return 'groups';
    if (document.getElementById('content-subgroups').style.display !== 'none') return 'subgroups';
    if (document.getElementById('content-stages').style.display !== 'none') return 'stages';
    return 'groups'; // fallback
}

// Event-Delegation für alle Livewire-Events
document.addEventListener('DOMContentLoaded', function() {
    // Observer für DOM-Änderungen
    const observer = new MutationObserver(function(mutations) {
        let shouldRestore = false;
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' || mutation.type === 'attributes') {
                shouldRestore = true;
            }
        });
        
        if (shouldRestore) {
            setTimeout(restoreActiveTab, 100);
        }
    });
    
    // Observer starten
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['wire:id', 'wire:loading']
    });
});
</script>