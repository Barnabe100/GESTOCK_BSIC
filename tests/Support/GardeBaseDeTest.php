<?php

namespace Tests\Support;

use RuntimeException;

/**
 * Empêche l'exécution des tests sur une base qui ne serait pas une base de
 * test isolée. Les tests utilisent RefreshDatabase, qui supprime et recrée
 * toutes les tables de la base ciblée.
 */
final class GardeBaseDeTest
{
    public const SUFFIXE_OBLIGATOIRE = '_test';

    public static function verifier(string $environnement, string $connexion, ?string $base): void
    {
        if ($environnement !== 'testing') {
            throw new RuntimeException(
                "Tests interrompus : APP_ENV vaut \"{$environnement}\" au lieu de \"testing\"."
            );
        }

        if ($connexion === 'sqlite' && $base === ':memory:') {
            return;
        }

        if ($base === null || $base === '' || ! str_ends_with($base, self::SUFFIXE_OBLIGATOIRE)) {
            throw new RuntimeException(
                'Tests interrompus : la base "'.($base ?? '(vide)').'" n\'est pas une base de test isolée. '
                .'Le nom doit se terminer par "'.self::SUFFIXE_OBLIGATOIRE.'" (voir phpunit.xml et tests/README.md).'
            );
        }
    }
}
