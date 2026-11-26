<?php

namespace Tests\Feature\L14;

use App\Models\Article;
use App\Models\ArticleDemande;
use App\Models\Demande;
use App\Models\MouvementStock;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * L1-4b, workflow « livraison d'une demande » (DemandeController::livrer).
 *
 * Règles vérifiées :
 * - le système exécute exactement les quantités accordées par le gestionnaire
 *   (à défaut, la quantité demandée, règle existante), sans jamais les modifier ;
 * - livraison intégrale ou rien (D-7) : tout est contrôlé avant la première
 *   écriture, et toute erreur annule l'ensemble ;
 * - quantité accordée 0 : ligne ignorée, aucune sortie (D-2) ; toutes les
 *   lignes à 0 : livrée sans aucun mouvement ; aucune ligne : refus
 *   « Cette demande ne contient aucun article. » ;
 * - quantité négative : refus de toute la livraison, sans message (P-2) ;
 * - pas de rejeu : refus si la demande est livrée ou si une sortie de cette
 *   demande existe déjà ;
 * - format historique conservé : une sortie par ligne livrée.
 *
 * DatabaseMigrations : les déclencheurs SQL de panne exigent des données
 * réellement validées. Base de test isolée (garde-fou du lot 0).
 */
class LivraisonDemandeTest extends TestCase
{
    use DatabaseMigrations;

    private const MSG_NON_VALIDEE = 'La demande n’a pas encore été validée complètement.';

    private const MSG_DEJA_LIVREE = 'Demande déjà livrée';

    private const MSG_AUCUN_ARTICLE = 'Cette demande ne contient aucun article.';

    private const PAGE = '/demandes-gestionnaire';

