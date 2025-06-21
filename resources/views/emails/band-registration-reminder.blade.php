{{-- ============================================================================ --}}
{{-- resources/views/emails/band-registration-reminder.blade.php --}}
{{-- Email-Template für Erinnerungen --}}
{{-- ============================================================================ --}}

@component('mail::message')
    # Erinnerung: Bandmitglieder-Registrierung

    Hallo {{ $managerName }}!

    Wir haben vor einer Woche einen Registrierungslink für Ihre Band **{{ $band->band_name }}** gesendet, aber die
    Registrierung ist noch nicht abgeschlossen.

    @component('mail::button', ['url' => $registrationUrl])
        Jetzt registrieren
    @endcomponent

    **Wichtig:** Der Link läuft am **{{ $expiresAt->format('d.m.Y H:i') }}** ab.

    Falls Sie Probleme haben oder Fragen zur Registrierung, antworten Sie einfach auf diese E-Mail.

    Vielen Dank!<br>
    Das {{ config('app.name') }} Team
@endcomponent
