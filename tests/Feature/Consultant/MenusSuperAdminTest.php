<?php

namespace Tests\Feature\Consultant;

use App\Autorisation\Capacites;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Arbitrage B (F2) : le SuperAdmin conserve les entrées « États » et
 * « Mouvements stock » dans son menu ; droits inchangés, aucune opération
 * supplémentaire.
 */
class MenusSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    /** Capacités du SuperAdmin attendues (inchangées depuis LC6). */
    private const CAPACITES_SUPERADMIN = [
        'gerer-utilisateurs', 'gerer-agence', 'gerer-departement', 'gerer-service', 'gerer-stock', 'voir-mouvements',
        'gerer-fournisseurs', 'voir-commandes', 'consulter-commandes-receptionnees', 'acceder-commandes', 'preparer-commandes',
        'supprimer-commandes', 'valider-commandes', 'voir-etats', 'consulter-etats', 'consulter-mouvements', 'exporter-demandes',
        'lister-validation-finale', 'traiter-demandes', 'valider-finale-demandes', 'consulter-toutes-demandes',
        'voir-tableau-de-bord-global', 'voir-tableau-de-bord-consultation', 'archiver-demandes',
    ];

    private function superAdmin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $u = User::factory()->create();
        $u->assignRole('SuperAdmin');

        return $u;
    }

    public function test_entrees_etats_et_mouvements_visibles_et_accessibles(): void
    {
        $sa = $this->superAdmin();

        $this->actingAs($sa)->get(route('mes.demandes'))->assertOk()
            ->assertSee(route('etats.index'), false)->assertSee('États')
            ->assertSee(route('mouvements.index'), false)->assertSee('Mouvements stock');

        $this->actingAs($sa)->get(route('etats.index'))->assertOk();
        $this->actingAs($sa)->get('/etats/stocks')->assertOk();
        $this->actingAs($sa)->get(route('mouvements.index'))->assertOk();
    }

    public function test_aucun_elargissement_des_droits_du_superadmin(): void
    {
        $sa = $this->superAdmin();

        $accordees = array_values(array_filter(Capacites::noms(), fn ($c) => Gate::forUser($sa)->allows($c)));
        $this->assertEqualsCanonicalizing(self::CAPACITES_SUPERADMIN, $accordees);

        // Réservées à d'autres rôles : toujours refusées au SuperAdmin.
        foreach (['receptionner-commandes', 'soumettre-commandes', 'lister-demandes-superieur', 'lister-demandes-gestionnaire'] as $capacite) {
            $this->assertFalse(Gate::forUser($sa)->allows($capacite), "SuperAdmin : « $capacite » doit rester refusée");
        }
    }

    public function test_menus_des_autres_roles_inchanges(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $attendus = [
            'Gestionnaire' => [true, true], 'ValidateurDemande' => [true, false], 'Consultant' => [true, true],
            'Demandeur' => [false, false], 'Admin' => [false, false], 'ValidateurCommande' => [false, false], 'Superieur' => [false, false],
        ];

        foreach ($attendus as $role => [$etats, $mouvements]) {
            $u = User::factory()->create();
            $u->assignRole($role);
            $page = $this->actingAs($u)->get(route('mes.demandes'))->assertOk()->getContent();
            $this->assertSame($etats, str_contains($page, route('etats.index')), "$role : menu États");
            $this->assertSame($mouvements, str_contains($page, route('mouvements.index')), "$role : menu Mouvements stock");
        }
    }
}
