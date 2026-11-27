<?php

namespace App\Policies;

use App\Autorisation\Capacites;
use App\Models\Demande;
use App\Models\User;

/**
 * Droits sur une demande (socle des droits serveur).
 *
 * Décision validée : un utilisateur ne peut JAMAIS valider sa propre demande,
 * quel que soit son rôle ou le niveau de validation. Elle est appliquée à
 * chaque décision du circuit (validation ou rejet), au niveau du supérieur,
 * du gestionnaire et de la validation finale.
 *
 * Les gardes d'état (statut attendu) sont dans DemandeController, sous verrou,
 * avec les messages existants.
 */
class DemandePolicy
{
    private function estProprietaire(User $user, Demande $demande): bool
    {
        return (int) $demande->user_id === (int) $user->id;
    }

    /** Règle existante, inchangée (User::peutVoirDemande). */
    public function view(User $user, Demande $demande): bool
    {
        return $user->peutVoirDemande($demande);
    }

    /**
     * Modifier l'en-tête : règle de l'interface (bouton « Editer ») :
     * propriétaire ou SuperAdmin, tant que le supérieur n'a pas validé.
     */
    public function update(User $user, Demande $demande): bool
    {
        return ($this->estProprietaire($user, $demande) || $user->hasRole('SuperAdmin'))
            && ! $demande->validation_superieur;
    }

    /** Archivage : règle de l'interface (@role('SuperAdmin')). */
    public function delete(User $user, Demande $demande): bool
    {
        return Capacites::autorise($user, 'archiver-demandes');
    }

    /** Soumission : règle existante (propriétaire). */
    public function soumettre(User $user, Demande $demande): bool
    {
        return $this->estProprietaire($user, $demande);
    }

    /** Décision du supérieur hiérarchique du demandeur, jamais du demandeur. */
    public function deciderSuperieur(User $user, Demande $demande): bool
    {
        return ! $this->estProprietaire($user, $demande)
            && $demande->user
            && $user->peutValiderCommeSuperieur($demande->user);
    }

    /** Quantités accordées et rejet par le gestionnaire, jamais sur sa propre demande. */
    public function deciderGestionnaire(User $user, Demande $demande): bool
    {
        return ! $this->estProprietaire($user, $demande)
            && Capacites::autorise($user, 'traiter-demandes');
    }

    /** Validation finale et rejet final, jamais sur sa propre demande. */
    public function deciderFinale(User $user, Demande $demande): bool
    {
        return ! $this->estProprietaire($user, $demande)
            && Capacites::autorise($user, 'valider-finale-demandes');
    }

    /** Livraison : rôles de l'interface (Gestionnaire, SuperAdmin). */
    public function livrer(User $user, Demande $demande): bool
    {
        return Capacites::autorise($user, 'traiter-demandes');
    }

    /** Décision validée : même agence OU même département que le demandeur. */
    public function confirmerReception(User $user, Demande $demande): bool
    {
        return $user->peutConfirmerReception($demande);
    }

    /**
     * Q8 : accord du demandeur sur les quantités accordées (étape
     * « En attente accord demandeur »). Seul le demandeur peut donner son
     * accord sur SA demande ; ce n'est pas une validation hiérarchique et cela
     * ne lui donne aucun droit de validation ni de rejet.
     */
    public function accepterQuantites(User $user, Demande $demande): bool
    {
        return (int) $demande->user_id === (int) $user->id;
    }
}
