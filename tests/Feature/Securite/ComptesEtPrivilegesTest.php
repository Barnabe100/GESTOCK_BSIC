<?php

namespace Tests\Feature\Securite;

use App\Models\Agence;
use App\Models\Demande;
use App\Models\HistoriqueSuperieur;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Socle des droits : gestion des comptes et des privilèges.
 * Toutes les requêtes sont des appels HTTP directs (formulaires contournés).
 */
class ComptesEtPrivilegesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['gestok.mot_de_passe_initial' => 'Initial#Socle2026']);
    }

    private function compte(string|array $roles, array $attr = []): User
    {
        $u = User::factory()->create($attr);
        $u->assignRole($roles);

        return $u;
    }

    private function fichierCsv(string $contenu): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('utilisateurs.csv', $contenu);
    }

    // ---------------- Élévation de privilèges ----------------

    public function test_un_demandeur_ne_peut_pas_creer_de_compte_ni_devenir_superadmin(): void
    {
        $dem = $this->compte('Demandeur');
        $autre = $this->compte('Demandeur');

        $this->actingAs($dem)->post(route('users.store'), ['name' => 'X', 'prenom' => 'Y', 'email' => 'complice@ex.test',
            'password' => 'Complice#2026', 'password_confirmation' => 'Complice#2026'])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'complice@ex.test']);

        $this->actingAs($dem)->put(route('users.update', $autre->id), ['name' => 'X', 'prenom' => 'Y', 'email' => $autre->email,
            'roles' => ['SuperAdmin']])->assertForbidden();
        $this->assertFalse($autre->fresh()->hasRole('SuperAdmin'));
    }

    public function test_un_demandeur_ne_peut_pas_s_attribuer_superadmin_par_son_profil(): void
    {
        $dem = $this->compte('Demandeur', ['password' => Hash::make('Ancien#Motdp1')]);

        $this->actingAs($dem)->put(route('users.update', $dem->id), ['name' => 'X', 'prenom' => 'Y', 'email' => $dem->email,
            'roles' => ['SuperAdmin'], 'superieur' => 1]);

        $dem->refresh();
        $this->assertSame(['Demandeur'], $dem->getRoleNames()->all());
        $this->assertFalse((bool) $dem->superieur);
    }

    public function test_un_gestionnaire_ne_peut_ni_s_attribuer_de_privileges_ni_modifier_un_autre_compte(): void
    {
        $gest = $this->compte('Gestionnaire');
        $autre = $this->compte('Demandeur');

        $this->actingAs($gest)->put(route('users.update', $gest->id), ['roles' => ['Admin', 'SuperAdmin']]);
        $this->assertSame(['Gestionnaire'], $gest->fresh()->getRoleNames()->all());

        $this->actingAs($gest)->put(route('users.update', $autre->id), ['name' => 'X', 'prenom' => 'Y', 'email' => $autre->email,
            'roles' => ['Gestionnaire']])->assertForbidden();
        $this->assertSame(['Demandeur'], $autre->fresh()->getRoleNames()->all());
    }

    public function test_prise_de_controle_d_un_superadmin_impossible(): void
    {
        $admin = $this->compte('Admin');
        $dem = $this->compte('Demandeur');
        $superAdmin = $this->compte('SuperAdmin', ['email' => 'sa@ex.test']);

        foreach ([$dem, $admin] as $acteur) {
            $this->actingAs($acteur)->put(route('users.update', $superAdmin->id), ['name' => 'A', 'prenom' => 'B',
                'email' => 'pirate@ex.test', 'password' => 'Pirate#20261', 'password_confirmation' => 'Pirate#20261',
                'roles' => ['SuperAdmin']])->assertForbidden();
        }

        $superAdmin->refresh();
        $this->assertSame('sa@ex.test', $superAdmin->email);
        $this->assertFalse(Hash::check('Pirate#20261', $superAdmin->password));
    }

    public function test_seul_un_superadmin_attribue_le_role_superadmin(): void
    {
        $admin = $this->compte('Admin');
        $superAdmin = $this->compte('SuperAdmin');
        $cible = $this->compte('Demandeur');

        $this->actingAs($admin)->put(route('users.update', $cible->id), ['name' => 'X', 'prenom' => 'Y', 'email' => $cible->email,
            'roles' => ['SuperAdmin']])->assertForbidden();
        $this->assertFalse($cible->fresh()->hasRole('SuperAdmin'));

        $this->actingAs($superAdmin)->put(route('users.update', $cible->id), ['name' => 'X', 'prenom' => 'Y', 'email' => $cible->email,
            'roles' => ['SuperAdmin']])->assertRedirect(route('users.index'));
        $this->assertTrue($cible->fresh()->hasRole('SuperAdmin'));
    }

    public function test_role_inconnu_refuse(): void
    {
        $admin = $this->compte('Admin');
        $cible = $this->compte('Demandeur');

        $this->actingAs($admin)->from('/users')->put(route('users.update', $cible->id), ['name' => 'X', 'prenom' => 'Y',
            'email' => $cible->email, 'roles' => ['RoleInexistant']])->assertSessionHasErrors('roles.0');
        $this->assertSame(['Demandeur'], $cible->fresh()->getRoleNames()->all());
    }

    // ---------------- Parcours légitimes de l'administrateur ----------------

    public function test_un_admin_cree_un_compte_et_attribue_un_role_non_privilegie(): void
    {
        $admin = $this->compte('Admin');

        $this->actingAs($admin)->post(route('users.store'), ['name' => 'N', 'prenom' => 'P', 'email' => 'nouveau@ex.test',
            'password' => 'Provisoire1', 'password_confirmation' => 'Provisoire1'])->assertRedirect(route('users.index'));
        $nouveau = User::where('email', 'nouveau@ex.test')->firstOrFail();
        $this->assertSame(['Demandeur'], $nouveau->getRoleNames()->all());

        $this->actingAs($admin)->put(route('users.update', $nouveau->id), ['name' => 'N', 'prenom' => 'P', 'email' => 'nouveau@ex.test',
            'roles' => ['Demandeur', 'Gestionnaire']])->assertRedirect(route('users.index'));
        $this->assertEqualsCanonicalizing(['Demandeur', 'Gestionnaire'], $nouveau->fresh()->getRoleNames()->all());
    }

    public function test_le_role_superieur_n_est_plus_perdu_sans_autre_case_cochee(): void
    {
        $admin = $this->compte('Admin');
        $u = $this->compte('Gestionnaire', ['agence_id' => Agence::factory()->create()->id]);

        $this->actingAs($admin)->put(route('users.update', $u->id), ['name' => 'A', 'prenom' => 'B', 'email' => $u->email,
            'agence_id' => $u->agence_id, 'superieur' => 1])->assertRedirect(route('users.index'));

        $u->refresh();
        $this->assertTrue((bool) $u->superieur);
        $this->assertSame(['Superieur'], $u->getRoleNames()->all());
    }

    // ---------------- Supérieur remplaçant (décision validée) ----------------

    public function test_designer_un_remplacant_retire_la_fonction_au_precedent_avec_historique(): void
    {
        $admin = $this->compte('Admin');
        $agence = Agence::factory()->create();
        $ancien = $this->compte(['Demandeur', 'Superieur'], ['agence_id' => $agence->id, 'superieur' => 1]);
        $remplacant = $this->compte('Demandeur', ['agence_id' => $agence->id]);

        $this->actingAs($admin)->put(route('users.update', $remplacant->id), ['name' => 'R', 'prenom' => 'P', 'email' => $remplacant->email,
            'agence_id' => $agence->id, 'superieur' => 1, 'roles' => ['Demandeur']])->assertRedirect(route('users.index'));

        $this->assertFalse((bool) $ancien->fresh()->superieur);
        $this->assertFalse($ancien->fresh()->hasRole('Superieur'));
        $this->assertTrue((bool) $remplacant->fresh()->superieur);
        $this->assertTrue($remplacant->fresh()->hasRole('Superieur'));
        $this->assertDatabaseHas('historique_superieurs', ['entite_type' => 'agence', 'entite_id' => $agence->id,
            'ancien_superieur_id' => $ancien->id, 'nouveau_superieur_id' => $remplacant->id, 'designe_par' => $admin->id]);

        // Retrait de la fonction : tracé également.
        $this->actingAs($admin)->put(route('users.update', $remplacant->id), ['name' => 'R', 'prenom' => 'P', 'email' => $remplacant->email,
            'agence_id' => $agence->id, 'roles' => ['Demandeur']]);
        $this->assertFalse($remplacant->fresh()->hasRole('Superieur'));
        $this->assertSame(1, HistoriqueSuperieur::where('ancien_superieur_id', $remplacant->id)->whereNull('nouveau_superieur_id')->count());
    }

    public function test_creer_un_superieur_dans_une_entite_pourvue_le_designe_remplacant(): void
    {
        $admin = $this->compte('Admin');
        $agence = Agence::factory()->create();
        $ancien = $this->compte(['Demandeur', 'Superieur'], ['agence_id' => $agence->id, 'superieur' => 1]);

        $this->actingAs($admin)->post(route('users.store'), ['name' => 'N', 'prenom' => 'P', 'email' => 'remplacant@ex.test',
            'password' => 'Provisoire1', 'password_confirmation' => 'Provisoire1', 'agence_id' => $agence->id, 'superieur' => 1])
            ->assertRedirect(route('users.index'));

        $nouveau = User::where('email', 'remplacant@ex.test')->firstOrFail();
        $this->assertTrue($nouveau->hasRole('Superieur'));
        $this->assertFalse($ancien->fresh()->hasRole('Superieur'));
        $this->assertDatabaseHas('historique_superieurs', ['ancien_superieur_id' => $ancien->id, 'nouveau_superieur_id' => $nouveau->id]);
    }

    // ---------------- Blocage ----------------

    public function test_blocage_reserve_a_l_administration_et_jamais_sur_soi_ou_un_superadmin(): void
    {
        $dem = $this->compte('Demandeur');
        $admin = $this->compte('Admin');
        $superAdmin = $this->compte('SuperAdmin');
        $cible = $this->compte('Gestionnaire');

        $this->actingAs($dem)->get(route('users.bloquer', $cible->id))->assertForbidden();
        $this->actingAs($admin)->get(route('users.bloquer', $admin->id))->assertForbidden();
        $this->actingAs($admin)->get(route('users.bloquer', $superAdmin->id))->assertForbidden();
        $this->assertFalse((bool) $cible->fresh()->bloque);

        $this->actingAs($admin)->from('/users')->get(route('users.bloquer', $cible->id))->assertRedirect('/users');
        $this->assertTrue((bool) $cible->fresh()->bloque);
        $this->actingAs($admin)->from('/users')->get(route('users.debloquer', $cible->id));
        $this->assertFalse((bool) $cible->fresh()->bloque);
    }

    public function test_blocage_par_requete_intersite_refuse(): void
    {
        $admin = $this->compte('Admin');
        $cible = $this->compte('Gestionnaire');

        $this->actingAs($admin)->withHeaders(['Sec-Fetch-Site' => 'cross-site'])->get(route('users.bloquer', $cible->id))->assertForbidden();
        $this->actingAs($admin)->withHeaders(['Referer' => 'https://site-tiers.example/page'])->get(route('users.bloquer', $cible->id))->assertForbidden();
        $this->assertFalse((bool) $cible->fresh()->bloque);

        $this->actingAs($admin)->withHeaders(['Sec-Fetch-Site' => 'same-origin'])->get(route('users.bloquer', $cible->id));
        $this->assertTrue((bool) $cible->fresh()->bloque);
    }

    public function test_un_compte_bloque_perd_sa_session_et_ne_peut_plus_se_connecter(): void
    {
        $u = $this->compte('Gestionnaire', ['email' => 'bloque@ex.test', 'password' => Hash::make('Correct#Motdp1')]);
        $this->actingAs($u)->get('/dashboard')->assertOk();

        $u->update(['bloque' => true]);
        $this->actingAs($u->fresh())->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Votre compte est bloqué, veuillez contacter votre administrateur.']);
        $this->assertGuest();

        $this->post('/login', ['email' => 'bloque@ex.test', 'password' => 'Correct#Motdp1'])
            ->assertSessionHasErrors(['email' => 'Votre compte est bloqué, veuillez contacter votre administrateur.']);
        $this->assertGuest();
    }

    public function test_un_mauvais_mot_de_passe_ne_revele_pas_qu_un_compte_est_bloque(): void
    {
        $this->compte('Demandeur', ['email' => 'bloque@ex.test', 'bloque' => true]);

        $this->post('/login', ['email' => 'bloque@ex.test', 'password' => 'mauvais'])
            ->assertSessionHasErrors(['email' => 'Email ou mot de passe incorrect.']);
    }

    // ---------------- Suppression ----------------

    public function test_suppression_jamais_de_soi_ni_d_un_compte_avec_historique(): void
    {
        $admin = $this->compte('Admin');
        $avecHistorique = $this->compte('Demandeur');
        Demande::factory()->create(['user_id' => $avecHistorique->id]);
        $sansHistorique = $this->compte('Demandeur');

        $this->actingAs($admin)->delete(route('users.destroy', $admin->id))->assertForbidden();
        $this->actingAs($admin)->delete(route('users.destroy', $avecHistorique->id))->assertForbidden();
        $this->assertNotNull($avecHistorique->fresh());
        $this->assertSame(1, Demande::where('user_id', $avecHistorique->id)->count());

        $this->actingAs($admin)->delete(route('users.destroy', $sansHistorique->id))->assertRedirect(route('users.index'));
        $this->assertNull($sansHistorique->fresh());

        $this->actingAs($this->compte('Demandeur'))->delete(route('users.destroy', $admin->id))->assertForbidden();
    }

    // ---------------- Annuaire, exports, import ----------------

    public function test_annuaire_exports_et_import_reserves_a_l_administration(): void
    {
        $dem = $this->compte('Demandeur');
        foreach (['users.index', 'users.create', 'users.export.csv', 'users.export.excel', 'users.import.form'] as $route) {
            $this->actingAs($dem)->get(route($route))->assertForbidden();
        }
        $this->actingAs($dem)->get(route('users.show', $this->compte('Admin')->id))->assertForbidden();
        $this->actingAs($dem)->post(route('users.import'), ['file' => $this->fichierCsv("name,prenom,email,agence_id,departement_id,service_id,superieur,roles\nI,M,imp@ex.test,,,,0,Demandeur\n")])
            ->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'imp@ex.test']);
    }

    public function test_import_tout_ou_rien_et_roles_controles(): void
    {
        $admin = $this->compte('Admin');
        $entete = "name,prenom,email,agence_id,departement_id,service_id,superieur,roles\n";

        $this->actingAs($admin)->post(route('users.import'), ['file' => $this->fichierCsv($entete
            ."A,A,a@ex.test,,,,0,Demandeur\nB,B,b@ex.test,,,,0,SuperAdmin\n")])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'a@ex.test']);
        $this->assertDatabaseMissing('users', ['email' => 'b@ex.test']);

        $this->actingAs($admin)->post(route('users.import'), ['file' => $this->fichierCsv($entete."C,C,c@ex.test,,,,0,RoleInconnu\n")])
            ->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'c@ex.test']);

        $this->actingAs($admin)->post(route('users.import'), ['file' => $this->fichierCsv($entete."D,D,d@ex.test,,,,0,Gestionnaire;Demandeur\n")])
            ->assertRedirect(route('users.index'));
        $this->assertEqualsCanonicalizing(['Gestionnaire', 'Demandeur'], User::where('email', 'd@ex.test')->firstOrFail()->getRoleNames()->all());

        $superAdmin = $this->compte('SuperAdmin');
        $this->actingAs($superAdmin)->post(route('users.import'), ['file' => $this->fichierCsv($entete."E,E,e@ex.test,,,,0,SuperAdmin\n")])
            ->assertRedirect(route('users.index'));
        $this->assertTrue(User::where('email', 'e@ex.test')->firstOrFail()->hasRole('SuperAdmin'));
    }

    public function test_import_refuse_un_fichier_qui_n_est_pas_un_tableur(): void
    {
        $admin = $this->compte('Admin');

        $this->actingAs($admin)->from('/users-import')->post(route('users.import'), [
            'file' => UploadedFile::fake()->create('script.php', 1, 'application/x-php'),
        ])->assertSessionHasErrors('file');
    }

    // ---------------- Mot de passe ----------------

    public function test_le_changement_de_mot_de_passe_applique_la_politique_et_leve_l_obligation(): void
    {
        $u = $this->compte('Demandeur', ['password' => Hash::make('Ancien#Motdp1'), 'force_password_change' => true]);

        $this->actingAs($u)->put(route('password.update'), ['current_password' => 'Ancien#Motdp1', 'password' => 'faible12',
            'password_confirmation' => 'faible12'])->assertSessionHasErrorsIn('updatePassword', 'password');
        $this->assertTrue(Hash::check('Ancien#Motdp1', $u->fresh()->password));

        $this->actingAs($u->fresh())->put(route('password.update'), ['current_password' => 'Ancien#Motdp1', 'password' => 'Nouveau#Motdp2026',
            'password_confirmation' => 'Nouveau#Motdp2026']);
        $this->assertTrue(Hash::check('Nouveau#Motdp2026', $u->fresh()->password));
        $this->assertFalse((bool) $u->fresh()->force_password_change);
    }
}
