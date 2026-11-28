<?php

namespace Tests\Feature\Consultant;

use App\Models\Article;
use App\Models\ArticleCommande;
use App\Models\Commande;
use App\Models\Fournisseur;
use App\Models\MouvementStock;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * LC3 : A1 (réception par le Gestionnaire seulement) et consultation des
 * commandes réceptionnées (livraison = 1) par le Consultant, sur toute
 * l'organisation, sans aucune opération.
 */
class A1CommandesTest extends TestCase
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

    private function commande(array $attr, int $quantite = 4, int $livree = 0): Commande
    {
        $c = Commande::forceCreate(array_merge(['numero_bon_commande' => 'BC-'.Str::random(6), 'date' => '2026-11-01', 'montant' => 400,
            'fournisseur_id' => Fournisseur::factory()->create()->id, 'statut' => 'Prête pour validation', 'validation' => 0, 'livraison' => 0, 'archiver' => 0], $attr));
        ArticleCommande::create(['commande_id' => $c->id, 'article_id' => Article::factory()->create(['stock_disponible' => 0])->id,
            'quantite' => $quantite, 'prix_unitaire' => 100, 'quantite_livree' => $livree, 'quantite_restante' => $quantite - $livree]);

        return $c->fresh();
    }

    private function receptionner(User $par, Commande $c)
    {
        return $this->actingAs($par)->from('/commandes')->put(route('commandes.livrer', $c->id), ['date_livraison' => '2026-11-10',
            'quantites' => [$c->articles()->first()->id => 1], 'jeton_reception' => (string) Str::uuid()]);
    }

    // ---------------- Réception ----------------

    public function test_seul_le_gestionnaire_receptionne(): void
    {
        $c = $this->commande(['validation' => 1]);

        foreach ([['ValidateurCommande'], ['SuperAdmin'], ['Consultant'], ['Demandeur', 'Consultant'], ['Admin'], ['ValidateurDemande'], ['Superieur']] as $roles) {
            $this->receptionner($this->compte($roles), $c)->assertForbidden();
        }
        $this->assertSame(0, MouvementStock::count());

        $this->receptionner($this->compte(['Gestionnaire']), $c)->assertSessionHas('success', 'Réception enregistrée');
        $this->assertSame(1, (int) $c->articles()->first()->quantite_livree);
    }

    public function test_bouton_de_reception_affiche_au_gestionnaire_seulement(): void
    {
        $c = $this->commande(['validation' => 1]);
        $modale = 'data-bs-target="#livrer'.$c->id.'"';

        $this->actingAs($this->compte(['Gestionnaire']))->get(route('commandes.index'))->assertOk()->assertSee($modale, false);
        foreach ([['ValidateurCommande'], ['SuperAdmin'], ['Consultant']] as $roles) {
            $this->actingAs($this->compte($roles))->get(route('commandes.index'))->assertDontSee($modale, false);
        }

        // Aucun bouton quand la commande n'est pas validée.
        $nonValidee = $this->commande([]);
        $this->actingAs($this->compte(['Gestionnaire']))->get(route('commandes.index'))
            ->assertDontSee('data-bs-target="#livrer'.$nonValidee->id.'"', false);
    }

    public function test_validation_des_commandes_toujours_fonctionnelle(): void
    {
        $gest = $this->compte(['Gestionnaire']);
        $c = $this->commande(['soumise_par' => $gest->id]);

        $this->actingAs($this->compte(['ValidateurCommande']))->from('/commandes')->post(route('commandes.valider', $c->id))
            ->assertSessionHas('success', 'Commande validée');
        $this->actingAs($this->compte(['SuperAdmin']))->from('/commandes')->post(route('commandes.valider', $this->commande(['soumise_par' => $gest->id])->id))
            ->assertSessionHas('success', 'Commande validée');
    }

    // ---------------- Consultation par le Consultant ----------------

    public function test_consultant_voit_seulement_les_commandes_receptionnees_de_toute_l_organisation(): void
    {
        $receptionnee = $this->commande(['validation' => 1, 'livraison' => 1], 4, 4);
        $partielle = $this->commande(['validation' => 1], 4, 2);
        $nonRecue = $this->commande(['validation' => 1]);
        $consultant = $this->compte(['Consultant']);

        $liste = $this->actingAs($consultant)->get(route('commandes.index'))->assertOk()->viewData('commandes')->pluck('id')->all();
        $this->assertSame([$receptionnee->id], $liste);

        $this->actingAs($consultant)->get(route('commandes.show', $receptionnee->id))->assertOk();
        $this->actingAs($consultant)->get(route('commandes.show', $partielle->id))->assertForbidden();
        $this->actingAs($consultant)->get(route('commandes.show', $nonRecue->id))->assertForbidden();

        // Les rôles du module gardent l'accès complet.
        foreach ([['Gestionnaire'], ['ValidateurCommande'], ['SuperAdmin']] as $roles) {
            $tout = $this->actingAs($this->compte($roles))->get(route('commandes.index'))->viewData('commandes')->pluck('id')->all();
            $this->assertEqualsCanonicalizing([$receptionnee->id, $partielle->id, $nonRecue->id], $tout);
        }
    }

    public function test_consultant_aucune_operation_sur_les_commandes(): void
    {
        $consultant = $this->compte(['Consultant']);
        $enPreparation = $this->commande(['statut' => 'En préparation']);
        $aValider = $this->commande([]);
        $receptionnee = $this->commande(['validation' => 1, 'livraison' => 1], 4, 4);

        $this->actingAs($consultant)->get(route('commandes.create'))->assertForbidden();
        $this->actingAs($consultant)->post(route('commandes.store'), ['numero_bon_commande' => 'X'])->assertForbidden();
        $this->actingAs($consultant)->get(route('commandes.edit', $enPreparation->id))->assertForbidden();
        $this->actingAs($consultant)->put(route('commandes.update', $enPreparation->id), ['libelle' => 'X'])->assertForbidden();
        $this->actingAs($consultant)->delete(route('commandes.destroy', $enPreparation->id))->assertForbidden();
        $this->actingAs($consultant)->put(route('commandes.soumettre', $enPreparation->id))->assertForbidden();
        $this->actingAs($consultant)->post(route('commandes.valider', $aValider->id))->assertForbidden();
        $this->receptionner($consultant, $receptionnee)->assertForbidden();
        $this->actingAs($consultant)->post(route('article_commande.store'), ['commande_id' => $enPreparation->id])->assertForbidden();
        // Arbitrage A : l'export est permis, filtré côté serveur (ExportCommandesConsultantTest).

        $this->assertSame('En préparation', $enPreparation->fresh()->statut);
        $this->assertFalse((bool) $aValider->fresh()->validation);

        // L'interface ne propose aucune action au Consultant.
        $this->actingAs($consultant)->get(route('commandes.index'))->assertOk()
            ->assertSee(route('commandes.show', $receptionnee->id), false)
            ->assertDontSee(route('commandes.create'), false)
            ->assertDontSee(route('commandes.edit', $receptionnee->id), false);
    }

    public function test_menu_commandes_affiche_au_consultant(): void
    {
        $this->actingAs($this->compte(['Consultant']))->get(route('mes.demandes'))->assertOk()
            ->assertSee(route('commandes.index'), false);
        $this->actingAs($this->compte(['Demandeur']))->get(route('mes.demandes'))->assertOk()
            ->assertDontSee(route('commandes.index'), false);
    }
}
