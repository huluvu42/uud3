{{-- ============================================================================ --}}
{{-- resources/views/emails/band-registration.blade.php --}}
{{-- Email-Template für neue Registrierungslinks --}}
{{-- ============================================================================ --}}

@component('mail::message')
    # Hallo {{ $managerName }}!

    Herzlich willkommen zum **{{ config('app.name') }} {{ $band->year }}**!

    Für Ihre Band **{{ $band->band_name }}** ({{ $band->stage->name ?? 'Bühne' }}) benötigen wir noch einige Informationen
    über Ihre Bandmitglieder.

    Bitte klicken Sie auf den folgenden Link, um die Registrierung zu vervollständigen:

    @component('mail::button', ['url' => $registrationUrl])
        Bandmitglieder registrieren
    @endcomponent

    ## Wichtige Informationen:
    - Dieser Link ist bis zum **{{ $expiresAt->format('d.m.Y H:i') }}** gültig
    - Sie können die Registrierung jederzeit unterbrechen und später fortsetzen
    - Bei Problemen wenden Sie sich bitte an unser Organisationsteam

    ## Was Sie benötigen:
    - Anzahl der anreisenden Bandmitglieder
    - Vor- und Nachnamen aller Mitglieder
    - Fahrzeugkennzeichen (optional)

    Vielen Dank für Ihre Mithilfe!

    Mit freundlichen Grüßen,<br>
    Das {{ config('app.name') }} Team

    ---
    *Diese E-Mail wurde automatisch generiert. Bei Fragen antworten Sie bitte auf diese E-Mail.*
@endcomponent

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
