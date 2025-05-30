<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Prüfe ob Benutzer eingeloggt ist
        if (!Auth::check()) {
            // Log unauthorized access attempt
            \Log::warning('Unauthorized admin access attempt', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
            ]);

            // Wenn AJAX/API Request, JSON Response zurückgeben
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Anmeldung erforderlich für Admin-Bereich.',
                    'error' => 'Unauthenticated'
                ], 401);
            }

            // Ansonsten zur Login-Seite weiterleiten
            return redirect()->route('login')->with('error', 'Bitte melden Sie sich an, um den Admin-Bereich zu betreten.');
        }

        $user = Auth::user();

        // Prüfe ob Benutzer Admin-Rechte hat
        if (!$user->is_admin) {
            // Log unauthorized admin access attempt by regular user
            \Log::warning('Non-admin user tried to access admin area', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            // Wenn AJAX/API Request, JSON Response zurückgeben
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Keine Berechtigung für den Admin-Bereich.',
                    'error' => 'Forbidden'
                ], 403);
            }

            // Ansonsten Fehlerseite anzeigen oder zur Hauptseite weiterleiten
            if ($request->route() && $request->route()->getName()) {
                // Wenn von einer benannten Route, zur Hauptseite mit Fehlermeldung
                return redirect()->route('home')->with('error', 'Sie haben keine Berechtigung für den Admin-Bereich.');
            } else {
                // Ansonsten 403 Fehler werfen
                abort(403, 'Keine Berechtigung für den Admin-Bereich.');
            }
        }

        // Log successful admin access
        \Log::info('Admin area accessed', [
            'user_id' => $user->id,
            'username' => $user->username,
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }
}