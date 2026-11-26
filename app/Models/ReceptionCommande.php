<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Une réception (totale ou partielle) d'une commande ; jeton unique anti-rejeu. */
class ReceptionCommande extends Model
{
    protected $table = 'receptions_commandes';

    protected $fillable = ['commande_id', 'jeton', 'date_reception', 'quantite_totale', 'user_id'];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }
}
