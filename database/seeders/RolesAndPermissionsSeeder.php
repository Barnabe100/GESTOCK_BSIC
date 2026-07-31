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

        $validateurC = Role::firstOrCreate([
            'name' => 'Superieur'
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

        // 🔥 ACHETEUR
        $validateurC->givePermissionTo([
            'valider-commande',
        ]);
    }
}
