<?php

namespace Tests\Feature\Consultant;

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
 * LC2 : N01 (visibilité par entité, brouillons compris ; modification par
 * le seul propriétaire, sans exception SuperAdmin) et visibilité du
 * Supérieur limitée à son entité, à ses demandes attribuées et à ses intérims.
 */
class N01VisibiliteDemandesTest extends TestCase
{
    use RefreshDatabase;

    private Agence $agence;

    private Agence $autreAgence;

    private Agence $siege;

    private Departement $depA;

    private Departement $depB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->agence = Agence::factory()->create();
        $this->autreAgence = Agence::factory()->create();
        $this->siege = Agence::factory()->siege()->create();
        $this->depA = Departement::factory()->create(['agence_id' => $this->siege->id]);
        $this->depB = Departement::factory()->create(['agence_id' => $this->siege->id]);
    }

    private function compte(array $roles, array $attr): User
    {
        $u = User::factory()->create($attr);
        $u->assignRole($roles);

        return $u;
    }

    private function enAgence(array $roles = ['Demandeur'], ?Agence $agence = null, array $extra = []): User
    {
        return $this->compte($roles, ['agence_id' => ($agence ?? $this->agence)->id] + $extra);
    }

    private function auSiege(Departement $dep, array $roles = ['Demandeur'], array $extra = []): User
    {
        return $this->compte($roles, ['agence_id' => $this->siege->id, 'departement_id' => $dep->id] + $extra);
    }

    private function demande(User $proprietaire, string $statut = 'En préparation', array $attr = []): Demande
    {
        $d = Demande::factory()->create(['user_id' => $proprietaire->id, 'statut' => $statut] + $attr);
        ArticleDemande::factory()->create(['demande_id' => $d->id, 'article_id' => Article::factory()->create()->id, 'quantite' => 2]);

        return $d->fresh();
    }

    private function voit(User $u, Demande $d): int
    {
        return $this->actingAs($u)->get(route('demandes.show', $d->id))->getStatusCode();
    }

    private function listeEntite(User $u): array
    {
        return $this->actingAs($u)->get(route('demandes.organisation'))->assertOk()->viewData('demandes')->pluck('id')->all();
    }

    // ---------------- Visibilité par entité ----------------

    public function test_meme_agence_voit_les_demandes_y_compris_les_brouillons(): void
    {
        $auteur = $this->enAgence();
        $brouillon = $this->demande($auteur);
        $soumise = $this->demande($auteur, 'En attente supérieur');
        $collegue = $this->enAgence();

        $this->assertSame(200, $this->voit($collegue, $brouillon));
        $this->assertSame(200, $this->voit($collegue, $soumise));
        $this->assertEqualsCanonicalizing([$brouillon->id, $soumise->id], $this->listeEntite($collegue));
    }

    public function test_meme_departement_au_siege_voit_autre_departement_non(): void
    {
        $auteur = $this->auSiege($this->depA);
        $d = $this->demande($auteur);

        $this->assertSame(200, $this->voit($this->auSiege($this->depA), $d));
        $autreDep = $this->auSiege($this->depB);
        $this->assertSame(403, $this->voit($autreDep, $d));
        $this->assertNotContains($d->id, $this->listeEntite($autreDep));
    }

    public function test_autre_agence_refusee_en_liste_et_par_url(): void
    {
        $d = $this->demande($this->enAgence());
        $ailleurs = $this->enAgence(['Demandeur'], $this->autreAgence);

        $this->assertSame(403, $this->voit($ailleurs, $d));
        $this->assertNotContains($d->id, $this->listeEntite($ailleurs));
    }

    public function test_tout_membre_quel_que_soit_son_role_voit_la_liste_de_son_entite(): void
    {
        $d = $this->demande($this->enAgence(), 'En attente supérieur');

        foreach ([['Consultant'], ['ValidateurCommande'], ['Admin'], ['Gestionnaire']] as $roles) {
            $membre = $this->enAgence($roles);
            $this->assertContains($d->id, $this->listeEntite($membre), implode('+', $roles).' doit voir la liste de son entité');
        }
    }

    public function test_consultant_limite_a_son_entite(): void
    {
        $consultant = $this->enAgence(['Consultant']);
        $memeAgence = $this->demande($this->enAgence());
        $autre = $this->demande($this->enAgence(['Demandeur'], $this->autreAgence));

        $this->assertSame(200, $this->voit($consultant, $memeAgence));
        $this->assertSame(403, $this->voit($consultant, $autre));
        $this->assertSame([$memeAgence->id], $this->listeEntite($consultant));
    }

    // ---------------- Propriété exclusive ----------------

    public function test_collegue_ne_modifie_ni_en_tete_ni_lignes(): void
    {
        $auteur = $this->enAgence();
        $d = $this->demande($auteur);
        $ligne = $d->articles()->first();
        $collegue = $this->enAgence();

        $this->actingAs($collegue)->get(route('demandes.edit', $d->id))->assertForbidden();
        $this->actingAs($collegue)->put(route('demandes.update', $d->id), ['objet' => 'Piraté'])->assertForbidden();
        $this->actingAs($collegue)->post(route('article_demande.store'), ['demande_id' => $d->id, 'article_id' => Article::factory()->create()->id, 'quantite' => 1])->assertForbidden();
        $this->actingAs($collegue)->put(route('article-demandes.update', $ligne->id), ['quantite' => 9])->assertForbidden();
        $this->actingAs($collegue)->delete(route('article_demande.destroy', $ligne->id))->assertForbidden();

        $this->assertNotSame('Piraté', $d->fresh()->objet);
        $this->assertSame(2, (int) $ligne->fresh()->quantite);
        $this->assertSame(1, $d->articles()->count());
    }

    public function test_superadmin_ne_modifie_pas_l_en_tete_d_une_demande_d_autrui(): void
    {
        $this->enAgence(['Superieur'], null, ['superieur' => 1]);
        $auteur = $this->enAgence();
        $d = $this->demande($auteur);
        $sa = $this->enAgence(['SuperAdmin']);

        $this->actingAs($sa)->get(route('demandes.edit', $d->id))->assertForbidden();
        $this->actingAs($sa)->put(route('demandes.update', $d->id), ['objet' => 'Modifié par SA'])->assertForbidden();
        $this->assertNotSame('Modifié par SA', $d->fresh()->objet);

        // Le bouton « Editer » n'est plus proposé au SuperAdmin pour la demande d'autrui.
        $d->forceFill(['statut' => 'En attente gestionnaire', 'validation_superieur' => 0])->save();
        $this->actingAs($sa)->get(route('demandes.index'))->assertOk()->assertDontSee(route('demandes.edit', $d->id), false);

        // Le propriétaire garde son droit, et le SuperAdmin sur ses propres demandes.
        $this->actingAs($auteur)->put(route('demandes.update', $d->id), ['objet' => 'Modifié par l\'auteur'])->assertRedirect(route('mes.demandes'));
        $propre = $this->demande($sa);
        $this->actingAs($sa)->get(route('demandes.edit', $propre->id))->assertOk();
    }

    // ---------------- Supérieur ----------------

    public function test_superieur_limite_a_son_entite_ses_attributions_et_ses_interims(): void
    {
        $sup = $this->enAgence(['Demandeur', 'Superieur'], null, ['superieur' => 1]);
        $demandeAutreAgence = $this->demande($this->enAgence(['Demandeur'], $this->autreAgence), 'En attente supérieur');

        // Hors périmètre : plus de visibilité globale.
        $this->assertSame(403, $this->voit($sup, $demandeAutreAgence));
        $this->assertSame(200, $this->voit($sup, $this->demande($this->enAgence(), 'En attente supérieur')));

        // Demande qui lui est attribuée dans le circuit.
        $attribuee = $this->demande($this->enAgence(['Demandeur'], $this->autreAgence), 'En attente gestionnaire', ['superieur_id' => $sup->id]);
        $this->assertSame(200, $this->voit($sup, $attribuee));

        // Intérim en vigueur sur l'autre agence : visible.
        $interim = InterimValidation::create(['entite_type' => 'agence', 'entite_id' => $this->autreAgence->id, 'user_id' => $sup->id,
            'debut_le' => now()->toDateString(), 'fin_le' => now()->addDays(5)->toDateString()]);
        $this->assertSame(200, $this->voit($sup, $demandeAutreAgence));

        // Intérim révoqué, puis échu : plus visible.
        $interim->update(['revoque_le' => now()]);
        $this->assertSame(403, $this->voit($sup, $demandeAutreAgence));
        InterimValidation::create(['entite_type' => 'agence', 'entite_id' => $this->autreAgence->id, 'user_id' => $sup->id,
            'debut_le' => now()->subDays(10)->toDateString(), 'fin_le' => now()->subDay()->toDateString()]);
        $this->assertSame(403, $this->voit($sup, $demandeAutreAgence));
    }

    // ---------------- Droits transversaux conservés ----------------

    public function test_circuits_transversaux_conserves(): void
    {
        $d = $this->demande($this->enAgence(['Demandeur'], $this->autreAgence), 'En attente gestionnaire');

        foreach ([['Gestionnaire'], ['ValidateurDemande'], ['SuperAdmin']] as $roles) {
            $this->assertSame(200, $this->voit($this->enAgence($roles), $d), implode('+', $roles).' garde la consultation liée à son circuit');
        }
        foreach ([['Admin'], ['ValidateurCommande'], ['Consultant'], ['Demandeur']] as $roles) {
            $this->assertSame(403, $this->voit($this->enAgence($roles), $d), implode('+', $roles).' ne voit pas hors de son entité');
        }
    }

    public function test_circuit_de_reception_conserve(): void
    {
        // Au siège : même agence, autre département, demande en attente de réception.
        $auteur = $this->auSiege($this->depA);
        $d = $this->demande($auteur, 'En attente réception demandeur', ['validation_superieur' => 1, 'validation_gestionnaire' => 1,
            'validation_finale' => 1, 'livraison' => 1]);

        $this->assertSame(200, $this->voit($this->auSiege($this->depB), $d));
    }
}
