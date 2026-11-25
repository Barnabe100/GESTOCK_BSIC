<?php

namespace Tests\Feature\Lot1;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use PDOException;
use RuntimeException;
use Tests\TestCase;

/**
 * L1-5b : rotation atomique, revérification sous verrou, empreintes
 * distinctes, journalisation sans donnée sensible.
 *
 * Valeurs fictives (données de test).
 */
class RotationAtomiqueTest extends TestCase
{
    use RefreshDatabase;

    private const INITIAL = 'Initial#Atomique26';
    private const EXPOSEE = 'Ancien#ExposeX9';
    private const PERSO = 'Personnel#Choisi7';
    private const PERSO_PENDANT = 'Choisi#Pendant42';
    private const Q1 = 'Valeur exposée n°1 (laisser vide pour terminer)';
    private const Q2 = 'Valeur exposée n°2 (laisser vide pour terminer)';
    private const CONFIRMATION_3 = 'Attribuer le nouveau mot de passe initial à ces 3 compte(s), avec changement obligatoire à la prochaine connexion ?';

    /** @var list<array{niveau: string, message: string, contexte: string}> */
    private array $journal = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['gestok.mot_de_passe_initial' => self::INITIAL]);

        Event::listen(MessageLogged::class, function (MessageLogged $e) {
            $this->journal[] = [
                'niveau' => $e->level,
                'message' => $e->message,
                'contexte' => json_encode($e->context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ];
        });
    }

    protected function tearDown(): void
    {
        User::flushEventListeners();
        parent::tearDown();
    }

    /**
     * 3 comptes exposés + 1 compte non concerné.
     *
     * @return array{exposes: list<User>, sain: User}
     */
    private function jeuDeComptes(): array
    {
        $exposes = [];
        foreach (['un', 'deux', 'trois'] as $n) {
            $exposes[] = User::factory()->create([
                'email' => "expose.{$n}@example.com",
                'password' => Hash::make(self::EXPOSEE),
                'force_password_change' => false,
                'remember_token' => "jeton-{$n}",
            ]);
        }

        $sain = User::factory()->create([
            'email' => 'sain@example.com',
            'password' => Hash::make(self::PERSO),
            'remember_token' => 'jeton-sain',
        ]);

        return ['exposes' => $exposes, 'sain' => $sain];
    }

    /** @return array<int, string> id => empreinte|obligation|jeton */
    private function etat(): array
    {
        return User::orderBy('id')->get()
            ->mapWithKeys(fn (User $u) => [$u->id => $u->password.'|'.(int) $u->force_password_change.'|'.$u->remember_token])
            ->all();
    }

    private function appliquer(string $confirmation = self::CONFIRMATION_3)
    {
        return $this->artisan('gestok:mots-de-passe-exposes', ['--appliquer' => true])
            ->expectsQuestion(self::Q1, self::EXPOSEE)
            ->expectsQuestion(self::Q2, '')
            ->expectsConfirmation($confirmation, 'yes');
    }

    private function journalContient(string $fragment): bool
    {
        foreach ($this->journal as $ligne) {
            if (str_contains($ligne['message'], $fragment)) {
                return true;
            }
        }

        return false;
    }

    // ------------------------------------------------------------------

    public function test_rotation_complete_reussie_avec_empreintes_distinctes(): void
    {
        ['exposes' => $exposes, 'sain' => $sain] = $this->jeuDeComptes();
        $etatSain = $this->etat()[$sain->id];

        $this->appliquer()
            ->expectsOutputToContain('Rotation appliquée à 3 compte(s)')
            ->assertSuccessful();

        $empreintes = [];
        foreach ($exposes as $u) {
            $u->refresh();
            $this->assertTrue(Hash::check(self::INITIAL, $u->password));
            $this->assertFalse(Hash::check(self::EXPOSEE, $u->password));
            $this->assertTrue((bool) $u->force_password_change);
            $this->assertNotSame('jeton-'.explode('.', explode('@', $u->email)[0])[1], $u->remember_token);
            $empreintes[] = $u->password;
        }

        // Une empreinte distincte (sel distinct) par compte.
        $this->assertCount(3, array_unique($empreintes));

        // Compte non concerné strictement préservé.
        $this->assertSame($etatSain, $this->etat()[$sain->id]);

        $this->assertTrue($this->journalContient('rotation démarrée'));
        $this->assertTrue($this->journalContient('rotation appliquée'));
    }

    public function test_erreur_au_milieu_aucune_modification_partielle(): void
    {
        $this->jeuDeComptes();
        $avant = $this->etat();

        // Panne simulée lors de l'écriture du 2e compte.
        User::updating(function (User $u) {
            if ($u->email === 'expose.deux@example.com') {
                throw new RuntimeException('Panne simulée');
            }
        });

        $this->appliquer()
            ->expectsOutputToContain('Rotation annulée : une erreur est survenue, aucun compte n\'a été modifié.')
            ->assertFailed();

        // Le 1er compte, pourtant traité avant la panne, a été annulé.
        $this->assertSame($avant, $this->etat());

        $this->assertTrue($this->journalContient('rotation annulée, aucune modification'));
        $this->assertFalse($this->journalContient('rotation appliquée'));
    }

    public function test_relance_apres_erreur(): void
    {
        ['exposes' => $exposes] = $this->jeuDeComptes();

        User::updating(function (User $u) {
            if ($u->email === 'expose.deux@example.com') {
                throw new RuntimeException('Panne simulée');
            }
        });
        $this->appliquer()->assertFailed();

        // Cause corrigée : relance.
        User::flushEventListeners();

        $this->appliquer()
            ->expectsOutputToContain('Rotation appliquée à 3 compte(s)')
            ->assertSuccessful();

        foreach ($exposes as $u) {
            $this->assertTrue(Hash::check(self::INITIAL, $u->fresh()->password));
        }
    }

    public function test_compte_qui_change_son_mot_de_passe_pendant_la_preparation_est_preserve(): void
    {
        ['exposes' => $exposes, 'sain' => $sain] = $this->jeuDeComptes();
        $change = $exposes[1];

        // Juste après la confirmation et avant la transaction (journal
        // « rotation démarrée »), l'utilisateur change son mot de passe.
        Event::listen(MessageLogged::class, function (MessageLogged $e) use ($change) {
            if (str_contains($e->message, 'rotation démarrée')) {
                $change->forceFill([
                    'password' => Hash::make(self::PERSO_PENDANT),
                    'force_password_change' => false,
                ])->save();
            }
        });

        $this->appliquer()
            ->expectsOutputToContain('Rotation appliquée à 2 compte(s)')
            ->expectsOutputToContain('1 compte(s) ignoré(s) : mot de passe modifié (ou compte supprimé) depuis la détection.')
            ->assertSuccessful();

        $change->refresh();
        $this->assertTrue(Hash::check(self::PERSO_PENDANT, $change->password), 'Le mot de passe choisi pendant la préparation doit être conservé.');
        $this->assertFalse((bool) $change->force_password_change);

        foreach ([$exposes[0], $exposes[2]] as $u) {
            $this->assertTrue(Hash::check(self::INITIAL, $u->fresh()->password));
        }
        $this->assertTrue(Hash::check(self::PERSO, $sain->fresh()->password));

        $appliquee = collect($this->journal)->first(fn ($l) => str_contains($l['message'], 'rotation appliquée'));
        $this->assertStringContainsString('"ignores":['.$change->id.']', $appliquee['contexte']);
    }

    public function test_compte_supprime_pendant_la_preparation_est_ignore(): void
    {
        ['exposes' => $exposes] = $this->jeuDeComptes();
        $supprime = $exposes[2];

        Event::listen(MessageLogged::class, function (MessageLogged $e) use ($supprime) {
            if (str_contains($e->message, 'rotation démarrée')) {
                $supprime->delete();
            }
        });

        $this->appliquer()
            ->expectsOutputToContain('Rotation appliquée à 2 compte(s)')
            ->expectsOutputToContain('1 compte(s) ignoré(s)')
            ->assertSuccessful();

        $this->assertNull(User::find($supprime->id));
    }

    public function test_simulation_par_defaut_sans_aucune_modification_ni_ecriture(): void
    {
        $this->jeuDeComptes();
        $avant = $this->etat();

        $this->artisan('gestok:mots-de-passe-exposes')
            ->expectsQuestion(self::Q1, self::EXPOSEE)
            ->expectsQuestion(self::Q2, '')
            ->expectsOutputToContain('Mode SIMULATION')
            ->expectsOutputToContain('3 compte(s) utilise(nt) encore une valeur exposée')
            ->assertSuccessful();

        $this->assertSame($avant, $this->etat());
        $this->assertFalse($this->journalContient('rotation démarrée'));
        $this->assertFalse($this->journalContient('rotation appliquée'));
    }

    public function test_confidentialite_des_journaux_et_de_la_sortie_en_cas_de_succes(): void
    {
        $this->jeuDeComptes();

        $this->appliquer()
            ->doesntExpectOutputToContain(self::EXPOSEE)
            ->doesntExpectOutputToContain(self::INITIAL)
            ->doesntExpectOutputToContain('$2y$')
            ->assertSuccessful();

        $this->assertJournalSansSecret();
    }

    public function test_confidentialite_en_cas_d_erreur_sql_contenant_des_valeurs(): void
    {
        $this->jeuDeComptes();

        // Une QueryException embarque la requête ET ses valeurs liées
        // (empreinte, jeton) : elles ne doivent jamais être journalisées.
        User::updating(function (User $u) {
            if ($u->email === 'expose.deux@example.com') {
                throw new QueryException(
                    'mysql',
                    'update `users` set `password` = ?, `remember_token` = ? where `id` = ?',
                    [$u->password, $u->remember_token, $u->id],
                    new PDOException('SQLSTATE[HY000]: General error: 2006 MySQL server has gone away')
                );
            }
        });

        $this->appliquer()
            ->expectsOutputToContain('Détail technique : SQLSTATE[HY000]: General error: 2006 MySQL server has gone away')
            ->doesntExpectOutputToContain('$2y$')
            ->doesntExpectOutputToContain(self::INITIAL)
            ->doesntExpectOutputToContain(self::EXPOSEE)
            ->assertFailed();

        $this->assertJournalSansSecret();

        $erreur = collect($this->journal)->first(fn ($l) => $l['niveau'] === 'error');
        $this->assertNotNull($erreur, 'L\'échec doit être journalisé.');
        $this->assertStringContainsString('QueryException', $erreur['contexte']);
        $this->assertStringContainsString('2006 MySQL server has gone away', $erreur['contexte']);
    }

    private function assertJournalSansSecret(): void
    {
        $this->assertNotEmpty($this->journal);

        $jetons = User::pluck('remember_token')->filter()->all();

        foreach ($this->journal as $ligne) {
            $texte = $ligne['message'].' '.$ligne['contexte'];

            foreach ([self::EXPOSEE, self::INITIAL, self::PERSO, '$2y$', '$2b$', 'password'] as $interdit) {
                $this->assertStringNotContainsString($interdit, $texte, "Donnée sensible dans le journal : {$ligne['message']}");
            }
            foreach ($jetons as $jeton) {
                $this->assertStringNotContainsString($jeton, $texte);
            }
        }
    }
}
