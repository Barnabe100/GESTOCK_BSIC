<?php

namespace Tests\Feature\L14;

use App\Models\Article;
use App\Models\ArticleDemande;
use App\Models\Demande;
use App\Models\MouvementStock;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L1-4, workflow « articles d'une demande » (ajout, modification, suppression).
 *
 * Règles vérifiées (plan définitif L1-4, décisions validées) :
 * - seul le propriétaire de la demande agit sur ses lignes, sans exception (SuperAdmin compris) ;
 * - uniquement tant que le supérieur n'a pas validé (validation_superieur = 0, D-1) ;
 * - quantité entière strictement positive, refus sans message (P-1, D-5) ;
 * - aucune opération sur les lignes ne crée de mouvement ni ne modifie le stock ;
 * - les contrôles et messages existants sont inchangés (D-6).
 */
class LignesDemandeTest extends TestCase
{
    use RefreshDatabase;

    private const STOCK = 100;

    private const MSG_VERROU = 'Cette demande ne peut plus être modifiée.';

    /** Statut => attributs de la demande dans cet état. */
    private const ETATS = [
        'En préparation' => [],
        'En attente supérieur' => [],
        'Rejetée' => [],
        'En attente gestionnaire' => ['validation_superieur' => 1],
        'Rejetée gestionnaire' => ['validation_superieur' => 1],
        'En attente validation finale' => ['validation_superieur' => 1, 'validation_gestionnaire' => 1],
        'Rejetée validation finale' => ['validation_superieur' => 1, 'validation_gestionnaire' => 1],
        'En attente livraison' => ['validation_superieur' => 1, 'validation_gestionnaire' => 1, 'validation_finale' => 1],
        'En attente réception demandeur' => ['validation_superieur' => 1, 'validation_gestionnaire' => 1, 'validation_finale' => 1, 'livraison' => 1],
        'Livrée - Demande clôturée' => ['validation_superieur' => 1, 'validation_gestionnaire' => 1, 'validation_finale' => 1, 'livraison' => 1],
    ];

    private const ACTEURS = ['proprietaire', 'collegue', 'superieur', 'gestionnaire', 'superadmin'];

    /** @var array<string, User> */
    private array $acteurs;

    private Article $article;

    private Article $autreArticle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $roles = [
            'proprietaire' => 'Demandeur',
            'collegue' => 'Demandeur',
            'superieur' => 'Superieur',
            'gestionnaire' => 'Gestionnaire',
            'superadmin' => 'SuperAdmin',
        ];
        foreach ($roles as $acteur => $role) {
            $this->acteurs[$acteur] = User::factory()->create();
            $this->acteurs[$acteur]->assignRole($role);
        }

