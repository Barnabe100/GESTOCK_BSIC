<?php

namespace App\Autorisation;

use App\Models\User;
use App\Services\DesignationSuperieur;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Blocage, désactivation et réactivation des comptes (décisions validées).
 *
 * - Un compte bloqué ou désactivé ne peut plus se connecter ; ses sessions
 *   ouvertes sont révoquées à la requête suivante par le middleware
 *   CompteBloque, et son jeton « se souvenir de moi » est renouvelé, ce qui
 *   invalide les cookies de connexion persistante déjà émis.
 * - Désactivation (Q9) : le compte et tout son historique métier sont
 *   conservés ; seule la fonction de supérieur lui est retirée (avec
 *   historique), puisqu'il ne peut plus valider.
 * - Les droits (UserPolicy) sont vérifiés par l'appelant.
 */
class CycleDeVieComptes
{
    public function __construct(private DesignationSuperieur $designation)
    {
    }

    public function bloquer(User $cible): void
    {
        $cible->forceFill(['bloque' => true, 'remember_token' => Str::random(60)])->save();
    }

    public function debloquer(User $cible): void
    {
        $cible->forceFill(['bloque' => false])->save();
    }

    public function desactiver(User $cible, User $par): void
    {
        DB::transaction(function () use ($cible, $par) {
            $cible = User::whereKey($cible->id)->lockForUpdate()->firstOrFail();

            if ($cible->desactive_le !== null) {
                return;
            }

            if ($cible->superieur || $cible->hasRole('Superieur')) {
                $this->designation->retirer($cible, $par);
            }

            $cible->forceFill([
                'desactive_le' => now(),
                'desactive_par' => $par->id,
                'remember_token' => Str::random(60),
            ])->save();
        });
    }

    public function reactiver(User $cible): void
    {
        $cible->forceFill(['desactive_le' => null, 'desactive_par' => null])->save();
    }
}
