<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * N03 : type explicite des entités organisationnelles (table agences).
 *
 * - type : 'SIEGE' ou 'AGENCE'. NULL = entité non encore qualifiée. Aucune
 *   valeur n'est déduite du nom : la qualification des données existantes est
 *   une décision humaine (commande gestok:organisation:qualifier).
 * - siege_actif : colonne générée STOCKÉE, égale à 1 pour un siège actif
 *   (type SIEGE et non archivé), NULL sinon. L'index UNIQUE sur cette colonne
 *   garantit, par le moteur lui-même (MariaDB et MySQL 8), un seul siège actif,
 *   y compris face à deux écritures concurrentes. Plusieurs NULL restent permis
 *   (agences, sièges archivés).
 * - historique_entites : trace des qualifications, changements de siège et
 *   désactivations.
 *
 * Aucune ligne existante n'est modifiée ; le retour arrière supprime seulement
 * ce que cette migration ajoute.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agences', function (Blueprint $table) {
            $table->string('type', 10)->nullable()->after('nom');
        });

        DB::statement("ALTER TABLE agences ADD CONSTRAINT agences_type_valeurs CHECK (type IS NULL OR type IN ('SIEGE', 'AGENCE'))");
        DB::statement("ALTER TABLE agences ADD COLUMN siege_actif TINYINT UNSIGNED AS (CASE WHEN type = 'SIEGE' AND archiver = 0 THEN 1 ELSE NULL END) STORED");
        DB::statement('ALTER TABLE agences ADD UNIQUE INDEX agences_un_seul_siege_actif (siege_actif)');

        Schema::create('historique_entites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agence_id')->nullable()->constrained('agences')->nullOnDelete();
            $table->string('action', 30);
            $table->string('ancien_type', 10)->nullable();
            $table->string('nouveau_type', 10)->nullable();
            $table->foreignId('effectue_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agence_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_entites');

        DB::statement('ALTER TABLE agences DROP INDEX agences_un_seul_siege_actif');
        DB::statement('ALTER TABLE agences DROP COLUMN siege_actif');
        DB::statement('ALTER TABLE agences DROP CONSTRAINT agences_type_valeurs');

        Schema::table('agences', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
