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
        'validation',
        'livraison',
        'date_livraison',
        'archiver',
        'user_id'
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
}