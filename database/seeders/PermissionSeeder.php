<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // SERVICES
            'service.view',
            'service.create',
            'service.update',
            'service.delete',

            // DEMANDES
            'demande.view',
            'demande.create',
            'demande.update',
            'demande.delete',

            // COMMANDES
            'commande.view',
            'commande.create',
            'commande.update',
            'commande.delete',

            // ARTICLES
            'article.view',
            'article.create',
            'article.update',
            'article.delete',

            // CATEGORIES
            'categorie.manage',

            // FOURNISSEURS
            'fournisseur.manage',

            // AGENCES
            'agence.manage',

            // DEPARTEMENTS
            'departement.manage',

            // SERVICES (admin global)
            'service.manage',

            // USERS
            'user.manage',

        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
