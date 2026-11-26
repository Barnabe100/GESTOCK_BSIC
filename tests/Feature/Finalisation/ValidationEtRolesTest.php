<?php

namespace Tests\Feature\Finalisation;

use App\Models\Agence;
use App\Models\Article;
use App\Models\ArticleDemande;
use App\Models\Demande;
use App\Models\Departement;
use App\Models\HistoriqueRole;
use App\Models\HistoriqueSuperieur;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Décisions validées : Q3 (deux valideurs supérieurs alternatifs), Q1
 * (SuperAdmin sans auto-décision), rôle Admin attribuable par un Admin,
 * historique des rôles, auto-rejet interdit, livraison de sa propre demande.
 */
class ValidationEtRolesTest extends TestCase
{
    use RefreshDatabase;

    private Agence $agence;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->agence = Agence::factory()->create();
    }

    private function compte(string|array $roles, array $attr = []): User
    {
        $u = User::factory()->create(array_merge(['agence_id' => $this->agence->id], $attr));
        $u->assignRole($roles);

        return $u;
    }

    private function demande(User $proprietaire, array $attr = []): Demande
    {
        $d = Demande::factory()->create(array_merge(['user_id' => $proprietaire->id, 'statut' => 'En attente supérieur'], $attr));
        ArticleDemande::factory()->create(['demande_id' => $d->id, 'article_id' => Article::factory()->create(['stock_disponible' => 20])->id,
            'quantite' => 2, 'quantite_accordee' => 2]);

        return $d->fresh();
    }

    // ---------------- Q3 : deux valideurs supérieurs ----------------

    public function test_deux_superieurs_alternatifs_et_validation_de_la_demande_d_un_collegue(): void
    {
        $admin = $this->compte('Admin');
        $sup1 = $this->compte('Demandeur');
        $sup2 = $this->compte('Demandeur');
        $demandeur = $this->compte('Demandeur');
        foreach ([$sup1, $sup2] as $s) {
            $this->actingAs($admin)->put(route('users.update', $s->id), ['name' => 'S', 'prenom' => 'P', 'email' => $s->email,
                'agence_id' => $this->agence->id, 'superieur' => 1, 'roles' => ['Demandeur']])->assertRedirect(route('users.index'));
        }
        $this->assertTrue($sup1->fresh()->hasRole('Superieur'));
        $this->assertTrue($sup2->fresh()->hasRole('Superieur'));

        // Demande d'un demandeur : l'un OU l'autre supérieur valide (alternatifs).
        $d1 = $this->demande($demandeur);
        $this->actingAs($sup2->fresh())->from('/demandes-a-valider')->put(route('demandes.valider.superieur', $d1->id))
            ->assertSessionHas('success', 'Demande validée.');
        $this->assertSame('En attente gestionnaire', $d1->fresh()->statut);

        // Demande du supérieur 1 : refus pour lui-même, validation par son collègue.
        $d2 = $this->demande($sup1);
        $this->actingAs($sup1->fresh())->put(route('demandes.valider.superieur', $d2->id))->assertForbidden();
        $this->actingAs($sup1->fresh())->put(route('demandes.rejeter.superieur', $d2->id))->assertForbidden();
        $this->actingAs($sup2->fresh())->from('/demandes-a-valider')->put(route('demandes.valider.superieur', $d2->id))
            ->assertSessionHas('success', 'Demande validée.');
        $this->assertSame($sup2->id, (int) $d2->fresh()->superieur_id);

        // Sa propre demande n'apparaît pas dans sa liste « à valider ».
        $d3 = $this->demande($sup1);
        $liste = $this->actingAs($sup1->fresh())->get('/demandes-a-valider')->assertOk()->viewData('enAttente');
        $this->assertFalse($liste->contains('id', $d3->id));
        $this->assertTrue($this->actingAs($sup2->fresh())->get('/demandes-a-valider')->viewData('enAttente')->contains('id', $d3->id));
    }

    public function test_un_troisieme_superieur_exige_un_remplacement_explicite(): void
    {
        $admin = $this->compte('Admin');
        $sup1 = $this->compte(['Demandeur', 'Superieur'], ['superieur' => 1]);
        $sup2 = $this->compte(['Demandeur', 'Superieur'], ['superieur' => 1]);
        $troisieme = $this->compte('Demandeur');

        $this->actingAs($admin)->from('/users/'.$troisieme->id.'/edit')->put(route('users.update', $troisieme->id), ['name' => 'T', 'prenom' => 'P',
            'email' => $troisieme->email, 'agence_id' => $this->agence->id, 'superieur' => 1, 'roles' => ['Demandeur']])
            ->assertSessionHas('error', 'Deux supérieurs sont déjà désignés pour cette entité. Indiquez le supérieur remplacé.');
        $this->assertFalse($troisieme->fresh()->hasRole('Superieur'));

        // Délégation (supérieur absent) : remplacement explicite, avec historique.
        $this->actingAs($admin)->put(route('users.update', $troisieme->id), ['name' => 'T', 'prenom' => 'P', 'email' => $troisieme->email,
            'agence_id' => $this->agence->id, 'superieur' => 1, 'superieur_remplace_id' => $sup1->id, 'roles' => ['Demandeur']])
            ->assertRedirect(route('users.index'));
        $this->assertFalse($sup1->fresh()->hasRole('Superieur'));
        $this->assertTrue($sup2->fresh()->hasRole('Superieur'));
        $this->assertTrue($troisieme->fresh()->hasRole('Superieur'));
        $this->assertDatabaseHas('historique_superieurs', ['ancien_superieur_id' => $sup1->id, 'nouveau_superieur_id' => $troisieme->id, 'designe_par' => $admin->id]);
        $this->assertDatabaseHas('historique_roles', ['user_id' => $sup1->id, 'role' => 'Superieur', 'action' => 'retire', 'origine' => 'designation', 'effectue_par' => $admin->id]);
        $this->assertDatabaseHas('historique_roles', ['user_id' => $troisieme->id, 'role' => 'Superieur', 'action' => 'attribue', 'origine' => 'designation']);

        // Un remplacé d'une autre entité est refusé.
        $ailleurs = $this->compte(['Demandeur', 'Superieur'], ['superieur' => 1, 'agence_id' => Agence::factory()->create()->id]);
        $quatrieme = $this->compte('Demandeur');
        $this->actingAs($admin)->put(route('users.update', $quatrieme->id), ['name' => 'Q', 'prenom' => 'P', 'email' => $quatrieme->email,
            'agence_id' => $this->agence->id, 'superieur' => 1, 'superieur_remplace_id' => $ailleurs->id, 'roles' => ['Demandeur']])
            ->assertSessionHas('error', 'Le supérieur remplacé doit être un supérieur en fonction de la même entité.');
        $this->assertTrue($ailleurs->fresh()->hasRole('Superieur'));
    }

    public function test_creation_de_demande_par_un_superieur_seul_puis_avec_un_collegue(): void
    {
        $sup = $this->compte(['Demandeur', 'Superieur'], ['superieur' => 1]);

        // Seul supérieur de l'entité : aucun autre valideur, création refusée.
        $this->actingAs($sup)->from('/demandes/create')->post(route('demandes.store'), ['objet' => 'Papier'])
            ->assertSessionHas('error', 'Aucun supérieur défini');
        $this->assertSame(0, Demande::count());

        // Un second supérieur est désigné : la demande lui est adressée.
        $collegue = $this->compte(['Demandeur', 'Superieur'], ['superieur' => 1]);
        $this->actingAs($sup)->post(route('demandes.store'), ['objet' => 'Papier'])->assertRedirect(route('mes.demandes'));
        $this->assertSame($collegue->id, (int) Demande::firstOrFail()->superieur_id);
    }

    public function test_superieur_au_siege_valide_par_departement(): void
    {
        $siege = Agence::factory()->siege()->create();
        $depA = Departement::factory()->create(['agence_id' => $siege->id]);
        $depB = Departement::factory()->create(['agence_id' => $siege->id]);
        $supA = $this->compte(['Demandeur', 'Superieur'], ['superieur' => 1, 'agence_id' => $siege->id, 'departement_id' => $depA->id]);
        $demA = $this->compte('Demandeur', ['agence_id' => $siege->id, 'departement_id' => $depA->id]);
        $demB = $this->compte('Demandeur', ['agence_id' => $siege->id, 'departement_id' => $depB->id]);

        $this->actingAs($supA)->from('/demandes-a-valider')->put(route('demandes.valider.superieur', $this->demande($demA)->id))
            ->assertSessionHas('success', 'Demande validée.');
        $this->actingAs($supA)->put(route('demandes.valider.superieur', $this->demande($demB)->id))->assertForbidden();
    }

    // ---------------- Q1 : SuperAdmin ----------------

    public function test_superadmin_ne_decide_jamais_sur_sa_propre_demande(): void
    {
        $sa = $this->compte(['SuperAdmin', 'Gestionnaire', 'ValidateurDemande', 'Superieur'], ['superieur' => 1]);
        $etats = [
            ['valider.superieur', 'rejeter.superieur', ['statut' => 'En attente supérieur']],
            ['valider', 'rejeter.gestionnaire', ['statut' => 'En attente gestionnaire', 'validation_superieur' => 1]],
            ['validation.finale', 'rejet.finale', ['statut' => 'En attente validation finale', 'validation_superieur' => 1, 'validation_gestionnaire' => 1]],
        ];

        foreach ($etats as [$valider, $rejeter, $attr]) {
            $d = $this->demande($sa, $attr);
            $this->actingAs($sa)->put(route('demandes.'.$valider, $d->id), ['quantite_accordee' => [$d->articles()->first()->article_id => 1]])->assertForbidden();
            $this->actingAs($sa)->put(route('demandes.'.$rejeter, $d->id))->assertForbidden();
            $this->assertSame($attr['statut'], $d->fresh()->statut);
        }
    }

    // ---------------- Livraison de sa propre demande ----------------

    public function test_livraison_de_sa_propre_demande_si_permission_et_refus_sans(): void
    {
        $gest = $this->compte(['Demandeur', 'Gestionnaire']);
        $prete = ['statut' => 'En attente livraison', 'validation_superieur' => 1, 'validation_gestionnaire' => 1, 'validation_finale' => 1];

        $d = $this->demande($gest, $prete);
        $this->actingAs($gest)->from('/demandes-gestionnaire')->post(route('demandes.livrer', $d->id))->assertRedirect('/demandes-gestionnaire');
        $this->assertSame(1, (int) $d->fresh()->livraison);

        // Sans la permission de livraison, même sa propre demande : refus.
        $dem = $this->compte('Demandeur');
        $d2 = $this->demande($dem, $prete);
        $this->actingAs($dem)->post(route('demandes.livrer', $d2->id))->assertForbidden();
        $this->assertSame(0, (int) $d2->fresh()->livraison);

        // La livraison ne donne aucun droit de validation sur sa propre demande.
        $d3 = $this->demande($gest, ['statut' => 'En attente gestionnaire', 'validation_superieur' => 1]);
        $this->actingAs($gest)->put(route('demandes.valider', $d3->id), ['quantite_accordee' => [$d3->articles()->first()->article_id => 1]])->assertForbidden();
    }

    // ---------------- Rôles : Admin, auto-attribution, historique ----------------

    public function test_un_admin_attribue_le_role_admin_a_un_autre_jamais_a_lui_meme(): void
    {
        $admin = $this->compte('Admin');
        $cible = $this->compte('Demandeur');

        $this->actingAs($admin)->put(route('users.update', $cible->id), ['name' => 'C', 'prenom' => 'P', 'email' => $cible->email,
            'roles' => ['Demandeur', 'Admin']])->assertRedirect(route('users.index'));
        $this->assertTrue($cible->fresh()->hasRole('Admin'));
        $this->assertDatabaseHas('historique_roles', ['user_id' => $cible->id, 'role' => 'Admin', 'action' => 'attribue', 'origine' => 'formulaire', 'effectue_par' => $admin->id]);

        // Retrait tracé également.
        $this->actingAs($admin)->put(route('users.update', $cible->id), ['name' => 'C', 'prenom' => 'P', 'email' => $cible->email, 'roles' => ['Demandeur']]);
        $this->assertDatabaseHas('historique_roles', ['user_id' => $cible->id, 'role' => 'Admin', 'action' => 'retire', 'effectue_par' => $admin->id]);

        // Jamais à soi-même, quel que soit le rôle.
        foreach (['Admin', 'SuperAdmin'] as $role) {
            $acteur = $this->compte($role);
            $this->actingAs($acteur)->put(route('users.update', $acteur->id), ['name' => 'X', 'prenom' => 'P', 'email' => $acteur->email,
                'roles' => [$role, 'Gestionnaire']]);
            $this->assertFalse($acteur->fresh()->hasRole('Gestionnaire'));
        }

        // Le rôle SuperAdmin reste réservé au SuperAdmin.
        $this->actingAs($admin)->put(route('users.update', $cible->id), ['name' => 'C', 'prenom' => 'P', 'email' => $cible->email,
            'roles' => ['SuperAdmin']])->assertForbidden();
        $this->assertSame(0, HistoriqueRole::where('role', 'SuperAdmin')->count());
    }

    public function test_historique_des_roles_a_la_creation_et_a_la_designation(): void
    {
        $admin = $this->compte('Admin');

        $this->actingAs($admin)->post(route('users.store'), ['name' => 'N', 'prenom' => 'P', 'email' => 'nouveau@ex.test',
            'password' => 'Provisoire1', 'password_confirmation' => 'Provisoire1', 'agence_id' => $this->agence->id, 'superieur' => 1])
            ->assertRedirect(route('users.index'));

        $nouveau = User::where('email', 'nouveau@ex.test')->firstOrFail();
        $this->assertDatabaseHas('historique_roles', ['user_id' => $nouveau->id, 'role' => 'Demandeur', 'action' => 'attribue', 'origine' => 'creation', 'effectue_par' => $admin->id]);
        $this->assertDatabaseHas('historique_roles', ['user_id' => $nouveau->id, 'role' => 'Superieur', 'action' => 'attribue', 'origine' => 'designation']);
        $this->assertSame(1, HistoriqueSuperieur::where('nouveau_superieur_id', $nouveau->id)->count());
    }
}
