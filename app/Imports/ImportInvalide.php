<?php

namespace App\Imports;

use RuntimeException;

/** Import refusé : liste des erreurs, avec leur numéro de ligne (aucune écriture conservée). */
class ImportInvalide extends RuntimeException
{
    /** @param list<string> $erreurs */
    public function __construct(public readonly array $erreurs)
    {
        parent::__construct(implode(' ', $erreurs));
    }
}
