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
        Schema::create('article_commande', function (Blueprint $table) {
            $table->id();
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 10, 2);
            $table->integer('quantite_livree')->default(0);
            $table->integer('quantite_restante')->default(0);
            $table->boolean('archiver')->default(0);
            $table->foreignId('commande_id')
                ->constrained('commandes')
                ->cascadeOnDelete();

            $table->foreignId('article_id')
                ->constrained('articles')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_commande');
    }
};
