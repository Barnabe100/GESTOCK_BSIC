<?php

namespace App\Autorisation;

use App\Models\HistoriqueRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Seul point d'écriture des rôles d'un compte : chaque changement est
 * enregistré dans historique_roles (décision validée). Les contrôles de droits
 * (UserPolicy) sont faits AVANT, par l'appelant.
 */
class JournalRoles
{
    /** Remplace les rôles du compte par $roles (hors $exclus, qui sont conservés tels quels). */
    public function synchroniser(User $cible, array $roles, ?User $par, string $origine, array $exclus = []): void
    {
        DB::transaction(function () use ($cible, $roles, $par, $origine, $exclus) {
            $avant = $cible->getRoleNames()->all();
            $conserves = array_values(array_intersect($avant, $exclus));
            $apres = array_values(array_unique(array_merge(array_diff($roles, $exclus), $conserves)));

            $cible->syncRoles($apres);
            $this->tracer($cible, array_diff($apres, $avant), 'attribue', $par, $origine);
            $this->tracer($cible, array_diff($avant, $apres), 'retire', $par, $origine);
        });
    }

    public function attribuer(User $cible, string|array $roles, ?User $par, string $origine): void
    {
        $nouveaux = array_values(array_diff((array) $roles, $cible->getRoleNames()->all()));

        if ($nouveaux === []) {
            return;
        }

        $cible->assignRole($nouveaux);
        $this->tracer($cible, $nouveaux, 'attribue', $par, $origine);
    }

    public function retirer(User $cible, string $role, ?User $par, string $origine): void
    {
        if (! $cible->hasRole($role)) {
            return;
        }

        $cible->removeRole($role);
        $this->tracer($cible, [$role], 'retire', $par, $origine);
    }

    private function tracer(User $cible, array $roles, string $action, ?User $par, string $origine): void
    {
        foreach ($roles as $role) {
            HistoriqueRole::create([
                'user_id' => $cible->id,
                'utilisateur' => trim($cible->name.' '.$cible->prenom).' <'.$cible->email.'>',
                'role' => $role,
                'action' => $action,
                'origine' => $origine,
                'effectue_par' => $par?->id,
            ]);
        }
    }
}
