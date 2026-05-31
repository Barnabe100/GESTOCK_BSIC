<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('demandes', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('objet');
            $table->text('description')->nullable();

            $table->boolean('validation_gestionnaire')->default(0);
            $table->boolean('livraison')->default(0);
            $table->date('date_livraison')->nullable();

            $table->boolean('archiver')->default(0);

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('superieur_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean(
                'validation_superieur'
            )->default(false);

            $table->timestamp(
                'date_validation_superieur'
            )->nullable();

            $table->text(
                'commentaire_superieur'
            )->nullable();

            $table->string(
                'statut'
            )->default(
                'En attente supérieur'
            );
            $table->text(
                'commentaire_gestionnaire'
            )->nullable();

            $table->timestamp(
                'date_validation_gestionnaire'
            )->nullable();
            $table->boolean(
                'validation_finale'
            )->default(false);
            $table->boolean(
                'confirmation_reception'
            )->default(false);

            $table->text(
                'commentaire_reception'
            )->nullable();








            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes');
    }
};
