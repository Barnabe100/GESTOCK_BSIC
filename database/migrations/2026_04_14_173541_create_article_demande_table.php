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
        Schema::create('article_demande', function (Blueprint $table) {
            $table->id();
            $table->integer('quantite');
            $table->integer('quantite_accordee')->nullable();
            $table->boolean('archiver')->default(0);

            $table->foreignId('demande_id')
                ->constrained('demandes')
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
        Schema::dropIfExists('article_demande');
    }
};
