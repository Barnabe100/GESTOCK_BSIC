<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Demande extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'objet',
        'description',
        'validation_gestionnaire',
        'livraison',
        'date_livraison',
        'archiver',
        'user_id',
        'superieur_id',
        'statut',
        'commentaire_superieur',
        'validation_superieur',
        'date_validation_superieur',
        'commentaire_gestionnaire',
        'date_validation_gestionnaire',
        'validation_finale',
        'confirmation_reception',
        'commentaire_reception',
        'commentaire_validation_finale',
        'gestionnaire_id',
        'validateur_final_id',
        'date_validation_finale',


    ];

    protected $casts = [
        'archiver' => 'boolean',
        'validation' => 'boolean',
        'livraison' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // public function articles()
    // {
    //     return $this->belongsToMany(Article::class)
    //         ->withPivot('quantite', 'archiver')
    //         ->withTimestamps();
    // }

    public function articles()
    {
        return $this->hasMany(ArticleDemande::class);
    }



    public function scopeActif($query)
    {
        return $query->where('archiver', 0);
    }

    public function superieur()
    {
        return $this->belongsTo(
            User::class,
            'superieur_id'
        );
    }

    public function gestionnaire()
    {
        return $this->belongsTo(
            User::class,
            'gestionnaire_id'
        );
    }

    public function validateurFinal()
    {
        return $this->belongsTo(
            User::class,
            'validateur_final_id'
        );
    }
}
