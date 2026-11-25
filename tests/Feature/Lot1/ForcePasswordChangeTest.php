<?php

namespace Tests\Feature\Lot1;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * L1-1 : boucle de redirection lors du changement obligatoire de mot de passe.
 */
class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    private const NOUVEAU_MOT_DE_PASSE = 'Nouveau#Passe2026';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function utilisateur(bool $doitChanger): User
    {
        $user = User::factory()->create(['force_password_change' => $doitChanger]);
        $user->assignRole('Demandeur');

        return $user;
    }

    public function test_un_utilisateur_oblige_est_redirige_une_seule_fois_vers_le_changement(): void
    {
        $user = $this->utilisateur(true);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('force.password.change'));

        // La page cible s'affiche (plus de boucle).
        $this->actingAs($user)
            ->get(route('force.password.change'))
            ->assertOk();
    }

    public function test_les_autres_pages_redirigent_vers_le_changement_sans_boucle(): void
    {
        $user = $this->utilisateur(true);

        foreach (['/mon-profil', '/articles', '/mes-demandes'] as $url) {
            $reponse = $this->actingAs($user)->get($url);

            $reponse->assertRedirect(route('force.password.change'));
            $this->assertNotSame(url($url), $reponse->headers->get('Location'));
        }

        // Le suivi complet des redirections aboutit à une page affichée.
        $this->actingAs($user)
            ->followingRedirects()
            ->get('/mon-profil')
            ->assertOk()
            ->assertViewIs('auth.force-password-change');
    }

    public function test_la_deconnexion_reste_possible(): void
    {
        $user = $this->utilisateur(true);

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_non_regression_un_mot_de_passe_conforme_leve_l_obligation(): void
    {
        $user = $this->utilisateur(true);

        $this->actingAs($user)
            ->post(route('force.password.update'), [
                'password' => self::NOUVEAU_MOT_DE_PASSE,
                'password_confirmation' => self::NOUVEAU_MOT_DE_PASSE,
            ])
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertFalse((bool) $user->force_password_change);
        $this->assertTrue(Hash::check(self::NOUVEAU_MOT_DE_PASSE, $user->password));

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_non_regression_les_regles_de_mot_de_passe_sont_inchangees(): void
    {
        $user = $this->utilisateur(true);

        // Trop court / sans complexité
        $this->actingAs($user)
            ->from(route('force.password.change'))
            ->post(route('force.password.update'), [
                'password' => 'court',
                'password_confirmation' => 'court',
            ])
            ->assertSessionHasErrors('password');

        // Identique au mot de passe actuel ('password' dans la factory) : refusé
        $user->update(['password' => Hash::make(self::NOUVEAU_MOT_DE_PASSE)]);
        $this->actingAs($user)
            ->from(route('force.password.change'))
            ->post(route('force.password.update'), [
                'password' => self::NOUVEAU_MOT_DE_PASSE,
                'password_confirmation' => self::NOUVEAU_MOT_DE_PASSE,
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue((bool) $user->fresh()->force_password_change);
    }

    public function test_non_regression_un_utilisateur_sans_obligation_navigue_normalement(): void
    {
        $user = $this->utilisateur(false);

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get('/mon-profil')->assertOk();
    }
}
