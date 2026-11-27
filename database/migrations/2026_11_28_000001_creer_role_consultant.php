<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rôle Consultant (décision validée) : consultation sans opération.
 *
 * Permissions de consultation au nom DÉDIÉ (préfixe « consulter- ») : avec
 * register_permission_check_method, Spatie accorde toute ability qui porte
 * exactement le nom d'une permission détenue ; ces noms ne correspondent donc
 * à aucune capacité d'opération ni à aucune méthode de Policy (contrôlé par
 * un test).
 *
 * Les permissions sont aussi attribuées explicitement au SuperAdmin existant
 * (le seeder ne s'exécute pas sur une base déjà installée).
 */
return new class extends Migration
{
    public const PERMISSIONS = [
        'consulter-commandes-receptionnees',
        'consulter-etats',
        'consulter-mouvements',
    ];

    public function up(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $nom) {
            Permission::findOrCreate($nom, 'web');
        }

        Role::findOrCreate('Consultant', 'web')->givePermissionTo(self::PERMISSIONS);

        if ($superAdmin = Role::where('name', 'SuperAdmin')->where('guard_name', 'web')->first()) {
            $superAdmin->givePermissionTo(self::PERMISSIONS);
        }

        $registrar->forgetCachedPermissions();
    }

    public function down(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        // Retour arrière : suppression du rôle (et de ses attributions) et des
        // permissions ajoutées par cette migration, rien d'autre.
        Role::where('name', 'Consultant')->where('guard_name', 'web')->get()->each->delete();
        Permission::whereIn('name', self::PERMISSIONS)->where('guard_name', 'web')->get()->each->delete();

        $registrar->forgetCachedPermissions();
    }
};
