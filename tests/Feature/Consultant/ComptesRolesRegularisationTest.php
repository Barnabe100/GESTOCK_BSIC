<?php

namespace Tests\Feature\Consultant;

use App\Console\Commands\AttribuerDemandeurParDefaut;
use App\Http\Controllers\DashboardController;
use App\Models\Agence;
use App\Models\Article;
use App\Models\HistoriqueRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * F3 : comptes sans rôle (diagnostic, accès limités, aucun droit nouveau).
 * F4 : régularisation E11 du rôle Demandeur sur les comptes existants
 * (ciblée, idempotente, traçable, réversible sans défaire un administrateur).
 */
class ComptesRolesRegularisationTest extends TestCase
{
    use RefreshDatabase;

    private Agence $agence;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->agence = Agence::factory()->create();
    }

    private function compte(array $roles, array $attr = []): User
    {
        $u = User::factory()->create(array_merge(['agence_id' => $this->agence->id], $attr));
        if ($roles !== []) {
            $u->assignRole($roles);
        }

        return $u;
    }

    private function empreinte(): string
    {
        return json_encode([DB::table('model_has_roles')->orderBy('model_id')->orderBy('role_id')->get(),
            DB::table('historique_roles')->count(), DB::table('users')->orderBy('id')->get()]);
    }

    private function diagnostic(): array
    {
        $fichier = tempnam(sys_get_temp_dir(), 'diag');
        $this->artisan('gestok:comptes:diagnostic-roles', ['--json' => $fichier])->assertSuccessful();
        $resultat = json_decode(file_get_contents($fichier), true);
        unlink($fichier);

        return $resultat;
    }

    // ---------------- F3 : comptes sans rôle ----------------

    public function test_compte_sans_role_acces_limites_et_aucun_droit_operationnel(): void
    {
        $this->compte(['Superieur'], ['superieur' => 1]); // supérieur de l'entité
        $sansRole = $this->compte([]);
        $article = Article::factory()->create();

        $this->actingAs($sansRole)->get(route('dashboard'))->assertRedirect(route('mon.profil'))
            ->assertSessionHas('error', DashboardController::MESSAGE_SANS_ROLE);
        $this->actingAs($sansRole)->get(route('mes.demandes'))->assertOk();
        $this->actingAs($sansRole)->get(route('demandes.create'))->assertOk();
        $this->actingAs($sansRole)->post(route('demandes.store'), ['objet' => 'Papier'])->assertRedirect(route('mes.demandes'));

        foreach (['/commandes', '/articles', '/etats', '/mouvements-stock', '/fournisseurs', '/users', '/demandes-a-valider',
            '/demandes-gestionnaire', '/demandes-validation-finale', '/commandes-export-csv', '/demandes-export-csv'] as $url) {
            $this->actingAs($sansRole)->get($url)->assertForbidden();
        }
        $this->actingAs($sansRole)->put(route('articles.rebut', $article->id), ['quantite' => 1, 'motif' => 'x', 'date_rebut' => '2026-11-10'])->assertForbidden();
    }

    public function test_diagnostic_des_comptes_sans_role_en_lecture_seule(): void
    {
        $actif = $this->compte([]);
        $bloque = $this->compte([], ['bloque' => 1]);
        $desactive = $this->compte([], ['desactive_le' => now()]);
        $sansEntite = $this->compte([], ['agence_id' => null]);
        $this->compte(['Demandeur']);
        $avant = $this->empreinte();

        $diag = $this->diagnostic();

        $this->assertSame($avant, $this->empreinte(), 'Le diagnostic ne doit rien écrire');
        $parId = collect($diag['sans_role'])->keyBy('id');
        $this->assertEqualsCanonicalizing([$actif->id, $bloque->id, $desactive->id, $sansEntite->id], $parId->keys()->all());
        $this->assertSame('actif', $parId[$actif->id]['statut']);
        $this->assertSame('bloque', $parId[$bloque->id]['statut']);
        $this->assertSame('desactive', $parId[$desactive->id]['statut']);
        $this->assertSame(['sans_rattachement'], $parId[$sansEntite->id]['particularites']);
        $this->assertFalse($parId[$actif->id]['acces_metier']['tableau_de_bord']);
        $this->assertSame([], $parId[$actif->id]['acces_metier']['capacites']);
        $this->assertSame(['total' => 4, 'actifs' => 2, 'bloques' => 1, 'desactives' => 1, 'particuliers' => 1], $diag['synthese']['sans_role']);
    }

    // ---------------- F4 : attribution du rôle Demandeur ----------------

    public function test_simulation_puis_attribution_ciblee_qui_conserve_les_roles(): void
    {
        $admin = $this->compte(['Admin', 'Demandeur']);
        $gestCons = $this->compte(['Gestionnaire', 'Consultant']);
        $valc = $this->compte(['ValidateurCommande']);
        $dejaDemandeur = $this->compte(['Demandeur', 'Gestionnaire']);
        $sansRole = $this->compte([]);
        $bloque = $this->compte(['Gestionnaire'], ['bloque' => 1]);
        $desactive = $this->compte(['Gestionnaire'], ['desactive_le' => now()]);
        $installation = $this->compte(['SuperAdmin'], ['email' => 'superadmin@test.com']);
        $technique = $this->compte(['Gestionnaire'], ['email' => 'robot@ex.test']);
        $sansEntite = $this->compte(['Gestionnaire'], ['agence_id' => null]);
        config(['gestok.comptes_techniques' => ['robot@ex.test']]);

        // Diagnostic préalable.
        $diag = $this->diagnostic();
        $this->assertEqualsCanonicalizing([$gestCons->id, $valc->id], $diag['synthese']['sans_demandeur_avec_autres_roles']['identifiants_eligibles']);
        $exclus = collect($diag['sans_demandeur']['exclus'])->keyBy('id');
        $this->assertEqualsCanonicalizing([$bloque->id, $desactive->id, $installation->id, $technique->id, $sansEntite->id], $exclus->keys()->all());
        $this->assertSame('compte bloque', $exclus[$bloque->id]['motif_exclusion']);
        $this->assertStringContainsString('compte_installation', $exclus[$installation->id]['motif_exclusion']);
        $this->assertStringContainsString('declare_technique', $exclus[$technique->id]['motif_exclusion']);

        // Simulation : aucune écriture.
        $avant = $this->empreinte();
        $this->artisan('gestok:comptes:attribuer-demandeur')->assertSuccessful();
        $this->assertSame($avant, $this->empreinte());

        // Application.
        $this->artisan('gestok:comptes:attribuer-demandeur', ['--appliquer' => true, '--par' => $admin->id])->assertSuccessful();

        $this->assertEqualsCanonicalizing(['Gestionnaire', 'Consultant', 'Demandeur'], $gestCons->fresh()->getRoleNames()->all());
        $this->assertEqualsCanonicalizing(['ValidateurCommande', 'Demandeur'], $valc->fresh()->getRoleNames()->all());
        $this->assertDatabaseHas('historique_roles', ['user_id' => $gestCons->id, 'role' => 'Demandeur', 'action' => 'attribue',
            'origine' => AttribuerDemandeurParDefaut::ORIGINE, 'effectue_par' => $admin->id]);

        // Comptes non concernés : strictement inchangés.
        $this->assertEqualsCanonicalizing(['Demandeur', 'Gestionnaire'], $dejaDemandeur->fresh()->getRoleNames()->all());
        $this->assertSame([], $sansRole->fresh()->getRoleNames()->all());
        foreach ([$bloque, $desactive, $technique, $sansEntite] as $u) {
            $this->assertSame(['Gestionnaire'], $u->fresh()->getRoleNames()->all());
        }
        $this->assertSame(['SuperAdmin'], $installation->fresh()->getRoleNames()->all());
        $this->assertSame(2, HistoriqueRole::where('origine', AttribuerDemandeurParDefaut::ORIGINE)->count());
    }

    public function test_reexecution_sans_doublon_ni_effet_secondaire(): void
    {
        $u = $this->compte(['Gestionnaire']);
        $this->artisan('gestok:comptes:attribuer-demandeur', ['--appliquer' => true])->assertSuccessful();
        $apres = $this->empreinte();

        $this->artisan('gestok:comptes:attribuer-demandeur', ['--appliquer' => true])->assertSuccessful();

        $this->assertSame($apres, $this->empreinte());
        $this->assertSame(1, DB::table('model_has_roles')->where('model_id', $u->id)
            ->where('role_id', DB::table('roles')->where('name', 'Demandeur')->value('id'))->count());
    }

    public function test_annulation_sans_defaire_une_action_ulterieure_d_un_administrateur(): void
    {
        $admin = $this->compte(['Admin', 'Demandeur']);
        $simple = $this->compte(['Gestionnaire']);
        $retouche = $this->compte(['ValidateurCommande']);
        $this->artisan('gestok:comptes:attribuer-demandeur', ['--appliquer' => true])->assertSuccessful();

        // Après l'opération, un administrateur retire puis réattribue Demandeur.
        $form = fn (array $roles) => ['name' => 'R', 'prenom' => 'P', 'email' => $retouche->email, 'roles' => $roles];
        $this->actingAs($admin)->put(route('users.update', $retouche->id), $form(['ValidateurCommande']))->assertRedirect(route('users.index'));
        $this->actingAs($admin)->put(route('users.update', $retouche->id), $form(['ValidateurCommande', 'Demandeur']))->assertRedirect(route('users.index'));

        // Simulation d'annulation : rien n'est écrit.
        $avant = $this->empreinte();
        $this->artisan('gestok:comptes:attribuer-demandeur', ['--annuler' => true])->assertSuccessful();
        $this->assertSame($avant, $this->empreinte());

        $this->artisan('gestok:comptes:attribuer-demandeur', ['--annuler' => true, '--appliquer' => true])->assertSuccessful();

        $this->assertSame(['Gestionnaire'], $simple->fresh()->getRoleNames()->all(), 'Attribution de l\'opération annulée');
        $this->assertEqualsCanonicalizing(['ValidateurCommande', 'Demandeur'], $retouche->fresh()->getRoleNames()->all(),
            'L\'affectation ultérieure de l\'administrateur est conservée');
        $this->assertDatabaseHas('historique_roles', ['user_id' => $simple->id, 'role' => 'Demandeur', 'action' => 'retire',
            'origine' => AttribuerDemandeurParDefaut::ORIGINE_ANNULATION]);
        $this->assertEqualsCanonicalizing(['Admin', 'Demandeur'], $admin->fresh()->getRoleNames()->all());
    }
}
