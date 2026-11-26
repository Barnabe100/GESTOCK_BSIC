<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Entité organisationnelle (N03) : le siège ou une agence.
 *
 * Le type est un attribut explicite (colonne `type`), jamais déduit du nom :
 * un renommage ne change pas le type. Une entité de type NULL n'est pas encore
 * qualifiée (données antérieures à N03, à régulariser par décision humaine).
 * L'unicité du siège actif est garantie par la base (index unique
 * agences_un_seul_siege_actif) ; les changements passent par
 * App\Organisation\StructureOrganisationnelle.
 */
class Agence extends Model
{
    use HasFactory;

    public const TYPE_SIEGE = 'SIEGE';

    public const TYPE_AGENCE = 'AGENCE';

    public const TYPES = [self::TYPE_SIEGE, self::TYPE_AGENCE];

    protected $fillable = ['code', 'nom', 'lieu', 'type', 'archiver'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function departements()
    {
        return $this->hasMany(Departement::class);
    }

    public function historique()
    {
        return $this->hasMany(HistoriqueEntite::class)->latest('id');
    }

    public function estSiege(): bool
    {
        return $this->type === self::TYPE_SIEGE;
    }

    public function estAgence(): bool
    {
        return $this->type === self::TYPE_AGENCE;
    }

    public function estQualifiee(): bool
    {
        return in_array($this->type, self::TYPES, true);
    }

    public function libelleType(): string
    {
        return match ($this->type) {
            self::TYPE_SIEGE => 'Siège',
            self::TYPE_AGENCE => 'Agence',
            default => 'Non qualifiée',
        };
    }
}
