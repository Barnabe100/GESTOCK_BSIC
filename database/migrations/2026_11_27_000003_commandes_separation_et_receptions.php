<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Commandes (décisions validées) :
 * - soumise_par / validee_par (+ dates) : séparation des responsabilités
 *   (soumission par un Gestionnaire, validation par une AUTRE personne) et
 *   traçabilité ;
 * - receptions_commandes : une ligne par réception, avec un jeton UNIQUE
 *   fourni par le formulaire ; une même réception rejouée (double clic,
 *   renvoi du formulaire, requêtes concurrentes) est refusée par la base,
 *   donc jamais comptée ni mouvementée deux fois ;
 * - N-13 : la permission valider-commande est donnée au rôle
 *   ValidateurCommande s'il existe (ajout seulement ; rien n'est retiré).
 *
 * Migration additive ; le retour arrière retire uniquement ce qu'elle ajoute.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->foreignId('soumise_par')->nullable()->after('statut')->constrained('users')->nullOnDelete();
            $table->timestamp('soumise_le')->nullable()->after('soumise_par');
            $table->foreignId('validee_par')->nullable()->after('soumise_le')->constrained('users')->nullOnDelete();
            $table->timestamp('validee_le')->nullable()->after('validee_par');
        });

        Schema::create('receptions_commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->cascadeOnDelete();
            $table->string('jeton', 64)->unique();
            $table->date('date_reception');
            $table->unsignedInteger('quantite_totale');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $this->accorderPermissionValidateurCommande();
    }

    public function down(): void
    {
        // La permission accordée au ValidateurCommande n'est pas retirée : il
        // est impossible de savoir si elle existait avant cette migration, et
        // un retour arrière ne doit pas supprimer un droit existant.
        Schema::dropIfExists('receptions_commandes');

        Schema::table('commandes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validee_par');
            $table->dropColumn('validee_le');
            $table->dropConstrainedForeignId('soumise_par');
            $table->dropColumn('soumise_le');
        });
    }

    private function accorderPermissionValidateurCommande(): void
    {
        $tables = config('permission.table_names');
        $role = DB::table($tables['roles'])->where('name', 'ValidateurCommande')->where('guard_name', 'web')->first();
        $permission = DB::table($tables['permissions'])->where('name', 'valider-commande')->where('guard_name', 'web')->first();

        if (! $role || ! $permission) {
            return;
        }

        DB::table($tables['role_has_permissions'])
            ->insertOrIgnore(['permission_id' => $permission->id, 'role_id' => $role->id]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
