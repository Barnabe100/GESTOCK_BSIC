<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;


/**
 * @method bool hasRole(string|array $roles)
 * @method bool hasAnyRole(...$roles)
 * @method bool hasPermissionTo(string $permission)
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'prenom',
        'email',
        'password',
        'force_password_change',
        'departement_id',
        'agence_id',
        'service_id',
        'bloque',
        'superieur',


    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function agence()
    {
        return $this->belongsTo(Agence::class);
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function demandes()
    {
        return $this->hasMany(Demande::class);
    }
    public function mouvements()
    {
        return $this->hasMany(MouvementStock::class);
    }


    public function estEnAgence()
    {
        return is_null($this->departement_id)
            //|| $this->departement_id == 7
            || optional($this->departement)->nom == 'Département Réseau';
    }

    public function estAuSiege()
    {
        return !$this->estEnAgence();
    }

    public function peutVoirDemande(Demande $demande): bool
    {
        // Les administrateurs voient tout
        if ($this->hasAnyRole([
            'SuperAdmin',
            'Gestionnaire',
            'Superieur',
            'ValidateurDemande'
        ])) {
            return true;
        }

        if ($this->estAuSiege()) {
            return $demande->user->departement_id == $this->departement_id;
        }

        return $demande->user->agence_id == $this->agence_id;
    }

    public function peutConfirmerReception(Demande $demande): bool
    {
        if ($demande->statut !== 'En attente réception demandeur') {
            return false;
        }

        return $this->peutVoirDemande($demande);
    }
}
