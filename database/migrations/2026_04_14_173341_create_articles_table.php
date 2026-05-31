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
            $table->decimal('prix_unitaire', 10, 2);
            $table->integer('stock_initial')->default(0);

            $table->integer('stock_disponible')->default(0);
            $table->integer('stock_securite')->default(0);
            $table->boolean('archiver')->default(0);

            $table->foreignId('categorie_id')
                ->constrained('categories')
                ->cascadeOnDelete();
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
