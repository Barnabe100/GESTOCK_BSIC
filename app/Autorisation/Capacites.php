<?php

namespace App\Autorisation;

use App\Models\User;

/**
 * Socle des droits côté serveur : définition UNIQUE des capacités de GESTOK.
 *
 * Chaque capacité reproduit exactement les conditions d'accès DÉJÀ présentes
 * dans l'application (menu latéral, boutons, seeder des rôles et des
 * permissions). Aucune règle n'est inventée :
 * - « permissions » : permission Spatie déjà utilisée par l'interface (@can)
 *   ou déjà attribuée par RolesAndPermissionsSeeder ;
 * - « roles » : rôles auxquels l'interface propose déjà la fonctionnalité.
 * Quand l'interface et le seeder divergent, l'UNION est retenue à titre
 * provisoire, pour ne bloquer aucun usage légitime existant, et la divergence
 * est documentée (MATRICE_ROLES_PERMISSIONS_GESTOK.md) dans l'attente de la
 * décision Q1.
 *
 * Les capacités sont enregistrées comme Gates (AuthServiceProvider) et
 * appliquées sur les routes par le middleware « can: ».
 */
final class Capacites
{
    public const DEFINITIONS = [
        // Gestion des comptes : @can('gerer-utilisateurs') (menu, Admin et SuperAdmin)
        'gerer-utilisateurs' => ['permissions' => ['gerer-utilisateurs'], 'roles' => []],

        // Organisation : @can('gerer-agence'|'gerer-departement'|'gerer-service') (menu)
        'gerer-agence' => ['permissions' => ['gerer-agence'], 'roles' => []],
        'gerer-departement' => ['permissions' => ['gerer-departement'], 'roles' => []],
        'gerer-service' => ['permissions' => ['gerer-service'], 'roles' => []],

        // Articles, catégories, types d'articles, rebut : @can('gerer-stock') (menu)
        'gerer-stock' => ['permissions' => ['gerer-stock'], 'roles' => []],

        // Mouvements de stock : menu @role('Gestionnaire') ; module de stock (gerer-stock)
        'voir-mouvements' => ['permissions' => ['gerer-stock'], 'roles' => ['Gestionnaire']],

        // Fournisseurs : menu hasAnyRole(['SuperAdmin', 'Gestionnaire'])
        'gerer-fournisseurs' => ['permissions' => [], 'roles' => ['SuperAdmin', 'Gestionnaire']],

        // Commandes (module) : menu hasAnyRole(['ValidateurCommande', 'Gestionnaire', 'SuperAdmin'])
        'acceder-commandes' => ['permissions' => [], 'roles' => ['ValidateurCommande', 'Gestionnaire', 'SuperAdmin']],
        // Création, modification, lignes : @hasanyrole('Gestionnaire|SuperAdmin') (commandes/index, show)
        'preparer-commandes' => ['permissions' => [], 'roles' => ['Gestionnaire', 'SuperAdmin']],
        // Suppression (archivage) : @role('SuperAdmin') (commandes/index)
        'supprimer-commandes' => ['permissions' => [], 'roles' => ['SuperAdmin']],
        // Validation : hasAnyRole(['ValidateurCommande', 'SuperAdmin']). La permission
        // valider-commande n'est PAS utilisée : le seeder l'attribue par erreur au rôle
        // Superieur (variable écrasée, anomalie N-13).
        'valider-commandes' => ['permissions' => [], 'roles' => ['ValidateurCommande', 'SuperAdmin']],
        // Réception : interface hasAnyRole(['ValidateurCommande', 'SuperAdmin']) ;
        // seeder : permission livrer-commande (Gestionnaire). Union provisoire.
        'receptionner-commandes' => ['permissions' => ['livrer-commande'], 'roles' => ['ValidateurCommande', 'SuperAdmin']],

        // États et leurs exports : menu hasAnyRole(['Gestionnaire', 'ValidateurDemande']) ;
        // seeder : permission voir-etats (Gestionnaire, SuperAdmin). Union provisoire.
        'voir-etats' => ['permissions' => ['voir-etats'], 'roles' => ['Gestionnaire', 'ValidateurDemande']],

        // Exports des demandes : hasAnyRole(['SuperAdmin', 'Admin', 'Gestionnaire'])
        'exporter-demandes' => ['permissions' => [], 'roles' => ['SuperAdmin', 'Admin', 'Gestionnaire']],

        // Listes du circuit des demandes (menu)
        'lister-demandes-superieur' => ['permissions' => [], 'roles' => ['Superieur']],
        'lister-demandes-gestionnaire' => ['permissions' => [], 'roles' => ['Gestionnaire']],
        // Validation finale : menu @role('ValidateurDemande') ; permission valider-demande
        'lister-validation-finale' => ['permissions' => ['valider-demande'], 'roles' => ['ValidateurDemande']],

        // Traitement par le gestionnaire (quantités accordées, rejet, livraison) :
        // hasAnyRole(['Gestionnaire', 'SuperAdmin'])
        'traiter-demandes' => ['permissions' => [], 'roles' => ['Gestionnaire', 'SuperAdmin']],
        // Validation finale : menu @role('ValidateurDemande') ; permission valider-demande
        'valider-finale-demandes' => ['permissions' => ['valider-demande'], 'roles' => ['ValidateurDemande']],
        // Archivage d'une demande : @role('SuperAdmin')
        'archiver-demandes' => ['permissions' => [], 'roles' => ['SuperAdmin']],
    ];

    public static function noms(): array
    {
        return array_keys(self::DEFINITIONS);
    }

    public static function autorise(?User $utilisateur, string $capacite): bool
    {
        if (! $utilisateur || ! isset(self::DEFINITIONS[$capacite])) {
            return false;
        }

        $definition = self::DEFINITIONS[$capacite];

        if ($definition['roles'] !== [] && $utilisateur->hasAnyRole($definition['roles'])) {
            return true;
        }

        foreach ($definition['permissions'] as $permission) {
            // Une permission absente de la base ne doit pas lever d'exception.
            if ($utilisateur->getAllPermissions()->contains('name', $permission)) {
                return true;
            }
        }

        return false;
    }
}
