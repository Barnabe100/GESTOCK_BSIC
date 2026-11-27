<?php

namespace Tests\Feature\Consolidation;

use App\Models\Agence;
use App\Models\Article;
use App\Models\ArticleDemande;
use App\Models\Demande;
use App\Models\MouvementStock;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Q8 (décision définitive) : le demandeur n'accepte ni ne refuse les
 * quantités accordées ; une réduction ne déclenche aucune étape d'accord.
 * Exemple de la décision : riz 10 → 7, huile 5 → 3, sucre 8 → 8.
 */
class QuantitesAccordeesSansAccordTest extends TestCase
{
    use RefreshDatabase;

    public function test_reduction_sans_accord_du_demandeur_circuit_complet_et_tracabilite(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $agence = Agence::factory()->create();
        $mk = function (array $roles, array $attr = []) use ($agence) {
            $u = User::factory()->create(['agence_id' => $agence->id] + $attr);
            $u->assignRole($roles);

            return $u;
        };
        $demandeur = $mk(['Demandeur']);
        $superieur = $mk(['Demandeur', 'Superieur'], ['superieur' => 1]);
        $gestionnaire = $mk(['Demandeur', 'Gestionnaire']);
        $validateur = $mk(['Demandeur', 'ValidateurDemande']);

        $riz = Article::factory()->create(['libelle' => 'Riz', 'stock_disponible' => 50]);
        $huile = Article::factory()->create(['libelle' => 'Huile', 'stock_disponible' => 50]);
        $sucre = Article::factory()->create(['libelle' => 'Sucre', 'stock_disponible' => 50]);
        $d = Demande::factory()->create(['user_id' => $demandeur->id, 'superieur_id' => $superieur->id, 'statut' => 'En attente supérieur']);
        foreach ([[$riz, 10], [$huile, 5], [$sucre, 8]] as [$a, $q]) {
            ArticleDemande::factory()->create(['demande_id' => $d->id, 'article_id' => $a->id, 'quantite' => $q, 'quantite_accordee' => null]);
        }
        $statuts = [];
        $suivre = function () use ($d, &$statuts) {
            $statuts[] = $d->fresh()->statut;
        };

        $this->actingAs($superieur)->from('/demandes-a-valider')->put(route('demandes.valider.superieur', $d->id))->assertSessionHas('success');
        $suivre();
        $this->actingAs($gestionnaire)->from('/demandes-gestionnaire')->put(route('demandes.valider', $d->id),
            ['quantite_accordee' => [$riz->id => 7, $huile->id => 3, $sucre->id => 8]])
            ->assertSessionHas('success', 'Quantités accordées enregistrées');
        $suivre();

        // Aucune étape d'accord : la demande passe directement en validation finale.
        $this->assertSame('En attente validation finale', $d->fresh()->statut);

        $this->actingAs($validateur)->from('/demandes-validation-finale')->put(route('demandes.validation.finale', $d->id))->assertSessionHas('success');
        $suivre();
        $this->actingAs($gestionnaire)->from('/demandes-gestionnaire')->post(route('demandes.livrer', $d->id));
        $suivre();

        $this->assertNotContains('En attente accord demandeur', $statuts);
        $this->assertSame('En attente réception demandeur', $d->fresh()->statut);

        // Quantités demandées et accordées conservées et traçables.
        $lignes = ArticleDemande::where('demande_id', $d->id)->orderBy('id')->get(['article_id', 'quantite', 'quantite_accordee'])
            ->map(fn ($l) => [(int) $l->article_id, (int) $l->quantite, (int) $l->quantite_accordee])->all();
        $this->assertSame([[$riz->id, 10, 7], [$huile->id, 5, 3], [$sucre->id, 8, 8]], $lignes);

        // Livraison selon les quantités accordées.
        $sorties = MouvementStock::where('type', 'SORTIE')->orderBy('article_id')->get()
            ->map(fn ($m) => [(int) $m->article_id, (int) $m->quantite])->all();
        $this->assertSame([[$riz->id, 7], [$huile->id, 3], [$sucre->id, 8]], $sorties);
        $this->assertSame(43, (int) $riz->fresh()->stock_disponible);

        // Le demandeur n'a aucune action d'accord à mener sur cette demande.
        $this->actingAs($demandeur)->put(route('demandes.valider.demandeur', $d->id))->assertForbidden();
    }
}
