<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Révocation effective d'un compte bloqué : à la requête suivante, sa session
 * (y compris par « se souvenir de moi ») est fermée et il est renvoyé vers la
 * connexion, avec le message existant de LoginRequest.
 */
class CompteBloque
{
    public const MESSAGE = 'Votre compte est bloqué, veuillez contacter votre administrateur.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user && $user->bloque) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()->route('login')->withErrors(['email' => self::MESSAGE]);
        }

        return $next($request);
    }
}
