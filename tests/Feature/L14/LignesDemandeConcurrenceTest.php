<?php

namespace Tests\Feature\L14;

use App\Models\Article;
use App\Models\ArticleDemande;
use App\Models\Demande;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * L1-4, workflow « articles d'une demande » : sérialisation avec la validation
 * du supérieur, prouvée par une SECONDE connexion MySQL réelle (processus PHP
 * séparé), comme pour L1-5b.
 *
 * DatabaseMigrations (et non RefreshDatabase) : les données doivent être
 * réellement validées pour être visibles de l'autre connexion. La base ciblée
 * reste la base de test isolée (garde-fou du lot 0).
 */
class LignesDemandeConcurrenceTest extends TestCase
{
    use DatabaseMigrations;

    private User $proprietaire;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();
        $this->proprietaire = User::factory()->create();
        $this->article = Article::factory()->create(['stock_disponible' => 50]);
    }

    private function demande(): Demande
    {
        return Demande::factory()->create(['user_id' => $this->proprietaire->id, 'statut' => 'En attente supérieur']);
    }

    /**
     * Exécute une requête dans un PROCESSUS SÉPARÉ (autre connexion MySQL),
     * avec un délai d'attente de verrou d'une seconde.
     */
    private function autreConnexion(string $sql, array $valeurs): array
    {
        $c = config('database.connections.'.config('database.default'));

        $script = <<<'PHP'
$pdo = new PDO('mysql:host='.getenv('C_HOST').';port='.getenv('C_PORT').';dbname='.getenv('C_DB'), getenv('C_USER'), getenv('C_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('SET SESSION innodb_lock_wait_timeout = 1');
$debut = microtime(true);
try {
    $st = $pdo->prepare(getenv('C_SQL'));
    $st->execute(json_decode(getenv('C_VALEURS'), true));
    echo json_encode(['statut' => 'ok', 'lignes' => $st->rowCount(), 'attente' => round(microtime(true) - $debut, 2)]);
} catch (PDOException $e) {
    echo json_encode(['statut' => 'erreur', 'code' => $e->errorInfo[1] ?? null, 'attente' => round(microtime(true) - $debut, 2)]);
}
PHP;

        $p = new Process([PHP_BINARY, '-r', $script], null, [
            'C_HOST' => $c['host'], 'C_PORT' => (string) $c['port'], 'C_DB' => $c['database'],
            'C_USER' => $c['username'], 'C_PASS' => (string) $c['password'],
            'C_SQL' => $sql, 'C_VALEURS' => json_encode($valeurs),
        ], null, 30);
        $p->run();

        return json_decode($p->getOutput(), true) ?? ['statut' => 'sortie illisible', 'brut' => $p->getOutput().$p->getErrorOutput()];
    }

    /**
     * Démarre, dans un PROCESSUS SÉPARÉ, une transaction qui verrouille la
     * demande, exécute une écriture, garde le verrou 1,5 s puis valide.
     */
    private function transactionConcurrente(int $demandeId, string $sql, array $valeurs): Process
    {
        $c = config('database.connections.'.config('database.default'));

        $script = <<<'PHP'
$pdo = new PDO('mysql:host='.getenv('C_HOST').';port='.getenv('C_PORT').';dbname='.getenv('C_DB'), getenv('C_USER'), getenv('C_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->beginTransaction();
$pdo->prepare('SELECT id FROM demandes WHERE id = ? FOR UPDATE')->execute([(int) getenv('C_DEMANDE')]);
$pdo->prepare(getenv('C_SQL'))->execute(json_decode(getenv('C_VALEURS'), true));
echo "verrou\n";
usleep(1500000);
$pdo->commit();
echo "valide\n";
PHP;

        $p = new Process([PHP_BINARY, '-r', $script], null, [
            'C_HOST' => $c['host'], 'C_PORT' => (string) $c['port'], 'C_DB' => $c['database'],
            'C_USER' => $c['username'], 'C_PASS' => (string) $c['password'],
            'C_DEMANDE' => (string) $demandeId, 'C_SQL' => $sql, 'C_VALEURS' => json_encode($valeurs),
        ], null, 30);
        $p->start();
        $p->waitUntil(fn ($type, $sortie) => str_contains($sortie, 'verrou'));

        return $p;
    }

    /**
     * Pendant l'écriture de la ligne (événement de modèle déclaré dans le test
     * seulement), une autre connexion tente de poser la validation du
     * supérieur : elle doit être BLOQUÉE (1205), alors qu'une autre demande
     * reste libre.
     */
    private function verifierVerrouPendant(string $evenement, Demande $demande, callable $requete): array
    {
        $autre = $this->demande();
        $observations = [];

        call_user_func([ArticleDemande::class, $evenement], function () use ($demande, $autre, &$observations) {
            $observations['meme_demande'] = $this->autreConnexion(
                'UPDATE demandes SET validation_superieur = 1 WHERE id = ?', [$demande->id]);
            $observations['autre_demande'] = $this->autreConnexion(
                'UPDATE demandes SET objet = ? WHERE id = ?', ['libre', $autre->id]);
        });

        $requete()->assertSessionHas('success');

        // 1205 = ER_LOCK_WAIT_TIMEOUT : la demande était verrouillée.
        $this->assertSame('erreur', $observations['meme_demande']['statut'], json_encode($observations));
        $this->assertSame(1205, $observations['meme_demande']['code']);
        $this->assertGreaterThanOrEqual(0.9, $observations['meme_demande']['attente']);
        // Verrou de ligne, et non de table : l'autre demande n'est pas bloquée.
        $this->assertSame('ok', $observations['autre_demande']['statut'], json_encode($observations));
        $this->assertLessThan(0.9, $observations['autre_demande']['attente']);
        $this->assertFalse((bool) $demande->fresh()->validation_superieur);

        return $observations;
    }

    public function test_l_ajout_verrouille_la_demande_pendant_l_ecriture(): void
    {
        $demande = $this->demande();

        $this->verifierVerrouPendant('creating', $demande, fn () => $this->actingAs($this->proprietaire)
            ->from('/demandes/'.$demande->id)
            ->post(route('article_demande.store'), [
                'demande_id' => $demande->id, 'article_id' => $this->article->id, 'quantite' => 2,
            ]));

        $this->assertSame(1, ArticleDemande::where('demande_id', $demande->id)->count());
    }

    public function test_la_modification_verrouille_la_demande_pendant_l_ecriture(): void
    {
        $demande = $this->demande();
        $ligne = ArticleDemande::factory()->create([
            'demande_id' => $demande->id, 'article_id' => $this->article->id, 'quantite' => 2,
        ]);

        $this->verifierVerrouPendant('updating', $demande, fn () => $this->actingAs($this->proprietaire)
            ->from('/demandes/'.$demande->id)
            ->put(route('article-demandes.update', $ligne->id), [
                'article_id' => $this->article->id, 'quantite' => 6,
            ]));

        $this->assertSame(6, (int) $ligne->fresh()->quantite);
    }

    public function test_la_suppression_verrouille_la_demande_pendant_l_ecriture(): void
    {
        $demande = $this->demande();
        $ligne = ArticleDemande::factory()->create([
            'demande_id' => $demande->id, 'article_id' => $this->article->id, 'quantite' => 2,
        ]);

        $this->verifierVerrouPendant('deleting', $demande, fn () => $this->actingAs($this->proprietaire)
            ->from('/demandes/'.$demande->id)
            ->delete(route('article_demande.destroy', $ligne->id)));

        $this->assertNull(ArticleDemande::find($ligne->id));
        $this->assertSame(50, (int) $this->article->fresh()->stock_disponible);
    }

    public function test_une_validation_du_superieur_validee_par_une_autre_connexion_bloque_l_ajout(): void
    {
        $demande = $this->demande();

        $resultat = $this->autreConnexion('UPDATE demandes SET validation_superieur = 1 WHERE id = ?', [$demande->id]);
        $this->assertSame('ok', $resultat['statut']);

        $this->actingAs($this->proprietaire)
            ->from('/demandes/'.$demande->id)
            ->post(route('article_demande.store'), [
                'demande_id' => $demande->id, 'article_id' => $this->article->id, 'quantite' => 2,
            ])
            ->assertSessionHas('error', 'Cette demande ne peut plus être modifiée.');

        $this->assertSame(0, ArticleDemande::count());
    }

    public function test_un_ajout_simultane_du_meme_article_ne_cree_pas_de_doublon(): void
    {
        $demande = $this->demande();

        // L'autre connexion verrouille la demande et y ajoute l'article, puis valide 1,5 s plus tard.
        $autre = $this->transactionConcurrente($demande->id,
            'INSERT INTO article_demande (demande_id, article_id, quantite, archiver, created_at, updated_at) VALUES (?, ?, 3, 0, NOW(), NOW())',
            [$demande->id, $this->article->id]);

        $debut = microtime(true);
        $this->actingAs($this->proprietaire)
            ->from('/demandes/'.$demande->id)
            ->post(route('article_demande.store'), [
                'demande_id' => $demande->id, 'article_id' => $this->article->id, 'quantite' => 2,
            ])
            ->assertSessionHas('error', 'Cet article est déjà présent dans cette demande.');
        $attente = microtime(true) - $debut;
        $autre->wait();

        $this->assertStringContainsString('valide', $autre->getOutput());
        $this->assertGreaterThanOrEqual(1.0, $attente, 'L\'ajout doit attendre la fin de l\'autre transaction.');
        $this->assertSame(1, ArticleDemande::where('demande_id', $demande->id)->count());
        $this->assertSame(3, (int) ArticleDemande::where('demande_id', $demande->id)->value('quantite'));
    }

    public function test_une_ligne_supprimee_pendant_l_attente_du_verrou_n_est_pas_modifiee(): void
    {
        $demande = $this->demande();
        $ligne = ArticleDemande::factory()->create([
            'demande_id' => $demande->id, 'article_id' => $this->article->id, 'quantite' => 2,
        ]);

        $autre = $this->transactionConcurrente($demande->id, 'DELETE FROM article_demande WHERE id = ?', [$ligne->id]);

        $this->actingAs($this->proprietaire)
            ->from('/demandes/'.$demande->id)
            ->put(route('article-demandes.update', $ligne->id), [
                'article_id' => $this->article->id, 'quantite' => 6,
            ])
            ->assertNotFound();
        $autre->wait();

        $this->assertStringContainsString('valide', $autre->getOutput());
        $this->assertSame(0, ArticleDemande::count());
    }

    public function test_une_validation_du_superieur_pendant_l_attente_du_verrou_bloque_la_suppression(): void
    {
        $demande = $this->demande();
        $ligne = ArticleDemande::factory()->create([
            'demande_id' => $demande->id, 'article_id' => $this->article->id, 'quantite' => 2,
        ]);

        $autre = $this->transactionConcurrente($demande->id,
            "UPDATE demandes SET validation_superieur = 1, statut = 'En attente gestionnaire' WHERE id = ?", [$demande->id]);

        $this->actingAs($this->proprietaire)
            ->from('/demandes/'.$demande->id)
            ->delete(route('article_demande.destroy', $ligne->id))
            ->assertSessionHas('error', 'Cette demande ne peut plus être modifiée.');
        $autre->wait();

        $this->assertNotNull(ArticleDemande::find($ligne->id));
    }
}
