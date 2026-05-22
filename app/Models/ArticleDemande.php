<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleDemande extends Model
{
    use HasFactory;

    protected $table = 'article_demande';

    protected $fillable = [
        'demande_id',
        'article_id',
        'quantite',
        'archiver'
    ];

    public function demande()
    {
        return $this->belongsTo(Demande::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
