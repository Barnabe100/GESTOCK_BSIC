<?php

namespace Tests\Feature\Lot1;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\ArticleDemande;
use App\Models\Demande;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * L1-3 : routes annexes (/dashbord, doublon /login) et suppression de son
 * propre compte.
 */
class RoutesAnnexesTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE_REFUS = "La suppression de compte n'est pas autorisée. Contactez l'administrateur.";

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_la_route_dashbord_n_existe_plus_pour_un_visiteur(): void
    {
        $this->get('/dashbord')->assertNotFound();
    }

    public function test_la_route_dashbord_n_existe_plus_pour_un_utilisateur_connecte(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashbord')->assertNotFound();
    }

    public function test_non_regression_la_connexion_utilise_la_route_unique_d_auth(): void
    {
        $route = Route::getRoutes()->getByName('login');

        $this->assertNotNull($route);
        $this->assertSame(AuthenticatedSessionController::class.'@create', $route->getActionName());
        $this->get('/login')->assertOk()->assertViewIs('auth.login');
    }

    public function test_non_regression_le_tableau_de_bord_reste_accessible(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Gestionnaire');

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_la_suppression_de_son_compte_conserve_le_compte_et_ses_demandes(): void
    {
        $user = User::factory()->create();
        $demande = Demande::factory()->create(['user_id' => $user->id]);
        $ligne = ArticleDemande::factory()->create(['demande_id' => $demande->id]);

        $this->actingAs($user)
            ->from('/profile')
            ->delete('/profile', ['password' => 'password'])
            ->assertRedirect('/profile')
            ->assertSessionHasErrorsIn('userDeletion', ['password' => self::MESSAGE_REFUS]);

        $this->assertNotNull($user->fresh());
        $this->assertNotNull($demande->fresh());
        $this->assertNotNull($ligne->fresh());
        $this->assertAuthenticatedAs($user);
    }

    public function test_le_message_de_refus_s_affiche_dans_la_fenetre_existante(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->followingRedirects()
            ->delete('/profile', ['password' => 'password'])
            ->assertOk()
            ->assertViewIs('profile.edit')
            ->assertSee(e(self::MESSAGE_REFUS), false);
    }

    public function test_non_regression_un_mauvais_mot_de_passe_reste_refuse(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->delete('/profile', ['password' => 'mauvais'])
            ->assertSessionHasErrorsIn('userDeletion', 'password');

        $this->assertNotNull($user->fresh());
    }
}
