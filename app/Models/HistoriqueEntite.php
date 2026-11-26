<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Trace des qualifications, changements de siège et désactivations (N03). */
class HistoriqueEntite extends Model
{
    protected $table = 'historique_entites';

    protected $fillable = ['agence_id', 'action', 'ancien_type', 'nouveau_type', 'effectue_par'];

    public function agence()
    {
        return $this->belongsTo(Agence::class);
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'effectue_par');
    }
}
