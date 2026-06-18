<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MouvementStock extends Model
{
    use HasFactory;

    protected $fillable = [

        'article_id',
        'user_id',
        'type',
        'quantite',
        'motif',
        'date_mouvement',
        'prix_unitaire',

    ];

    public function article()
    {
        return $this->belongsTo(
            Article::class
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }
}
