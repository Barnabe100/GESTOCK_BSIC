<?php

namespace Tests\Feature\Finalisation;

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
use Tests\TestCase;

/**
 * Décision 3.6 (séparation soumission / validation) et sécurisation des
 * commandes : lignes figées après validation, réception atomique et
 * protégée contre les doublons. Requêtes HTTP directes.
 */
class CommandesTest extends TestCase
{
    use RefreshDatabase;

    private User $gestionnaire;

    private User $validateur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->gestionnaire = $this->compte(['Demandeur', 'Gestionnaire']);
        $this->validateur = $this->compte(['Demandeur', 'ValidateurCommande']);
    }

    private function compte(array $roles): User
    {
        $u = User::factory()->create();
        $u->assignRole($roles);

        return $u;
    }

    /** @param list<array{0: Article, 1: int}> $lignes */
    private function commande(array $lignes, array $attr = []): Commande
    {
        $montant = array_sum(array_map(fn ($l) => $l[1] * 100, $lignes));
        $c = Commande::forceCreate(array_merge(['numero_bon_commande' => 'BC-'.Str::random(6), 'date' => '2026-11-20', 'montant' => $montant,
            'fournisseur_id' => Fournisseur::factory()->create()->id, 'statut' => 'En préparation', 'validation' => 0, 'livraison' => 0, 'archiver' => 0], $attr));
        foreach ($lignes as [$article, $quantite]) {
            ArticleCommande::create(['commande_id' => $c->id, 'article_id' => $article->id, 'quantite' => $quantite, 'prix_unitaire' => 100,
                'quantite_livree' => 0, 'quantite_restante' => $quantite]);
        }

        return $c->fresh();
    }

    private function article(int $stock = 0): Article
    {
        return Article::factory()->create(['stock_disponible' => $stock, 'stock_initial' => 0]);
    }

    private function receptionner(User $par, Commande $c, array $quantites, ?string $jeton = null, string $date = '2026-11-25')
    {
        return $this->actingAs($par)->from('/commandes')->put(route('commandes.livrer', $c->id),
            ['date_livraison' => $date, 'quantites' => $quantites, 'jeton_reception' => $jeton ?? (string) Str::uuid()]);
    }

    // ---------------- Séparation soumission / validation ----------------

    public function test_soumission_reservee_au_gestionnaire(): void
    {
        foreach ([$this->validateur, $this->compte(['SuperAdmin']), $this->compte(['Demandeur'])] as $autre) {
            $c = $this->commande([[$this->article(), 2]]);
            $this->actingAs($autre)->put(route('commandes.soumettre', $c->id))->assertForbidden();
            $this->assertSame('En préparation', $c->fresh()->statut);
        }

        $c = $this->commande([[$this->article(), 2]]);
        $this->actingAs($this->gestionnaire)->put(route('commandes.soumettre', $c->id))->assertRedirect(route('commandes.index'));
        $this->assertSame('Prête pour validation', $c->fresh()->statut);
        $this->assertSame($this->gestionnaire->id, (int) $c->fresh()->soumise_par);
    }

    public function test_validation_par_une_autre_personne_jamais_par_celle_qui_a_soumis(): void
    {
        // Un Gestionnaire qui est aussi ValidateurCommande soumet, puis tente de valider.
        $double = $this->compte(['Gestionnaire', 'ValidateurCommande']);
        $c = $this->commande([[$this->article(), 2]]);
        $this->actingAs($double)->put(route('commandes.soumettre', $c->id));
        $this->actingAs($double)->post(route('commandes.valider', $c->id))->assertForbidden();
        $this->assertFalse((bool) $c->fresh()->validation);

        $this->actingAs($this->validateur)->from('/commandes')->post(route('commandes.valider', $c->id))
            ->assertSessionHas('success', 'Commande validée');
        $this->assertTrue((bool) $c->fresh()->validation);
        $this->assertSame($this->validateur->id, (int) $c->fresh()->validee_par);
    }

    public function test_le_validateur_doit_avoir_le_role_et_la_permission(): void
    {
        $c = $this->commande([[$this->article(), 2]], ['statut' => 'Prête pour validation', 'soumise_par' => $this->gestionnaire->id]);

        // Rôle sans permission (permission retirée) : refus.
        $sansPermission = $this->compte(['ValidateurCommande']);
        \Spatie\Permission\Models\Role::findByName('ValidateurCommande')->revokePermissionTo('valider-commande');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($sansPermission->fresh())->post(route('commandes.valider', $c->id))->assertForbidden();

        // Permission sans le rôle (le Superieur l'avait reçue par erreur, N-13) : refus.
        $superieur = $this->compte(['Superieur']);
        $superieur->givePermissionTo('valider-commande');
        $this->actingAs($superieur)->post(route('commandes.valider', $c->id))->assertForbidden();
        $this->assertFalse((bool) $c->fresh()->validation);

        // SuperAdmin (rôle et toutes les permissions), autre que le soumetteur : accepté.
        $this->actingAs($this->compte(['SuperAdmin']))->from('/commandes')->post(route('commandes.valider', $c->id))
            ->assertSessionHas('success', 'Commande validée');
    }

    // ---------------- Lignes figées après validation ----------------

    public function test_quantites_non_modifiables_apres_validation_et_lignes_controlees(): void
    {
        $c = $this->commande([[$this->article(), 4]], ['statut' => 'Prête pour validation', 'validation' => 1]);
        $ligne = $c->articles()->first();

        $this->actingAs($this->gestionnaire)->from('/commandes/'.$c->id)->put(route('article-commandes.update', $ligne->id), ['quantite' => 1, 'prix_unitaire' => 100])
            ->assertSessionHas('error', 'Commande déjà validée');
        $this->assertSame(4, (int) $ligne->fresh()->quantite);

        // Ligne à quantité négative refusée à l'ajout.
        $enPreparation = $this->commande([]);
        $enPreparation->forceFill(['montant' => 10000])->save();
        $this->actingAs($this->gestionnaire)->from('/commandes/'.$enPreparation->id)->post(route('article_commande.store'),
            ['commande_id' => $enPreparation->id, 'article_id' => $this->article()->id, 'quantite' => -5, 'prix_unitaire' => 100])
            ->assertSessionHasErrors('quantite');
        $this->assertSame(0, ArticleCommande::where('commande_id', $enPreparation->id)->count());
    }

    // ---------------- Réception ----------------

    public function test_reception_atomique_rien_n_est_ecrit_si_une_ligne_est_invalide(): void
    {
        $a1 = $this->article(5);
        $a2 = $this->article(5);
        $c = $this->commande([[$a1, 4], [$a2, 3]], ['statut' => 'Prête pour validation', 'validation' => 1]);
        [$l1, $l2] = $c->articles()->orderBy('id')->get()->all();

        // Ligne 1 valide, ligne 2 au-delà du reste à livrer : refus global.
        $this->receptionner($this->validateur, $c, [$l1->id => 2, $l2->id => 9])
            ->assertSessionHas('error', 'Quantité reçue supérieure à la quantité restante pour '.$a2->libelle);

        $this->assertSame(0, (int) $l1->fresh()->quantite_livree);
        $this->assertSame(5, (int) $a1->fresh()->stock_disponible);
        $this->assertSame(0, MouvementStock::count());
        $this->assertSame(0, ReceptionCommande::count());

        // Quantité négative ou date absente : refus, rien d'écrit.
        $this->receptionner($this->validateur, $c, [$l1->id => -1])->assertSessionHasErrors('quantites.'.$l1->id);
        $this->actingAs($this->validateur)->put(route('commandes.livrer', $c->id), ['quantites' => [$l1->id => 1], 'jeton_reception' => (string) Str::uuid()])
            ->assertSessionHasErrors('date_livraison');
        // Aucune quantité saisie.
        $this->receptionner($this->validateur, $c, [$l1->id => 0])->assertSessionHas('error', 'Aucune quantité reçue n’a été saisie.');
        // Ligne d'une autre commande.
        $autre = $this->commande([[$this->article(), 1]], ['validation' => 1]);
        $this->receptionner($this->validateur, $c, [$autre->articles()->first()->id => 1])->assertForbidden();
        $this->assertSame(0, MouvementStock::count());

        // Réception valide : tout est écrit.
        $this->receptionner($this->validateur, $c, [$l1->id => 4, $l2->id => 3])->assertSessionHas('success', 'Réception enregistrée');
        $this->assertSame(9, (int) $a1->fresh()->stock_disponible);
        $this->assertSame(2, MouvementStock::where('type', 'ENTREE')->count());
        $this->assertTrue((bool) $c->fresh()->livraison);
    }

    public function test_reception_rejouee_jamais_comptee_deux_fois(): void
    {
        $a = $this->article(0);
        $c = $this->commande([[$a, 10]], ['statut' => 'Prête pour validation', 'validation' => 1]);
        $ligne = $c->articles()->first();
        $jeton = (string) Str::uuid();

        $this->receptionner($this->validateur, $c, [$ligne->id => 3], $jeton)->assertSessionHas('success', 'Réception enregistrée');
        $this->receptionner($this->validateur, $c, [$ligne->id => 3], $jeton)->assertSessionHas('error', 'Cette réception a déjà été enregistrée.');

        $this->assertSame(3, (int) $ligne->fresh()->quantite_livree);
        $this->assertSame(1, MouvementStock::where('type', 'ENTREE')->count());
        $this->assertSame(1, ReceptionCommande::count());

        // Sans jeton : refus (le formulaire en fournit toujours un).
        $this->actingAs($this->validateur)->put(route('commandes.livrer', $c->id), ['date_livraison' => '2026-11-25', 'quantites' => [$ligne->id => 1]])
            ->assertSessionHasErrors('jeton_reception');
        $this->assertSame(1, MouvementStock::count());
    }

    public function test_reception_reservee_et_commande_non_validee_refusee(): void
    {
        $c = $this->commande([[$this->article(), 2]], ['statut' => 'Prête pour validation']);
        $ligne = $c->articles()->first();

        $this->receptionner($this->compte(['Demandeur']), $c, [$ligne->id => 1])->assertForbidden();
        $this->receptionner($this->validateur, $c, [$ligne->id => 1])->assertSessionHas('error', 'Validez d’abord la commande');
        $this->assertSame(0, MouvementStock::count());
    }
}
