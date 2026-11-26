<?php

namespace Tests\Feature\Finalisation;

use App\Models\Article;
use App\Models\ArticleCommande;
use App\Models\Commande;
use App\Models\Fournisseur;
use App\Models\MouvementStock;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\RequeteDansUnAutreProcessus;
use Tests\TestCase;

/**
 * Réception de commande face à des requêtes réellement concurrentes : jamais
 * de double comptage, jamais de dépassement du reste à livrer.
 */
class ReceptionCommandeConcurrenceTest extends TestCase
{
    use DatabaseMigrations;
    use RequeteDansUnAutreProcessus;

    private User $validateur;

    private Article $article;

    private Commande $commande;

    private ArticleCommande $ligne;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->validateur = User::factory()->create();
        $this->validateur->assignRole('ValidateurCommande');
        $this->article = Article::factory()->create(['stock_disponible' => 0, 'stock_initial' => 0]);
        $this->commande = Commande::forceCreate(['numero_bon_commande' => 'BC-C1', 'date' => '2026-11-20', 'montant' => 1000,
            'fournisseur_id' => Fournisseur::factory()->create()->id, 'statut' => 'Prête pour validation', 'validation' => 1, 'livraison' => 0, 'archiver' => 0]);
        $this->ligne = ArticleCommande::create(['commande_id' => $this->commande->id, 'article_id' => $this->article->id,
            'quantite' => 10, 'prix_unitaire' => 100, 'quantite_livree' => 0, 'quantite_restante' => 10]);
    }

    private function donnees(int $quantite, string $jeton): array
    {
        return ['_method' => 'PUT', 'date_livraison' => '2026-11-25', 'quantites' => [$this->ligne->id => $quantite], 'jeton_reception' => $jeton];
    }

    /** Première réception dans une transaction ouverte, seconde dans un autre processus. */
    private function deuxReceptionsSimultanees(array $premiere, array $seconde): array
    {
        DB::beginTransaction();
        $this->actingAs($this->validateur)->from('/commandes')->post(route('commandes.livrer', $this->commande->id), $premiere)
            ->assertSessionHas('success', 'Réception enregistrée');

        $autre = $this->requeteDansUnAutreProcessus($this->validateur->id, 'POST', '/commandes/'.$this->commande->id.'/livrer', $seconde);
        usleep(800_000);
        $this->assertTrue($autre->isRunning(), 'La seconde réception doit attendre la première.');

        DB::commit();

        return $this->resultatProcessus($autre);
    }

    public function test_meme_reception_envoyee_deux_fois_simultanement_comptee_une_seule_fois(): void
    {
        $jeton = (string) Str::uuid();
        $resultat = $this->deuxReceptionsSimultanees($this->donnees(3, $jeton), $this->donnees(3, $jeton));

        $this->assertSame('Cette réception a déjà été enregistrée.', $resultat['error'] ?? null, $resultat['brut']);
        $this->assertSame(3, (int) $this->ligne->fresh()->quantite_livree);
        $this->assertSame(3, (int) $this->article->fresh()->stock_disponible);
        $this->assertSame(1, MouvementStock::where('type', 'ENTREE')->count());
    }

    public function test_deux_receptions_distinctes_simultanees_ne_depassent_pas_le_reste(): void
    {
        $resultat = $this->deuxReceptionsSimultanees($this->donnees(6, (string) Str::uuid()), $this->donnees(6, (string) Str::uuid()));

        $this->assertSame('Quantité reçue supérieure à la quantité restante pour '.$this->article->libelle, $resultat['error'] ?? null, $resultat['brut']);
        $this->assertSame(6, (int) $this->ligne->fresh()->quantite_livree);
        $this->assertSame(4, (int) $this->ligne->fresh()->quantite_restante);
        $this->assertSame(6, (int) $this->article->fresh()->stock_disponible);
        $this->assertSame(1, MouvementStock::where('type', 'ENTREE')->count());
    }
}
