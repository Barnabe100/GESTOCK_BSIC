<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agence extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'nom', 'lieu'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function departements()
    {
        return $this->hasMany(Departement::class);
    }
}
