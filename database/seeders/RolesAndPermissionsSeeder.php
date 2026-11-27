<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        /*
        |--------------------------------------------------------------------------
        | RESET CACHE
        |--------------------------------------------------------------------------
        */

        app()[\Spatie\Permission\PermissionRegistrar::class]
            ->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | PERMISSIONS
        |--------------------------------------------------------------------------
        */

        $permissions = [

            // DEMANDES
            'creer-demande',
            'modifier-demande',
            'supprimer-demande',
            'lister-demande',
            'valider-demande',

            // COMMANDES
            'creer-commande',
            'modifier-commande',
            'supprimer-commande',
            'lister-commande',
            'gerer-commande',
            'valider-commande',
            'livrer-commande',

            // STOCK
            'gerer-stock',

            // UTILISATEURS
            'gerer-utilisateurs',

            // ETATS
            'voir-etats',
            'exporter-etats',

            // SERVICES
            'gerer-service',

            // DEPARTEMENTS
            'gerer-departement',

            // AGENCES
            'gerer-agence',

            // TYPE ARTICLE
            'gerer-type-article',
            // CONSULTATION (rôle Consultant) : noms dédiés, jamais identiques à
            // une capacité d'opération ni à une méthode de Policy
            'consulter-commandes-receptionnees',
            'consulter-etats',
            'consulter-mouvements',
        ];

        foreach ($permissions as $permission) {

            Permission::firstOrCreate([
                'name' => $permission
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | ROLES
        |--------------------------------------------------------------------------
        */

        $superAdmin = Role::firstOrCreate([
            'name' => 'SuperAdmin'
        ]);

        $admin = Role::firstOrCreate([
            'name' => 'Admin'
        ]);
        $gestionnaire = Role::firstOrCreate([
            'name' => 'Gestionnaire'
        ]);

        $demandeur = Role::firstOrCreate([
            'name' => 'Demandeur'
        ]);

        $validateurD = Role::firstOrCreate([
            'name' => 'ValidateurDemande'
        ]);

        $validateurC = Role::firstOrCreate([
            'name' => 'ValidateurCommande'
        ]);

        // N-13 : variable distincte (l'ancienne réutilisation de $validateurC
        // donnait valider-commande au Superieur au lieu du ValidateurCommande).
        $superieur = Role::firstOrCreate([
            'name' => 'Superieur'
        ]);
        // Consultation sans opération (décision validée)
        $consultant = Role::firstOrCreate([
            'name' => 'Consultant'
        ]);



        /*
        |--------------------------------------------------------------------------
        | AFFECTATION PERMISSIONS
        |--------------------------------------------------------------------------
        */

        // 🔥 SUPER ADMIN
        $superAdmin->givePermissionTo(
            Permission::all()
        );



        // 🔥 ADMIN
        $admin->givePermissionTo([
            'gerer-service',
            'gerer-departement',
            'gerer-agence',
            'gerer-utilisateurs',


        ]);
        //GESTIONNAIRE
        $gestionnaire->givePermissionTo([
            'gerer-stock',
            'voir-etats',
            'exporter-etats',


            'creer-commande',
            'modifier-commande',
            'livrer-commande',
            'lister-commande',
            'gerer-type-article',
        ]);

        // 🔥 DEMANDEUR
        $demandeur->givePermissionTo([
            'creer-demande',
            'modifier-demande',
            'lister-demande',
        ]);



        // 🔥 MAGASINIER
        $validateurD->givePermissionTo([

            'valider-demande',
        ]);

        // 🔥 ACHETEUR : validation des commandes (rôle ET permission exigés)
        $validateurC->givePermissionTo([
            'valider-commande',
        ]);
        // Superieur : aucune permission de module (validation des demandes par
        // désignation, DesignationSuperieur).
        // 🔥 CONSULTANT : lecture seule
        $consultant->givePermissionTo([
            'consulter-commandes-receptionnees',
            'consulter-etats',
            'consulter-mouvements',
        ]);
    }
}
