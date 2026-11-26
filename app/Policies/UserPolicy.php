<?php

namespace App\Policies;

use App\Autorisation\Capacites;
use App\Models\Commande;
use App\Models\Demande;
use App\Models\ReceptionCommande;
use App\Models\MouvementStock;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Droits sur les comptes utilisateurs (socle des droits serveur).
 *
 * - Gestion des comptes réservée à la capacité « gerer-utilisateurs »
 *   (permission déjà utilisée par le menu : Admin et SuperAdmin).
 * - Un utilisateur ne modifie jamais ses propres rôles, ne se bloque pas et ne
 *   se supprime pas lui-même ; il garde la modification de son mot de passe.
 * - Seul un SuperAdmin peut attribuer ou retirer le rôle SuperAdmin, ou agir
 *   sur un compte SuperAdmin. Le dernier SuperAdmin ne peut pas perdre ce rôle.
 * - Un compte qui porte un historique métier n'est jamais supprimé
 *   physiquement : il est désactivé (décision Q9). Désactivation et
 *   réactivation suivent les mêmes protections que le blocage ; le dernier
 *   SuperAdmin actif ne peut être ni désactivé ni supprimé.
 * - Un Admin peut attribuer le rôle Admin à un autre compte (décision
 *   validée) ; le rôle SuperAdmin reste réservé au SuperAdmin.
 */
class UserPolicy
{
    private function gere(User $acteur): bool
    {
        return Capacites::autorise($acteur, 'gerer-utilisateurs');
    }

    private function peutAgirSur(User $acteur, User $cible): bool
    {
        return ! $cible->hasRole('SuperAdmin') || $acteur->hasRole('SuperAdmin');
    }

    public function viewAny(User $acteur): bool
    {
        return $this->gere($acteur);
    }

    public function create(User $acteur): bool
    {
        return $this->gere($acteur);
    }

    public function view(User $acteur, User $cible): bool
    {
        return (int) $acteur->id === (int) $cible->id || $this->gere($acteur);
    }

    /** Son propre compte (mot de passe), ou un compte géré. */
    public function update(User $acteur, User $cible): bool
    {
        if ((int) $acteur->id === (int) $cible->id) {
            return true;
        }

        return $this->gere($acteur) && $this->peutAgirSur($acteur, $cible);
    }

    /**
     * Attribution de rôles à un AUTRE compte.
     *
     * @param  list<string>  $roles  rôles demandés
     */
    public function attribuerRoles(User $acteur, User $cible, array $roles): bool
    {
        if ((int) $acteur->id === (int) $cible->id || ! $this->gere($acteur)) {
            return false;
        }

        $toucheSuperAdmin = in_array('SuperAdmin', $roles, true) || $cible->hasRole('SuperAdmin');

        if ($toucheSuperAdmin && ! $acteur->hasRole('SuperAdmin')) {
            return false;
        }

        // Le dernier SuperAdmin actif ne peut pas perdre ce rôle.
        if ($cible->hasRole('SuperAdmin') && ! in_array('SuperAdmin', $roles, true)) {
            return self::autreSuperAdminActif($cible);
        }

        return true;
    }

    /** Rôles qu'un acteur peut attribuer (création, import). */
    public function attribuerRolesALaCreation(User $acteur, array $roles): bool
    {
        return $this->gere($acteur)
            && (! in_array('SuperAdmin', $roles, true) || $acteur->hasRole('SuperAdmin'));
    }

    public function bloquer(User $acteur, User $cible): bool
    {
        return $this->gere($acteur)
            && (int) $acteur->id !== (int) $cible->id
            && $this->peutAgirSur($acteur, $cible)
            && (! $cible->hasRole('SuperAdmin') || $cible->bloque || self::autreSuperAdminActif($cible));
    }

    public function desactiver(User $acteur, User $cible): bool
    {
        return $this->gere($acteur)
            && (int) $acteur->id !== (int) $cible->id
            && $this->peutAgirSur($acteur, $cible)
            && (! $cible->hasRole('SuperAdmin') || self::autreSuperAdminActif($cible));
    }

    public function delete(User $acteur, User $cible): bool
    {
        return $this->gere($acteur)
            && (int) $acteur->id !== (int) $cible->id
            && $this->peutAgirSur($acteur, $cible)
            && ! self::porteUnHistorique($cible)
            && (! $cible->hasRole('SuperAdmin') || self::autreSuperAdminActif($cible));
    }

    /** Existe-t-il un autre SuperAdmin actif (ni bloqué ni désactivé) ? */
    public static function autreSuperAdminActif(User $cible): bool
    {
        return User::role('SuperAdmin')
            ->where('id', '!=', $cible->id)
            ->where('bloque', false)
            ->whereNull('desactive_le')
            ->exists();
    }

    /** Compte référencé par une demande, un mouvement de stock, une commande ou une réception. */
    public static function porteUnHistorique(User $cible): bool
    {
        $id = $cible->id;

        return Demande::where('user_id', $id)
            ->orWhere('superieur_id', $id)
            ->orWhere('gestionnaire_id', $id)
            ->orWhere('validateur_final_id', $id)
            ->orWhere('reception_par', $id)
            ->exists()
            || MouvementStock::where('user_id', $id)->exists()
            || Commande::where('soumise_par', $id)->orWhere('validee_par', $id)->exists()
            || ReceptionCommande::where('user_id', $id)->exists();
    }

    /** Noms de rôles existants (contrôle des valeurs reçues). */
    public static function rolesExistants(): array
    {
        return Role::pluck('name')->all();
    }
}
