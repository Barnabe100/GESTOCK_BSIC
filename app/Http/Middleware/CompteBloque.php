<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Révocation effective d'un compte bloqué ou désactivé (Q9) : à la requête
 * suivante, sa session (y compris par « se souvenir de moi ») est fermée et
 * il est renvoyé vers la connexion, avec le message correspondant.
 */
class CompteBloque
{
    public const MESSAGE = 'Votre compte est bloqué, veuillez contacter votre administrateur.';

    public const MESSAGE_DESACTIVE = 'Votre compte est désactivé, veuillez contacter votre administrateur.';

    /** Message de refus pour un compte bloqué ou désactivé, null s'il est actif. */
    public static function refus($user): ?string
    {
        if ($user->desactive_le !== null) {
            return self::MESSAGE_DESACTIVE;
        }

        return $user->bloque ? self::MESSAGE : null;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user && ($message = self::refus($user)) !== null) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
