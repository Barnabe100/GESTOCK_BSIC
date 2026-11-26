<?php

namespace Tests\Feature\Finalisation;

use App\Models\Agence;
use App\Models\Departement;
use App\Models\HistoriqueEntite;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * N03 : type explicite SIEGE / AGENCE des entités organisationnelles.
 */
class StructureOrganisationnelleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');
    }

    private function creer(array $donnees)
    {
        return $this->actingAs($this->admin)->from(route('agences.create'))
            ->post(route('agences.store'), $donnees + ['code' => 'C'.uniqid(), 'nom' => 'Entité', 'lieu' => 'Ouaga']);
    }

    private function siegesActifs(): int
    {
        return Agence::where('type', 'SIEGE')->where('archiver', 0)->count();
    }

    public function test_creation_d_un_siege_et_de_plusieurs_agences(): void
    {
        $this->creer(['type' => 'SIEGE', 'nom' => 'Direction générale'])->assertRedirect(route('agences.index'));
        $this->creer(['type' => 'AGENCE', 'nom' => 'Agence Bobo'])->assertRedirect(route('agences.index'));
        $this->creer(['type' => 'AGENCE', 'nom' => 'Agence Koudougou'])->assertRedirect(route('agences.index'));

        $this->assertSame(1, $this->siegesActifs());
        $this->assertSame(2, Agence::where('type', 'AGENCE')->count());
        $this->assertDatabaseHas('historique_entites', ['action' => 'creation', 'nouveau_type' => 'SIEGE', 'effectue_par' => $this->admin->id]);
    }

    public function test_un_deuxieme_siege_actif_est_refuse(): void
    {
        $this->creer(['type' => 'SIEGE']);
        $agence = Agence::factory()->create();

        $this->creer(['type' => 'SIEGE', 'nom' => 'Second siège'])
            ->assertRedirect(route('agences.create'))
            ->assertSessionHas('error', 'Un siège actif existe déjà. Utilisez « Désigner comme siège » pour changer de siège.');

        $this->actingAs($this->admin)->from('/agences')->put(route('agences.update', $agence->id), ['code' => $agence->code, 'nom' => $agence->nom, 'type' => 'SIEGE'])
            ->assertSessionHas('error', 'Un siège actif existe déjà. Utilisez « Désigner comme siège » pour changer de siège.');

        $this->assertSame(1, $this->siegesActifs());
        $this->assertSame('AGENCE', $agence->fresh()->type);
    }

    public function test_la_base_refuse_un_second_siege_meme_hors_application(): void
    {
        Agence::factory()->siege()->create();

        $this->expectException(QueryException::class);
        DB::table('agences')->insert(['code' => 'DIRECT', 'nom' => 'Écriture directe', 'type' => 'SIEGE', 'archiver' => 0]);
    }

    public function test_type_invalide_refuse(): void
    {
        $this->creer(['type' => 'AUTRE'])->assertSessionHasErrors('type');
        $this->assertSame(0, Agence::count());
    }

    public function test_renommage_sans_changement_de_type(): void
    {
        $siege = Agence::factory()->siege()->create(['nom' => 'Direction générale']);
        $agence = Agence::factory()->create(['nom' => 'Agence Bobo']);

        // Renommer une agence avec l'ancien nom du siège ne la rend pas siège.
        $this->actingAs($this->admin)->put(route('agences.update', $agence->id), ['code' => $agence->code, 'nom' => 'AGENCE PRINCIPALE SIEGE'])
            ->assertRedirect(route('agences.index'));
        $this->actingAs($this->admin)->put(route('agences.update', $siege->id), ['code' => $siege->code, 'nom' => 'Agence quelconque'])
            ->assertRedirect(route('agences.index'));

        $this->assertSame('AGENCE', $agence->fresh()->type);
        $this->assertSame('SIEGE', $siege->fresh()->type);
        $this->assertSame(0, HistoriqueEntite::where('action', 'changement_type')->count());
    }

    public function test_changement_de_siege_preserve_identifiants_et_rattachements(): void
    {
        $ancien = Agence::factory()->siege()->create();
        $nouveau = Agence::factory()->create();
        $dep = Departement::factory()->create(['agence_id' => $ancien->id]);
        $u = User::factory()->create(['agence_id' => $ancien->id, 'departement_id' => $dep->id]);

        $this->actingAs($this->admin)->patch(route('agences.designer.siege', $nouveau->id))->assertRedirect(route('agences.index'));

        $this->assertSame('AGENCE', $ancien->fresh()->type);
        $this->assertSame('SIEGE', $nouveau->fresh()->type);
        $this->assertSame(1, $this->siegesActifs());
        $this->assertSame($ancien->id, (int) $u->fresh()->agence_id);
        $this->assertSame($dep->id, (int) $u->fresh()->departement_id);
        $this->assertSame($ancien->id, (int) $dep->fresh()->agence_id);
        $this->assertSame(2, HistoriqueEntite::where('action', 'changement_siege')->count());
    }

    public function test_desactivation_du_siege_conserve_type_et_rattachements(): void
    {
        $siege = Agence::factory()->siege()->create();
        $dep = Departement::factory()->create(['agence_id' => $siege->id]);
        $u = User::factory()->create(['agence_id' => $siege->id, 'departement_id' => $dep->id]);

        $this->actingAs($this->admin)->delete(route('agences.destroy', $siege->id))->assertRedirect(route('agences.index'));

        $this->assertSame(1, (int) $siege->fresh()->archiver);
        $this->assertSame('SIEGE', $siege->fresh()->type);
        $this->assertSame(0, $this->siegesActifs());
        $this->assertTrue($u->fresh()->estAuSiege());
        $this->assertSame(['departement', $dep->id], $u->fresh()->entiteDeValidation());
        $this->assertDatabaseHas('historique_entites', ['agence_id' => $siege->id, 'action' => 'desactivation_siege']);

        // Un nouveau siège actif peut ensuite être créé.
        $this->creer(['type' => 'SIEGE', 'nom' => 'Nouveau siège'])->assertRedirect(route('agences.index'));
        $this->assertSame(1, $this->siegesActifs());

        // Une entité archivée ne peut pas redevenir siège par « Désigner comme siège ».
        $this->actingAs($this->admin)->from('/agences')->patch(route('agences.designer.siege', $siege->id))
            ->assertSessionHas('error', 'Une entité archivée ne peut pas devenir le siège.');
    }

    public function test_modifications_reservees_a_la_gestion_des_agences(): void
    {
        $agence = Agence::factory()->create();
        $demandeur = User::factory()->create();
        $demandeur->assignRole('Demandeur');

        $this->actingAs($demandeur)->post(route('agences.store'), ['code' => 'X', 'nom' => 'X', 'type' => 'SIEGE'])->assertForbidden();
        $this->actingAs($demandeur)->patch(route('agences.designer.siege', $agence->id))->assertForbidden();
        $this->actingAs($demandeur)->put(route('agences.update', $agence->id), ['code' => 'X', 'nom' => 'X', 'type' => 'SIEGE'])->assertForbidden();
        $this->assertSame(0, $this->siegesActifs());
    }

    public function test_rattachement_fonde_sur_le_type_jamais_sur_le_nom(): void
    {
        $siege = Agence::factory()->siege()->create(['nom' => 'Direction générale']);
        $agenceNommeeSiege = Agence::factory()->create(['nom' => 'AGENCE PRINCIPALE SIEGE']);
        $nonQualifiee = Agence::factory()->create(['type' => null]);
        $dep = Departement::factory()->create(['agence_id' => $siege->id, 'nom' => 'Département Réseau']);

        $auSiege = User::factory()->create(['agence_id' => $siege->id, 'departement_id' => $dep->id]);
        $enAgence = User::factory()->create(['agence_id' => $agenceNommeeSiege->id, 'departement_id' => $dep->id]);
        $nonRattache = User::factory()->create(['agence_id' => $nonQualifiee->id]);

        $this->assertTrue($auSiege->estAuSiege());
        $this->assertSame(['departement', $dep->id], $auSiege->entiteDeValidation());
        $this->assertTrue($enAgence->estEnAgence());
        $this->assertSame(['agence', $agenceNommeeSiege->id], $enAgence->entiteDeValidation());
        $this->assertFalse($nonRattache->estAuSiege());
        $this->assertFalse($nonRattache->estEnAgence());
        $this->assertNull($nonRattache->entiteDeValidation());
    }

    public function test_diagnostic_en_lecture_seule_et_qualification_sur_decision(): void
    {
        $siegeHistorique = Agence::factory()->create(['nom' => 'AGENCE PRINCIPALE SIEGE', 'type' => null]);
        $agence = Agence::factory()->create(['nom' => 'Agence Bobo', 'type' => null]);
        $dep = Departement::factory()->create(['agence_id' => $siegeHistorique->id, 'nom' => 'Département Finances']);
        User::factory()->create(['agence_id' => $siegeHistorique->id, 'departement_id' => $dep->id]);
        User::factory()->create(['agence_id' => $agence->id]);
        $empreinte = fn () => json_encode([DB::table('agences')->get(), DB::table('users')->get(), DB::table('historique_entites')->count()]);
        $avant = $empreinte();

        $fichier = tempnam(sys_get_temp_dir(), 'diag');
        $this->artisan('gestok:organisation:diagnostic', ['--json' => $fichier])->assertSuccessful();
        $this->assertSame($avant, $empreinte(), 'Le diagnostic ne doit rien écrire.');
        $diag = json_decode(file_get_contents($fichier), true);
        unlink($fichier);
        $this->assertSame([$siegeHistorique->id], $diag['synthese']['candidats_siege']);
        $this->assertSame('un_candidat_a_confirmer', $diag['synthese']['organisation']);
        $this->assertSame(2, $diag['synthese']['non_qualifiees']);

        // Simulation : rien n'est écrit sans --appliquer.
        $this->artisan('gestok:organisation:qualifier', ['--siege' => $siegeHistorique->id, '--agences' => (string) $agence->id])->assertSuccessful();
        $this->assertSame($avant, $empreinte());

        // Aucune qualification sans identifiant explicite.
        $this->artisan('gestok:organisation:qualifier', ['--appliquer' => true])->assertFailed();

        $this->artisan('gestok:organisation:qualifier', ['--siege' => $siegeHistorique->id, '--agences' => (string) $agence->id, '--appliquer' => true])->assertSuccessful();
        $this->assertSame('SIEGE', $siegeHistorique->fresh()->type);
        $this->assertSame('AGENCE', $agence->fresh()->type);
        $this->assertSame(2, HistoriqueEntite::where('action', 'qualification')->count());
    }

    public function test_qualification_refusee_si_elle_cree_un_second_siege(): void
    {
        Agence::factory()->siege()->create();
        $autre = Agence::factory()->create(['type' => null]);

        $this->artisan('gestok:organisation:qualifier', ['--siege' => $autre->id, '--appliquer' => true])->assertFailed();
        $this->assertNull($autre->fresh()->type);
    }
}
