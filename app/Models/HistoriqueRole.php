<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Historique des attributions et retraits de rôles. */
class HistoriqueRole extends Model
{
    protected $table = 'historique_roles';

    protected $fillable = ['user_id', 'utilisateur', 'role', 'action', 'origine', 'effectue_par'];
}
