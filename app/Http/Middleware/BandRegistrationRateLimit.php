<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class BandRegistrationRateLimit
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'band_registration:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) { // Max 5 Versuche pro Stunde
            abort(429, 'Zu viele Registrierungsversuche. Bitte versuchen Sie es in einer Stunde erneut.');
        }

        RateLimiter::hit($key, 3600); // 1 Stunde

        return $next($request);
    }
}
