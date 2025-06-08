<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  Berechtigung die geprüft werden soll (admin|manage|reset)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $permission = 'admin'): Response
    {
        // Prüfe ob Benutzer eingeloggt ist
        if (!Auth::check()) {
            // Log unauthorized access attempt
            Log::warning('Unauthorized access attempt', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'required_permission' => $permission,
            ]);

            // Wenn AJAX/API Request, JSON Response zurückgeben
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Anmeldung erforderlich.',
                    'error' => 'Unauthenticated'
                ], 401);
            }

            // Ansonsten zur Login-Seite weiterleiten
            return redirect()->route('login')->with('error', 'Bitte melden Sie sich an.');
        }

        $user = Auth::user();

        // Prüfe spezifische Berechtigung basierend auf Parameter
        $hasPermission = $this->checkPermission($user, $permission);

        if (!$hasPermission) {
            // Log unauthorized access attempt by user without required permission
            Log::warning('User tried to access area without required permission', [
                'user_id' => $user->id,
                'username' => $user->username,
                'required_permission' => $permission,
                'user_permissions' => [
                    'is_admin' => $user->is_admin,
                    'can_manage' => $user->can_manage ?? false,
                    'can_reset_changes' => $user->can_reset_changes,
                ],
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            $permissionName = $this->getPermissionDisplayName($permission);

            // Wenn AJAX/API Request, JSON Response zurückgeben
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => "Keine Berechtigung für {$permissionName}.",
                    'error' => 'Forbidden'
                ], 403);
            }

            // Ansonsten Fehlerseite anzeigen oder zur Hauptseite weiterleiten
            if ($request->route() && $request->route()->getName()) {
                // Wenn von einer benannten Route, zur Hauptseite mit Fehlermeldung
                return redirect()->route('home')->with('error', "Sie haben keine Berechtigung für {$permissionName}.");
            } else {
                // Ansonsten 403 Fehler werfen
                abort(403, "Keine Berechtigung für {$permissionName}.");
            }
        }

        // Log successful access
        Log::info('Protected area accessed', [
            'user_id' => $user->id,
            'username' => $user->username,
            'required_permission' => $permission,
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }

    /**
     * Prüft ob der Benutzer die angeforderte Berechtigung hat
     */
    private function checkPermission($user, string $permission): bool
    {
        switch ($permission) {
            case 'admin':
                // Nur echte Administratoren
                return $user->is_admin;

            case 'manage':
                // Administratoren oder Benutzer mit Verwaltungsrechten
                return $user->is_admin || ($user->can_manage ?? false);

            case 'reset':
                // Administratoren oder Benutzer mit Reset-Rechten
                return $user->is_admin || $user->can_reset_changes;

            case 'admin_or_manage':
                // Alias für manage (rückwärtskompatibel)
                return $user->is_admin || ($user->can_manage ?? false);

            case 'admin_or_reset':
                // Admin oder Reset-Berechtigung
                return $user->is_admin || $user->can_reset_changes;

            case 'any_special':
                // Jede spezielle Berechtigung (Admin, Verwaltung oder Reset)
                return $user->is_admin || ($user->can_manage ?? false) || $user->can_reset_changes;

            default:
                // Unbekannte Berechtigung - nur für Admins
                Log::warning('Unknown permission type requested', [
                    'permission' => $permission,
                    'user_id' => $user->id,
                ]);
                return $user->is_admin;
        }
    }

    /**
     * Gibt einen benutzerfreundlichen Namen für die Berechtigung zurück
     */
    private function getPermissionDisplayName(string $permission): string
    {
        switch ($permission) {
            case 'admin':
                return 'den Administrator-Bereich';
            case 'manage':
            case 'admin_or_manage':
                return 'den Verwaltungsbereich';
            case 'reset':
            case 'admin_or_reset':
                return 'Reset-Funktionen';
            case 'any_special':
                return 'diesen Bereich';
            default:
                return 'diesen Bereich';
        }
    }
}
