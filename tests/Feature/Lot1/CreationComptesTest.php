<?php

namespace Tests\Feature\Lot1;

use App\Models\Agence;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * L1-2 (non-régression) : la fermeture de l'inscription publique ne touche ni
 * la connexion, ni la création de comptes par un administrateur.
 */
class CreationComptesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_la_page_de_connexion_reste_disponible(): void
    {
        $this->get('/login')->assertOk()->assertViewIs('auth.login');
    }

    public function test_la_connexion_fonctionne_toujours(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_un_administrateur_cree_toujours_un_compte(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $agence = Agence::factory()->create();

        $this->actingAs($admin)
            ->post('/users', [
                'name' => 'KOFFI',
                'prenom' => 'Ama',
                'email' => 'ama.koffi@example.com',
                'password' => 'Initial#2026xx',
                'password_confirmation' => 'Initial#2026xx',
                'agence_id' => $agence->id,
            ])
            ->assertRedirect(route('users.index'));

        $cree = User::where('email', 'ama.koffi@example.com')->firstOrFail();
        $this->assertTrue((bool) $cree->force_password_change);
        $this->assertTrue($cree->hasRole('Demandeur'));
        $this->assertTrue(Hash::check('Initial#2026xx', $cree->password));
    }
}
