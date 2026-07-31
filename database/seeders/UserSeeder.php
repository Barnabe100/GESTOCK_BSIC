<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use \App\Models\User;



class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Assigner rôles
        // $admin->assignRole('Admin');
        // $user->assignRole('Demandeur');

        User::firstOrCreate([
            'name' => 'TEBDA',
            'prenom' => 'Barnabé',
            'email' => 'superadmin@test.com',
            'password' => Hash::make('password'),
            'agence_id' => 1,
            'departement_id' => 1,
            'service_id' => 1,
            'superieur' => 0,
        ])->assignRole('SuperAdmin');

        User::firstOrCreate([
            'name' => 'ADMIN',
            'prenom' => 'Système',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'agence_id' => 1,
            'departement_id' => 1,
            'service_id' => 1,
            'superieur' => 0,
        ])->assignRole('Admin');

        /*User::firstOrCreate([
            'name' => 'GESTIONNAIRE',
            'prenom' => 'Stock',
            'email' => 'gestionnaire@test.com',
            'password' => Hash::make('password'),
            'agence_id' => 1,
            'departement_id' => 1,
            'service_id' => 1,
            'superieur' => 0,
        ])->assignRole('Gestionnaire');

        User::firstOrCreate([
            'name' => 'Departement',
            'prenom' => 'Superieur',
            'email' => 'supdep@test.com',
            'password' => Hash::make('password'),
            'agence_id' => 1,
            'departement_id' => 1,
            'service_id' => 1,
            'superieur' => 0,
        ])->assignRole('Superieur');

        User::firstOrCreate([
            'name' => 'Agence',
            'prenom' => 'Superieur',
            'email' => 'supag@test.com',
            'password' => Hash::make('password'),
            'agence_id' => 1,
            'departement_id' => 1,
            'service_id' => 1,
            'superieur' => 0,
        ])->assignRole('Superieur');

        User::firstOrCreate([
            'name' => 'Agence',
            'prenom' => 'Demandeur',
            'email' => 'demag@test.com',
            'password' => Hash::make('password'),
            'agence_id' => 1,
            'departement_id' => 1,
            'service_id' => 1,
            'superieur' => 0,
        ])->assignRole('Superieur');

        User::firstOrCreate([
            'name' => 'Departement',
            'prenom' => 'Demandeur',
            'email' => 'demdep@test.com',
            'password' => Hash::make('password'),
            'agence_id' => 1,
            'departement_id' => 1,
            'service_id' => 1,
            'superieur' => 0,
        ])->assignRole('Superieur');

        User::firstOrCreate([
            'name' => 'Finale',
            'prenom' => 'Validateur',
            'email' => 'valfinal@test.com',
            'password' => Hash::make('password'),
            'agence_id' => 1,
            'departement_id' => 1,
            'service_id' => 1,
            'superieur' => 0,
        ])->assignRole('ValidateurDemande');

        User::firstOrCreate([
            'name' => 'Commande',
            'prenom' => 'Validateur',
            'email' => 'valcom@test.com',
            'password' => Hash::make('password'),
            'agence_id' => 1,
            'departement_id' => 1,
            'service_id' => 1,
            'superieur' => 0,
        ])->assignRole('ValidateurCommande'); */
    }
}
