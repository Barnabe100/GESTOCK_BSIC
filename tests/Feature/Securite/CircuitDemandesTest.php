<?php

namespace Tests\Feature\Securite;

use App\Models\Agence;
use App\Models\Article;
use App\Models\ArticleDemande;
use App\Models\Demande;
use App\Models\Departement;
use App\Models\MouvementStock;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Socle des droits : circuit des demandes (propriété, séparation des tâches,
 * auto-validation interdite, gardes d'état, rejet vers « En préparation »,
 * confirmation de réception). Requêtes HTTP directes.
 */
class CircuitDemandesTest extends TestCase
{
    use RefreshDatabase;

    private Agence $agence;

    private User $demandeur;

    private User $superieur;

    private User $gestionnaire;

    private User $validateur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->agence = Agence::factory()->create();
        $this->demandeur = $this->compte('Demandeur');
        $this->superieur = $this->compte(['Demandeur', 'Superieur'], ['superieur' => 1]);
        $this->gestionnaire = $this->compte(['Demandeur', 'Gestionnaire']);
        $this->validateur = $this->compte(['Demandeur', 'ValidateurDemande']);
    }

    private function compte(string|array $roles, array $attr = []): User
    {
        $u = User::factory()->create(array_merge(['agence_id' => $this->agence->id], $attr));
        $u->assignRole($roles);

        return $u;
    }

    private const ETATS = [
        'preparation' => ['statut' => 'En préparation'],
        'attente_sup' => ['statut' => 'En attente supérieur'],
        'attente_gest' => ['statut' => 'En attente gestionnaire', 'validation_superieur' => 1],
        'attente_finale' => ['statut' => 'En attente validation finale', 'validation_superieur' => 1, 'validation_gestionnaire' => 1],
        'attente_livraison' => ['statut' => 'En attente livraison', 'validation_superieur' => 1, 'validation_gestionnaire' => 1, 'validation_finale' => 1],
        'attente_reception' => ['statut' => 'En attente réception demandeur', 'validation_superieur' => 1, 'validation_gestionnaire' => 1, 'validation_finale' => 1, 'livraison' => 1],
    ];

    private function demande(string $etat, ?User $proprietaire = null): Demande
    {
        $d = Demande::factory()->create(array_merge(['user_id' => ($proprietaire ?? $this->demandeur)->id,
            'superieur_id' => $this->superieur->id], self::ETATS[$etat]));
        $a = Article::factory()->create(['stock_disponible' => 50]);
        ArticleDemande::factory()->create(['demande_id' => $d->id, 'article_id' => $a->id, 'quantite' => 2, 'quantite_accordee' => 2]);

        return $d->fresh();
    }

    private function quantites(Demande $d): array
    {
        return ['quantite_accordee' => [$d->articles()->first()->article_id => 2]];
    }

    // ---------------- Personne ne valide sa propre demande ----------------

    public function test_personne_ne_valide_ni_ne_rejette_sa_propre_demande(): void
    {
        $sup = $this->demande('attente_sup', $this->superieur);
        $this->actingAs($this->superieur)->put(route('demandes.valider.superieur', $sup->id))->assertForbidden();
        $this->actingAs($this->superieur)->put(route('demandes.rejeter.superieur', $sup->id))->assertForbidden();

        $gest = $this->demande('attente_gest', $this->gestionnaire);
        $this->actingAs($this->gestionnaire)->put(route('demandes.valider', $gest->id), $this->quantites($gest))->assertForbidden();
        $this->actingAs($this->gestionnaire)->put(route('demandes.rejeter.gestionnaire', $gest->id))->assertForbidden();

        $fin = $this->demande('attente_finale', $this->validateur);
        $this->actingAs($this->validateur)->put(route('demandes.validation.finale', $fin->id))->assertForbidden();
        $this->actingAs($this->validateur)->put(route('demandes.rejet.finale', $fin->id))->assertForbidden();

        $superAdmin = $this->compte('SuperAdmin');
        $sa = $this->demande('attente_gest', $superAdmin);
        $this->actingAs($superAdmin)->put(route('demandes.valider', $sa->id), $this->quantites($sa))->assertForbidden();

        $this->assertSame('En attente supérieur', $sup->fresh()->statut);
        $this->assertSame('En attente gestionnaire', $gest->fresh()->statut);
        $this->assertSame('En attente validation finale', $fin->fresh()->statut);
    }

    public function test_un_demandeur_ne_peut_pas_derouler_lui_meme_le_circuit(): void
    {
        $d = $this->demande('attente_sup');
        $this->actingAs($this->demandeur)->put(route('demandes.valider.superieur', $d->id))->assertForbidden();
        $this->actingAs($this->demandeur)->put(route('demandes.valider', $d->id), $this->quantites($d))->assertForbidden();
        $this->actingAs($this->demandeur)->put(route('demandes.validation.finale', $d->id))->assertForbidden();
        $this->actingAs($this->demandeur)->post(route('demandes.livrer', $d->id))->assertForbidden();
        $this->assertSame('En attente supérieur', $d->fresh()->statut);
        $this->assertSame(0, MouvementStock::count());
    }

    // ---------------- Rôles et entité ----------------

    public function test_un_tiers_ne_livre_n_archive_ni_ne_modifie_une_demande(): void
    {
        $tiers = $this->compte('Demandeur');
        $livrable = $this->demande('attente_livraison');
        $this->actingAs($tiers)->post(route('demandes.livrer', $livrable->id))->assertForbidden();
        $this->actingAs($this->superieur)->post(route('demandes.livrer', $livrable->id))->assertForbidden();
        $this->actingAs($this->validateur)->post(route('demandes.livrer', $livrable->id))->assertForbidden();
        $this->assertFalse((bool) $livrable->fresh()->livraison);

        $prep = $this->demande('preparation');
        $this->actingAs($tiers)->put(route('demandes.update', $prep->id), ['objet' => 'modifié'])->assertForbidden();
        $this->actingAs($tiers)->get(route('demandes.edit', $prep->id))->assertForbidden();
        $this->actingAs($tiers)->delete(route('demandes.destroy', $prep->id))->assertForbidden();
        $this->actingAs($this->demandeur)->delete(route('demandes.destroy', $prep->id))->assertForbidden();
        $this->assertNotSame('modifié', $prep->fresh()->objet);
        $this->assertFalse((bool) $prep->fresh()->archiver);
    }

    public function test_l_en_tete_n_est_plus_modifiable_apres_validation_du_superieur(): void
    {
        $d = $this->demande('attente_gest');
        $this->actingAs($this->demandeur)->put(route('demandes.update', $d->id), ['objet' => 'modifié'])->assertForbidden();
        $this->assertNotSame('modifié', $d->fresh()->objet);
    }

    public function test_un_superieur_d_une_autre_entite_ne_valide_pas(): void
    {
        $autreAgence = Agence::factory()->create();
        $etranger = $this->compte(['Demandeur', 'Superieur'], ['agence_id' => $autreAgence->id, 'superieur' => 1]);
        $d = $this->demande('attente_sup');

        $this->actingAs($etranger)->put(route('demandes.valider.superieur', $d->id))->assertForbidden();
        $this->assertSame('En attente supérieur', $d->fresh()->statut);
    }

    public function test_le_superieur_remplace_perd_son_droit_au_profit_du_remplacant(): void
    {
        $admin = $this->compte('Admin');
        $remplacant = $this->compte('Demandeur');
        $d = $this->demande('attente_sup');

        $this->actingAs($admin)->put(route('users.update', $remplacant->id), ['name' => 'R', 'prenom' => 'P', 'email' => $remplacant->email,
            'agence_id' => $this->agence->id, 'superieur' => 1, 'roles' => ['Demandeur']]);

        $this->actingAs($this->superieur->fresh())->put(route('demandes.valider.superieur', $d->id))->assertForbidden();
        $this->actingAs($remplacant->fresh())->from('/demandes-a-valider')->put(route('demandes.valider.superieur', $d->id))
            ->assertSessionHas('success', 'Demande validée.');
        $this->assertSame('En attente gestionnaire', $d->fresh()->statut);
        $this->assertSame($remplacant->id, (int) $d->fresh()->superieur_id);
    }

    // ---------------- Parcours légitime complet ----------------

    public function test_parcours_complet_par_quatre_acteurs_distincts(): void
    {
        $d = $this->demande('attente_sup');

        $this->actingAs($this->superieur)->from('/demandes-a-valider')->put(route('demandes.valider.superieur', $d->id))
            ->assertSessionHas('success', 'Demande validée.');
        $this->actingAs($this->gestionnaire)->from('/demandes-gestionnaire')->put(route('demandes.valider', $d->id), $this->quantites($d))
            // Message existant inchangé : il est choisi après l'enregistrement,
            // donc « modifiées » dès la première saisie (anomalie S-13, hors lot).
            ->assertSessionHas('success', 'Quantités accordées modifiées');
        $this->actingAs($this->validateur)->from('/demandes-validation-finale')->put(route('demandes.validation.finale', $d->id))
            ->assertSessionHas('success', 'Validation finale effectuée');
        $this->actingAs($this->gestionnaire)->from('/demandes-gestionnaire')->post(route('demandes.livrer', $d->id))
            ->assertSessionHas('success', 'Demande livrée');
        $this->actingAs($this->demandeur)->from('/mes-demandes')->put(route('demandes.confirmer.reception', $d->id), ['commentaire' => 'reçu'])
            ->assertSessionHas('success', 'Réception confirmée avec succès.');

        $this->assertSame('Livrée - Demande clôturée', $d->fresh()->statut);
        $this->assertSame(1, MouvementStock::where('type', 'SORTIE')->count());
    }

    public function test_le_superadmin_garde_ses_actions_de_l_interface(): void
    {
        $superAdmin = $this->compte('SuperAdmin');
        $g = $this->demande('attente_gest');
        $this->actingAs($superAdmin)->from('/demandes')->put(route('demandes.valider', $g->id), $this->quantites($g))
            ->assertSessionHas('success');
        $l = $this->demande('attente_livraison');
        $this->actingAs($superAdmin)->from('/demandes')->post(route('demandes.livrer', $l->id))->assertSessionHas('success', 'Demande livrée');
        $p = $this->demande('preparation');
        $this->actingAs($superAdmin)->delete(route('demandes.destroy', $p->id))->assertRedirect(route('demandes.index'));
        $this->assertTrue((bool) $p->fresh()->archiver);
    }

    // ---------------- Gardes d'état ----------------

    public function test_gardes_d_etat_avec_messages_existants(): void
    {
        $valide = $this->demande('attente_gest');
        $this->actingAs($this->superieur)->from('/x')->put(route('demandes.valider.superieur', $valide->id))
            ->assertSessionHas('error', 'Demande déjà validée');

        $sansGestionnaire = $this->demande('attente_gest');
        $sansGestionnaire->update(['statut' => 'En attente validation finale']);
        $this->actingAs($this->validateur)->from('/x')->put(route('demandes.validation.finale', $sansGestionnaire->id))
            ->assertSessionHas('error', 'La demande n’a pas encore été validée complètement.');
        $this->assertFalse((bool) $sansGestionnaire->fresh()->validation_finale);

        $nonValidee = $this->demande('attente_sup');
        $this->actingAs($this->gestionnaire)->from('/x')->put(route('demandes.valider', $nonValidee->id), $this->quantites($nonValidee))
            ->assertSessionHas('error', 'La demande n’a pas encore été validée complètement.');

        $livree = $this->demande('attente_reception');
        $livree->update(['validation_finale' => 0]);
        $this->actingAs($this->gestionnaire)->from('/x')->put(route('demandes.valider', $livree->id), ['quantite_accordee' => [999 => 9]])
            ->assertSessionHas('error', 'La demande est déjà validée définitivement');
        $this->assertSame(2, (int) $livree->articles()->first()->quantite_accordee);

        $this->actingAs($this->validateur)->put(route('demandes.rejet.finale', $livree->id), ['motif_rejet' => 'x'])->assertForbidden();
        $this->actingAs($this->superieur)->put(route('demandes.valider.superieur', $livree->fresh()->id));
        $this->assertSame('En attente réception demandeur', $livree->fresh()->statut, 'Une demande livrée ne peut pas être « rouverte ».');
    }

    // ---------------- Rejet : retour « En préparation » (décision validée) ----------------

    public function test_un_rejet_renvoie_en_preparation_puis_la_demande_est_resoumise(): void
    {
        $cas = [
            ['attente_sup', $this->superieur, 'demandes.rejeter.superieur', ['commentaire' => 'à revoir']],
            ['attente_gest', $this->gestionnaire, 'demandes.rejeter.gestionnaire', ['commentaire' => 'à revoir']],
            ['attente_finale', $this->validateur, 'demandes.rejet.finale', ['motif_rejet' => 'à revoir']],
        ];

        foreach ($cas as [$etat, $acteur, $route, $donnees]) {
            $d = $this->demande($etat);
            $this->actingAs($acteur)->from('/x')->put(route($route, $d->id), $donnees)->assertSessionHas('success', 'Demande rejetée.');
            $d->refresh();
            $this->assertSame('En préparation', $d->statut, $route);
            $this->assertSame([0, 0, 0], [(int) $d->validation_superieur, (int) $d->validation_gestionnaire, (int) $d->validation_finale], $route);

            // Le propriétaire peut corriger ses lignes puis resoumettre.
            $ligne = $d->articles()->first();
            $this->actingAs($this->demandeur)->from('/demandes/'.$d->id)->put(route('article-demandes.update', $ligne->id),
                ['article_id' => $ligne->article_id, 'quantite' => 3])->assertSessionHas('success');
            $this->actingAs($this->demandeur)->put(route('demandes.soumettre', $d->id))->assertRedirect(route('mes.demandes'));
            $this->assertSame('En attente supérieur', $d->fresh()->statut, $route);
        }
    }

    // ---------------- Confirmation de réception (décision validée) ----------------

    public function test_reception_confirmee_par_la_meme_agence_ou_le_meme_departement(): void
    {
        $collegueAgence = $this->compte('Demandeur');
        $dep = Departement::factory()->create();
        $demandeurSiege = $this->compte('Demandeur', ['agence_id' => Agence::factory()->create()->id, 'departement_id' => $dep->id]);
        $collegueDepartement = $this->compte('Demandeur', ['agence_id' => null, 'departement_id' => $dep->id]);
        $gestionnaireAilleurs = $this->compte(['Demandeur', 'Gestionnaire'], ['agence_id' => Agence::factory()->create()->id]);

        $a = $this->demande('attente_reception');
        $this->actingAs($gestionnaireAilleurs)->put(route('demandes.confirmer.reception', $a->id), ['commentaire' => 'x'])->assertForbidden();
        $this->actingAs($collegueAgence)->from('/x')->put(route('demandes.confirmer.reception', $a->id), ['commentaire' => 'reçu'])
            ->assertSessionHas('success');
        $this->assertSame('Livrée - Demande clôturée', $a->fresh()->statut);

        $b = $this->demande('attente_reception', $demandeurSiege);
        $this->actingAs($collegueDepartement)->from('/x')->put(route('demandes.confirmer.reception', $b->id), ['commentaire' => 'reçu'])
            ->assertSessionHas('success');
        $this->assertSame($collegueDepartement->id, (int) $b->fresh()->reception_par);
    }

    // ---------------- Consultation ----------------

    public function test_la_liste_des_demandes_ne_montre_plus_la_demande_d_un_tiers(): void
    {
        $tiers = $this->compte('Demandeur');
        Demande::factory()->create(['id' => 900, 'user_id' => $tiers->id, 'objet' => 'DEMANDE-DU-TIERS']);
        $u = User::factory()->create(['id' => 900]);
        $u->assignRole('Demandeur');
        Demande::factory()->create(['user_id' => $u->id, 'objet' => 'MA-DEMANDE']);

        $page = $this->actingAs($u)->get(route('demandes.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('DEMANDE-DU-TIERS', $page);
        $this->assertStringContainsString('MA-DEMANDE', $page);
    }

    public function test_consultation_d_une_demande_d_une_autre_entite_refusee(): void
    {
        $etranger = User::factory()->create(['agence_id' => Agence::factory()->create()->id]);
        $etranger->assignRole('Demandeur');
        $d = $this->demande('attente_sup');

        $this->actingAs($etranger)->get(route('demandes.show', $d->id))->assertForbidden();
        $this->actingAs($this->demandeur)->get(route('demandes.show', $d->id))->assertOk();
    }
}
