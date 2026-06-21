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

            if (
                !in_array(
                    $request->route()->getName(),
                    $routesAutorisees
                )
            ) {

                return redirect()
                    ->route('mon.profil')
                    ->with(
                        'warning',
                        'Vous devez modifier votre mot de passe avant de continuer.'
                    );
            }
        }

        return $next($request);
    }
}
