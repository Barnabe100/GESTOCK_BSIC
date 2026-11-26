<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Historique des désignations de supérieurs hiérarchiques (socle des droits). */
class HistoriqueSuperieur extends Model
{
    protected $table = 'historique_superieurs';

    protected $fillable = [
        'entite_type',
        'entite_id',
        'ancien_superieur_id',
        'nouveau_superieur_id',
        'designe_par',
    ];
}
