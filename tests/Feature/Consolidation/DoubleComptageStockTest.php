<?php

namespace Tests\Feature\Consolidation;

use App\Http\Controllers\EtatStockController;
use App\Http\Controllers\Etats\EtatMouvementStockController;
use App\Models\Article;
use App\Models\ArticleCommande;
use App\Models\Commande;
use App\Models\Fournisseur;
use App\Models\MouvementStock;
use App\Models\ReceptionCommande;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

/**
 * P1-04 : double comptage du stock à la première réception d'une commande.
 *
 * Les états de stock calculent : stock_initial + entrées − sorties. Avant la
 * correction, la première réception augmentait À LA FOIS stock_initial et
 * créait un mouvement ENTREE de la même quantité : les états comptaient la
 * quantité deux fois, alors que stock_disponible ne la comptait qu'une fois.
 *
 * Les trois calculs d'état réels (stock, stock valorisé, stock final) sont
 * appelés directement, pour vérifier la valeur que l'utilisateur obtient.
 */
class DoubleComptageStockTest extends TestCase
{
    use RefreshDatabase;

    private User $validateur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->validateur = User::factory()->create();
        $this->validateur->assignRole('ValidateurCommande');
    }

    private function article(int $initial = 0, int $disponible = 0): Article
    {
        return Article::factory()->create(['stock_initial' => $initial, 'stock_disponible' => $disponible]);
    }

    /** @param list<array{0: Article, 1: int}> $lignes */
    private function commandeValidee(array $lignes): Commande
    {
        $c = Commande::forceCreate(['numero_bon_commande' => 'BC-'.Str::random(6), 'date' => '2026-11-01', 'montant' => 0,
            'fournisseur_id' => Fournisseur::factory()->create()->id, 'statut' => 'Prête pour validation', 'validation' => 1, 'livraison' => 0, 'archiver' => 0]);
        foreach ($lignes as [$article, $quantite]) {
            ArticleCommande::create(['commande_id' => $c->id, 'article_id' => $article->id, 'quantite' => $quantite, 'prix_unitaire' => 100,
                'quantite_livree' => 0, 'quantite_restante' => $quantite]);
        }

        return $c;
    }

    private function receptionner(Commande $c, array $quantites, ?string $jeton = null)
    {
        $parLigne = [];
        foreach ($quantites as $articleId => $q) {
            $parLigne[ArticleCommande::where('commande_id', $c->id)->where('article_id', $articleId)->value('id')] = $q;
        }

        return $this->actingAs($this->validateur)->from('/commandes')->put(route('commandes.livrer', $c->id),
            ['date_livraison' => '2026-11-10', 'quantites' => $parLigne, 'jeton_reception' => $jeton ?? (string) Str::uuid()]);
    }

    /** Stock de fin de période tel que l'affichent les trois états. */
    private function stockSelonLesEtats(Article $article): array
    {
        $etat = new ReflectionMethod(EtatStockController::class, 'getEtatStock');
        $valorise = new ReflectionMethod(EtatStockController::class, 'getEtatStockValorise');
        $final = new ReflectionMethod(EtatMouvementStockController::class, 'getEtatStockFinal');
        $libelle = $article->libelle;
        $trouver = fn ($lignes, $cle) => collect($lignes)->firstWhere('article', $libelle)[$cle] ?? null;

        return [
            'etat_stock' => $trouver($etat->invoke(app(EtatStockController::class), '2026-11-01', '2026-11-30'), 'stock_fin'),
            'etat_valorise' => $trouver($valorise->invoke(app(EtatStockController::class), '2026-11-01', '2026-11-30'), 'stock_fin_qte'),
            'etat_final' => $trouver($final->invoke(app(EtatMouvementStockController::class), '2026-11-01', '2026-11-30'), 'quantite'),
        ];
    }

    private function assertStockCoherent(Article $article, int $attendu): void
    {
        $article->refresh();
        $this->assertSame($attendu, (int) $article->stock_disponible, 'stock_disponible');
        $this->assertSame(
            ['etat_stock' => $attendu, 'etat_valorise' => $attendu, 'etat_final' => $attendu],
            array_map('intval', $this->stockSelonLesEtats($article)),
            'Les états de stock doivent compter chaque quantité reçue une seule fois.'
        );
    }

    public function test_premiere_reception_comptee_une_seule_fois(): void
    {
        $a = $this->article();
        $c = $this->commandeValidee([[$a, 10]]);

        $this->receptionner($c, [$a->id => 10])->assertSessionHas('success', 'Réception enregistrée');

        $this->assertSame(0, (int) $a->fresh()->stock_initial, 'La réception ne doit pas modifier le stock initial.');
        $this->assertSame(10, (int) MouvementStock::where('article_id', $a->id)->where('type', 'ENTREE')->sum('quantite'));
        $this->assertStockCoherent($a, 10);
    }

    public function test_receptions_partielles_successives(): void
    {
        $a = $this->article();
        $c = $this->commandeValidee([[$a, 10]]);

        $this->receptionner($c, [$a->id => 4])->assertSessionHas('success');
        $this->assertStockCoherent($a, 4);
        $this->receptionner($c, [$a->id => 6])->assertSessionHas('success');
        $this->assertStockCoherent($a, 10);
        $this->assertSame(2, MouvementStock::where('article_id', $a->id)->count());
        $this->assertTrue((bool) $c->fresh()->livraison);
    }

    public function test_article_avec_stock_initial_parametre(): void
    {
        $a = $this->article(5, 5);
        $c = $this->commandeValidee([[$a, 3]]);

        $this->receptionner($c, [$a->id => 3]);

        $this->assertSame(5, (int) $a->fresh()->stock_initial);
        $this->assertStockCoherent($a, 8);
    }

    public function test_reception_repetee_comptee_une_seule_fois(): void
    {
        $a = $this->article();
        $c = $this->commandeValidee([[$a, 10]]);
        $jeton = (string) Str::uuid();

        $this->receptionner($c, [$a->id => 5], $jeton)->assertSessionHas('success');
        $this->receptionner($c, [$a->id => 5], $jeton)->assertSessionHas('error', 'Cette réception a déjà été enregistrée.');

        $this->assertSame(1, MouvementStock::where('article_id', $a->id)->count());
        $this->assertStockCoherent($a, 5);
    }

    public function test_interruption_pendant_la_reception_rien_n_est_ecrit(): void
    {
        $a1 = $this->article();
        $a2 = $this->article();
        $c = $this->commandeValidee([[$a1, 4], [$a2, 3]]);

        // Panne simulée au deuxième mouvement : la première ligne est déjà
        // écrite dans la transaction au moment de l'interruption.
        $appels = 0;
        MouvementStock::creating(function () use (&$appels) {
            if (++$appels === 2) {
                throw new RuntimeException('Interruption simulée');
            }
        });

        $this->withoutExceptionHandling();
        try {
            $this->receptionner($c, [$a1->id => 4, $a2->id => 3]);
            $this->fail('L\'interruption simulée devait interrompre la réception.');
        } catch (RuntimeException $e) {
            $this->assertSame('Interruption simulée', $e->getMessage());
        }

        $this->assertSame(0, MouvementStock::count());
        $this->assertSame(0, ReceptionCommande::count());
        $this->assertSame([0, 0], ArticleCommande::where('commande_id', $c->id)->orderBy('id')->pluck('quantite_livree')->map(fn ($v) => (int) $v)->all());
        $this->assertSame([4, 3], ArticleCommande::where('commande_id', $c->id)->orderBy('id')->pluck('quantite_restante')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(0, (int) $a1->fresh()->stock_disponible);
        $this->assertSame(0, (int) $a1->fresh()->stock_initial);
        $this->assertFalse((bool) $c->fresh()->livraison);
    }

    public function test_diagnostic_des_articles_deja_touches_sans_aucune_ecriture(): void
    {
        // Article touché par l'ancienne règle : stock initial = première réception.
        $touche = $this->article(10, 10);
        MouvementStock::create(['article_id' => $touche->id, 'type' => 'ENTREE', 'quantite' => 10, 'prix_unitaire' => 100,
            'motif' => 'Réception commande N° BC-ANCIEN', 'date_mouvement' => '2026-06-01']);
        // Article paramétré manuellement : pas de signature.
        $parametre = $this->article(7, 7);
        $empreinte = fn () => json_encode([\DB::table('articles')->orderBy('id')->get(), \DB::table('mouvement_stocks')->orderBy('id')->get()]);
        $avant = $empreinte();

        $fichier = tempnam(sys_get_temp_dir(), 'p104');
        $this->artisan('gestok:stock:diagnostic-p104', ['--json' => $fichier])->assertSuccessful();
        $suspects = json_decode(file_get_contents($fichier), true);
        unlink($fichier);

        $this->assertSame($avant, $empreinte(), 'Le diagnostic ne doit rien écrire.');
        $this->assertSame([$touche->id], array_column($suspects, 'article_id'));
        $this->assertSame(20, $suspects[0]['stock_selon_etats']);
        $this->assertSame(10, $suspects[0]['stock_disponible']);
        $this->assertTrue($suspects[0]['double_comptage_probable']);
        $this->assertNotContains($parametre->id, array_column($suspects, 'article_id'));
    }
}
