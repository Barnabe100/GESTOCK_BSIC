<?php

namespace Tests\Feature\Finalisation;

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
 * C1 (quantités accordées, demande vide) et Q8 (étape valider-demandeur).
 */
class WorkflowsDemandesTest extends TestCase
{
    use RefreshDatabase;

    private User $gestionnaire;

    private User $demandeur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $agence = Agence::factory()->create();
        $this->gestionnaire = User::factory()->create(['agence_id' => $agence->id]);
        $this->gestionnaire->assignRole(['Demandeur', 'Gestionnaire']);
        $this->demandeur = User::factory()->create(['agence_id' => $agence->id]);
        $this->demandeur->assignRole('Demandeur');
    }

    private function demandeEnAttenteGestionnaire(int $lignes = 1): Demande
    {
        $d = Demande::factory()->create(['user_id' => $this->demandeur->id, 'statut' => 'En attente gestionnaire', 'validation_superieur' => 1]);
        for ($i = 0; $i < $lignes; $i++) {
            ArticleDemande::factory()->create(['demande_id' => $d->id, 'article_id' => Article::factory()->create(['stock_disponible' => 30])->id,
                'quantite' => 5, 'quantite_accordee' => null]);
        }

        return $d->fresh();
    }

    private function valider(Demande $d, $quantites)
    {
        return $this->actingAs($this->gestionnaire)->from('/demandes-gestionnaire')
            ->put(route('demandes.valider', $d->id), $quantites === null ? [] : ['quantite_accordee' => $quantites]);
    }

    // ---------------- C1 ----------------

    public function test_quantite_accordee_negative_refusee_sans_aucune_ecriture(): void
    {
        $d = $this->demandeEnAttenteGestionnaire(2);
        [$l1, $l2] = $d->articles()->orderBy('id')->get()->all();

        $this->valider($d, [$l1->article_id => 3, $l2->article_id => -5])
            ->assertRedirect('/demandes-gestionnaire')
            ->assertSessionHas('error', 'La quantité accordée doit être un nombre entier positif ou nul.');

        $this->assertNull($l1->fresh()->quantite_accordee, 'Aucune ligne ne doit être écrite.');
        $this->assertNull($l2->fresh()->quantite_accordee);
        $this->assertSame('En attente gestionnaire', $d->fresh()->statut);
        $this->assertSame(0, (int) $d->fresh()->validation_gestionnaire);
    }

    public function test_quantite_non_entiere_refusee(): void
    {
        $d = $this->demandeEnAttenteGestionnaire();
        $this->valider($d, [$d->articles()->first()->article_id => 'abc'])
            ->assertSessionHas('error', 'La quantité accordée doit être un nombre entier positif ou nul.');
        $this->valider($d, [$d->articles()->first()->article_id => '2.5'])
            ->assertSessionHas('error', 'La quantité accordée doit être un nombre entier positif ou nul.');
        $this->assertSame(0, (int) $d->fresh()->validation_gestionnaire);
    }

    public function test_demande_vide_message_maitrise_et_jamais_d_erreur_500(): void
    {
        $vide = Demande::factory()->create(['user_id' => $this->demandeur->id, 'statut' => 'En attente gestionnaire', 'validation_superieur' => 1]);

        $this->valider($vide, null)->assertRedirect('/demandes-gestionnaire')
            ->assertSessionHas('error', 'Cette demande ne contient aucun article.');
        $this->valider($vide, [999 => 1])->assertSessionHas('error', 'Cette demande ne contient aucun article.');
        $this->assertSame('En attente gestionnaire', $vide->fresh()->statut);

        // Demande non vide mais aucune quantité envoyée : message, pas de 500.
        $d = $this->demandeEnAttenteGestionnaire();
        $this->valider($d, null)->assertSessionHas('error', 'Veuillez saisir les quantités accordées.');
    }

    public function test_article_d_une_autre_demande_refuse(): void
    {
        $d = $this->demandeEnAttenteGestionnaire();
        $autre = Article::factory()->create();

        $this->valider($d, [$autre->id => 1])->assertForbidden();
        $this->assertSame(0, (int) $d->fresh()->validation_gestionnaire);
    }

    public function test_quantite_nulle_conserve_le_comportement_valide_et_la_livraison_l14b(): void
    {
        $d = $this->demandeEnAttenteGestionnaire(2);
        [$l1, $l2] = $d->articles()->orderBy('id')->get()->all();

        $this->valider($d, [$l1->article_id => 0, $l2->article_id => 4])
            ->assertSessionHas('success', 'Quantités accordées enregistrées');
        $this->assertSame(0, (int) $l1->fresh()->quantite_accordee);
        $this->assertSame('En attente validation finale', $d->fresh()->statut);

        // Seconde saisie : message « modifiées » (S-13).
        $this->valider($d, [$l1->article_id => 0, $l2->article_id => 3])
            ->assertSessionHas('success', 'Quantités accordées modifiées');

        // Livraison L1-4b : aucune sortie pour la ligne à 0.
        $d->forceFill(['statut' => 'En attente livraison', 'validation_finale' => 1])->save();
        $this->actingAs($this->gestionnaire)->from('/demandes-gestionnaire')->post(route('demandes.livrer', $d->id));
        $sorties = MouvementStock::where('type', 'SORTIE')->where('motif', 'Livraison demande N° '.$d->id)->get();
        $this->assertCount(1, $sorties);
        $this->assertSame([(int) $l2->article_id, 3], [(int) $sorties[0]->article_id, (int) $sorties[0]->quantite]);
    }

    // ---------------- Q8 : valider-demandeur ----------------

    public function test_valider_demandeur_par_le_seul_demandeur_dans_l_etat_attendu(): void
    {
        $d = Demande::factory()->create(['user_id' => $this->demandeur->id, 'statut' => 'En attente accord demandeur',
            'validation_superieur' => 1, 'validation_gestionnaire' => 1]);

        // Personne d'autre, même gestionnaire : refus.
        $this->actingAs($this->gestionnaire)->put(route('demandes.valider.demandeur', $d->id))->assertForbidden();

        $this->actingAs($this->demandeur)->from('/mes-demandes')->put(route('demandes.valider.demandeur', $d->id))
            ->assertRedirect('/mes-demandes')
            ->assertSessionHas('success', 'Quantités accordées acceptées.');
        $this->assertSame('En attente validation finale', $d->fresh()->statut);

        // Rejeu : refus (plus dans l'état attendu), jamais d'erreur 500.
        $this->actingAs($this->demandeur)->put(route('demandes.valider.demandeur', $d->id))->assertForbidden();
    }

    public function test_valider_demandeur_hors_etat_refuse_sans_erreur_500(): void
    {
        foreach (['En préparation', 'En attente supérieur', 'En attente gestionnaire', 'En attente livraison'] as $statut) {
            $d = Demande::factory()->create(['user_id' => $this->demandeur->id, 'statut' => $statut]);
            $this->actingAs($this->demandeur)->put(route('demandes.valider.demandeur', $d->id))->assertForbidden();
            $this->assertSame($statut, $d->fresh()->statut);
        }
    }

    public function test_creation_par_un_utilisateur_sans_agence_message_et_jamais_d_erreur_500(): void
    {
        $sansAgence = User::factory()->create(['agence_id' => null]);
        $sansAgence->assignRole('Demandeur');

        $this->actingAs($sansAgence)->from('/demandes/create')->post(route('demandes.store'), ['objet' => 'Papier'])
            ->assertRedirect('/demandes/create')
            ->assertSessionHas('error', 'Aucun supérieur défini');
        $this->assertSame(0, Demande::count());
    }
}
