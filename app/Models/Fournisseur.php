<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fournisseur extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'nom',
        'telephone',
        'email',
        'adresse',
        'archiver'
    ];

    protected $casts = [
        'archiver' => 'boolean',
    ];

    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }

    public function scopeActif($query)
    {
        return $query->where('archiver', 0);
    }
}