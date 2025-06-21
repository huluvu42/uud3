{{-- resources/views/band-registration/form.blade.php --}}
{{-- Öffentliches Registrierungsformular --}}
{{-- ============================================================================ --}}

@extends('layouts.public')

@section('title', 'Bandmitglieder registrieren - ' . $band->band_name)

@section('content')
    <div class="min-h-screen bg-gray-50 py-12">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 rounded-lg bg-white p-8 shadow-lg">
                <div class="text-center">
                    <h1 class="mb-2 text-3xl font-bold text-gray-900">Bandmitglieder registrieren</h1>
                    <h2 class="mb-4 text-xl text-blue-600">{{ $band->band_name }}</h2>
                    <div class="flex items-center justify-center space-x-4 text-sm text-gray-600">
                        @if ($band->stage)
                            <span class="inline-flex items-center rounded-full bg-purple-100 px-3 py-1 text-purple-800">
                                🎤 {{ $band->stage->name }}
                            </span>
                        @endif
                        <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-blue-800">
                            📅 {{ $band->year }}
                        </span>
                    </div>
                    <p class="mt-4 text-gray-600">
                        Herzlich willkommen! Bitte geben Sie die Daten Ihrer Bandmitglieder ein.
                    </p>
                </div>
            </div>

            <!-- Fehler-Anzeige -->
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                Bitte korrigieren Sie folgende Fehler:
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc space-y-1 pl-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Hauptformular -->
            <form method="POST" action="{{ route('band.register.store', $band->registration_token) }}" class="space-y-8">
                @csrf

                <!-- Schritt 1: Travel Party -->
                <div class="rounded-lg bg-white p-8 shadow-lg">
                    <h3 class="mb-6 text-lg font-semibold text-gray-900">1. Anzahl Bandmitglieder</h3>

                    <div class="max-w-sm">
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Wie viele Personen reisen mit Ihrer Band an? <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="travel_party" id="travel_party"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            min="1" max="50" value="{{ old('travel_party', $band->travel_party) }}" required>
                        <p class="mt-2 text-sm text-gray-600">
                            Geben Sie die Gesamtzahl aller anreisenden Personen an (inkl. Crew, Techniker, etc.)
                        </p>
                    </div>
                </div>

                <!-- Schritt 2: Bandmitglieder (erweitert) -->
                <div class="rounded-lg bg-white p-8 shadow-lg">
                    <h3 class="mb-6 text-lg font-semibold text-gray-900">2. Bandmitglieder</h3>

                    <div id="members-container" class="space-y-6">
                        @if (old('members') || $existingMembers)
                            @php
                                $members = old('members', $existingMembers);
                                $memberCount = old('travel_party', $band->travel_party ?? count($members));
                            @endphp

                            @for ($i = 0; $i < $memberCount; $i++)
                                <div class="member-row rounded-lg bg-gray-50 p-6">
                                    <h4 class="mb-4 border-b border-gray-200 pb-2 font-medium text-gray-900">
                                        👤 Person {{ $i + 1 }}
                                    </h4>

                                    <!-- Hauptperson -->
                                    <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                                Vorname <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" name="members[{{ $i }}][first_name]"
                                                class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                value="{{ $members[$i]['first_name'] ?? '' }}" required>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                                Nachname <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" name="members[{{ $i }}][last_name]"
                                                class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                value="{{ $members[$i]['last_name'] ?? '' }}" required>
                                        </div>
                                    </div>

                                    <!-- KFZ-Kennzeichen für diese Person -->
                                    <div class="mb-4">
                                        <label class="mb-1 block text-sm font-medium text-gray-700">
                                            🚗 KFZ-Kennzeichen (optional)
                                        </label>
                                        <input type="text" name="members[{{ $i }}][vehicle_plate]"
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 uppercase shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="z.B. B-MW 1234" value="{{ $members[$i]['vehicle_plate'] ?? '' }}">
                                        <p class="mt-1 text-xs text-gray-500">Falls diese Person mit eigenem Fahrzeug
                                            anreist</p>
                                    </div>

                                    <!-- Gast für diese Person -->
                                    <div class="border-t border-gray-200 pt-4">
                                        <h5 class="mb-3 text-sm font-medium text-gray-700">👥 Gast (optional)</h5>
                                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-gray-600">
                                                    Gast Vorname
                                                </label>
                                                <input type="text" name="members[{{ $i }}][guest_first_name]"
                                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    value="{{ $members[$i]['guest_first_name'] ?? '' }}"
                                                    placeholder="Vorname des Gastes">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-gray-600">
                                                    Gast Nachname
                                                </label>
                                                <input type="text" name="members[{{ $i }}][guest_last_name]"
                                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    value="{{ $members[$i]['guest_last_name'] ?? '' }}"
                                                    placeholder="Nachname des Gastes">
                                            </div>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">Falls diese Person einen Gast mitbringt</p>
                                    </div>
                                </div>
                            @endfor
                        @endif
                    </div>
                </div>
                <!-- Schritt 4: Zusätzliche Informationen -->
                <div class="rounded-lg bg-white p-8 shadow-lg">
                    <h3 class="mb-6 text-lg font-semibold text-gray-900">4. Zusätzliche Informationen</h3>

                    <div class="space-y-6">
                        <!-- Notfallkontakt -->
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">
                                Notfallkontakt
                            </label>
                            <input type="text" name="emergency_contact"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Name und Telefonnummer für Notfälle"
                                value="{{ old('emergency_contact', $band->emergency_contact) }}">
                            <p class="mt-1 text-sm text-gray-600">
                                Kontaktperson, die im Notfall erreicht werden kann.
                            </p>
                        </div>

                        <!-- Besondere Anforderungen -->
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">
                                Besondere Anforderungen
                            </label>
                            <textarea name="special_requirements" rows="4"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="z.B. Diätanforderungen, Barrierefreiheit, besondere Ausrüstung...">{{ old('special_requirements', $band->special_requirements) }}</textarea>
                            <p class="mt-1 text-sm text-gray-600">
                                Teilen Sie uns mit, wenn Sie besondere Unterstützung benötigen.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="rounded-lg bg-white p-8 shadow-lg">
                    <div class="text-center">
                        <button type="submit"
                            class="inline-flex items-center rounded-md border border-transparent bg-green-600 px-8 py-3 text-base font-medium text-white transition-colors duration-200 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            Registrierung abschließen
                        </button>

                        <p class="mt-4 text-sm text-gray-600">
                            Sie erhalten nach dem Absenden eine Bestätigung per E-Mail.
                        </p>
                    </div>
                </div>
            </form>

            <!-- Info Box -->
            <div class="mt-8 rounded-lg border border-blue-200 bg-blue-50 p-6">
                <h4 class="mb-3 text-lg font-medium text-blue-900">Wichtige Hinweise</h4>
                <ul class="space-y-2 text-sm text-blue-800">
                    <li class="flex items-start">
                        <span class="mr-3 mt-2 h-2 w-2 flex-shrink-0 rounded-full bg-blue-400"></span>
                        <span>Sie können diese Registrierung jederzeit unterbrechen und später fortsetzen.</span>
                    </li>
                    <li class="flex items-start">
                        <span class="mr-3 mt-2 h-2 w-2 flex-shrink-0 rounded-full bg-blue-400"></span>
                        <span>Alle Angaben können bis zum {{ $band->registration_token_expires_at->format('d.m.Y') }}
                            geändert werden.</span>
                    </li>
                    <li class="flex items-start">
                        <span class="mr-3 mt-2 h-2 w-2 flex-shrink-0 rounded-full bg-blue-400"></span>
                        <span>Bei Problemen wenden Sie sich an unser Organisationsteam.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const travelPartyInput = document.getElementById('travel_party');
            const membersContainer = document.getElementById('members-container');

            function updateMemberFields() {
                const count = parseInt(travelPartyInput.value) || 0;

                // Container leeren
                membersContainer.innerHTML = '';

                // Neue Felder generieren
                for (let i = 0; i < count; i++) {
                    const memberDiv = document.createElement('div');
                    memberDiv.className = 'member-row bg-gray-50 rounded-lg p-6';
                    memberDiv.innerHTML = `
                <h4 class="font-medium text-gray-900 mb-4 border-b border-gray-200 pb-2">
                    👤 Person ${i + 1}
                </h4>
                
                <!-- Hauptperson -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Vorname <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="members[${i}][first_name]" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nachname <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="members[${i}][last_name]" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               required>
                    </div>
                </div>
                
                <!-- KFZ-Kennzeichen -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        🚗 KFZ-Kennzeichen (optional)
                    </label>
                    <input type="text" name="members[${i}][vehicle_plate]" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 uppercase" 
                           placeholder="z.B. B-MW 1234">
                    <p class="text-xs text-gray-500 mt-1">Falls diese Person mit eigenem Fahrzeug anreist</p>
                </div>
                
                <!-- Gast -->
                <div class="border-t border-gray-200 pt-4">
                    <h5 class="text-sm font-medium text-gray-700 mb-3">👥 Gast (optional)</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">
                                Gast Vorname
                            </label>
                            <input type="text" name="members[${i}][guest_first_name]" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                   placeholder="Vorname des Gastes">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">
                                Gast Nachname
                            </label>
                            <input type="text" name="members[${i}][guest_last_name]" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                   placeholder="Nachname des Gastes">
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Falls diese Person einen Gast mitbringt</p>
                </div>
            `;
                    membersContainer.appendChild(memberDiv);
                }

                // Info-Text hinzufügen wenn Container leer ist
                if (count === 0) {
                    membersContainer.innerHTML =
                        '<p class="text-gray-500 text-center py-8">Geben Sie oben die Anzahl der Bandmitglieder ein.</p>';
                }
            }

            // Event Listeners hinzufügen
            travelPartyInput.addEventListener('input', updateMemberFields);
            travelPartyInput.addEventListener('change', updateMemberFields);

            // Initial update wenn Seite geladen wird
            if (travelPartyInput.value) {
                updateMemberFields();
            }

            // Für den Fall dass das Input-Feld bereits einen Wert hat beim Laden
            setTimeout(updateMemberFields, 100);
        });
    </script>
@endsection
