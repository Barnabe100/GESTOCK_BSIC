<?php

namespace Tests\Feature\Consolidation;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Création et modification d'un compte : adresse e-mail unique, contrôlée
 * côté serveur, message compréhensible, jamais d'erreur 500.
 */
class EmailDoublonTest extends TestCase
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

    private function creer(string $email)
    {
        return $this->actingAs($this->admin)->from(route('users.create'))->post(route('users.store'), [
            'name' => 'NOUVEAU', 'prenom' => 'Compte', 'email' => $email,
            'password' => 'Provisoire1', 'password_confirmation' => 'Provisoire1',
        ]);
    }

    public function test_email_disponible(): void
    {
        $this->creer('libre@ex.test')->assertRedirect(route('users.index'));
        $this->assertSame(1, User::where('email', 'libre@ex.test')->count());
    }

    public function test_email_deja_utilise_message_et_aucune_erreur_500(): void
    {
        User::factory()->create(['email' => 'pris@ex.test']);

        $this->creer('pris@ex.test')
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors(['email' => 'Cette adresse e-mail est déjà utilisée par un autre compte.']);
        $this->creer('PRIS@ex.test')
            ->assertSessionHasErrors(['email' => 'Cette adresse e-mail est déjà utilisée par un autre compte.']);
        $this->assertSame(1, User::whereRaw('LOWER(email) = ?', ['pris@ex.test'])->count());

        // Le message est affiché sous le champ du formulaire.
        $this->followingRedirects()->creer('pris@ex.test')->assertOk()
            ->assertSee('Cette adresse e-mail est déjà utilisée par un autre compte.', false);
    }

    public function test_email_invalide_ou_absent(): void
    {
        $this->creer('pas-un-email')->assertSessionHasErrors(['email' => 'L\'adresse e-mail est invalide.']);
        $this->creer('')->assertSessionHasErrors(['email' => 'L\'adresse e-mail est obligatoire.']);
        $this->assertSame(1, User::count());
    }

    public function test_modification_vers_un_email_deja_utilise(): void
    {
        User::factory()->create(['email' => 'pris@ex.test']);
        $cible = User::factory()->create(['email' => 'cible@ex.test']);
        $cible->assignRole('Demandeur');

        $this->actingAs($this->admin)->from('/users/'.$cible->id.'/edit')->put(route('users.update', $cible->id),
            ['name' => 'C', 'prenom' => 'P', 'email' => 'pris@ex.test', 'roles' => ['Demandeur']])
            ->assertSessionHasErrors(['email' => 'Cette adresse e-mail est déjà utilisée par un autre compte.']);
        $this->assertSame('cible@ex.test', $cible->fresh()->email);

        // Conserver sa propre adresse reste permis.
        $this->actingAs($this->admin)->put(route('users.update', $cible->id),
            ['name' => 'C2', 'prenom' => 'P', 'email' => 'cible@ex.test', 'roles' => ['Demandeur']])
            ->assertRedirect(route('users.index'));
        $this->assertSame('C2', $cible->fresh()->name);
    }

    public function test_creation_toujours_reservee_a_l_administration(): void
    {
        $demandeur = User::factory()->create();
        $demandeur->assignRole('Demandeur');

        $this->actingAs($demandeur)->post(route('users.store'), ['name' => 'X', 'prenom' => 'Y', 'email' => 'x@ex.test',
            'password' => 'Provisoire1', 'password_confirmation' => 'Provisoire1'])->assertForbidden();
        $this->assertNull(User::where('email', 'x@ex.test')->first());
    }
}
