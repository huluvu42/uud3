{{-- resources/views/partials/navigation.blade.php --}}

<div class="border-b bg-white shadow-sm">
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between py-4">
            <h1 class="text-2xl font-bold text-gray-800">U&D Manager</h1>
            <div class="flex items-center space-x-4">
                <!-- Management Menu -->
                <!-- Dashboard Link -->
                <div class="border-r border-gray-300 pr-4">
                    <a href="{{ route('dashboard') }}"
                        class="{{ request()->routeIs('dashboard') ? 'ring-2 ring-green-300' : '' }} rounded bg-green-100 px-3 py-2 text-sm text-green-700 transition-colors hover:bg-green-200">
                        🏠 Suche / Startseite
                    </a>
                </div>
                <!-- Personen & Bands Links -->
                <div class="flex items-center space-x-2 border-r border-gray-300 pr-4">

                    <a href="{{ route('management.persons') }}"
                        class="{{ request()->routeIs('management.persons') ? 'ring-2 ring-purple-300' : '' }} rounded bg-purple-100 px-3 py-2 text-sm text-purple-700 transition-colors hover:bg-purple-200">
                        👥 Personen
                    </a>
                    <a href="{{ route('management.bands') }}"
                        class="{{ request()->routeIs('management.bands') ? 'ring-2 ring-purple-300' : '' }} rounded bg-purple-100 px-3 py-2 text-sm text-purple-700 transition-colors hover:bg-purple-200">
                        🎵 Bands
                    </a>

                </div>

                <!-- Admin Dropdown (nur für Admins) -->
                @if (auth()->user()->is_admin)
                    <div class="relative border-r border-gray-300 pr-4">
                        <div class="dropdown">
                            <button onclick="toggleDropdown('adminDropdown')"
                                class="{{ request()->routeIs(['management.groups', 'admin.users', 'admin.settings', 'admin.changelog', 'admin.knack-import', 'admin.import', 'admin.datenimport', 'admin.knack-objects', 'admin.knack-objekte']) ? 'ring-2 ring-blue-300' : '' }} flex items-center space-x-1 rounded bg-blue-100 px-3 py-2 text-sm text-blue-700 transition-colors hover:bg-blue-200">
                                <span>⚙️ Admin</span>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="adminDropdown"
                                class="absolute right-0 z-50 mt-2 hidden w-48 rounded-md border border-gray-200 bg-white shadow-lg">
                                <div class="py-1">
                                    <a href="{{ route('management.groups') }}"
                                        class="{{ request()->routeIs('management.groups') ? 'bg-blue-50 text-blue-700' : '' }} block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        📁 Gruppen & Bühnen
                                    </a>
                                    <a href="{{ route('admin.users') }}"
                                        class="{{ request()->routeIs('admin.users') ? 'bg-blue-50 text-blue-700' : '' }} block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        👥 Benutzer verwalten
                                    </a>
                                    <a href="{{ route('admin.settings') }}"
                                        class="{{ request()->routeIs('admin.settings') ? 'bg-blue-50 text-blue-700' : '' }} block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        ⚙️ Einstellungen
                                    </a>
                                    <a href="{{ route('admin.person-import') }}"
                                        class="{{ request()->routeIs(['admin.person-import', 'admin.personen-import']) ? 'bg-blue-50 text-blue-700' : '' }} block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        📥 Personen Import
                                    </a>
                                    <a href="{{ route('admin.knack-import') }}"
                                        class="{{ request()->routeIs(['admin.knack-import', 'admin.import', 'admin.datenimport']) ? 'bg-blue-50 text-blue-700' : '' }} block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        📥 Knack Import
                                    </a>
                                    <a href="{{ route('admin.knack-objects') }}"
                                        class="{{ request()->routeIs(['admin.knack-objects', 'admin.knack-objekte']) ? 'bg-blue-50 text-blue-700' : '' }} block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        🔗 Knack Objects
                                    </a>
                                    <a href="{{ route('admin.duplicates') }}"
                                        class="{{ request()->routeIs(['admin.duplicates', 'admin.duplikate']) ? 'bg-blue-50 text-blue-700' : '' }} block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        👥 Duplikat-Verwaltung
                                    </a>
                                    <a href="{{ route('admin.changelog') }}"
                                        class="{{ request()->routeIs('admin.changelog') ? 'bg-blue-50 text-blue-700' : '' }} block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        📋 Protokoll anzeigen
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <!-- User Info & Status -->
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <div class="text-sm font-medium text-gray-900">{{ auth()->user()->first_name }}
                            {{ auth()->user()->last_name }}</div>
                        <div class="text-xs text-gray-500">
                            @if (auth()->user()->is_admin)
                                Administrator
                            @else
                                Mitarbeiter
                            @endif
                        </div>
                    </div>

                    @php
                        // Festival-Settings aus der Datenbank laden
                        $settings = \App\Models\Settings::current();

                        if ($settings) {
                            // Aktuellen Festival-Tag automatisch ermitteln
                            $currentDay = $settings->getCurrentDay();
                            $currentDayDate = $settings->getDateForDay($currentDay);

                            // Falls kein Datum für den aktuellen Tag, verwende den nächsten verfügbaren
                            if (!$currentDayDate) {
                                $currentDay = $settings->getNextFestivalDay();
                                $currentDayDate = $settings->getDateForDay($currentDay);
                            }

                            // Session aktualisieren falls sich der Tag geändert hat
                            if (session('current_day') !== $currentDay) {
                                session(['current_day' => $currentDay]);
                            }
                        } else {
                            // Fallback wenn keine Einstellungen vorhanden
                            $currentDay = session('current_day', 1);
                            $currentDayDate = \Carbon\Carbon::parse(config('festival.start_date', '2025-08-07'));
                        }

                        // Sicherstellen, dass wir ein Carbon-Objekt haben
                        if ($currentDayDate && !$currentDayDate instanceof \Carbon\Carbon) {
                            $currentDayDate = \Carbon\Carbon::parse($currentDayDate);
                        }
                    @endphp

                    <div class="border-l border-gray-300 pl-4 text-center">
                        @php
                            // Deutsche Wochentage (2 Buchstaben)
                            $germanWeekdays = [
                                0 => 'So', // Sonntag
                                1 => 'Mo', // Montag
                                2 => 'Di', // Dienstag
                                3 => 'Mi', // Mittwoch
                                4 => 'Do', // Donnerstag
                                5 => 'Fr', // Freitag
                                6 => 'Sa', // Samstag
                            ];

                            $weekdayShort = $currentDayDate ? $germanWeekdays[$currentDayDate->dayOfWeek] : '';
                        @endphp

                        <div class="text-sm font-medium text-gray-700">
                            Tag {{ $currentDay }}@if ($weekdayShort)
                                ({{ $weekdayShort }})
                            @endif
                        </div>
                        @if ($currentDayDate)
                            <div class="text-xs text-gray-500">{{ $currentDayDate->format('d.m.Y') }}</div>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="rounded bg-red-100 px-3 py-2 text-sm text-red-700 transition-colors hover:bg-red-200">
                            🚪 Abmelden
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleDropdown(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        const isHidden = dropdown.classList.contains('hidden');

        // Alle anderen Dropdowns schließen
        document.querySelectorAll('[id$="Dropdown"]').forEach(dd => {
            if (dd.id !== dropdownId) {
                dd.classList.add('hidden');
            }
        });

        // Das gewünschte Dropdown umschalten
        if (isHidden) {
            dropdown.classList.remove('hidden');
        } else {
            dropdown.classList.add('hidden');
        }
    }

    // Dropdown schließen wenn außerhalb geklickt wird
    document.addEventListener('click', function(event) {
        const dropdowns = document.querySelectorAll('[id$="Dropdown"]');
        const buttons = document.querySelectorAll('.dropdown button');

        let clickedOnDropdown = false;
        buttons.forEach(button => {
            if (button.contains(event.target)) {
                clickedOnDropdown = true;
            }
        });

        dropdowns.forEach(dropdown => {
            if (!dropdown.contains(event.target) && !clickedOnDropdown) {
                dropdown.classList.add('hidden');
            }
        });
    });
</script>
