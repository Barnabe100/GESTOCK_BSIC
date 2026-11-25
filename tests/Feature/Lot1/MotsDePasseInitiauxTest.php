<?php

namespace Tests\Feature\Lot1;

use App\Models\Agence;
use App\Models\Departement;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

/**
 * L1-5 : mots de passe par défaut sortis du code (configuration serveur),
 * procédure de création / import inchangée, commande de rotation.
 *
 * Les valeurs utilisées ici sont fictives (données de test).
 */
class MotsDePasseInitiauxTest extends TestCase
{
    use RefreshDatabase;

    private const INITIAL = 'Initial#Test2026';
    private const EXPOSEE_A = 'Ancien#ExposeA1';
    private const EXPOSEE_B = 'Ancien#ExposeB2';
    private const QUESTION_1 = 'Valeur exposée n°1 (laisser vide pour terminer)';
    private const QUESTION_2 = 'Valeur exposée n°2 (laisser vide pour terminer)';
    private const QUESTION_3 = 'Valeur exposée n°3 (laisser vide pour terminer)';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function administrateur(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        return $admin;
    }

    private function fichierImport(string $contenu): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('utilisateurs.csv', $contenu);
    }

    private function csvAvecRoles(Agence $agence): string
    {
        return "name,prenom,email,agence_id,departement_id,service_id,superieur,roles\n"
            ."DIALLO,Awa,awa.diallo@example.com,{$agence->id},,,0,Gestionnaire;Demandeur\n"
            ."MENSAH,Kodjo,kodjo.mensah@example.com,{$agence->id},,,0,\n";
    }

    // ------------------------------------------------------------------
    // Import d'utilisateurs
    // ------------------------------------------------------------------

    public function test_import_avec_mot_de_passe_configure_procedure_inchangee(): void
    {
        config(['gestok.mot_de_passe_initial' => self::INITIAL]);
        $agence = Agence::factory()->create();

        $this->actingAs($this->administrateur())
            ->post('/users-import', ['file' => $this->fichierImport($this->csvAvecRoles($agence))])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'Import effectué avec succès.');

        foreach (['awa.diallo@example.com', 'kodjo.mensah@example.com'] as $email) {
            $user = User::where('email', $email)->firstOrFail();
            $this->assertTrue(Hash::check(self::INITIAL, $user->password), "Mot de passe initial attendu pour {$email}");
            $this->assertTrue((bool) $user->force_password_change);
            $this->assertFalse((bool) $user->bloque);
        }
    }

    public function test_non_regression_import_attribue_les_roles_du_fichier_ou_demandeur(): void
    {
        config(['gestok.mot_de_passe_initial' => self::INITIAL]);
        $agence = Agence::factory()->create();

        $this->actingAs($this->administrateur())
            ->post('/users-import', ['file' => $this->fichierImport($this->csvAvecRoles($agence))]);

        $this->assertTrue(User::where('email', 'awa.diallo@example.com')->first()->hasAllRoles(['Gestionnaire', 'Demandeur']));
        $this->assertSame(['Demandeur'], User::where('email', 'kodjo.mensah@example.com')->first()->getRoleNames()->all());
    }

    public function test_import_sans_mot_de_passe_configure_est_annule_sans_creation(): void
    {
        config(['gestok.mot_de_passe_initial' => null]);
        $agence = Agence::factory()->create();
        $avant = User::count();

        $this->actingAs($this->administrateur())
            ->from('/users-import')
            ->post('/users-import', ['file' => $this->fichierImport($this->csvAvecRoles($agence))])
            ->assertRedirect('/users-import')
            ->assertSessionHas('error', "Import annulé : le mot de passe initial n'est pas configuré sur le serveur (GESTOK_MOT_DE_PASSE_INITIAL).");

        $this->assertSame($avant + 1, User::count()); // seul l'administrateur créé par le test
        $this->assertDatabaseMissing('users', ['email' => 'awa.diallo@example.com']);
    }

    public function test_le_message_d_annulation_s_affiche_dans_le_bandeau_existant(): void
    {
        config(['gestok.mot_de_passe_initial' => '']);
        $agence = Agence::factory()->create();

        $this->actingAs($this->administrateur())
            ->from('/users-import')
            ->followingRedirects()
            ->post('/users-import', ['file' => $this->fichierImport($this->csvAvecRoles($agence))])
            ->assertOk()
            ->assertViewIs('users.import')
            ->assertSee(e("Import annulé : le mot de passe initial n'est pas configuré sur le serveur (GESTOK_MOT_DE_PASSE_INITIAL)."), false);
    }

    public function test_non_regression_creation_par_administrateur_inchangee(): void
    {
        config(['gestok.mot_de_passe_initial' => null]); // sans effet sur la création manuelle
        $agence = Agence::factory()->create();

        $this->actingAs($this->administrateur())
            ->post('/users', [
                'name' => 'SOW',
                'prenom' => 'Fatou',
                'email' => 'fatou.sow@example.com',
                'password' => 'Saisi#Admin2026',
                'password_confirmation' => 'Saisi#Admin2026',
                'agence_id' => $agence->id,
            ])
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'fatou.sow@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('Saisi#Admin2026', $user->password));
        $this->assertTrue((bool) $user->force_password_change);
    }

    // ------------------------------------------------------------------
    // Seeder d'installation
    // ------------------------------------------------------------------

    private function referentielInstallation(): void
    {
        Agence::factory()->create(['id' => 1]);
        Departement::factory()->create(['id' => 1, 'agence_id' => 1]);
        Service::factory()->create(['id' => 1, 'departement_id' => 1]);
    }

    public function test_seeder_sans_mot_de_passe_configure_s_arrete(): void
    {
        config(['gestok.seed_mot_de_passe' => null]);
        $this->referentielInstallation();

        $this->expectException(RuntimeException::class);
        $this->seed(UserSeeder::class);
    }

    public function test_seeder_cree_les_comptes_avec_changement_obligatoire_et_reste_rejouable(): void
    {
        config(['gestok.seed_mot_de_passe' => 'Seed#Install2026']);
        $this->referentielInstallation();

        $this->seed(UserSeeder::class);
        $this->seed(UserSeeder::class); // seconde exécution : ni erreur ni doublon

        $this->assertSame(1, User::where('email', 'superadmin@test.com')->count());
        $this->assertSame(1, User::where('email', 'admin@test.com')->count());

        $superAdmin = User::where('email', 'superadmin@test.com')->first();
        $admin = User::where('email', 'admin@test.com')->first();

        $this->assertTrue($superAdmin->hasRole('SuperAdmin'));
        $this->assertTrue($admin->hasRole('Admin'));

        foreach ([$superAdmin, $admin] as $compte) {
            $this->assertTrue(Hash::check('Seed#Install2026', $compte->password));
            $this->assertTrue((bool) $compte->force_password_change);
        }
    }

    // ------------------------------------------------------------------
    // Commande de rotation
    // ------------------------------------------------------------------

    /**
     * @return array{0: User, 1: User, 2: User}
     */
    private function comptesDeTest(): array
    {
        return [
            User::factory()->create(['email' => 'expose.a@example.com', 'password' => Hash::make(self::EXPOSEE_A)]),
            User::factory()->create(['email' => 'expose.b@example.com', 'password' => Hash::make(self::EXPOSEE_B)]),
            User::factory()->create(['email' => 'sain@example.com', 'password' => Hash::make('Personnel#Choisi9')]),
        ];
    }

    public function test_rotation_en_simulation_par_defaut_ne_modifie_rien(): void
    {
        config(['gestok.mot_de_passe_initial' => self::INITIAL]);
        [$a, $b, $sain] = $this->comptesDeTest();
        $empreintes = [$a->password, $b->password, $sain->password];

        $this->artisan('gestok:mots-de-passe-exposes')
            ->expectsQuestion(self::QUESTION_1, self::EXPOSEE_A)
            ->expectsQuestion(self::QUESTION_2, self::EXPOSEE_B)
            ->expectsQuestion(self::QUESTION_3, '')
            ->expectsOutputToContain('Mode SIMULATION')
            ->expectsOutputToContain('2 compte(s) utilise(nt) encore une valeur exposée')
            ->expectsOutputToContain('Simulation terminée : aucune modification')
            ->assertSuccessful();

        $this->assertSame($empreintes, [$a->fresh()->password, $b->fresh()->password, $sain->fresh()->password]);
        $this->assertFalse((bool) $a->fresh()->force_password_change);
    }

    public function test_rotation_appliquee_apres_confirmation_ne_touche_que_les_comptes_exposes(): void
    {
        config(['gestok.mot_de_passe_initial' => self::INITIAL]);
        [$a, $b, $sain] = $this->comptesDeTest();
        $empreinteSain = $sain->password;
        $jetonA = $a->remember_token;

        $this->artisan('gestok:mots-de-passe-exposes', ['--appliquer' => true])
            ->expectsQuestion(self::QUESTION_1, self::EXPOSEE_A)
            ->expectsQuestion(self::QUESTION_2, self::EXPOSEE_B)
            ->expectsQuestion(self::QUESTION_3, '')
            ->expectsConfirmation('Attribuer le nouveau mot de passe initial à ces 2 compte(s), avec changement obligatoire à la prochaine connexion ?', 'yes')
            ->expectsOutputToContain('Rotation appliquée à 2 compte(s)')
            ->assertSuccessful();

        foreach ([$a, $b] as $compte) {
            $compte->refresh();
            $this->assertTrue(Hash::check(self::INITIAL, $compte->password));
            $this->assertTrue((bool) $compte->force_password_change);
        }
        $this->assertNotSame($jetonA, $a->remember_token);
        $this->assertFalse(Hash::check(self::EXPOSEE_A, $a->password));

        $this->assertSame($empreinteSain, $sain->fresh()->password);
        $this->assertFalse((bool) $sain->fresh()->force_password_change);
    }

    public function test_rotation_refusee_a_la_confirmation_ne_modifie_rien(): void
    {
        config(['gestok.mot_de_passe_initial' => self::INITIAL]);
        [$a] = $this->comptesDeTest();
        $empreinte = $a->password;

        $this->artisan('gestok:mots-de-passe-exposes', ['--appliquer' => true])
            ->expectsQuestion(self::QUESTION_1, self::EXPOSEE_A)
            ->expectsQuestion(self::QUESTION_2, '')
            ->expectsConfirmation('Attribuer le nouveau mot de passe initial à ces 1 compte(s), avec changement obligatoire à la prochaine connexion ?', 'no')
            ->expectsOutputToContain('Rotation annulée : aucune modification')
            ->assertSuccessful();

        $this->assertSame($empreinte, $a->fresh()->password);
    }

    public function test_rotation_impossible_sans_nouveau_mot_de_passe_configure(): void
    {
        config(['gestok.mot_de_passe_initial' => null]);
        [$a] = $this->comptesDeTest();
        $empreinte = $a->password;

        $this->artisan('gestok:mots-de-passe-exposes', ['--appliquer' => true])
            ->expectsQuestion(self::QUESTION_1, self::EXPOSEE_A)
            ->expectsQuestion(self::QUESTION_2, '')
            ->assertFailed();

        $this->assertSame($empreinte, $a->fresh()->password);
    }

    public function test_rotation_refuse_un_nouveau_mot_de_passe_identique_a_une_valeur_exposee(): void
    {
        config(['gestok.mot_de_passe_initial' => self::EXPOSEE_A]);
        [$a] = $this->comptesDeTest();
        $empreinte = $a->password;

        $this->artisan('gestok:mots-de-passe-exposes', ['--appliquer' => true])
            ->expectsQuestion(self::QUESTION_1, self::EXPOSEE_A)
            ->expectsQuestion(self::QUESTION_2, '')
            ->assertFailed();

        $this->assertSame($empreinte, $a->fresh()->password);
    }

    public function test_rotation_sans_valeur_saisie_est_annulee(): void
    {
        $this->artisan('gestok:mots-de-passe-exposes')
            ->expectsQuestion(self::QUESTION_1, '')
            ->expectsOutputToContain('Aucune valeur saisie')
            ->assertFailed();
    }
}
