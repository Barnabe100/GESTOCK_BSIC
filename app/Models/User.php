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
        'desactive_le' => 'datetime',
    ];

    /** Q9 : compte désactivé (conservé avec son historique, sans connexion possible). */
    public function estDesactive(): bool
    {
        return $this->desactive_le !== null;
    }

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


    /**
     * N03 : rattachement organisationnel, fondé uniquement sur le type
     * explicite de l'entité (Agence::type), jamais sur un nom.
     * Utilisateur d'une entité non qualifiée : ni siège ni agence.
     */
    public function estEnAgence(): bool
    {
        return (bool) $this->agence?->estAgence();
    }

    public function estAuSiege(): bool
    {
        return (bool) $this->agence?->estSiege();
    }

    /**
     * Entité de validation hiérarchique : au siège, le département ; en
     * agence, l'agence. Null si l'entité n'est pas qualifiée ou si le
     * rattachement est incomplet (aucun supérieur ne peut alors être résolu).
     *
     * @return array{0: string, 1: int}|null
     */
    public function entiteDeValidation(): ?array
    {
        if ($this->estAuSiege()) {
            return $this->departement_id ? ['departement', (int) $this->departement_id] : null;
        }

        if ($this->estEnAgence()) {
            return $this->agence_id ? ['agence', (int) $this->agence_id] : null;
        }

        return null;
    }

    /**
     * Restreint une requête sur les utilisateurs à ceux de la même entité de
     * validation que $reference (aucun résultat si elle n'est pas résolue).
     */
    public static function filtrerMemeEntite($requete, User $reference)
    {
        $entite = $reference->entiteDeValidation();

        if ($entite === null) {
            return $requete->whereRaw('1 = 0');
        }

        [$type, $id] = $entite;

        if ($type === 'departement') {
            return $requete->where('departement_id', $id)
                ->whereHas('agence', fn ($q) => $q->where('type', Agence::TYPE_SIEGE));
        }

        return $requete->where('agence_id', $id)
            ->whereHas('agence', fn ($q) => $q->where('type', Agence::TYPE_AGENCE));
    }

    public function estDeLaMemeEntiteQue(User $autre): bool
    {
        $entite = $this->entiteDeValidation();

        return $entite !== null && $entite === $autre->entiteDeValidation();
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

        return $demande->user !== null && $this->estDeLaMemeEntiteQue($demande->user);
    }

    /**
     * Décision validée (novembre 2026) : la confirmation de réception est
     * autorisée aux utilisateurs appartenant à la même agence OU au même
     * département que le demandeur (le demandeur lui-même compris), tant que
     * la demande attend sa réception.
     */
    public function peutConfirmerReception(Demande $demande): bool
    {
        if ($demande->statut !== 'En attente réception demandeur') {
            return false;
        }

        $demandeur = $demande->user;

        if (! $demandeur) {
            return false;
        }

        if ((int) $demandeur->id === (int) $this->id) {
            return true;
        }

        return ($this->agence_id !== null && (int) $this->agence_id === (int) $demandeur->agence_id)
            || ($this->departement_id !== null && (int) $this->departement_id === (int) $demandeur->departement_id);
    }

    /**
     * Q3 (décision validée) : jusqu'à deux supérieurs désignés par entité,
     * qui sont des valideurs ALTERNATIFS de l'unique étape « supérieur » du
     * circuit. Est habilité à décider pour un demandeur : un titulaire désigné
     * (indicateur superieur et rôle Superieur) de la même entité de
     * validation, qui n'est pas le demandeur lui-même.
     */
    public function estSuperieurHierarchiqueDe(User $demandeur): bool
    {
        return (int) $this->id !== (int) $demandeur->id
            && (bool) $this->superieur
            && $this->hasRole('Superieur')
            && $this->estDeLaMemeEntiteQue($demandeur);
    }
}
