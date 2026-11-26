<?php

namespace Tests\Feature\Lot1;

use App\Console\Commands\ControlerMotsDePasseExposes;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * L1-5b : preuves de protection contre les modifications concurrentes, avec
 * une SECONDE connexion MySQL réelle (processus PHP séparé) et une panne SQL
 * réelle (déclencheur).
 *
 * DatabaseMigrations (et non RefreshDatabase) : les données doivent être
 * réellement validées pour être visibles d'une autre connexion. La base
 * ciblée reste la base de test isolée (garde-fou du lot 0).
 */
class RotationConcurrenceTest extends TestCase
{
    use DatabaseMigrations;

    private const INITIAL = 'Initial#Concur2026';
    private const EXPOSEE = 'Ancien#ExposeZ3';
    private const PERSO_CONCURRENT = 'Choisi#Concurrent8';

    protected function setUp(): void
    {
        parent::setUp();
        config(['gestok.mot_de_passe_initial' => self::INITIAL]);
    }

    protected function tearDown(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS panne_rotation_test');
        parent::tearDown();
    }

    /** @return list<User> */
    private function comptesExposes(int $nombre): array
    {
        $comptes = [];
        for ($i = 1; $i <= $nombre; $i++) {
            $comptes[] = User::factory()->create([
                'email' => "concurrence{$i}@example.com",
                'password' => Hash::make(self::EXPOSEE),
            ]);
        }

        return $comptes;
    }

