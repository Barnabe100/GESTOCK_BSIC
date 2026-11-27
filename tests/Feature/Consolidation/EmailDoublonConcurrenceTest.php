<?php

namespace Tests\Feature\Consolidation;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\Support\RequeteDansUnAutreProcessus;
use Tests\TestCase;

/**
 * Deux créations simultanées du même e-mail : la vérification applicative
 * passe pour les deux (aucune n'est encore validée), la contrainte unique de
 * la base refuse la seconde, qui reçoit le message de validation (pas de 500).
 */
class EmailDoublonConcurrenceTest extends TestCase
{
    use DatabaseMigrations;
    use RequeteDansUnAutreProcessus;

    public function test_deux_creations_simultanees_du_meme_email(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['gestok.mot_de_passe_initial' => 'Initial#Doublon2026']);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $donnees = ['name' => 'N', 'prenom' => 'P', 'email' => 'simultane@ex.test', 'password' => 'Provisoire1', 'password_confirmation' => 'Provisoire1'];

        DB::beginTransaction();
        $this->actingAs($admin)->post(route('users.store'), $donnees)->assertRedirect(route('users.index'));

        $autre = $this->requeteDansUnAutreProcessus($admin->id, 'POST', '/users', $donnees);
        usleep(800_000);
        $this->assertTrue($autre->isRunning(), 'La seconde création doit attendre la première.');

        DB::commit();
        $resultat = $this->resultatProcessus($autre);

        $this->assertSame(302, $resultat['code'] ?? null, $resultat['brut']);
        $this->assertSame(['Cette adresse e-mail est déjà utilisée par un autre compte.'], $resultat['errors']['email'] ?? null, $resultat['brut']);
        $this->assertSame(1, User::where('email', 'simultane@ex.test')->count());
    }
}
