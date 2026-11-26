<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protection contre les requêtes intersites des actions déclenchées par un
 * lien GET (blocage et déblocage de comptes), sans modifier l'interface.
 *
 * Refus si le navigateur signale une requête provenant d'un autre site
 * (en-tête Sec-Fetch-Site), ou, à défaut, si le Referer désigne un autre hôte.
 * La protection complète (méthode PATCH et formulaire avec jeton CSRF)
 * demande une modification des vues : elle est soumise à décision.
 */
class MemeOrigine
{
    public function handle(Request $request, Closure $next): Response
    {
        $site = $request->headers->get('Sec-Fetch-Site');

        if ($site !== null && ! in_array(strtolower($site), ['same-origin', 'none'], true)) {
            abort(403);
        }

        $referer = $request->headers->get('referer');

        if ($site === null && $referer) {
            $hote = parse_url($referer, PHP_URL_HOST);

            if ($hote && strcasecmp($hote, $request->getHost()) !== 0) {
                abort(403);
            }
        }

        return $next($request);
    }
}
