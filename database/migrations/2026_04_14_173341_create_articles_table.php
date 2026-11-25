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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('libelle');

            $table->decimal('prix_unitaire', 10, 2)->nullable();
            $table->integer('stock_initial')->default(0);
            $table->integer('stock_disponible')->default(0);
            $table->integer('stock_securite')->default(0);
            $table->boolean('archiver')->default(0);
            $table->foreignId('categorie_id')
                ->constrained('categories')
                ->cascadeOnDelete();
            // La contrainte vers `type_articles` est posée par la migration
            // 2026_07_28_235701_create_type_articles_table, car cette table
            // n'existe pas encore à ce stade sur une base vierge.
            // Schéma final identique (même colonne, même nom de contrainte,
            // même ON DELETE CASCADE). Aucun effet sur les bases déjà migrées.
            $table->foreignId('type_article_id')
                ->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