        $this->article = Article::factory()->create(['stock_disponible' => self::STOCK]);
        $this->autreArticle = Article::factory()->create(['stock_disponible' => self::STOCK]);
    }

    private function demande(string $statut = 'En préparation', ?User $proprietaire = null): Demande
    {
        return Demande::factory()->create(array_merge([
            'statut' => $statut,
            'user_id' => ($proprietaire ?? $this->acteurs['proprietaire'])->id,
            'superieur_id' => $this->acteurs['superieur']->id,
        ], self::ETATS[$statut]));
    }

    private function ligne(Demande $demande, int $quantite = 4): ArticleDemande
    {
        return ArticleDemande::factory()->create([
            'demande_id' => $demande->id,
            'article_id' => $this->article->id,
            'quantite' => $quantite,
        ]);
    }

    private function pageDemande(Demande $demande): string
    {
        return '/demandes/'.$demande->id;
    }

    /** Aucun mouvement de stock, stock des articles inchangé. */
    private function assertStockIntact(): void
    {
        $this->assertSame(0, MouvementStock::count(), 'Aucun mouvement de stock ne doit être créé.');
        $this->assertSame(self::STOCK, (int) $this->article->fresh()->stock_disponible);
        $this->assertSame(self::STOCK, (int) $this->autreArticle->fresh()->stock_disponible);
    }

    public static function acteursEtEtats(): iterable
    {
        foreach (self::ACTEURS as $acteur) {
            foreach (array_keys(self::ETATS) as $statut) {
                yield "$acteur / $statut" => [$acteur, $statut];
            }
        }
    }

    // ------------------------------------------------------------------
    // Matrice des droits : 5 acteurs x 10 états x 3 opérations
    // ------------------------------------------------------------------

    /** @dataProvider acteursEtEtats */
    public function test_matrice_ajout(string $acteur, string $statut): void
    {
        $demande = $this->demande($statut);

        $reponse = $this->actingAs($this->acteurs[$acteur])
            ->from($this->pageDemande($demande))
            ->post(route('article_demande.store'), [
                'demande_id' => $demande->id,
                'article_id' => $this->article->id,
                'quantite' => 3,
            ]);

        $this->verifierResultatMatrice($reponse, $acteur, $demande, 'Article ajouté et stock mis à jour');
        $attendu = ($acteur === 'proprietaire' && ! $demande->validation_superieur) ? 1 : 0;
        $this->assertSame($attendu, ArticleDemande::where('demande_id', $demande->id)->count());
        $this->assertStockIntact();
    }

    /** @dataProvider acteursEtEtats */
    public function test_matrice_modification(string $acteur, string $statut): void
    {
        $demande = $this->demande($statut);
        $ligne = $this->ligne($demande, 4);

        $reponse = $this->actingAs($this->acteurs[$acteur])
            ->from($this->pageDemande($demande))
            ->put(route('article-demandes.update', $ligne->id), [
                'article_id' => $this->autreArticle->id,
                'quantite' => 7,
            ]);

        $this->verifierResultatMatrice($reponse, $acteur, $demande, 'Article modifié avec succès.');
        $ligne->refresh();
        if ($acteur === 'proprietaire' && ! $demande->validation_superieur) {
            $this->assertSame([$this->autreArticle->id, 7], [(int) $ligne->article_id, (int) $ligne->quantite]);
        } else {
            $this->assertSame([$this->article->id, 4], [(int) $ligne->article_id, (int) $ligne->quantite]);
        }
        $this->assertStockIntact();
    }

    /** @dataProvider acteursEtEtats */
    public function test_matrice_suppression(string $acteur, string $statut): void
    {
        $demande = $this->demande($statut);
        $ligne = $this->ligne($demande, 4);

        $reponse = $this->actingAs($this->acteurs[$acteur])
            ->from($this->pageDemande($demande))
            ->delete(route('article_demande.destroy', $ligne->id));

        $this->verifierResultatMatrice($reponse, $acteur, $demande, 'Article supprimé et stock mis à jour');
        $attendu = ($acteur === 'proprietaire' && ! $demande->validation_superieur) ? 0 : 1;
        $this->assertSame($attendu, ArticleDemande::whereKey($ligne->id)->count());
        $this->assertStockIntact();
    }

    private function verifierResultatMatrice($reponse, string $acteur, Demande $demande, string $succes): void
    {
        if ($acteur !== 'proprietaire') {
            $reponse->assertForbidden();

            return;
        }

        $reponse->assertRedirect($this->pageDemande($demande));
        if ($demande->validation_superieur) {
            $reponse->assertSessionHas('error', self::MSG_VERROU);
            $reponse->assertSessionMissing('success');
        } else {
            $reponse->assertSessionHas('success', $succes);
            $reponse->assertSessionMissing('error');
        }
    }

    // ------------------------------------------------------------------
    // Quantité entière strictement positive (P-1, D-5), refus sans message
    // ------------------------------------------------------------------

    public static function quantitesInvalides(): iterable
    {
        yield 'zéro' => [0];
        yield 'négative' => [-3];
        yield 'décimale' => ['2.5'];
        yield 'texte' => ['abc'];
        yield 'vide' => [''];
        yield 'absente' => [null];
    }

    /** @dataProvider quantitesInvalides */
    public function test_ajout_refuse_une_quantite_invalide_sans_message(mixed $quantite): void
    {
        $demande = $this->demande();
        $donnees = ['demande_id' => $demande->id, 'article_id' => $this->article->id];
        if ($quantite !== null) {
            $donnees['quantite'] = $quantite;
        }

        $reponse = $this->actingAs($this->acteurs['proprietaire'])
            ->from($this->pageDemande($demande))
            ->post(route('article_demande.store'), $donnees);

        $reponse->assertRedirect($this->pageDemande($demande));
        $reponse->assertSessionHasErrors('quantite');
        $reponse->assertSessionMissing('error');
        $reponse->assertSessionMissing('success');
        $this->assertSame(0, ArticleDemande::count());
        $this->assertStockIntact();
    }

    /** @dataProvider quantitesInvalides */
    public function test_modification_refuse_une_quantite_invalide_sans_message(mixed $quantite): void
    {
        $demande = $this->demande();
        $ligne = $this->ligne($demande, 4);
        $donnees = ['article_id' => $this->article->id];
        if ($quantite !== null) {
            $donnees['quantite'] = $quantite;
        }

        $reponse = $this->actingAs($this->acteurs['proprietaire'])
            ->from($this->pageDemande($demande))
            ->put(route('article-demandes.update', $ligne->id), $donnees);

        $reponse->assertRedirect($this->pageDemande($demande));
        $reponse->assertSessionHasErrors('quantite');
        $reponse->assertSessionMissing('error');
        $reponse->assertSessionMissing('success');
        $this->assertSame(4, (int) $ligne->fresh()->quantite);
        $this->assertStockIntact();
    }

    public function test_le_controle_du_proprietaire_precede_la_validation_de_la_quantite(): void
    {
        $demande = $this->demande();

        $this->actingAs($this->acteurs['collegue'])
            ->post(route('article_demande.store'), [
                'demande_id' => $demande->id,
                'article_id' => $this->article->id,
                'quantite' => -3,
            ])
            ->assertForbidden();

        $this->assertSame(0, ArticleDemande::count());
    }

    // ------------------------------------------------------------------
    // Falsification, ressources introuvables, accès anonyme
    // ------------------------------------------------------------------

    public function test_ajout_sur_la_demande_d_autrui_par_identifiant_falsifie_est_refuse(): void
    {
        $demandeAutrui = $this->demande('En préparation', $this->acteurs['collegue']);

        $this->actingAs($this->acteurs['proprietaire'])
            ->post(route('article_demande.store'), [
                'demande_id' => $demandeAutrui->id,
                'article_id' => $this->article->id,
                'quantite' => 2,
            ])
            ->assertForbidden();

        $this->assertSame(0, ArticleDemande::count());
        $this->assertStockIntact();
    }

    public function test_ressources_introuvables_renvoient_404(): void
    {
        $proprietaire = $this->acteurs['proprietaire'];

        $this->actingAs($proprietaire)->post(route('article_demande.store'), [
            'demande_id' => 999999, 'article_id' => $this->article->id, 'quantite' => 2,
        ])->assertNotFound();

        $this->actingAs($proprietaire)->post(route('article_demande.store'), [
            'article_id' => $this->article->id, 'quantite' => 2,
        ])->assertNotFound();

        $this->actingAs($proprietaire)->post(route('article_demande.store'), [
            'demande_id' => $this->demande()->id, 'article_id' => 999999, 'quantite' => 2,
        ])->assertNotFound();

        $this->actingAs($proprietaire)->put(route('article-demandes.update', 999999), [
            'article_id' => $this->article->id, 'quantite' => 2,
        ])->assertNotFound();

        $this->actingAs($proprietaire)->delete(route('article_demande.destroy', 999999))->assertNotFound();

        $this->assertSame(0, ArticleDemande::count());
        $this->assertStockIntact();
    }

    public function test_acces_anonyme_redirige_vers_la_connexion(): void
    {
        $demande = $this->demande();
        $ligne = $this->ligne($demande);

        $this->post(route('article_demande.store'), ['demande_id' => $demande->id])->assertRedirect('/login');
        $this->put(route('article-demandes.update', $ligne->id), [])->assertRedirect('/login');
        $this->delete(route('article_demande.destroy', $ligne->id))->assertRedirect('/login');
        $this->assertSame(1, ArticleDemande::count());
    }

    // ------------------------------------------------------------------
    // Stock : aucun mouvement, plus de stock fantôme
    // ------------------------------------------------------------------

    public function test_la_suppression_ne_reincremente_plus_le_stock(): void
    {
        $demande = $this->demande();
        $ligne = $this->ligne($demande, 4);

        $this->actingAs($this->acteurs['proprietaire'])
            ->from($this->pageDemande($demande))
            ->delete(route('article_demande.destroy', $ligne->id))
            ->assertSessionHas('success', 'Article supprimé et stock mis à jour');

        $this->assertSame(0, ArticleDemande::count());
        $this->assertStockIntact();
    }

    public function test_dix_cycles_ajout_suppression_laissent_le_stock_inchange(): void
    {
        $demande = $this->demande();
        $proprietaire = $this->acteurs['proprietaire'];

        for ($i = 1; $i <= 10; $i++) {
            $this->actingAs($proprietaire)->from($this->pageDemande($demande))
                ->post(route('article_demande.store'), [
                    'demande_id' => $demande->id, 'article_id' => $this->article->id, 'quantite' => 5,
                ])->assertSessionHas('success');

            $ligne = ArticleDemande::where('demande_id', $demande->id)->sole();

            $this->actingAs($proprietaire)->from($this->pageDemande($demande))
                ->delete(route('article_demande.destroy', $ligne->id))->assertSessionHas('success');
        }

        $this->assertSame(0, ArticleDemande::count());
        $this->assertStockIntact();
    }

    // ------------------------------------------------------------------
    // Non-régression : contrôles et messages existants, au caractère près
    // ------------------------------------------------------------------

    public function test_ajout_valide_cree_la_ligne_sans_mouvement(): void
    {
        $demande = $this->demande();

        $this->actingAs($this->acteurs['proprietaire'])
            ->from($this->pageDemande($demande))
            ->post(route('article_demande.store'), [
                'demande_id' => $demande->id, 'article_id' => $this->article->id, 'quantite' => 3,
            ])
            ->assertRedirect($this->pageDemande($demande))
            ->assertSessionHas('success', 'Article ajouté et stock mis à jour');

        $ligne = ArticleDemande::sole();
        $this->assertSame(
            [$demande->id, $this->article->id, 3, null, 0],
            [(int) $ligne->demande_id, (int) $ligne->article_id, (int) $ligne->quantite, $ligne->quantite_accordee, (int) $ligne->archiver]
        );
        $this->assertStockIntact();
    }

    public function test_ajout_article_deja_present_message_existant(): void
    {
        $demande = $this->demande();
        $this->ligne($demande, 4);

        $this->actingAs($this->acteurs['proprietaire'])
            ->from($this->pageDemande($demande))
            ->post(route('article_demande.store'), [
                'demande_id' => $demande->id, 'article_id' => $this->article->id, 'quantite' => 2,
            ])
            ->assertRedirect($this->pageDemande($demande))
            ->assertSessionHas('error', 'Cet article est déjà présent dans cette demande.')
            ->assertSessionHasInput('article_id', $this->article->id);

        $this->assertSame(1, ArticleDemande::count());
        $this->assertStockIntact();
    }

    public function test_ajout_controle_de_stock_existant_inchange(): void
    {
        $demande = $this->demande();
        $proprietaire = $this->acteurs['proprietaire'];

        $this->actingAs($proprietaire)->from($this->pageDemande($demande))
            ->post(route('article_demande.store'), [
                'demande_id' => $demande->id, 'article_id' => $this->article->id, 'quantite' => self::STOCK + 1,
            ])
            ->assertSessionHas('error', 'Stock insuffisant');
        $this->assertSame(0, ArticleDemande::count());

        // Limite incluse : quantité égale au stock acceptée.
        $this->actingAs($proprietaire)->from($this->pageDemande($demande))
            ->post(route('article_demande.store'), [
                'demande_id' => $demande->id, 'article_id' => $this->article->id, 'quantite' => self::STOCK,
            ])
            ->assertSessionHas('success', 'Article ajouté et stock mis à jour');
        $this->assertStockIntact();
    }

    public function test_aucun_controle_de_disponibilite_nouveau_ni_reservation_a_l_ajout(): void
    {
        // Deux autres demandes visent déjà la totalité du stock : l'ajout reste
        // accepté, car la règle existante compare au seul stock disponible.
        $this->ligne($this->demande('En préparation', $this->acteurs['collegue']), self::STOCK);
        $this->ligne($this->demande('En attente gestionnaire', $this->acteurs['collegue']), self::STOCK);
        $demande = $this->demande();

        $this->actingAs($this->acteurs['proprietaire'])->from($this->pageDemande($demande))
            ->post(route('article_demande.store'), [
                'demande_id' => $demande->id, 'article_id' => $this->article->id, 'quantite' => self::STOCK,
            ])
            ->assertSessionHas('success', 'Article ajouté et stock mis à jour');

        $this->assertSame(3, ArticleDemande::where('article_id', $this->article->id)->count());
        $this->assertStockIntact();
    }

    public function test_modification_article_deja_present_message_existant(): void
    {
        $demande = $this->demande();
        $ligne = $this->ligne($demande, 4);
        ArticleDemande::factory()->create([
            'demande_id' => $demande->id, 'article_id' => $this->autreArticle->id, 'quantite' => 1,
        ]);

        $this->actingAs($this->acteurs['proprietaire'])->from($this->pageDemande($demande))
            ->put(route('article-demandes.update', $ligne->id), [
                'article_id' => $this->autreArticle->id, 'quantite' => 2,
            ])
            ->assertSessionHas('error', 'Cet article existe déjà dans la demande.');

        $this->assertSame($this->article->id, (int) $ligne->fresh()->article_id);
        $this->assertStockIntact();
    }

    public function test_modification_controle_de_stock_existant_inchange(): void
    {
        $demande = $this->demande();
        $ligne = $this->ligne($demande, 4);

        $this->actingAs($this->acteurs['proprietaire'])->from($this->pageDemande($demande))
            ->put(route('article-demandes.update', $ligne->id), [
                'article_id' => $this->article->id, 'quantite' => self::STOCK + 1,
            ])
            ->assertSessionHas('error', 'Stock insuffisant pour cet article.');

        $this->assertSame(4, (int) $ligne->fresh()->quantite);
        $this->assertStockIntact();
    }

    public function test_modification_article_inexistant_refusee_sans_message(): void
    {
        $demande = $this->demande();
        $ligne = $this->ligne($demande, 4);

        $this->actingAs($this->acteurs['proprietaire'])->from($this->pageDemande($demande))
            ->put(route('article-demandes.update', $ligne->id), ['article_id' => 999999, 'quantite' => 2])
            ->assertRedirect($this->pageDemande($demande))
            ->assertSessionHasErrors('article_id')
            ->assertSessionMissing('error');

        $this->assertSame($this->article->id, (int) $ligne->fresh()->article_id);
    }
}
