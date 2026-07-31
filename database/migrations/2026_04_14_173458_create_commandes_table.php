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
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->string('numero_bon_commande')->unique();
            $table->string('num_facture_proforma')->nullable();
            $table->date('date');
            $table->string('libelle')->nullable();
            $table->decimal('montant', 10, 2)->default(0);
            $table->string('statut')->default('En préparation');
            $table->boolean('validation')->default(0);
            $table->boolean('livraison')->default(0);
            $table->date('date_livraison')->nullable();

            $table->boolean('archiver')->default(0);

            $table->foreignId('fournisseur_id')
                ->constrained('fournisseurs')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
