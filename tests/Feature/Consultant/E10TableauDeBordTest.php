<?php

namespace Tests\Feature\Consultant;

use App\Http\Controllers\DashboardController;
use App\Models\Agence;
use App\Models\Article;
use App\Models\ArticleDemande;
use App\Models\Commande;
use App\Models\Demande;
use App\Models\Fournisseur;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * LC6 : E10, tableau de bord selon les droits, contrôlé côté serveur.
 */
class E10TableauDeBordTest extends TestCase
{
    use RefreshDatabase;

    private Agence $agence;

    private Agence $autreAgence;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->agence = Agence::factory()->create();
        $this->autreAgence = Agence::factory()->create();

        // Données de deux entités et commandes de tous états.
        foreach ([$this->agence, $this->autreAgence] as $i => $agence) {
            $u = User::factory()->create(['agence_id' => $agence->id]);
            $d = Demande::factory()->create(['user_id' => $u->id, 'statut' => 'Livrée - Demande clôturée', 'objet' => 'Demande entité '.$i, 'date' => '2026-03-10']);
            ArticleDemande::factory()->create(['demande_id' => $d->id, 'article_id' => Article::factory()->create(['libelle' => 'Article entité '.$i])->id, 'quantite' => 5]);
        }
        $f = Fournisseur::factory()->create();
        foreach ([[1, 1000], [0, 2000]] as [$livraison, $montant]) {
            Commande::forceCreate(['numero_bon_commande' => 'BC-'.Str::random(5), 'date' => '2026-04-01', 'montant' => $montant, 'fournisseur_id' => $f->id,
                'statut' => 'Prête pour validation', 'validation' => 1, 'livraison' => $livraison, 'archiver' => 0]);
        }
    }

    private function compte(array $roles): User
    {
        $u = User::factory()->create(['agence_id' => $this->agence->id]);
        $u->assignRole($roles);

        return $u;
    }

    public function test_compte_sans_role_sans_tableau_de_bord_metier(): void
    {
        $sansRole = User::factory()->create(['agence_id' => $this->agence->id]);

        $reponse = $this->actingAs($sansRole)->get(route('dashboard'));
        $reponse->assertRedirect(route('mon.profil'))->assertSessionHas('error', DashboardController::MESSAGE_SANS_ROLE);
        $this->assertNull($reponse->original, 'Aucune vue de tableau de bord ne doit être produite');

        // Il garde l'accès à la gestion de son compte.
        $this->actingAs($sansRole)->get(route('mon.profil'))->assertOk();
        $this->actingAs($sansRole)->get(route('profile.edit'))->assertOk();
    }

    public function test_consultant_tableau_de_consultation_sans_donnees_sensibles(): void
    {
        foreach ([['Consultant'], ['Demandeur', 'Consultant']] as $roles) {
            $reponse = $this->actingAs($this->compte($roles))->get(route('dashboard'))->assertOk()->assertViewIs('dashboard');
            $donnees = $reponse->original->getData();

            // Données non transmises (et non seulement masquées).
            $this->assertArrayNotHasKey('totalUsers', $donnees);
            $this->assertArrayNotHasKey('totalMontantCommandes', $donnees);
            $reponse->assertDontSee('Montant commandes')->assertDontSee('Utilisateurs</h6>', false);

            // Demandes : son entité seulement.
            $this->assertSame(1, $donnees['totalDemandes']);
            $this->assertSame(['Demande entité 0'], $donnees['dernieresDemandes']->pluck('objet')->all());
            $this->assertSame(['Article entité 0'], $donnees['topArticles']->pluck('libelle')->all());

            // Commandes : réceptionnées seulement (toute l'organisation).
            $this->assertSame(1, $donnees['totalCommandes']);
            $this->assertSame([1], $donnees['dernieresCommandes']->pluck('livraison')->map(fn ($v) => (int) $v)->all());
        }
    }

    public function test_roles_existants_conservent_leur_tableau_de_bord(): void
    {
        foreach ([['Gestionnaire'], ['SuperAdmin'], ['Admin'], ['ValidateurCommande'], ['Superieur'], ['ValidateurDemande'], ['Gestionnaire', 'Consultant']] as $roles) {
            $reponse = $this->actingAs($this->compte($roles))->get(route('dashboard'))->assertOk()->assertViewIs('dashboard');
            $donnees = $reponse->original->getData();
            $this->assertSame(User::count(), $donnees['totalUsers'], implode('+', $roles));
            $this->assertEquals(3000, $donnees['totalMontantCommandes'], implode('+', $roles));
            $this->assertSame(2, $donnees['totalDemandes'], implode('+', $roles));
            $reponse->assertSee('Montant commandes');
        }

        $this->actingAs($this->compte(['Demandeur']))->get(route('dashboard'))->assertOk()->assertViewIs('accueil');
    }
}
