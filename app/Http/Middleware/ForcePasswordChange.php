<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        if (
            auth()->check()
            && auth()->user()->force_password_change
        ) {

            $routesAutorisees = [

                'force.password.change',
                'force.password.update',
                'logout'

            ];

            if (!$request->routeIs($routesAutorisees)) {

                // Redirection vers la page de changement de mot de passe.
                // (Auparavant vers 'mon.profil', elle-même soumise à ce
                // middleware : boucle de redirection infinie.)
                return redirect()
                    ->route('force.password.change')
                    ->with(
                        'warning',
                        'Vous devez modifier votre mot de passe avant de continuer.'
                    );
            }
        }

        return $next($request);
    }
}
