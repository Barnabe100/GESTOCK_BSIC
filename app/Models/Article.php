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
        'stock_initial',
        'stock_disponible',
        'stock_securite',
        'archiver',
        'categorie_id',
        'type_article_id',
    ];

    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }

    public function typeArticle()
    {
        return $this->belongsTo(TypeArticle::class);
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

    public function mouvements()
    {
        return $this->hasMany(MouvementStock::class);
    }


    public function getCmpAttribute()
    {
        $entrees = $this->mouvements()
            ->where(
                'type',
                'ENTREE'
            )
            ->whereNotNull(
                'prix_unitaire'
            )
            ->get();

        $quantiteTotale =
            $entrees->sum(
                'quantite'
            );

        if ($quantiteTotale == 0) {

            return 0;
        }

        $valeurTotale =
            $entrees->sum(function ($mouvement) {

                return
                    $mouvement->quantite
                    *
                    $mouvement->prix_unitaire;
            });

        return round(
            $valeurTotale
                /
                $quantiteTotale,
            2
        );
    }



    public function mouvementsStock()
    {
        return $this->hasMany(
            MouvementStock::class
        );
    }
}
