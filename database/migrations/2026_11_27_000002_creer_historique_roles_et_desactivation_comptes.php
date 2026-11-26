<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comptes et rôles (décisions validées) :
 * - historique_roles : chaque attribution ou retrait de rôle est conservé
 *   (qui, quel rôle, par qui, d'où, quand) ;
 * - users.desactive_le / desactive_par : désactivation (et non suppression)
 *   des comptes qui portent un historique métier (Q9).
 *
 * Migration additive : aucune donnée existante n'est modifiée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historique_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('utilisateur', 255);
            $table->string('role', 100);
            $table->string('action', 10);
            $table->string('origine', 30);
            $table->foreignId('effectue_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('desactive_le')->nullable()->after('bloque');
            $table->foreignId('desactive_par')->nullable()->after('desactive_le')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('desactive_par');
            $table->dropColumn('desactive_le');
        });

        Schema::dropIfExists('historique_roles');
    }
};
