<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('type_articles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->text('description')->nullable();

            $table->boolean('archiver')->default(false);
            $table->timestamps();
        });

        // Contrainte articles.type_article_id -> type_articles.id
        // (auparavant déclarée dans la migration `articles`, exécutée avant
        // la création de cette table : `migrate` échouait sur une base vierge).
        // N'est ajoutée que si elle est absente : sans effet sur une base où
        // elle existe déjà.
        if (Schema::hasTable('articles') && ! $this->contrainteTypeArticleExiste()) {
            Schema::table('articles', function (Blueprint $table) {
                $table->foreign('type_article_id')
                    ->references('id')
                    ->on('type_articles')
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // La contrainte doit être retirée avant de supprimer la table référencée.
        if (Schema::hasTable('articles') && $this->contrainteTypeArticleExiste()) {
            Schema::table('articles', function (Blueprint $table) {
                $table->dropForeign(['type_article_id']);
            });
        }

        Schema::dropIfExists('type_articles');
    }

    /**
     * Indique si une clé étrangère articles.type_article_id -> type_articles existe.
     * Vérification limitée à MySQL / MariaDB (moteur utilisé par GESTOK).
     */
    private function contrainteTypeArticleExiste(): bool
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return false;
        }

        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'articles')
            ->where('COLUMN_NAME', 'type_article_id')
            ->where('REFERENCED_TABLE_NAME', 'type_articles')
            ->exists();
    }
};
