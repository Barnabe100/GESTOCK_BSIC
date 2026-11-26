<?php

namespace Tests\Feature\L14;

use App\Models\Article;
use App\Models\ArticleDemande;
use App\Models\Demande;
use App\Models\MouvementStock;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * L1-4b : livraisons concurrentes et interruption réelle, avec une SECONDE
 * connexion MySQL dans un processus PHP séparé qui appelle la vraie route
 * POST /demandes/{id}/livrer (noyau HTTP de l'application).
 *
 * DatabaseMigrations : les données doivent être validées pour être visibles
 * de l'autre connexion. Base de test isolée (garde-fou du lot 0).
 */
class LivraisonConcurrenceTest extends TestCase
{
    use DatabaseMigrations;

    private User $gestionnaire;

    protected function setUp(): void
    {
        parent::setUp();
        // Socle des droits : la livraison est réservée aux rôles Gestionnaire
        // et SuperAdmin ; le livreur de ces tests a donc le rôle Gestionnaire.
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->gestionnaire = User::factory()->create();
        $this->gestionnaire->assignRole('Gestionnaire');
    }

    protected function tearDown(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS l14_pause_sortie');
        parent::tearDown();
    }

    private function article(string $code, int $stock): Article
    {
        return Article::factory()->create(['code' => $code, 'libelle' => "Article $code", 'stock_disponible' => $stock]);
    }

    /** @param list<array{0: Article, 1: int}> $lignes [article, quantité accordée] */
    private function demandePrete(array $lignes): Demande
    {
        $demande = Demande::factory()->create([
            'user_id' => User::factory()->create()->id,
            'statut' => 'En attente livraison',
            'validation_superieur' => 1, 'validation_gestionnaire' => 1, 'validation_finale' => 1, 'livraison' => 0,
        ]);
        foreach ($lignes as [$article, $accordee]) {
            ArticleDemande::factory()->create([
                'demande_id' => $demande->id, 'article_id' => $article->id,
                'quantite' => $accordee, 'quantite_accordee' => $accordee,
            ]);
        }

        return $demande;
    }

    private function livrer(Demande $demande)
    {
        return $this->actingAs($this->gestionnaire)->from('/demandes-gestionnaire')->post(route('demandes.livrer', $demande->id));
    }

    /**
     * Démarre, dans un processus séparé, POST /demandes/{id}/livrer par le
     * noyau HTTP de l'application. Il écrit « debut » avant la requête, puis
     * une ligne JSON (horodatage de fin, code, messages).
     */
    private function livraisonDansUnAutreProcessus(Demande $demande): Process
    {
        $script = <<<'PHP'
require getenv('APP_BASE').'/vendor/autoload.php';
$app = require getenv('APP_BASE').'/bootstrap/app.php';
$noyau = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app['auth']->guard('web')->loginUsingId((int) getenv('C_UTILISATEUR'));
echo json_encode(['debut' => microtime(true)])."\n";
$reponse = $noyau->handle(Illuminate\Http\Request::create('/demandes/'.getenv('C_DEMANDE').'/livrer', 'POST', [], [], [], ['HTTP_REFERER' => 'http://localhost/demandes-gestionnaire']));
echo json_encode(['fin' => microtime(true), 'code' => $reponse->getStatusCode(),
    'error' => $app['session.store']->get('error'), 'success' => $app['session.store']->get('success')], JSON_UNESCAPED_UNICODE)."\n";
PHP;

        $p = new Process([PHP_BINARY, '-r', $script], base_path(), [
            'APP_BASE' => base_path(), 'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => config('database.connections.mysql.database'),
            'SESSION_DRIVER' => 'array', 'CACHE_DRIVER' => 'array',
            'C_UTILISATEUR' => (string) $this->gestionnaire->id, 'C_DEMANDE' => (string) $demande->id,
        ], null, 60);
        $p->start();
        $p->waitUntil(fn ($type, $sortie) => str_contains($sortie, '"debut"'));

        return $p;
    }

    private function resultat(Process $p): array
    {
        $p->wait();
        $lignes = array_values(array_filter(explode("\n", trim($p->getOutput()))));

        return (json_decode(end($lignes), true) ?? []) + ['brut' => $p->getOutput().$p->getErrorOutput()];
    }

    private function sorties(Demande $demande): array
    {
        return MouvementStock::where('type', 'SORTIE')->where('motif', 'Livraison demande N° '.$demande->id)->orderBy('id')
            ->get(['article_id', 'quantite'])->map(fn ($m) => [(int) $m->article_id, (int) $m->quantite])->all();
    }

    private function stock(Article $article): int
    {
        return (int) $article->fresh()->stock_disponible;
    }

    /**
     * Pendant la première livraison (au moment de sa première sortie), une
     * seconde livraison démarre dans l'autre processus et doit ATTENDRE.
     */
    private function pendantLaPremiereSortie(Demande $autre): \stdClass
    {
        $etat = (object) ['processus' => null];
        MouvementStock::creating(function () use ($autre, $etat) {
            if ($etat->processus === null) {
                $etat->processus = $this->livraisonDansUnAutreProcessus($autre);
                usleep(500_000); // laisse à l'autre requête le temps d'atteindre le verrou
            }
        });

        return $etat;
    }

    // ------------------------------------------------------------------

    public function test_deux_livraisons_simultanees_de_la_meme_demande(): void
    {
        [$x, $y] = [$this->article('X', 10), $this->article('Y', 10)];
        $demande = $this->demandePrete([[$x, 2], [$y, 3]]);
        $etat = $this->pendantLaPremiereSortie($demande);

        $this->livrer($demande)->assertSessionHas('success', 'Demande livrée');
        $finPremiere = microtime(true);
        $seconde = $this->resultat($etat->processus);

        // La seconde a démarré pendant la première, a attendu sa fin, puis a relu l'état réel.
        $this->assertSame(302, $seconde['code'] ?? null, json_encode($seconde));
        $this->assertSame('Demande déjà livrée', $seconde['error'], json_encode($seconde));
        $this->assertNull($seconde['success']);
        $this->assertGreaterThan($finPremiere - 0.05, $seconde['fin'], 'La seconde livraison doit attendre la fin de la première.');

        $this->assertSame([[$x->id, 2], [$y->id, 3]], $this->sorties($demande), 'Une seule série de sorties.');
        $this->assertSame([8, 7], [$this->stock($x), $this->stock($y)]);
    }

    public function test_deux_demandes_simultanees_sur_le_meme_article_jamais_de_stock_negatif(): void
    {
        // Stock Y = 10 ; A demande X 1 et Y 7 ; B demande Y 8 : une seule des deux peut être servie.
        [$x, $y] = [$this->article('X', 10), $this->article('Y', 10)];
        $a = $this->demandePrete([[$x, 1], [$y, 7]]);
        $b = $this->demandePrete([[$y, 8]]);
        $etat = $this->pendantLaPremiereSortie($b);

        $this->livrer($a)->assertSessionHas('success', 'Demande livrée');
        $resultatB = $this->resultat($etat->processus);

        $this->assertSame('Stock insuffisant pour Article Y', $resultatB['error'], json_encode($resultatB));
        $this->assertSame([9, 3], [$this->stock($x), $this->stock($y)]);
        $this->assertGreaterThanOrEqual(0, $this->stock($y));
        $this->assertSame([], $this->sorties($b), 'Aucune sortie, aucune livraison partielle pour B.');
        $this->assertFalse((bool) $b->fresh()->livraison);
    }

    public function test_deux_livraisons_simultanees_aux_articles_croises_sans_interblocage(): void
    {
        [$x, $y] = [$this->article('X', 10), $this->article('Y', 10)];
        $a = $this->demandePrete([[$x, 2], [$y, 3]]);
        $b = $this->demandePrete([[$y, 4], [$x, 1]]); // ordre des lignes inversé
        $etat = $this->pendantLaPremiereSortie($b);

        $this->livrer($a)->assertSessionHas('success', 'Demande livrée');
        $resultatB = $this->resultat($etat->processus);

        $this->assertSame('Demande livrée', $resultatB['success'], json_encode($resultatB));
        $this->assertSame([7, 3], [$this->stock($x), $this->stock($y)]);
        $this->assertSame([[$y->id, 4], [$x->id, 1]], $this->sorties($b));
    }

    public function test_processus_tue_pendant_la_transaction_aucune_ecriture_puis_relance(): void
    {
        [$x, $y] = [$this->article('X', 10), $this->article('Y', 10)];
        $demande = $this->demandePrete([[$x, 2], [$y, 3]]);

        // L'autre processus est mis en pause (SLEEP) dans l'insertion de la sortie
        // du 2e article : il a donc déjà décrémenté X, enregistré sa sortie et
        // décrémenté Y. On l'observe dans la liste des connexions, où un compte
        // voit ses propres connexions sans privilège particulier : état
        // « User sleep », instruction « DO SLEEP » (relevé sur MariaDB et MySQL).
        DB::unprepared("CREATE TRIGGER l14_pause_sortie BEFORE INSERT ON mouvement_stocks FOR EACH ROW
            BEGIN IF NEW.article_id = {$y->id} THEN DO SLEEP(3); END IF; END");

        $p = $this->livraisonDansUnAutreProcessus($demande);
        $enPause = fn () => DB::table('information_schema.PROCESSLIST')
            ->where('STATE', 'User sleep')
            ->where('INFO', 'like', 'DO SLEEP%') // instruction du déclencheur, propre à la sortie de Y
            ->exists();
        $limite = microtime(true) + 20;
        while (! $enPause() && $p->isRunning() && microtime(true) < $limite) {
            usleep(50_000);
        }
        $this->assertTrue($enPause(), "L'autre processus doit être en pause dans la deuxième sortie. ".$p->getOutput());

        $p->signal(9); // arrêt brutal (kill -9) en pleine transaction
        $p->wait();
        $this->assertStringNotContainsString('"fin"', $p->getOutput(), "L'autre processus ne doit pas avoir terminé sa livraison.");

        // Attente de la fin de la transaction interrompue : prise du verrou sur la demande.
        DB::statement('SET SESSION innodb_lock_wait_timeout = 30');
        DB::transaction(fn () => Demande::whereKey($demande->id)->lockForUpdate()->first());

        $this->assertSame([10, 10], [$this->stock($x), $this->stock($y)], 'Aucun décrément ne doit subsister.');
        $this->assertSame(0, MouvementStock::count(), 'Aucune sortie ne doit subsister.');
        $this->assertFalse((bool) $demande->fresh()->livraison);

        DB::unprepared('DROP TRIGGER l14_pause_sortie');
        $this->livrer($demande)->assertSessionHas('success', 'Demande livrée');
        $this->assertSame([[$x->id, 2], [$y->id, 3]], $this->sorties($demande));
        $this->assertSame([8, 7], [$this->stock($x), $this->stock($y)]);
    }
}
