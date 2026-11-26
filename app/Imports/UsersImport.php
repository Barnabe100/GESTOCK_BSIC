<?php

namespace App\Imports;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToModel, WithHeadingRow
{
    /**
     * Socle des droits : l'utilisateur qui importe. Les rôles du fichier
     * doivent exister et lui être attribuables (SuperAdmin réservé au
     * SuperAdmin) ; sinon l'import entier est refusé (403) et annulé par la
     * transaction de UserController::import.
     */
    public function __construct(private ?User $acteur = null)
    {
    }

    public function model(array $row)
    {


        $user = User::create([

            'name' => $row['name'],

            'prenom' => $row['prenom'],

            'email' => $row['email'],

            'password' => Hash::make(
                config('gestok.mot_de_passe_initial')
            ),

            'force_password_change' => true,

            'bloque' => false,

            'agence_id' => $row['agence_id'],

            'departement_id' => $row['departement_id'],

            'service_id' => $row['service_id'],

            'superieur' => $row['superieur']

        ]);

        $roles = explode(
            ';',
            $row['roles'] ?? 'Demandeur'
        );

        $roles = array_values(array_filter(array_map('trim', $roles), fn ($r) => $r !== ''));

        if (array_diff($roles, UserPolicy::rolesExistants()) !== []
            || ! $this->acteur
            || Gate::forUser($this->acteur)->denies('attribuerRolesALaCreation', [User::class, $roles])) {
            abort(403);
        }

        $user->assignRole($roles);

        return $user;
    }
}