    private User $gestionnaire;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->gestionnaire = User::factory()->create();
        $this->gestionnaire->assignRole('Gestionnaire');
    }

    protected function tearDown(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS l14_panne_sortie');
        DB::unprepared('DROP TRIGGER IF EXISTS l14_panne_decrement');
        parent::tearDown();
    }

    private function article(string $code, int $stock): Article
    {
        return Article::factory()->create(['code' => $code, 'libelle' => "Article $code", 'stock_disponible' => $stock]);
    }

    /**
     * Demande prête à être livrée (toutes validations faites).
     *
     * @param  list<array{0: Article, 1: int, 2: ?int}>  $lignes  [article, quantité demandée, quantité accordée]
     */
    private function demandePrete(array $lignes, array $attributs = []): Demande
    {
        $demande = Demande::factory()->create(array_merge([
            'user_id' => User::factory()->create()->id,
            'statut' => 'En attente livraison',
            'validation_superieur' => 1,
            'validation_gestionnaire' => 1,
            'validation_finale' => 1,
            'livraison' => 0,
            'commentaire_superieur' => 'avis supérieur',
            'commentaire_gestionnaire' => 'avis gestionnaire',
            'commentaire_validation_finale' => 'avis final',
        ], $attributs));

        foreach ($lignes as [$article, $quantite, $accordee]) {
            ArticleDemande::factory()->create([
                'demande_id' => $demande->id,
                'article_id' => $article->id,
                'quantite' => $quantite,
                'quantite_accordee' => $accordee,
            ]);
        }

        return $demande;
    }

    private function livrer(Demande $demande)
    {
        return $this->actingAs($this->gestionnaire)
            ->from(self::PAGE)
            ->post(route('demandes.livrer', $demande->id));
    }

    /** Photographie de tout ce qu'une livraison peut écrire. */
    private function photo(Demande $demande): array
    {
        return [
            'articles' => Article::orderBy('id')->get(['id', 'stock_disponible', 'updated_at'])->toArray(),
            'mouvements' => MouvementStock::orderBy('id')->get()->toArray(),
            'demande' => Demande::whereKey($demande->id)->first([
                'livraison', 'date_livraison', 'statut', 'commentaire_superieur',
                'commentaire_gestionnaire', 'commentaire_validation_finale', 'updated_at',
            ])->toArray(),
        ];
    }

    private function sorties(Demande $demande): array
    {
        return MouvementStock::where('type', 'SORTIE')->where('motif', 'Livraison demande N° '.$demande->id)->orderBy('id')
            ->get(['article_id', 'quantite'])
            ->map(fn ($m) => [(int) $m->article_id, (int) $m->quantite])->all();
    }

    private function stock(Article $article): int
    {
        return (int) $article->fresh()->stock_disponible;
    }

    private function assertRefusSansEcriture($reponse, array $avant, Demande $demande, ?string $message): void
    {
        $reponse->assertRedirect(self::PAGE);
        if ($message === null) {
            $reponse->assertSessionMissing('error');
        } else {
            $reponse->assertSessionHas('error', $message);
        }
        $reponse->assertSessionMissing('success');
        $this->assertSame($avant, $this->photo($demande), 'Aucune écriture ne doit subsister.');
    }

    // ------------------------------------------------------------------
    // Cas nominaux
    // ------------------------------------------------------------------

    public function test_livraison_nominale_au_format_historique(): void
    {
        [$x, $y, $z] = [$this->article('X', 10), $this->article('Y', 10), $this->article('Z', 10)];
        // X : accordé 2 sur 3 demandés ; Y : pas d'accord saisi, la quantité demandée (4) s'applique ; Z : accordé 5.
        $demande = $this->demandePrete([[$x, 3, 2], [$y, 4, null], [$z, 5, 5]]);

        $this->livrer($demande)
            ->assertRedirect(self::PAGE)
            ->assertSessionHas('success', 'Demande livrée')
            ->assertSessionMissing('error');

        $this->assertSame([8, 6, 5], [$this->stock($x), $this->stock($y), $this->stock($z)]);

        $sorties = MouvementStock::orderBy('id')->get();
        $this->assertCount(3, $sorties);
        foreach ([[$x, 2], [$y, 4], [$z, 5]] as $i => [$article, $quantite]) {
            $this->assertSame([
                'article_id' => $article->id,
                'user_id' => $this->gestionnaire->id,
                'type' => 'SORTIE',
                'quantite' => $quantite,
                'motif' => 'Livraison demande N° '.$demande->id,
                'date_mouvement' => now()->toDateString(),
                'prix_unitaire' => null,
            ], [
                'article_id' => (int) $sorties[$i]->article_id,
                'user_id' => (int) $sorties[$i]->user_id,
                'type' => $sorties[$i]->type,
                'quantite' => (int) $sorties[$i]->quantite,
                'motif' => $sorties[$i]->motif,
                'date_mouvement' => substr((string) $sorties[$i]->date_mouvement, 0, 10),
                'prix_unitaire' => $sorties[$i]->prix_unitaire,
            ]);
        }

        $demande->refresh();
        $this->assertTrue((bool) $demande->livraison);
        $this->assertSame(now()->toDateString(), substr((string) $demande->date_livraison, 0, 10));
        $this->assertSame('En attente réception demandeur', $demande->statut);
        $this->assertNull($demande->commentaire_superieur);
        $this->assertNull($demande->commentaire_gestionnaire);
        $this->assertNull($demande->commentaire_validation_finale);

        // Les quantités accordées par le gestionnaire ne sont jamais modifiées.
        $this->assertSame([2, null, 5], ArticleDemande::orderBy('id')->pluck('quantite_accordee')->map(fn ($q) => $q === null ? null : (int) $q)->all());
    }

    public function test_deux_lignes_du_meme_article_besoin_total_et_une_sortie_par_ligne(): void
    {
        $x = $this->article('X', 9);
        $demande = $this->demandePrete([[$x, 4, 4], [$x, 5, 5]]);

        $this->livrer($demande)->assertSessionHas('success', 'Demande livrée');

        $this->assertSame(0, $this->stock($x));
        $this->assertSame([[$x->id, 4], [$x->id, 5]], $this->sorties($demande), 'Une sortie par ligne, dans l\'ordre des lignes.');
    }

    public function test_ligne_accordee_a_zero_ignoree_sans_sortie(): void
    {
        [$x, $y] = [$this->article('X', 10), $this->article('Y', 10)];
        $demande = $this->demandePrete([[$x, 3, 0], [$y, 2, 2]]);

        $this->livrer($demande)->assertSessionHas('success', 'Demande livrée');

        $this->assertSame([10, 8], [$this->stock($x), $this->stock($y)]);
        $this->assertSame([[$y->id, 2]], $this->sorties($demande));
        $this->assertSame(0, MouvementStock::where('quantite', 0)->count(), 'Aucune sortie de 0.');
    }

    // ------------------------------------------------------------------
    // Refus : quantités
    // ------------------------------------------------------------------

    public function test_toutes_les_quantites_accordees_nulles_livree_sans_mouvement(): void
    {
        [$x, $y] = [$this->article('X', 10), $this->article('Y', 10)];
        $demande = $this->demandePrete([[$x, 3, 0], [$y, 2, 0]]);
        $articlesAvant = Article::orderBy('id')->get(['id', 'stock_disponible', 'updated_at'])->toArray();

        $this->livrer($demande)
            ->assertRedirect(self::PAGE)
            ->assertSessionHas('success', 'Demande livrée')
            ->assertSessionMissing('error');

        // Aucun stock modifié, aucun mouvement créé.
        $this->assertSame($articlesAvant, Article::orderBy('id')->get(['id', 'stock_disponible', 'updated_at'])->toArray());
        $this->assertSame(0, MouvementStock::count());

        // Champs de la demande mis à jour comme pour toute livraison réussie.
        $demande->refresh();
        $this->assertTrue((bool) $demande->livraison);
        $this->assertSame(now()->toDateString(), substr((string) $demande->date_livraison, 0, 10));
        $this->assertSame('En attente réception demandeur', $demande->statut);
        $this->assertNull($demande->commentaire_superieur);
        $this->assertNull($demande->commentaire_gestionnaire);
        $this->assertNull($demande->commentaire_validation_finale);

        // Une seconde tentative est refusée.
        $avant = $this->photo($demande);
        $this->assertRefusSansEcriture($this->livrer($demande), $avant, $demande, self::MSG_DEJA_LIVREE);
    }

    public function test_quantite_accordee_negative_refuse_toute_la_livraison_sans_message(): void
    {
        [$x, $y] = [$this->article('X', 10), $this->article('Y', 10)];
        $demande = $this->demandePrete([[$y, 2, 2], [$x, 5, -5]]);
        $avant = $this->photo($demande);

        $this->assertRefusSansEcriture($this->livrer($demande), $avant, $demande, null);
        $this->assertSame(10, $this->stock($x), 'Le stock ne doit jamais augmenter par une livraison.');
    }

    public function test_quantite_demandee_negative_sans_accord_refuse_toute_la_livraison(): void
    {
        [$x, $y] = [$this->article('X', 10), $this->article('Y', 10)];
        $demande = $this->demandePrete([[$y, 2, 2], [$x, -3, null]]);
        $avant = $this->photo($demande);

        $this->assertRefusSansEcriture($this->livrer($demande), $avant, $demande, null);
    }

    // ------------------------------------------------------------------
    // Refus : stock
    // ------------------------------------------------------------------

    public function test_stock_insuffisant_sur_un_seul_article_refus_global(): void
    {
        // Exemple des instructions : clavier insuffisant (6 accordés, 4 en stock).
        [$papier, $encre, $clavier] = [$this->article('PAP', 15), $this->article('ENC', 8), $this->article('CLA', 4)];
        $demande = $this->demandePrete([[$papier, 10, 10], [$encre, 5, 5], [$clavier, 6, 6]]);
        $avant = $this->photo($demande);

        $this->assertRefusSansEcriture($this->livrer($demande), $avant, $demande, 'Stock insuffisant pour Article CLA');
        $this->assertSame(6, (int) ArticleDemande::where('article_id', $clavier->id)->value('quantite_accordee'), 'La quantité accordée n\'est pas réduite.');
    }

    public function test_stock_devenu_insuffisant_depuis_l_accord_refus_sans_livraison_partielle(): void
    {
        // Exemple des instructions : 10 accordées, le stock est tombé à 7 depuis l'accord.
        $papier = $this->article('PAP', 7);
        $demande = $this->demandePrete([[$papier, 10, 10]]);
        $avant = $this->photo($demande);

        $this->assertRefusSansEcriture($this->livrer($demande), $avant, $demande, 'Stock insuffisant pour Article PAP');
    }

    public function test_deux_lignes_du_meme_article_total_insuffisant_refus_global(): void
    {
        $x = $this->article('X', 8);
        $demande = $this->demandePrete([[$x, 4, 4], [$x, 5, 5]]);
        $avant = $this->photo($demande);

        $this->assertRefusSansEcriture($this->livrer($demande), $avant, $demande, 'Stock insuffisant pour Article X');
    }

    public function test_relance_apres_reapprovisionnement_une_seule_sortie_par_ligne(): void
    {
        [$x, $y] = [$this->article('X', 10), $this->article('Y', 1)];
        $demande = $this->demandePrete([[$x, 3, 3], [$y, 5, 5]]);

        $this->livrer($demande)->assertSessionHas('error', 'Stock insuffisant pour Article Y');
        $this->assertSame([10, 1], [$this->stock($x), $this->stock($y)]);

        $y->update(['stock_disponible' => 10]);
        $this->livrer($demande)->assertSessionHas('success', 'Demande livrée');

        $this->assertSame([7, 5], [$this->stock($x), $this->stock($y)]);
        $this->assertSame([[$x->id, 3], [$y->id, 5]], $this->sorties($demande));
    }

    // ------------------------------------------------------------------
    // Refus : demande vide, préconditions, rejeu
    // ------------------------------------------------------------------

    public function test_demande_sans_ligne_refus_avec_le_message_valide(): void
    {
        $demande = $this->demandePrete([]);
        $avant = $this->photo($demande);

        $this->assertRefusSansEcriture($this->livrer($demande), $avant, $demande, self::MSG_AUCUN_ARTICLE);
        $this->assertFalse((bool) $demande->fresh()->livraison);
    }

    public function test_preconditions_existantes_inchangees(): void
    {
        $x = $this->article('X', 10);

        $sansGestionnaire = $this->demandePrete([[$x, 2, 2]], ['validation_gestionnaire' => 0]);
        $avant = $this->photo($sansGestionnaire);
        $this->assertRefusSansEcriture($this->livrer($sansGestionnaire), $avant, $sansGestionnaire, self::MSG_NON_VALIDEE);

        $sansFinale = $this->demandePrete([[$x, 2, 2]], ['validation_finale' => 0]);
        $avant = $this->photo($sansFinale);
        $this->assertRefusSansEcriture($this->livrer($sansFinale), $avant, $sansFinale, self::MSG_NON_VALIDEE);

        $this->actingAs($this->gestionnaire)->post(route('demandes.livrer', 999999))->assertNotFound();
        $this->assertSame(0, MouvementStock::count());
    }

    public function test_demande_deja_livree_refus(): void
    {
        $x = $this->article('X', 10);
        $demande = $this->demandePrete([[$x, 2, 2]]);
        $this->livrer($demande)->assertSessionHas('success', 'Demande livrée');
        $avant = $this->photo($demande);

        $this->assertRefusSansEcriture($this->livrer($demande), $avant, $demande, self::MSG_DEJA_LIVREE);
    }

    public function test_indicateur_livraison_remis_a_zero_avec_sorties_existantes_refus(): void
    {
        $x = $this->article('X', 10);
        $demande = $this->demandePrete([[$x, 2, 2]]);
        $this->livrer($demande)->assertSessionHas('success', 'Demande livrée');
        DB::table('demandes')->where('id', $demande->id)->update(['livraison' => 0]);
        $avant = $this->photo($demande);

        $this->assertRefusSansEcriture($this->livrer($demande), $avant, $demande, self::MSG_DEJA_LIVREE);
        $this->assertSame(8, $this->stock($x));
    }

    public function test_etat_partiel_laisse_par_l_ancien_code_refus(): void
    {
        [$x, $y] = [$this->article('X', 7), $this->article('Y', 1)];
        $demande = $this->demandePrete([[$x, 3, 3], [$y, 5, 5]]);
        // Ancien code : X déjà sorti (10 -> 7), Y refusé, demande non livrée.
        MouvementStock::create(['article_id' => $x->id, 'user_id' => $this->gestionnaire->id, 'type' => 'SORTIE',
            'quantite' => 3, 'motif' => 'Livraison demande N° '.$demande->id, 'date_mouvement' => now()]);
        $avant = $this->photo($demande);

        $this->assertRefusSansEcriture($this->livrer($demande), $avant, $demande, self::MSG_DEJA_LIVREE);
    }

    public function test_pas_de_faux_positif_sur_le_motif_d_une_autre_demande_ou_d_un_autre_type(): void
    {
        $x = $this->article('X', 10);
        $demande = $this->demandePrete([[$x, 2, 2]], ['id' => 1]);
        // « N° 12 » commence comme « N° 1 » ; un REBUT peut porter un motif libre.
        MouvementStock::create(['article_id' => $x->id, 'type' => 'SORTIE', 'quantite' => 1, 'motif' => 'Livraison demande N° 12', 'date_mouvement' => now()]);
        MouvementStock::create(['article_id' => $x->id, 'type' => 'REBUT', 'quantite' => 1, 'motif' => 'Livraison demande N° 1', 'date_mouvement' => now()]);

        $this->livrer($demande)->assertSessionHas('success', 'Demande livrée');
        $this->assertSame([[$x->id, 2]], $this->sorties($demande));
    }

    // ------------------------------------------------------------------
    // Interruptions : aucune écriture partielle, puis relance
    // ------------------------------------------------------------------

    public function test_erreur_applicative_a_la_deuxieme_sortie_annule_tout_puis_relance(): void
    {
        [$x, $y] = [$this->article('X', 10), $this->article('Y', 10)];
        $demande = $this->demandePrete([[$x, 2, 2], [$y, 3, 3]]);
        $avant = $this->photo($demande);

        $sorties = 0;
        $panne = true;
        MouvementStock::creating(function () use (&$sorties, &$panne) {
            if ($panne && ++$sorties === 2) {
                throw new \RuntimeException('panne simulée à la deuxième sortie');
            }
        });

        $this->livrer($demande)->assertStatus(500);
        $this->assertSame($avant, $this->photo($demande), 'Le premier article déjà traité doit être annulé.');

        $panne = false;
        $this->livrer($demande)->assertSessionHas('success', 'Demande livrée');
        $this->assertSame([8, 7], [$this->stock($x), $this->stock($y)]);
        $this->assertSame([[$x->id, 2], [$y->id, 3]], $this->sorties($demande));
    }

    public function test_erreur_sql_a_la_deuxieme_sortie_par_declencheur_annule_tout_puis_relance(): void
    {
        [$x, $y] = [$this->article('X', 10), $this->article('Y', 10)];
        $demande = $this->demandePrete([[$x, 2, 2], [$y, 3, 3]]);
        $avant = $this->photo($demande);

        DB::unprepared("CREATE TRIGGER l14_panne_sortie BEFORE INSERT ON mouvement_stocks FOR EACH ROW
            BEGIN IF NEW.article_id = {$y->id} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'panne SQL simulee'; END IF; END");

        $this->livrer($demande)->assertStatus(500);
        $this->assertSame($avant, $this->photo($demande));

        DB::unprepared('DROP TRIGGER l14_panne_sortie');
        $this->livrer($demande)->assertSessionHas('success', 'Demande livrée');
        $this->assertSame([[$x->id, 2], [$y->id, 3]], $this->sorties($demande));
    }

    public function test_erreur_sql_pendant_une_decrementation_annule_tout_puis_relance(): void
    {
        [$x, $y] = [$this->article('X', 10), $this->article('Y', 10)];
        $demande = $this->demandePrete([[$x, 2, 2], [$y, 3, 3]]);
        $avant = $this->photo($demande);

        DB::unprepared("CREATE TRIGGER l14_panne_decrement BEFORE UPDATE ON articles FOR EACH ROW
            BEGIN IF NEW.id = {$y->id} AND NEW.stock_disponible < OLD.stock_disponible THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'panne SQL simulee'; END IF; END");

        $this->livrer($demande)->assertStatus(500);
        $this->assertSame($avant, $this->photo($demande), 'La sortie et le décrément de X doivent être annulés.');

        DB::unprepared('DROP TRIGGER l14_panne_decrement');
        $this->livrer($demande)->assertSessionHas('success', 'Demande livrée');
        $this->assertSame([8, 7], [$this->stock($x), $this->stock($y)]);
    }
}
