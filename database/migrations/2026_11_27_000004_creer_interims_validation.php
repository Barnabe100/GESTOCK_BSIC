<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Q3 (décision validée, §4.3) : remplaçant temporaire d'une AUTRE entité,
 * désigné explicitement par l'administrateur quand une entité n'a pas deux
 * validateurs supérieurs.
 *
 * Une ligne = un intérim : périmètre (une seule entité de validation),
 * remplaçant, période (date de fin obligatoire), auteur, motif, révocation.
 * Les lignes ne sont jamais supprimées : la table est elle-même l'historique.
 * Les titulaires restent gérés par users.superieur et historique_superieurs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interims_validation', function (Blueprint $table) {
            $table->id();
            $table->string('entite_type', 20);
            $table->unsignedBigInteger('entite_id');
            $table->foreignId('user_id')->constrained('users');
            $table->date('debut_le');
            $table->date('fin_le');
            $table->string('motif', 255)->nullable();
            $table->foreignId('designe_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoque_le')->nullable();
            $table->foreignId('revoque_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['entite_type', 'entite_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interims_validation');
    }
};
