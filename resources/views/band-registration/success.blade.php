{{-- ============================================================================ --}}
{{-- resources/views/band-registration/success.blade.php --}}
{{-- Vollständige Erfolgsseite nach Registrierung --}}
{{-- ============================================================================ --}}

@extends('layouts.app')

@section('title', 'Registrierung erfolgreich - ' . $band->band_name)

@section('content')
    <div class="min-h-screen bg-gray-50 py-12">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-8 text-center shadow-lg">
                <!-- Success Icon -->
                <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <h1 class="mb-4 text-3xl font-bold text-gray-900">
                    Registrierung erfolgreich abgeschlossen! 🎉
                </h1>

                <h2 class="mb-8 text-xl text-blue-600">{{ $band->band_name }}</h2>

                <!-- Zusammenfassung -->
                <div class="mb-8 rounded-lg bg-gray-50 p-6">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Zusammenfassung Ihrer Registrierung</h3>

                    <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                        <div class="rounded-lg bg-white p-4">
                            <div class="text-2xl font-bold text-blue-600">{{ $band->travel_party }}</div>
                            <div class="text-gray-600">Bandmitglieder</div>
                        </div>

                        <div class="rounded-lg bg-white p-4">
                            <div class="text-2xl font-bold text-purple-600">{{ $memberCount }}</div>
                            <div class="text-gray-600">Eingetragene Personen</div>
                        </div>

                        <div class="rounded-lg bg-white p-4">
                            <div class="text-2xl font-bold text-green-600">{{ $vehicleCount }}</div>
                            <div class="text-gray-600">Fahrzeuge</div>
                        </div>
                    </div>

                    <div class="mt-6 text-left">
                        <dl class="space-y-2">
                            <div class="flex justify-between">
                                <dt class="font-medium text-gray-600">Band:</dt>
                                <dd class="text-gray-900">{{ $band->band_name }}</dd>
                            </div>
                            @if ($band->stage)
                                <div class="flex justify-between">
                                    <dt class="font-medium text-gray-600">Bühne:</dt>
                                    <dd class="text-gray-900">{{ $band->stage->name }}</dd>
                                </div>
                            @endif
                            @if ($band->emergency_contact)
                                <div class="flex justify-between">
                                    <dt class="font-medium text-gray-600">Notfallkontakt:</dt>
                                    <dd class="text-gray-900">{{ $band->emergency_contact }}</dd>
                                </div>
                            @endif
                            @if ($band->special_requirements)
                                <div class="flex justify-between">
                                    <dt class="font-medium text-gray-600">Besondere Anforderungen:</dt>
                                    <dd class="text-gray-900">{{ Str::limit($band->special_requirements, 100) }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <!-- Mitglieder-Liste -->
                @if ($band->persons->count() > 0)
                    <div class="mb-8 rounded-lg border border-blue-200 bg-blue-50 p-6">
                        <h4 class="mb-4 text-lg font-semibold text-blue-900">Registrierte Bandmitglieder</h4>
                        <div class="grid grid-cols-1 gap-2 text-sm md:grid-cols-2 lg:grid-cols-3">
                            @foreach ($band->persons as $person)
                                <div class="rounded bg-white px-3 py-2 text-gray-900">
                                    {{ $person->first_name }} {{ $person->last_name }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Fahrzeuge-Liste -->
                @if ($band->vehiclePlates->count() > 0)
                    <div class="mb-8 rounded-lg border border-green-200 bg-green-50 p-6">
                        <h4 class="mb-4 text-lg font-semibold text-green-900">Registrierte Fahrzeuge</h4>
                        <div class="flex flex-wrap justify-center gap-2 text-sm">
                            @foreach ($band->vehiclePlates as $vehicle)
                                <span class="rounded-full bg-white px-4 py-2 font-mono text-gray-900">
                                    🚗 {{ $vehicle->license_plate }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Nächste Schritte -->
                <div class="mb-8 rounded-lg border border-blue-200 bg-blue-50 p-6">
                    <h4 class="mb-4 text-lg font-semibold text-blue-900">Was passiert als nächstes?</h4>
                    <ul class="space-y-3 text-left text-blue-800">
                        <li class="flex items-start">
                            <span
                                class="mr-3 mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-200">
                                <span class="text-xs font-semibold">1</span>
                            </span>
                            <span>Sie erhalten in Kürze eine Bestätigungs-E-Mail mit allen Details.</span>
                        </li>
                        <li class="flex items-start">
                            <span
                                class="mr-3 mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-200">
                                <span class="text-xs font-semibold">2</span>
                            </span>
                            <span>Unser Team prüft Ihre Angaben und meldet sich bei Rückfragen.</span>
                        </li>
                        <li class="flex items-start">
                            <span
                                class="mr-3 mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-200">
                                <span class="text-xs font-semibold">3</span>
                            </span>
                            <span>Sie erhalten rechtzeitig vor dem Festival weitere Informationen und Zugangsdaten.</span>
                        </li>
                        <li class="flex items-start">
                            <span
                                class="mr-3 mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-200">
                                <span class="text-xs font-semibold">4</span>
                            </span>
                            <span>Backstage- und Voucher-Berechtigung werden automatisch nach Ihren Bühnen-Vereinbarungen
                                zugeteilt.</span>
                        </li>
                    </ul>
                </div>

                <!-- Änderungen -->
                <div class="mb-8 rounded-lg border border-yellow-200 bg-yellow-50 p-6">
                    <h4 class="mb-3 text-lg font-semibold text-yellow-900">Änderungen erforderlich?</h4>
                    <p class="mb-4 text-sm text-yellow-800">
                        Falls Sie Änderungen an Ihren Angaben vornehmen müssen, wenden Sie sich bitte direkt an unser
                        Organisationsteam.
                        Kleinere Änderungen können in der Regel bis kurz vor dem Festival vorgenommen werden.
                    </p>
                    <div class="text-sm text-yellow-700">
                        <strong>Wichtig:</strong> Dieser Registrierungslink ist nach dem Abschluss nicht mehr verwendbar.
                    </div>
                </div>

                <!-- Kontakt-Info -->
                <div class="border-t border-gray-200 pt-6">
                    <p class="mb-4 text-gray-600">
                        Bei Fragen oder Änderungen wenden Sie sich gerne an unser Organisationsteam:
                    </p>

                    <div
                        class="flex flex-col items-center justify-center space-y-2 text-sm text-gray-600 sm:flex-row sm:space-x-6 sm:space-y-0">
                        <a href="mailto:bands@festival.com"
                            class="inline-flex items-center transition-colors duration-200 hover:text-blue-600">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                </path>
                            </svg>
                            bands@festival.com
                        </a>
                        <span class="hidden text-gray-400 sm:block">•</span>
                        <span class="inline-flex items-center">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                </path>
                            </svg>
                            +49 123 456 7890
                        </span>
                    </div>

                    <div class="mt-4 text-xs text-gray-500">
                        Geschäftszeiten: Montag - Freitag, 9:00 - 18:00 Uhr
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-8 border-t border-gray-200 pt-6">
                    <p class="mb-2 text-sm text-gray-500">
                        Vielen Dank für Ihre Teilnahme! Wir freuen uns auf Ihren Auftritt beim Festival.
                    </p>
                    <div class="text-xs text-gray-400">
                        Registrierung abgeschlossen am {{ now()->format('d.m.Y H:i') }} Uhr
                    </div>
                </div>
            </div>

            <!-- Social Media Links (Optional) -->
            <div class="mt-8 text-center">
                <p class="mb-4 text-sm text-gray-600">Folgen Sie uns für Updates:</p>
                <div class="flex justify-center space-x-4">
                    <a href="#" class="text-gray-400 transition-colors duration-200 hover:text-blue-500">
                        <span class="sr-only">Facebook</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" />
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 transition-colors duration-200 hover:text-blue-400">
                        <span class="sr-only">Twitter</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 transition-colors duration-200 hover:text-pink-500">
                        <span class="sr-only">Instagram</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987s11.987-5.367 11.987-11.987C24.014 5.367 18.647.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.49-3.323-1.297C4.198 14.895 3.708 13.743 3.708 12.446s.49-2.449 1.297-3.324c.875-.807 2.026-1.297 3.323-1.297s2.448.49 3.323 1.297c.807.875 1.297 2.026 1.297 3.323s-.49 2.448-1.297 3.323c-.875.807-2.026 1.297-3.323 1.297z" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
