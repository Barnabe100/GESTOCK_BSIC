<?php

namespace Tests\Feature\Lot0;

use App\Models\Article;
use App\Models\ArticleDemande;
use App\Models\TypeArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Lot 0 : la base de test est isolée et le schéma se construit sur une base vierge.
 */
class SchemaBaseDeTestTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_tests_ciblent_la_base_isolee(): void
    {
        $this->assertSame('gestok_test', DB::getDatabaseName());
    }

    public function test_la_contrainte_type_article_est_identique_a_l_origine(): void
    {
        $contrainte = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'articles')
            ->where('REFERENCED_TABLE_NAME', 'type_articles')
            ->first();

        $this->assertNotNull($contrainte, 'La clé étrangère articles.type_article_id est absente.');
        // Nom et comportement générés à l'origine par ->constrained()->cascadeOnDelete()
        $this->assertSame('articles_type_article_id_foreign', $contrainte->CONSTRAINT_NAME);
        $this->assertSame('CASCADE', $contrainte->DELETE_RULE);
    }

    public function test_la_colonne_type_article_reste_nullable(): void
    {
        $article = Article::factory()->create(['type_article_id' => null]);

        $this->assertNull($article->fresh()->type_article_id);
    }

    public function test_les_factories_produisent_des_donnees_coherentes(): void
    {
        $ligne = ArticleDemande::factory()->create();

        $this->assertNotNull($ligne->demande->user->prenom);
        $this->assertSame('En préparation', $ligne->demande->statut);
        $this->assertNotNull($ligne->article->categorie);
        $this->assertInstanceOf(TypeArticle::class, $ligne->article->typeArticle);
        $this->assertInstanceOf(User::class, $ligne->demande->user);
    }
}
