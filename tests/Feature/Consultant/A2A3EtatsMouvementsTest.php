<?php

namespace Tests\Feature\Consultant;

use App\Models\Agence;
use App\Models\Article;
use App\Models\ArticleDemande;
use App\Models\Demande;
use App\Models\Departement;
use App\Models\MouvementStock;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LC4 (A2) : consultation de tous les rapports d'états par le Consultant,
 * lecture seule. LC5 (A3) : consultation de tous les mouvements, sans
 * aucune opération de stock.
 */
class A2A3EtatsMouvementsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Anomalie N-06, hors périmètre de cette mission : l'état « demandes
     * validées » répond par une erreur 500 pour TOUS les rôles autorisés.
     */
    private const ROUTES_N06 = ['etats.demandes.validees.excel', 'etats.demandes.validees.pdf'];

    private Agence $agence;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->agence = Agence::factory()->create();
        $dep = Departement::factory()->create(['agence_id' => $this->agence->id]);
        $srv = Service::factory()->create(['departement_id' => $dep->id]);
        $demandeur = User::factory()->create(['agence_id' => $this->agence->id, 'departement_id' => $dep->id, 'service_id' => $srv->id]);
        $this->article = Article::factory()->create(['stock_disponible' => 20]);
        $d = Demande::factory()->create(['user_id' => $demandeur->id, 'statut' => 'Livrée - Demande clôturée', 'date' => '2026-06-01',
            'validation_superieur' => 1, 'validation_gestionnaire' => 1, 'validation_finale' => 1, 'livraison' => 1]);
        ArticleDemande::factory()->create(['demande_id' => $d->id, 'article_id' => $this->article->id, 'quantite' => 2, 'quantite_accordee' => 2]);
        MouvementStock::create(['article_id' => $this->article->id, 'type' => 'ENTREE', 'quantite' => 10, 'prix_unitaire' => 100, 'motif' => 'Réception commande N° BC-1', 'date_mouvement' => '2026-05-01']);
        MouvementStock::create(['article_id' => $this->article->id, 'type' => 'SORTIE', 'quantite' => 2, 'motif' => 'Livraison demande N° '.$d->id, 'date_mouvement' => '2026-06-02']);
    }

    private function compte(array $roles): User
    {
        $u = User::factory()->create(['agence_id' => $this->agence->id]);
        $u->assignRole($roles);

        return $u;
    }

    /** Les 22 routes du menu États, avec leur méthode. */
    private function routesEtats(): array
    {
        $routes = [];
        foreach (app('router')->getRoutes() as $r) {
            if (str_starts_with($r->uri(), 'etats')) {
                $routes[] = [in_array('POST', $r->methods(), true) ? 'post' : 'get', $r->getName(), $r->uri()];
            }
        }

        return $routes;
    }

    private function appeler(User $u, string $methode, string $uri)
    {
        $params = ['date_debut' => '2026-01-01', 'date_fin' => '2026-12-31', 'agence_id' => $this->agence->id];

        return $methode === 'post'
            ? $this->actingAs($u)->post('/'.$uri, $params)
            : $this->actingAs($u)->get('/'.$uri.'?'.http_build_query($params));
    }

    // ---------------- A2 : états ----------------

    public function test_consultant_consulte_les_22_rapports_d_etats(): void
    {
        $routes = $this->routesEtats();
        $this->assertCount(22, $routes);
        $consultant = $this->compte(['Consultant']);
        $gestionnaire = $this->compte(['Gestionnaire']);

        foreach ($routes as [$methode, $nom, $uri]) {
            $code = $this->appeler($consultant, $methode, $uri)->getStatusCode();

            if (in_array($nom, self::ROUTES_N06, true)) {
                // Même résultat que pour le Gestionnaire : anomalie N-06 existante, et non un refus.
                $this->assertSame($this->appeler($gestionnaire, $methode, $uri)->getStatusCode(), $code, "$nom : comportement identique au Gestionnaire");
                $this->assertNotSame(403, $code);

                continue;
            }

            $this->assertSame(200, $code, "$nom ($uri) doit être consultable par le Consultant");
        }
    }

    public function test_rapports_toujours_refuses_aux_roles_sans_consultation(): void
    {
        foreach ([['Demandeur'], ['ValidateurCommande'], ['Admin'], ['Superieur']] as $roles) {
            foreach ($this->routesEtats() as [$methode, $nom, $uri]) {
                $this->assertSame(403, $this->appeler($this->compte($roles), $methode, $uri)->getStatusCode(), implode('+', $roles)." : $nom");
            }
        }
    }

    public function test_menu_etats_et_mouvements_pour_le_consultant(): void
    {
        $page = $this->actingAs($this->compte(['Consultant']))->get(route('mes.demandes'))->assertOk();
        $page->assertSee(route('etats.index'), false)->assertSee(route('mouvements.index'), false);

        $this->actingAs($this->compte(['Demandeur']))->get(route('mes.demandes'))
            ->assertDontSee(route('etats.index'), false)->assertDontSee(route('mouvements.index'), false);
    }

    // ---------------- A3 : mouvements ----------------

    public function test_consultant_consulte_tous_les_mouvements(): void
    {
        $vus = $this->actingAs($this->compte(['Consultant']))->get(route('mouvements.index'))->assertOk()->viewData('mouvements');

        $this->assertSame(MouvementStock::count(), $vus->count());
        $this->assertEqualsCanonicalizing(['ENTREE', 'SORTIE'], $vus->pluck('type')->unique()->values()->all());
    }

    public function test_consultant_aucune_operation_de_stock(): void
    {
        $consultant = $this->compte(['Consultant']);
        $avant = [MouvementStock::count(), (int) $this->article->fresh()->stock_disponible, (int) $this->article->fresh()->stock_initial];

        $this->actingAs($consultant)->put(route('articles.rebut', $this->article->id), ['quantite' => 1, 'motif' => 'Essai', 'date_rebut' => '2026-11-10'])->assertForbidden();
        $this->actingAs($consultant)->put(route('articles.update', $this->article->id), ['stock_initial' => 99, 'stock_disponible' => 99, 'prix_unitaire' => 1, 'stock_securite' => 1])->assertForbidden();
        $this->actingAs($consultant)->get(route('articles.create'))->assertForbidden();
        $this->actingAs($consultant)->post(route('articles.store'), ['code' => 'X', 'libelle' => 'X'])->assertForbidden();
        $this->actingAs($consultant)->delete(route('articles.destroy', $this->article->id))->assertForbidden();
        $this->actingAs($consultant)->get(route('articles.index'))->assertForbidden();

        // Aucune route ne modifie ni ne supprime un mouvement.
        $ecritures = collect(app('router')->getRoutes())->filter(fn ($r) => str_contains($r->uri(), 'mouvement')
            && array_intersect($r->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) !== [] && ! str_starts_with($r->uri(), 'etats'));
        $this->assertCount(0, $ecritures, 'Aucune route d\'écriture sur les mouvements');

        $this->assertSame($avant, [MouvementStock::count(), (int) $this->article->fresh()->stock_disponible, (int) $this->article->fresh()->stock_initial]);
    }

    public function test_mouvements_inchanges_pour_les_autres_roles(): void
    {
        $this->actingAs($this->compte(['Gestionnaire']))->get(route('mouvements.index'))->assertOk();
        $this->actingAs($this->compte(['SuperAdmin']))->get(route('mouvements.index'))->assertOk();
        foreach ([['Demandeur'], ['ValidateurDemande'], ['ValidateurCommande'], ['Admin']] as $roles) {
            $this->actingAs($this->compte($roles))->get(route('mouvements.index'))->assertForbidden();
        }

        // La mise au rebut reste au Gestionnaire (et au SuperAdmin) : génération automatique du mouvement.
        $this->actingAs($this->compte(['Gestionnaire']))->from('/articles')->put(route('articles.rebut', $this->article->id),
            ['quantite' => 1, 'motif' => 'Casse', 'date_rebut' => '2026-11-10'])->assertRedirect();
        $this->assertSame(1, MouvementStock::where('type', 'REBUT')->count());
    }
}
