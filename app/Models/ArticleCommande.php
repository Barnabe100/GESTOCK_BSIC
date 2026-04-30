<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleCommande extends Model
{
    protected $table = 'article_commande';

    protected $fillable = [
        'commande_id',
        'article_id',
        'quantite',
        'prix'
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}