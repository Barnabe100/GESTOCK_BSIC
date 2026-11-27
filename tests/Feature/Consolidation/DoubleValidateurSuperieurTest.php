<?php

namespace Tests\Feature\Consolidation;

use App\Http\Controllers\DemandeController;
use App\Models\Agence;
use App\Models\Article;
use App\Models\ArticleDemande;
use App\Models\Demande;
use App\Models\Departement;
use App\Models\InterimValidation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Q3 (décision définitive) : deux validateurs supérieurs ALTERNATIFS par
 * département (siège) et par agence ; une seule validation ; jamais sur sa
 * propre demande ; supérieur seul (§4.3) : création permise, absence
 * signalée, remplaçant temporaire d'une autre entité explicite et historisé.
 */
class DoubleValidateurSuperieurTest extends TestCase
{
    use RefreshDatabase;

    private Agence $agence;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->agence = Agence::factory()->create();
        $this->admin = User::factory()->create(['agence_id' => $this->agence->id]);
        $this->admin->assignRole('Admin');
    }

    private function compte(string|array $roles, array $attr = []): User
    {
        $u = User::factory()->create(array_merge(['agence_id' => $this->agence->id], $attr));
        $u->assignRole($roles);

        return $u;
    }

    private function superieur(array $attr = []): User
    {
        return $this->compte(['Demandeur', 'Superieur'], ['superieur' => 1] + $attr);
    }

    private function demandeEnAttente(User $proprietaire, ?User $superieur = null): Demande
    {
        $d = Demande::factory()->create(['user_id' => $proprietaire->id, 'statut' => 'En attente supérieur', 'superieur_id' => $superieur?->id]);
        ArticleDemande::factory()->create(['demande_id' => $d->id, 'article_id' => Article::factory()->create()->id, 'quantite' => 2]);

        return $d->fresh();
    }

    private function valider(User $par, Demande $d)
    {
        return $this->actingAs($par)->from('/demandes-a-valider')->put(route('demandes.valider.superieur', $d->id));
    }

    // ---------------- Deux validateurs alternatifs ----------------

    public function test_validation_par_le_premier_puis_aucune_seconde_validation(): void
    {
        [$a, $b] = [$this->superieur(), $this->superieur()];
        $d = $this->demandeEnAttente($this->compte('Demandeur'), $a);

        $this->valider($a, $d)->assertSessionHas('success', 'Demande validée.');
        $date = $d->fresh()->date_validation_superieur;

        // Le second validateur ne peut pas valider une seconde fois.
        $this->valider($b, $d)->assertSessionHas('error', 'Demande déjà validée');
        $this->actingAs($b)->put(route('demandes.rejeter.superieur', $d->id))->assertForbidden();

        $d->refresh();
        $this->assertSame($a->id, (int) $d->superieur_id);
        $this->assertEquals($date, $d->date_validation_superieur);
        $this->assertSame('En attente gestionnaire', $d->statut);
    }

    public function test_validation_par_le_second_quand_le_premier_est_absent(): void
    {
        [$a, $b] = [$this->superieur(), $this->superieur()];
        $a->forceFill(['bloque' => true])->save(); // absent : compte bloqué
        $d = $this->demandeEnAttente($this->compte('Demandeur'), $a);

        $this->valider($b, $d)->assertSessionHas('success', 'Demande validée.');
        $this->assertSame($b->id, (int) $d->fresh()->superieur_id);
    }

    public function test_demande_du_premier_validee_par_le_second_jamais_par_lui_meme(): void
    {
        [$a, $b] = [$this->superieur(), $this->superieur()];

        // Création : la demande de A est adressée à B.
        $this->actingAs($a)->post(route('demandes.store'), ['objet' => 'Cartouches'])->assertRedirect(route('mes.demandes'));
        $d = Demande::where('user_id', $a->id)->firstOrFail();
        $this->assertSame($b->id, (int) $d->superieur_id);
        $d->forceFill(['statut' => 'En attente supérieur'])->save();

        $this->valider($a, $d)->assertForbidden();
        $this->actingAs($a)->put(route('demandes.rejeter.superieur', $d->id))->assertForbidden();
        $this->valider($b, $d)->assertSessionHas('success', 'Demande validée.');
    }

    public function test_deux_validateurs_par_departement_au_siege(): void
    {
        $siege = Agence::factory()->siege()->create();
        $info = Departement::factory()->create(['agence_id' => $siege->id]);
        $autre = Departement::factory()->create(['agence_id' => $siege->id]);
        $attr = ['agence_id' => $siege->id, 'departement_id' => $info->id];
        [$a, $b] = [$this->superieur($attr), $this->superieur($attr)];
        $demandeur = $this->compte('Demandeur', $attr);
        $horsDepartement = $this->superieur(['agence_id' => $siege->id, 'departement_id' => $autre->id]);

        $d1 = $this->demandeEnAttente($demandeur, $a);
        $this->valider($horsDepartement, $d1)->assertForbidden();
        $this->valider($b, $d1)->assertSessionHas('success', 'Demande validée.');
    }

    public function test_superadmin_superieur_ne_contourne_pas_l_interdiction(): void
    {
        $sa = $this->compte(['SuperAdmin', 'Superieur'], ['superieur' => 1]);
        $this->superieur();
        $d = $this->demandeEnAttente($sa);

        $this->valider($sa, $d)->assertForbidden();
        $this->actingAs($sa)->put(route('demandes.rejeter.superieur', $d->id))->assertForbidden();
    }

    // ---------------- Supérieur seul dans son entité (§4.3) ----------------

    public function test_superieur_seul_cree_sa_demande_absence_signalee_puis_interim(): void
    {
        $seul = $this->superieur();

        // Création permise, absence du second validateur signalée.
        $this->actingAs($seul)->post(route('demandes.store'), ['objet' => 'Papier'])
            ->assertRedirect(route('mes.demandes'))
            ->assertSessionHas('success', 'Demande créée. '.DemandeController::MESSAGE_SECOND_VALIDATEUR);
        $d = Demande::where('user_id', $seul->id)->firstOrFail();
        $this->assertNull($d->superieur_id);
        $d->forceFill(['statut' => 'En attente supérieur'])->save();

        // Absence également signalée à l'administrateur.
        $this->actingAs($this->admin)->get(route('users.index'))->assertOk()
            ->assertSee('Entités avec un seul validateur supérieur', false);

        // Personne ne valide : ni lui-même, ni un supérieur d'une autre entité, ni un admin.
        $ailleurs = Agence::factory()->create();
        $remplacant = $this->superieur(['agence_id' => $ailleurs->id]);
        $this->valider($seul, $d)->assertForbidden();
        $this->valider($remplacant, $d)->assertForbidden();
        $this->valider($this->compte('SuperAdmin'), $d)->assertForbidden();
        $this->assertSame('En attente supérieur', $d->fresh()->statut);

        // L'administrateur désigne un remplaçant temporaire d'une autre entité.
        $this->actingAs($this->admin)->post(route('interims.store'), ['entite' => 'agence:'.$this->agence->id, 'user_id' => $remplacant->id,
            'debut_le' => now()->toDateString(), 'fin_le' => now()->addDays(10)->toDateString(), 'motif' => 'Congé'])
            ->assertRedirect(route('interims.index'))->assertSessionHas('success', 'Remplaçant temporaire désigné.');
        $this->assertDatabaseHas('interims_validation', ['entite_type' => 'agence', 'entite_id' => $this->agence->id,
            'user_id' => $remplacant->id, 'designe_par' => $this->admin->id, 'motif' => 'Congé']);

        // Le remplaçant voit la demande et la valide.
        $this->assertTrue($this->actingAs($remplacant)->get('/demandes-a-valider')->viewData('enAttente')->contains('id', $d->id));
        $this->valider($remplacant, $d)->assertSessionHas('success', 'Demande validée.');
        $this->assertSame($remplacant->id, (int) $d->fresh()->superieur_id);
    }

    public function test_interim_limite_a_son_perimetre_a_sa_periode_et_revocable(): void
    {
        $this->superieur(); // titulaire unique de l'agence
        $autreAgence = Agence::factory()->create();
        $remplacant = $this->superieur(['agence_id' => Agence::factory()->create()->id]);
        $interim = InterimValidation::create(['entite_type' => 'agence', 'entite_id' => $this->agence->id, 'user_id' => $remplacant->id,
            'debut_le' => now()->toDateString(), 'fin_le' => now()->addDays(3)->toDateString(), 'designe_par' => $this->admin->id]);

        // Hors périmètre : une demande d'une autre agence.
        $horsPerimetre = $this->demandeEnAttente(User::factory()->create(['agence_id' => $autreAgence->id]));
        $this->valider($remplacant, $horsPerimetre)->assertForbidden();

        // Révocation : le remplaçant perd le droit ; l'historique reste.
        $d = $this->demandeEnAttente($this->compte('Demandeur'));
        $this->actingAs($this->admin)->patch(route('interims.revoquer', $interim->id))->assertRedirect(route('interims.index'));
        $this->valider($remplacant, $d)->assertForbidden();
        $this->assertNotNull($interim->fresh()->revoque_le);
        $this->assertSame($this->admin->id, (int) $interim->fresh()->revoque_par);

        // Intérim échu : aucun droit.
        InterimValidation::create(['entite_type' => 'agence', 'entite_id' => $this->agence->id, 'user_id' => $remplacant->id,
            'debut_le' => now()->subDays(10)->toDateString(), 'fin_le' => now()->subDay()->toDateString(), 'designe_par' => $this->admin->id]);
        $this->valider($remplacant, $d)->assertForbidden();
        $this->assertSame(2, InterimValidation::count());
    }

    public function test_conditions_de_designation_d_un_interim(): void
    {
        $titulaire = $this->superieur();
        $memeEntite = $this->superieur();
        $ailleurs = $this->superieur(['agence_id' => Agence::factory()->create()->id]);
        $nonSuperieur = $this->compte('Demandeur', ['agence_id' => Agence::factory()->create()->id]);
        $form = fn (User $u, array $extra = []) => $extra + ['entite' => 'agence:'.$this->agence->id, 'user_id' => $u->id,
            'debut_le' => now()->toDateString(), 'fin_le' => now()->addDays(5)->toDateString()];

        // Deux titulaires : un intérim n'est pas nécessaire.
        $this->actingAs($this->admin)->from('/interims-validation')->post(route('interims.store'), $form($ailleurs))
            ->assertSessionHas('error', 'Cette entité dispose déjà de deux validateurs supérieurs : un intérim n\'est pas nécessaire.');

        $memeEntite->forceFill(['superieur' => false])->save();
        $memeEntite->removeRole('Superieur');

        $this->actingAs($this->admin)->from('/interims-validation')->post(route('interims.store'), $form($nonSuperieur))
            ->assertSessionHas('error', 'Le remplaçant doit être un validateur supérieur désigné et actif.');
        $this->actingAs($this->admin)->from('/interims-validation')->post(route('interims.store'), $form($titulaire))
            ->assertSessionHas('error', 'Le remplaçant appartient à cette entité : désignez-le plutôt comme deuxième validateur.');
        $this->actingAs($this->admin)->from('/interims-validation')->post(route('interims.store'), $form($ailleurs, ['fin_le' => now()->subDay()->toDateString()]))
            ->assertSessionHasErrors('fin_le');

        $this->actingAs($this->admin)->post(route('interims.store'), $form($ailleurs))->assertSessionHas('success');
        $this->actingAs($this->admin)->from('/interims-validation')->post(route('interims.store'), $form($ailleurs))
            ->assertSessionHas('error', 'Un intérim est déjà prévu ou en vigueur pour cette entité : révoquez-le d\'abord.');
        $this->assertSame(1, InterimValidation::count());

        // Réservé à la gestion des comptes.
        $this->actingAs($ailleurs)->get(route('interims.index'))->assertForbidden();
        $this->actingAs($ailleurs)->post(route('interims.store'), $form($ailleurs))->assertForbidden();
        $this->actingAs($this->admin)->get(route('interims.index'))->assertOk()->assertSee('Remplaçants temporaires de validation');
    }

    public function test_entite_sans_aucun_superieur_regle_existante_conservee(): void
    {
        $this->actingAs($this->compte('Demandeur'))->from('/demandes/create')->post(route('demandes.store'), ['objet' => 'X'])
            ->assertSessionHas('error', 'Aucun supérieur défini');
        $this->assertSame(0, Demande::count());
    }
}
