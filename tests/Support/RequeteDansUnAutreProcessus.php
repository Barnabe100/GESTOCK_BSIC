<?php

namespace Tests\Support;

use Symfony\Component\Process\Process;

/**
 * Exécute une requête HTTP de l'application dans un PROCESSUS SÉPARÉ (autre
 * connexion à la base), pour éprouver les verrous et contraintes face à des
 * opérations réellement concurrentes. Même principe que les tests de
 * concurrence de L1-4b.
 */
trait RequeteDansUnAutreProcessus
{
    protected function requeteDansUnAutreProcessus(int $utilisateurId, string $methode, string $uri, array $donnees = []): Process
    {
        $script = <<<'PHP'
require getenv('APP_BASE').'/vendor/autoload.php';
$app = require getenv('APP_BASE').'/bootstrap/app.php';
$noyau = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app['auth']->guard('web')->loginUsingId((int) getenv('C_UTILISATEUR'));
echo json_encode(['debut' => microtime(true)])."\n";
$requete = Illuminate\Http\Request::create(getenv('C_URI'), getenv('C_METHODE'), json_decode(getenv('C_DONNEES'), true), [], [], ['HTTP_REFERER' => 'http://localhost/precedente']);
$reponse = $noyau->handle($requete);
echo json_encode(['fin' => microtime(true), 'code' => $reponse->getStatusCode(),
    'error' => $app['session.store']->get('error'), 'success' => $app['session.store']->get('success'),
    'errors' => ($e = $app['session.store']->get('errors')) ? $e->getBag('default')->toArray() : []], JSON_UNESCAPED_UNICODE)."\n";
PHP;

        $p = new Process([PHP_BINARY, '-r', $script], base_path(), [
            'APP_BASE' => base_path(), 'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => config('database.connections.mysql.database'),
            'DB_PORT' => (string) config('database.connections.mysql.port'),
            'SESSION_DRIVER' => 'array', 'CACHE_DRIVER' => 'array',
            'C_UTILISATEUR' => (string) $utilisateurId, 'C_METHODE' => $methode, 'C_URI' => $uri,
            'C_DONNEES' => json_encode($donnees),
        ], null, 60);
        $p->start();
        $p->waitUntil(fn ($type, $sortie) => str_contains($sortie, '"debut"'));

        return $p;
    }

    protected function resultatProcessus(Process $p): array
    {
        $p->wait();
        $lignes = array_values(array_filter(explode("\n", trim($p->getOutput()))));

        return (json_decode(end($lignes), true) ?? []) + ['brut' => $p->getOutput().$p->getErrorOutput()];
    }
}
