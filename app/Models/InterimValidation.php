<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Q3 : intérim de validation supérieure confié à un supérieur d'une autre entité. */
class InterimValidation extends Model
{
    protected $table = 'interims_validation';

    protected $fillable = ['entite_type', 'entite_id', 'user_id', 'debut_le', 'fin_le', 'motif', 'designe_par', 'revoque_le', 'revoque_par'];

    protected $casts = [
        'debut_le' => 'date',
        'fin_le' => 'date',
        'revoque_le' => 'datetime',
    ];

    public function remplacant()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'designe_par');
    }

    /** Intérims en vigueur aujourd'hui (non révoqués, dans leur période). */
    public function scopeEnVigueur(Builder $requete): Builder
    {
        $aujourdhui = now()->toDateString();

        return $requete->whereNull('revoque_le')
            ->whereDate('debut_le', '<=', $aujourdhui)
            ->whereDate('fin_le', '>=', $aujourdhui);
    }

    public function scopePourEntite(Builder $requete, array $entite): Builder
    {
        return $requete->where('entite_type', $entite[0])->where('entite_id', $entite[1]);
    }

    public function estEnVigueur(): bool
    {
        return $this->revoque_le === null
            && $this->debut_le->lte(now()->startOfDay())
            && $this->fin_le->gte(now()->startOfDay());
    }

    public function entite(): array
    {
        return [$this->entite_type, (int) $this->entite_id];
    }

    public function libelleEntite(): string
    {
        return self::libelle($this->entite());
    }

    /** Libellé d'une entité de validation : « Département X (siège) » ou le nom de l'agence. */
    public static function libelle(array $entite): string
    {
        [$type, $id] = $entite;

        return $type === 'departement'
            ? (Departement::find($id)->nom ?? 'Département n° '.$id).' (siège)'
            : (Agence::find($id)->nom ?? 'Agence n° '.$id);
    }
}
