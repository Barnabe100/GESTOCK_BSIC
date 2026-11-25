<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * L1-2 : l'inscription publique est fermée. Les comptes sont créés par les
 * administrateurs (UserController) ou par import.
 *
 * (Ce fichier remplace les tests Breeze d'origine, qui vérifiaient que
 * l'auto-inscription fonctionnait.)
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_page_d_inscription_n_existe_plus(): void
    {
        $this->get('/register')->assertNotFound();
        $this->assertFalse(Route::has('register'));
        $this->assertFalse(Route::has('register.store'));
    }

    public function test_aucun_compte_ne_peut_etre_cree_par_inscription_publique(): void
    {
        $reponse = $this->post('/register', [
            'name' => 'Test',
            'prenom' => 'Utilisateur',
            'email' => 'inscription@example.com',
            'password' => 'Inscription#2026',
            'password_confirmation' => 'Inscription#2026',
        ]);

        $this->assertContains($reponse->getStatusCode(), [404, 405]);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'inscription@example.com']);
    }
}
