<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'libelle',
        'prix_unitaire',
        'stock_disponible',
        'stock_securite',
        'archiver',
        'categorie_id'
    ];

    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }

    // public function commandes()
    // {
    //     return $this->belongsToMany(Commande::class)
    //         ->withPivot('quantite', 'archiver')
    //         ->withTimestamps();
    // }

    public function demandes()
    {
        return $this->belongsToMany(Demande::class)
            ->withPivot('quantite', 'archiver')
            ->withTimestamps();
    }

    public function commandes()
    {
        return $this->hasMany(ArticleCommande::class);
    }
}
