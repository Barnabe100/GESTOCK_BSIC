<?php

namespace Tests\Feature\Consolidation;

use App\Models\Article;
use App\Models\ArticleCommande;
use App\Models\Commande;
use App\Models\Fournisseur;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Décision : les commandes soumises AVANT le déploiement (soumetteur inconnu)
 * restent validables par tout utilisateur ayant le rôle et la permission de
 * validation, sans nouvelle soumission ; la séparation des responsabilités
 * s'applique aux commandes soumises après le déploiement.
 */
class CommandesHistoriquesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function compte(array $roles): User
    {
        $u = User::factory()->create();
        $u->assignRole($roles);

        return $u;
    }

    private function commandeSoumise(?User $soumetteur): Commande
    {
        $c = Commande::forceCreate(['numero_bon_commande' => 'BC-'.Str::random(6), 'date' => '2026-10-01', 'montant' => 200,
            'fournisseur_id' => Fournisseur::factory()->create()->id, 'statut' => 'Prête pour validation', 'validation' => 0,
            'livraison' => 0, 'archiver' => 0, 'soumise_par' => $soumetteur?->id]);
        ArticleCommande::create(['commande_id' => $c->id, 'article_id' => Article::factory()->create()->id, 'quantite' => 2,
            'prix_unitaire' => 100, 'quantite_livree' => 0, 'quantite_restante' => 2]);

        return $c;
    }

    public function test_commande_historique_validable_par_un_validateur_habilite_sans_soumetteur_artificiel(): void
    {
        foreach ([['ValidateurCommande'], ['SuperAdmin'], ['Gestionnaire', 'ValidateurCommande']] as $roles) {
            $historique = $this->commandeSoumise(null);
            $validateur = $this->compte($roles);

            $this->actingAs($validateur)->from('/commandes')->post(route('commandes.valider', $historique->id))
                ->assertSessionHas('success', 'Commande validée');

            $historique->refresh();
            $this->assertTrue((bool) $historique->validation);
            $this->assertNull($historique->soumise_par, 'Aucun soumetteur historique ne doit être inventé.');
            $this->assertSame($validateur->id, (int) $historique->validee_par);
        }
    }

    public function test_commande_historique_refusee_sans_role_ou_sans_permission(): void
    {
        $historique = $this->commandeSoumise(null);

        $this->actingAs($this->compte(['Gestionnaire']))->post(route('commandes.valider', $historique->id))->assertForbidden();
        $this->actingAs($this->compte(['Demandeur']))->post(route('commandes.valider', $historique->id))->assertForbidden();
        $this->assertFalse((bool) $historique->fresh()->validation);
    }

    public function test_nouvelle_commande_separation_appliquee_et_tracee(): void
    {
        $double = $this->compte(['Gestionnaire', 'ValidateurCommande']);
        $c = Commande::forceCreate(['numero_bon_commande' => 'BC-NEUVE', 'date' => '2026-12-01', 'montant' => 200,
            'fournisseur_id' => Fournisseur::factory()->create()->id, 'statut' => 'En préparation', 'validation' => 0, 'livraison' => 0, 'archiver' => 0]);
        ArticleCommande::create(['commande_id' => $c->id, 'article_id' => Article::factory()->create()->id, 'quantite' => 2,
            'prix_unitaire' => 100, 'quantite_livree' => 0, 'quantite_restante' => 2]);

        $this->actingAs($double)->put(route('commandes.soumettre', $c->id))->assertRedirect(route('commandes.index'));
        $this->assertSame($double->id, (int) $c->fresh()->soumise_par);
        $this->assertNotNull($c->fresh()->soumise_le);

        // Auto-validation refusée, validation par une autre personne acceptée.
        $this->actingAs($double)->post(route('commandes.valider', $c->id))->assertForbidden();
        $autre = $this->compte(['ValidateurCommande']);
        $this->actingAs($autre)->from('/commandes')->post(route('commandes.valider', $c->id))->assertSessionHas('success', 'Commande validée');
        $this->assertSame($autre->id, (int) $c->fresh()->validee_par);

        // Le bouton « Valider » est masqué pour le soumetteur d'une nouvelle commande.
        $c2 = $this->commandeSoumise($double);
        $this->actingAs($double)->get(route('commandes.index'))->assertOk()
            ->assertDontSee(route('commandes.valider', $c2->id), false);
        $this->actingAs($autre)->get(route('commandes.index'))->assertSee(route('commandes.valider', $c2->id), false);
    }
}
