## UUD Manager

0.6
Band Import
Band Mitglieder Import
Band Suche und anzeigen angepasst
Optimierung für mehr Geschwindigkeit
Suchfeld wird geleert

Installation:

composer require livewire/livewire
composer require doctrine/dba

## Install CSV/Excel Import:

composer require phpoffice/phpspreadsheet
composer require league/csv

mkdir -p storage/app/temp-imports
chmod 775 storage/app/temp-imports

Bandmitglieder von extern eintragen lassen.

# 1. Migration ausführen

php artisan migrate

# 2. Queue-Tabellen erstellen (für Email-Versendung)

php artisan queue:table
php artisan migrate

# 3. Storage-Links erstellen

php artisan storage:link

# 4. Konfiguration cachen

php artisan config:cache

# 5. Queue Worker starten (in separatem Terminal)

php artisan queue:work

# 6. Scheduler testen

php artisan schedule:run

// ============================================================================
// README_REGISTRATION.md
// Dokumentation für das Registration System
// ============================================================================

/\*

# Band Registration System

Ein vollständiges System für die Selbstregistrierung von Bands mit automatisierten Email-Workflows.

## Features

-   ✅ Sichere Token-basierte Registrierung (64-Zeichen)
-   ✅ Automatische Email-Versendung und Erinnerungen
-   ✅ Admin-Dashboard mit Live-Statistiken
-   ✅ Manager-Daten Import (CSV/Excel)
-   ✅ Rate Limiting und Sicherheitsfeatures
-   ✅ Responsive Design für alle Geräte
-   ✅ Event-System für Erweiterungen
-   ✅ Background Jobs für Wartung
-   ✅ Umfassende Validierung
-   ✅ Change Log Integration

## Installation

1. **Migration ausführen:**

```bash
php artisan migrate
```

2. **Commands registrieren:**

```bash
php artisan config:cache
```

3. **Queue-System einrichten:**

```bash
# Für Database Queue:
php artisan queue:table
php artisan migrate

# Queue Worker starten:
php artisan queue:work
```

4. **Scheduler einrichten (Cronjob):**

```bash
# In der Crontab hinzufügen:
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Usage

### Admin-Workflow:

1. Manager-Daten über CSV/Excel importieren
2. Registrierungslinks generieren und automatisch versenden
3. Dashboard überwachen für Fortschritt
4. Automatische Erinnerungen werden gesendet

### Band-Workflow:

1. Email mit Registrierungslink erhalten
2. Formular ausfüllen (Travel Party, Mitglieder, Fahrzeuge)
3. Bestätigung per Email erhalten

## Commands

```bash
# Erinnerungen senden
php artisan registration:send-reminders

# Abgelaufene Tokens löschen
php artisan registration:clean-expired

# Statistiken anzeigen
php artisan registration:stats
```

## API Endpoints

-   `GET /api/v1/registration/stats` - Statistiken
-   `GET /api/v1/registration/health` - Health Check

## Sicherheit

-   64-Zeichen kryptographische Tokens
-   Rate Limiting (5 Versuche/Stunde)
-   Input-Validierung gegen XSS/Injection
-   Zeitbasierte Token-Gültigkeit
-   CSRF-Protection

## Email-Templates

Alle Email-Templates sind anpassbar und unterstützen:

-   Markdown-Formatierung
-   Automatische Links
-   Corporate Design
-   Multi-Language Ready

## Monitoring

-   Live-Dashboard mit Statistiken
-   Daily Logs für Registrierungs-Aktivität
-   Error-Logging mit Context
-   Health-Check Endpoint

## Support

Bei Fragen zum Registration System:

-   Dokumentation prüfen
-   Logs analysieren (`storage/logs/registration.log`)
-   Health-Check aufrufen
-   Tests ausführen
