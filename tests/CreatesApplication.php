<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Tests\Support\GardeBaseDeTest;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Garde-fou : arrête les tests AVANT toute migration (RefreshDatabase)
        // si la base ciblée n'est pas une base de test isolée.
        $connexion = $app['config']->get('database.default');

        GardeBaseDeTest::verifier(
            (string) $app->environment(),
            (string) $connexion,
            $app['config']->get("database.connections.{$connexion}.database")
        );

        return $app;
    }
}
