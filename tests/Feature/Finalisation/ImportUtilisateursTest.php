<?php

namespace Tests\Feature\Finalisation;

use App\Models\Agence;
use App\Models\HistoriqueSuperieur;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Import des utilisateurs : contrôles complets avant écriture, erreurs
 * présentées ligne par ligne, tout ou rien, désignation officielle des
 * supérieurs, droits vérifiés côté serveur.
 */
class ImportUtilisateursTest extends TestCase
{
    use RefreshDatabase;

    private const ENTETE = "name,prenom,email,agence_id,departement_id,service_id,superieur,roles\n";

    private User $admin;

    private Agence $agence;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['gestok.mot_de_passe_initial' => 'Initial#Import2026']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');
        $this->agence = Agence::factory()->create();
    }

    private function importer(string $lignes)
    {
        return $this->actingAs($this->admin)->from('/users-import')->post(route('users.import'),
            ['file' => UploadedFile::fake()->createWithContent('utilisateurs.csv', self::ENTETE.$lignes)]);
    }

    private function nombreDeComptes(): int
    {
        return User::count();
    }

    public function test_cellules_obligatoires_vides_ou_invalides_erreurs_par_ligne_sans_500(): void
    {
        $avant = $this->nombreDeComptes();
        $a = $this->agence->id;

        $this->importer(",Awa,awa@ex.test,$a,,,0,\nDIALLO,,,$a,,,0,\nMENSAH,Kodjo,pas-un-email,$a,,,peut-etre,\n")
            ->assertRedirect('/users-import')
            ->assertSessionHasErrors(['import' => 'Ligne 2 : la cellule « name » est obligatoire.']);

        $erreurs = session('errors')->get('import');
        $this->assertContains('Ligne 3 : la cellule « prenom » est obligatoire.', $erreurs);
        $this->assertContains('Ligne 3 : la cellule « email » est obligatoire.', $erreurs);
        $this->assertContains('Ligne 4 : l\'adresse e-mail « pas-un-email » est invalide.', $erreurs);
        $this->assertContains('Ligne 4 : la cellule « superieur » doit valoir 1 ou 0 (oui ou non).', $erreurs);
        $this->assertSame($avant, $this->nombreDeComptes());
    }

    public function test_donnees_invalides_entites_inexistantes(): void
    {
        $this->importer("A,A,a@ex.test,99999,,,0,\nB,B,b@ex.test,abc,,,0,\n")
            ->assertSessionHasErrors('import');
        $erreurs = session('errors')->get('import');
        $this->assertContains('Ligne 2 : « agence_id » 99999 n\'existe pas.', $erreurs);
        $this->assertContains('Ligne 3 : la cellule « agence_id » doit être un identifiant numérique.', $erreurs);
        $this->assertNull(User::where('email', 'a@ex.test')->first());
    }

    public function test_doublons_dans_le_fichier_et_avec_les_comptes_existants(): void
    {
        $existant = User::factory()->create(['email' => 'deja@ex.test']);

        $this->importer("A,A,double@ex.test,,,,0,\nB,B,DOUBLE@ex.test,,,,0,\nC,C,Deja@ex.test,,,,0,\n")
            ->assertSessionHasErrors('import');
        $erreurs = session('errors')->get('import');
        $this->assertContains('Ligne 3 : l\'adresse e-mail « double@ex.test » figure déjà à la ligne 2.', $erreurs);
        $this->assertContains('Ligne 4 : un compte existe déjà avec l\'adresse e-mail « deja@ex.test ».', $erreurs);
        $this->assertNull(User::where('email', 'double@ex.test')->first());
        $this->assertSame(1, User::where('email', 'deja@ex.test')->count());
        $this->assertSame($existant->id, User::where('email', 'deja@ex.test')->value('id'));
    }

    public function test_atomicite_une_ligne_invalide_annule_tout_l_import(): void
    {
        $a = $this->agence->id;
        $this->importer("A,A,a@ex.test,$a,,,0,Demandeur\nB,B,b@ex.test,$a,,,0,Demandeur\nC,,c@ex.test,$a,,,0,Demandeur\n")
            ->assertSessionHasErrors(['import' => 'Ligne 4 : la cellule « prenom » est obligatoire.']);
        $this->assertSame(0, User::whereIn('email', ['a@ex.test', 'b@ex.test', 'c@ex.test'])->count());
    }

    public function test_superieur_du_fichier_passe_par_la_designation_officielle(): void
    {
        $a = $this->agence->id;
        $this->importer("S1,P,s1@ex.test,$a,,,1,Demandeur\nS2,P,s2@ex.test,$a,,,oui,Demandeur\n")
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'Import effectué avec succès.');

        foreach (['s1@ex.test', 's2@ex.test'] as $email) {
            $u = User::where('email', $email)->firstOrFail();
            $this->assertTrue((bool) $u->superieur);
            $this->assertTrue($u->hasRole('Superieur'));
            $this->assertSame(1, HistoriqueSuperieur::where('nouveau_superieur_id', $u->id)->count());
            $this->assertDatabaseHas('historique_roles', ['user_id' => $u->id, 'role' => 'Demandeur', 'origine' => 'import', 'effectue_par' => $this->admin->id]);
        }

        // Troisième supérieur pour la même entité : refusé, rien n'est créé.
        $this->importer("N,P,n@ex.test,$a,,,0,\nS3,P,s3@ex.test,$a,,,1,\n")
            ->assertSessionHasErrors(['import' => 'Ligne 3 : Deux supérieurs sont déjà désignés pour cette entité. Indiquez le supérieur remplacé.']);
        $this->assertSame(0, User::whereIn('email', ['n@ex.test', 's3@ex.test'])->count());

        // Rôle Superieur demandé dans la colonne des rôles sans l'indicateur : ignoré.
        $this->importer("R,P,r@ex.test,$a,,,0,Superieur;Demandeur\n");
        $this->assertFalse(User::where('email', 'r@ex.test')->firstOrFail()->hasRole('Superieur'));
    }

    public function test_import_reserve_et_role_reserve_refuse(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole('Gestionnaire');
        $this->actingAs($gestionnaire)->post(route('users.import'),
            ['file' => UploadedFile::fake()->createWithContent('u.csv', self::ENTETE."A,A,a@ex.test,,,,0,\n")])->assertForbidden();

        $this->importer("A,A,a@ex.test,,,,0,SuperAdmin\n")->assertForbidden();
        $this->assertNull(User::where('email', 'a@ex.test')->first());
    }

    public function test_fichier_xlsx_reel_avec_cellule_vide_sans_erreur_500(): void
    {
        $classeur = new Spreadsheet();
        $feuille = $classeur->getActiveSheet();
        $feuille->fromArray(['name', 'prenom', 'email', 'agence_id', 'departement_id', 'service_id', 'superieur', 'roles'], null, 'A1');
        $feuille->fromArray(['OUEDRAOGO', 'Ali', 'ali@ex.test', $this->agence->id], null, 'A2'); // superieur et roles vides
        $feuille->fromArray(['KABORE', 'Mariam', 'mariam@ex.test', $this->agence->id, null, null, 0, 'Gestionnaire'], null, 'A3');
        $chemin = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new Xlsx($classeur))->save($chemin);

        $this->actingAs($this->admin)->post(route('users.import'), ['file' => new UploadedFile($chemin, 'utilisateurs.xlsx', null, null, true)])
            ->assertRedirect(route('users.index'));
        @unlink($chemin);

        $ali = User::where('email', 'ali@ex.test')->firstOrFail();
        $this->assertFalse((bool) $ali->superieur);
        $this->assertSame(['Demandeur'], $ali->getRoleNames()->all());
        $this->assertTrue(User::where('email', 'mariam@ex.test')->firstOrFail()->hasRole('Gestionnaire'));
    }

    public function test_fichier_vide_message_maitrise(): void
    {
        $this->importer('')->assertSessionHasErrors(['import' => 'Le fichier ne contient aucune ligne à importer.']);
    }
}
