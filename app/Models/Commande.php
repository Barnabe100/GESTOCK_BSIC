<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_bon_commande',
        'num_facture_proforma',
        'date',
        'libelle',
        'montant',
        'validation',
        'livraison',
        'date_livraison',
        'archiver',
        'fournisseur_id'
    ];

    protected $casts = [
        'archiver' => 'boolean',
        'validation' => 'boolean',
        'livraison' => 'boolean',
    ];

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    // public function articles()
    // {
    //     return $this->belongsToMany(Article::class)
    //         ->withPivot('quantite', 'archiver')
    //         ->withTimestamps();
    // }

    public function articles()
    {
        return $this->hasMany(ArticleCommande::class);
    }

    // public function articles()
    // {
    //     return $this->belongsToMany(
    //         Article::class
    //     )

    //         ->withPivot([

    //             //'libelle',
    //             'quantite',

    //             'quantite_livree',

    //             'quantite_restante'

    //         ]);
    // }

    public function scopeActif($query)
    {
        return $query->where('archiver', 0);
    }
}
