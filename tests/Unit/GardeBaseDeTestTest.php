<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\GardeBaseDeTest;

/**
 * Lot 0 : le garde-fou doit refuser toute base qui n'est pas une base de test isolée.
 */
class GardeBaseDeTestTest extends TestCase
{
    public function test_accepte_une_base_mysql_suffixee_test(): void
    {
        GardeBaseDeTest::verifier('testing', 'mysql', 'gestok_test');
        $this->addToAssertionCount(1);
    }

    public function test_accepte_sqlite_en_memoire(): void
    {
        GardeBaseDeTest::verifier('testing', 'sqlite', ':memory:');
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider basesRefusees
     */
    public function test_refuse_les_bases_non_isolees(string $env, string $connexion, ?string $base): void
    {
        $this->expectException(RuntimeException::class);
        GardeBaseDeTest::verifier($env, $connexion, $base);
    }

    public static function basesRefusees(): array
    {
        return [
            'base de production' => ['testing', 'mysql', 'gestok'],
            'suffixe absent' => ['testing', 'mysql', 'gestokbsic'],
            'suffixe au milieu' => ['testing', 'mysql', 'gestok_test_prod'],
            'nom vide' => ['testing', 'mysql', ''],
            'nom absent' => ['testing', 'mysql', null],
            'sqlite fichier' => ['testing', 'sqlite', '/var/www/database.sqlite'],
            'environnement production' => ['production', 'mysql', 'gestok_test'],
            'environnement local' => ['local', 'mysql', 'gestok_test'],
        ];
    }
}
