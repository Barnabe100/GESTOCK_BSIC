<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Socle des droits, décision validée : l'administrateur peut désigner un
 * supérieur remplaçant ; le supérieur précédent perd sa fonction, avec
 * conservation de l'historique des désignations.
 *
 * Table nouvelle, sans modification des tables existantes : migration
 * réversible (down supprime uniquement cette table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historique_superieurs', function (Blueprint $table) {
            $table->id();
            $table->string('entite_type', 20); // 'agence' ou 'departement'
            $table->unsignedBigInteger('entite_id');
            $table->foreignId('ancien_superieur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('nouveau_superieur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('designe_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['entite_type', 'entite_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_superieurs');
    }
};
