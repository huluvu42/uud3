<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Zeige das Login-Formular
     */
    public function showLoginForm()
    {
        // Wenn bereits eingeloggt, zur Hauptseite weiterleiten
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    /**
     * Bearbeite Login-Versuch
     */
    public function login(Request $request)
    {
        // Validierung der Eingaben
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Benutzername ist erforderlich.',
            'password.required' => 'Passwort ist erforderlich.',
        ]);

        // Versuche Anmeldung
        if (Auth::attempt($credentials, $request->filled('remember'))) {
            // Session regenerieren für Sicherheit
            $request->session()->regenerate();

            // Log successful login
            \Log::info('User logged in', [
                'user_id' => Auth::id(),
                'username' => Auth::user()->username,
                'ip' => $request->ip(),
            ]);

            // Zur gewünschten Seite oder Hauptseite weiterleiten
            return redirect()->intended(route('home'));
        }

        // Log failed login attempt
        \Log::warning('Failed login attempt', [
            'username' => $request->username,
            'ip' => $request->ip(),
        ]);

        // Bei fehlgeschlagener Anmeldung zurück mit Fehlermeldung
        throw ValidationException::withMessages([
            'username' => ['Die Anmeldedaten sind nicht korrekt.'],
        ]);
    }

    /**
     * Benutzer abmelden
     */
    public function logout(Request $request)
    {
        // Log logout
        if (Auth::check()) {
            \Log::info('User logged out', [
                'user_id' => Auth::id(),
                'username' => Auth::user()->username,
            ]);
        }

        // Benutzer abmelden
        Auth::logout();

        // Session invalidieren und Token regenerieren
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Zur Login-Seite weiterleiten
        return redirect()->route('login')->with('message', 'Sie wurden erfolgreich abgemeldet.');
    }

    /**
     * Passwort ändern (für eingeloggte Benutzer)
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'current_password.required' => 'Aktuelles Passwort ist erforderlich.',
            'new_password.required' => 'Neues Passwort ist erforderlich.',
            'new_password.min' => 'Neues Passwort muss mindestens 6 Zeichen lang sein.',
            'new_password.confirmed' => 'Passwort-Bestätigung stimmt nicht überein.',
        ]);

        $user = Auth::user();

        // Überprüfe aktuelles Passwort
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Das aktuelle Passwort ist nicht korrekt.'],
            ]);
        }

        // Neues Passwort setzen
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        // Log password change
        \Log::info('User changed password', [
            'user_id' => $user->id,
            'username' => $user->username,
        ]);

        return back()->with('success', 'Passwort wurde erfolgreich geändert.');
    }

    /**
     * Benutzer-Profil anzeigen
     */
    public function showProfile()
    {
        return view('auth.profile', [
            'user' => Auth::user()
        ]);
    }

    /**
     * Profil aktualisieren
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
        ], [
            'first_name.required' => 'Vorname ist erforderlich.',
            'last_name.required' => 'Nachname ist erforderlich.',
            'username.required' => 'Benutzername ist erforderlich.',
            'username.unique' => 'Dieser Benutzername ist bereits vergeben.',
        ]);

        // Old values for logging
        $oldValues = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name, 
            'username' => $user->username,
        ];

        $user->update($validated);

        // Log profile changes
        foreach ($validated as $field => $newValue) {
            if ($oldValues[$field] !== $newValue) {
                \App\Models\ChangeLog::logChange($user, $field, $oldValues[$field], $newValue);
            }
        }

        return back()->with('success', 'Profil wurde erfolgreich aktualisiert.');
    }

    /**
     * Account-Status prüfen (für API oder AJAX-Calls)
     */
    public function checkAuth()
    {
        if (Auth::check()) {
            return response()->json([
                'authenticated' => true,
                'user' => [
                    'id' => Auth::id(),
                    'username' => Auth::user()->username,
                    'name' => Auth::user()->first_name . ' ' . Auth::user()->last_name,
                    'is_admin' => Auth::user()->is_admin,
                ]
            ]);
        }

        return response()->json([
            'authenticated' => false
        ], 401);
    }
}