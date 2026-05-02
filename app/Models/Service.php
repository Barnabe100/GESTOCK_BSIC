<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'nom',
        'localisation',
        'departement_id'
    ];

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
