{{-- ============================================================================ --}}
{{-- resources/views/emails/admin-band-registration-notification.blade.php --}}
{{-- Email-Template für Admin-Benachrichtigungen --}}
{{-- ============================================================================ --}}

@component('mail::message')
    # Neue Band-Registrierung

    Eine Band hat ihre Registrierung abgeschlossen:

    ## Details:
    - **Band:** {{ $band->band_name }}
    - **Bühne:** {{ $band->stage->name ?? 'Keine Bühne' }}
    - **Manager:** {{ $band->manager_full_name ?? 'Nicht angegeben' }}
    - **Email:** {{ $band->manager_email ?? 'Nicht angegeben' }}
    - **Mitglieder:** {{ $memberCount }}
    - **Travel Party:** {{ $band->travel_party }}
    @if ($vehicleCount > 0)
        - **Fahrzeuge:** {{ $vehicleCount }}
    @endif
    @if ($band->emergency_contact)
        - **Notfallkontakt:** {{ $band->emergency_contact }}
    @endif
    @if ($band->special_requirements)
        - **Besondere Anforderungen:** {{ $band->special_requirements }}
    @endif

    @component('mail::button', ['url' => $adminUrl])
        Im Admin-Bereich ansehen
    @endcomponent

    Diese Benachrichtigung wurde automatisch generiert.
@endcomponent
