<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use \App\Models\User;
use RuntimeException;



class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lot 1, L1-5 : le mot de passe des comptes d'installation est lu dans
        // la configuration du serveur (.env : GESTOK_SEED_MOT_DE_PASSE),
        // jamais dans le code. Changement obligatoire à la première connexion.
        $motDePasse = config('gestok.seed_mot_de_passe');

        if (blank($motDePasse)) {
            throw new RuntimeException(
                'UserSeeder : GESTOK_SEED_MOT_DE_PASSE n\'est pas configuré dans le fichier .env.'
            );
        }

        // Assigner rôles
        // $admin->assignRole('Admin');
        // $user->assignRole('Demandeur');

        // Recherche sur l'e-mail uniquement : le seeder peut être relancé
        // sans créer de doublon ni modifier un compte existant.
        User::firstOrCreate(['email' => 'superadmin@test.com'], [
            'name' => 'TEBDA',
            'prenom' => 'Barnabé',
            'password' => Hash::make($motDePasse),
            'force_password_change' => true,
            'agence_id' => 1,
            'departement_id' => 1,
            'service_id' => 1,
            'superieur' => 0,
        ])->assignRole('SuperAdmin');

        User::firstOrCreate(['email' => 'admin@test.com'], [
            'name' => 'ADMIN',
            'prenom' => 'Système',
            'password' => Hash::make($motDePasse),
            'force_password_change' => true,
            'agence_id' => 1,
            'departement_id' => 1,
            'service_id' => 1,
            'superieur' => 0,
        ])->assignRole('Admin');

        /*User::firstOrCreate([
            'name' => 'GESTIONNAIRE',
            'prenom' => 'Stock',
            'email' => 'gestionnaire@test.com',
            'password' => Hash::make($motDePasse),
            'agence_id' => 1,
            'departement_id' => 1,
            'service_id' => 1,
            'superieur' => 0,
        ])->assignRole('Gestionnaire');

        User::firstOrCreate([
            'name' => 'Departement',
            'prenom' => 'Superieur',
            'email' => 'supdep@test.com',
            'password' => Hash::make($motDePasse),
            'agence_id' => 1,
            'departement_id' => 1,
            'service_id' => 1,
            'superieur' => 0,
        ])->assignRole('Superieur');

        User::firstOrCreate([
            'name' => 'Agence',
            'prenom' => 'Superieur',
            'email' => 'supag@test.com',
            'password' => Hash::make($motDePasse),
            'agence_id' => 1,
            'departement_id' => 1,
            'service_id' => 1,
            'superieur' => 0,
        ])->assignRole('Superieur');

        User::firstOrCreate([
            'name' => 'Agence',
            'prenom' => 'Demandeur',
            'email' => 'demag@test.com',
            'password' => Hash::make($motDePasse),
            'agence_id' => 1,
            'departement_id' => 1,
            'service_id' => 1,
            'superieur' => 0,
        ])->assignRole('Superieur');

        User::firstOrCreate([
            'name' => 'Departement',
            'prenom' => 'Demandeur',
            'email' => 'demdep@test.com',
            'password' => Hash::make($motDePasse),
            'agence_id' => 1,
            'departement_id' => 1,
            'service_id' => 1,
            'superieur' => 0,
        ])->assignRole('Superieur');

        User::firstOrCreate([
            'name' => 'Finale',
            'prenom' => 'Validateur',
            'email' => 'valfinal@test.com',
            'password' => Hash::make($motDePasse),
            'agence_id' => 1,
            'departement_id' => 1,
            'service_id' => 1,
            'superieur' => 0,
        ])->assignRole('ValidateurDemande');

        User::firstOrCreate([
            'name' => 'Commande',
            'prenom' => 'Validateur',
            'email' => 'valcom@test.com',
            'password' => Hash::make($motDePasse),
            'agence_id' => 1,
            'departement_id' => 1,
            'service_id' => 1,
            'superieur' => 0,
        ])->assignRole('ValidateurCommande'); */
    }
}