    /**
     * Lance, dans un PROCESSUS SÉPARÉ (autre connexion MySQL), une requête
     * UPDATE sur la table users.
     */
    private function processusConcurrent(string $sql, array $valeurs, int $attenteVerrouSecondes): Process
    {
        $c = config('database.connections.'.config('database.default'));

        $script = <<<'PHP'
$pdo = new PDO('mysql:host='.getenv('C_HOST').';port='.getenv('C_PORT').';dbname='.getenv('C_DB'), getenv('C_USER'), getenv('C_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('SET SESSION innodb_lock_wait_timeout = '.(int) getenv('C_ATTENTE'));
$debut = microtime(true);
try {
    $st = $pdo->prepare(getenv('C_SQL'));
    $st->execute(json_decode(getenv('C_VALEURS'), true));
    echo json_encode(['statut' => 'ok', 'lignes' => $st->rowCount(), 'attente' => round(microtime(true) - $debut, 2)]);
} catch (PDOException $e) {
    echo json_encode(['statut' => 'erreur', 'code' => $e->errorInfo[1] ?? null, 'attente' => round(microtime(true) - $debut, 2)]);
}
PHP;

        return new Process([PHP_BINARY, '-r', $script], null, [
            'C_HOST' => $c['host'], 'C_PORT' => (string) $c['port'], 'C_DB' => $c['database'],
            'C_USER' => $c['username'], 'C_PASS' => (string) $c['password'],
            'C_ATTENTE' => (string) $attenteVerrouSecondes,
            'C_SQL' => $sql, 'C_VALEURS' => json_encode($valeurs),
        ], null, 30);
    }

    private function commande(): ControlerMotsDePasseExposes
    {
        return $this->app->make(ControlerMotsDePasseExposes::class);
    }

    // ------------------------------------------------------------------

    public function test_les_lignes_candidates_sont_verrouillees_pendant_la_rotation(): void
    {
        [$a, $b] = $this->comptesExposes(2);
        $nonConcerne = User::factory()->create(['email' => 'autre@example.com']);
        $observations = [];

        $this->commande()->appliquerRotation([$a->id, $b->id], [self::EXPOSEE], self::INITIAL,
            function () use ($a, $nonConcerne, &$observations) {
                // Compte candidat : l'autre connexion est BLOQUÉE (attente du verrou).
                $p = $this->processusConcurrent('UPDATE users SET name = ? WHERE id = ?', ['Concurrent', $a->id], 1);
                $p->run();
                $observations['candidat'] = json_decode($p->getOutput(), true);

                // Compte non concerné : AUCUN blocage.
                $p = $this->processusConcurrent('UPDATE users SET name = ? WHERE id = ?', ['Libre', $nonConcerne->id], 1);
                $p->run();
                $observations['non_concerne'] = json_decode($p->getOutput(), true);
            });

        // 1205 = ER_LOCK_WAIT_TIMEOUT : la ligne était verrouillée par la rotation.
        $this->assertSame('erreur', $observations['candidat']['statut'], json_encode($observations));
        $this->assertSame(1205, $observations['candidat']['code']);
        $this->assertGreaterThanOrEqual(0.9, $observations['candidat']['attente']);

        $this->assertSame('ok', $observations['non_concerne']['statut']);
        $this->assertLessThan(0.9, $observations['non_concerne']['attente']);

        // La rotation a abouti ; la modification bloquée n'a pas eu lieu.
        $this->assertTrue(Hash::check(self::INITIAL, $a->fresh()->password));
        $this->assertNotSame('Concurrent', $a->fresh()->name);
        $this->assertSame('Libre', $nonConcerne->fresh()->name);
    }

    public function test_un_changement_de_mot_de_passe_concurrent_attend_puis_l_emporte(): void
    {
        [$a, $b] = $this->comptesExposes(2);
        $empreintePerso = Hash::make(self::PERSO_CONCURRENT);
        $concurrent = null;

        $resultat = $this->commande()->appliquerRotation([$a->id, $b->id], [self::EXPOSEE], self::INITIAL,
            function () use ($a, $empreintePerso, &$concurrent) {
                // L'utilisateur A valide son nouveau mot de passe pendant la rotation.
                $concurrent = $this->processusConcurrent(
                    'UPDATE users SET password = ?, force_password_change = 0 WHERE id = ?',
                    [$empreintePerso, $a->id], 20
                );
                $concurrent->start();
                usleep(1_500_000); // la transaction de rotation reste ouverte 1,5 s
                $this->assertTrue($concurrent->isRunning(), 'La requête concurrente doit être en attente du verrou.');
            });

        $concurrent->wait();
        $sortie = json_decode($concurrent->getOutput(), true);

        $this->assertSame(['traites' => [$a->id, $b->id], 'ignores' => []], $resultat);
        $this->assertSame('ok', $sortie['statut']);
        $this->assertGreaterThanOrEqual(1.0, $sortie['attente'], 'La requête concurrente a dû attendre la fin de la rotation.');

        // Pas d'écriture entrelacée : le choix de l'utilisateur, postérieur,
        // est conservé ; B est renouvelé normalement.
        $this->assertTrue(Hash::check(self::PERSO_CONCURRENT, $a->fresh()->password));
        $this->assertFalse((bool) $a->fresh()->force_password_change);
        $this->assertTrue(Hash::check(self::INITIAL, $b->fresh()->password));
    }

    public function test_deux_rotations_simultanees_ne_traitent_chaque_compte_qu_une_fois(): void
    {
        [$a, $b] = $this->comptesExposes(2);
        $seconde = null;

        $c = config('database.connections.'.config('database.default'));
        $racine = base_path();

        $premiere = $this->commande()->appliquerRotation([$a->id, $b->id], [self::EXPOSEE], self::INITIAL,
            function () use (&$seconde, $racine, $a, $b) {
                // Une seconde rotation (autre processus, application complète)
                // démarre pendant que la première détient les verrous.
                $script = 'require "'.$racine.'/vendor/autoload.php"; $app = require "'.$racine.'/bootstrap/app.php"; '
                    .'$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); '
                    .'echo json_encode($app->make(App\Console\Commands\ControlerMotsDePasseExposes::class)'
                    .'->appliquerRotation(['.$a->id.','.$b->id.'], [getenv("C_EXPOSEE")], getenv("C_INITIAL")));';
                $seconde = new Process([PHP_BINARY, '-r', $script], $racine, [
                    'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => config('database.connections.mysql.database'),
                    'C_EXPOSEE' => self::EXPOSEE, 'C_INITIAL' => self::INITIAL,
                ], null, 60);
                $seconde->start();
                usleep(1_500_000);
                $this->assertTrue($seconde->isRunning(), 'La seconde rotation doit attendre les verrous.');
            });

        $seconde->wait();
        $resultatSeconde = json_decode($seconde->getOutput(), true);

        $this->assertSame(['traites' => [$a->id, $b->id], 'ignores' => []], $premiere);
        // La seconde voit, sous verrou, les mots de passe déjà renouvelés : elle n'écrit rien.
        $this->assertSame(['traites' => [], 'ignores' => [$a->id, $b->id]], $resultatSeconde, $seconde->getErrorOutput());
    }

    public function test_panne_sql_reelle_annule_tout_puis_relance(): void
    {
        $comptes = $this->comptesExposes(3);
        $avant = User::orderBy('id')->pluck('password', 'id')->all();
        $journal = [];
        Event::listen(MessageLogged::class, function (MessageLogged $e) use (&$journal) {
            $journal[] = $e->message.' '.json_encode($e->context, JSON_UNESCAPED_SLASHES);
        });

        DB::unprepared("CREATE TRIGGER panne_rotation_test BEFORE UPDATE ON users FOR EACH ROW
            BEGIN IF NEW.email = 'concurrence2@example.com' THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Panne SQL de test';
            END IF; END");

        $this->artisan('gestok:mots-de-passe-exposes', ['--appliquer' => true])
            ->expectsQuestion('Valeur exposée n°1 (laisser vide pour terminer)', self::EXPOSEE)
            ->expectsQuestion('Valeur exposée n°2 (laisser vide pour terminer)', '')
            ->expectsConfirmation('Attribuer le nouveau mot de passe initial à ces 3 compte(s), avec changement obligatoire à la prochaine connexion ?', 'yes')
            ->expectsOutputToContain('Rotation annulée : une erreur est survenue, aucun compte n\'a été modifié.')
            ->expectsOutputToContain('Panne SQL de test')
            ->doesntExpectOutputToContain('$2y$')
            ->assertFailed();

        // Rien n'a été conservé, y compris le 1er compte écrit avant la panne.
        $this->assertSame($avant, User::orderBy('id')->pluck('password', 'id')->all());

        $texte = implode("\n", $journal);
        $this->assertStringContainsString('rotation annulée, aucune modification', $texte);
        $this->assertStringContainsString('Panne SQL de test', $texte);
        $this->assertStringNotContainsString('$2y$', $texte);
        $this->assertStringNotContainsString(self::EXPOSEE, $texte);
        $this->assertStringNotContainsString(self::INITIAL, $texte);

        // Relance après correction de la cause.
        DB::unprepared('DROP TRIGGER panne_rotation_test');

        $this->artisan('gestok:mots-de-passe-exposes', ['--appliquer' => true])
            ->expectsQuestion('Valeur exposée n°1 (laisser vide pour terminer)', self::EXPOSEE)
            ->expectsQuestion('Valeur exposée n°2 (laisser vide pour terminer)', '')
            ->expectsConfirmation('Attribuer le nouveau mot de passe initial à ces 3 compte(s), avec changement obligatoire à la prochaine connexion ?', 'yes')
            ->expectsOutputToContain('Rotation appliquée à 3 compte(s)')
            ->assertSuccessful();

        foreach ($comptes as $u) {
            $this->assertTrue(Hash::check(self::INITIAL, $u->fresh()->password));
        }
    }
}
