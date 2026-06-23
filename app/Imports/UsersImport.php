<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {


        $user = User::create([

            'name' => $row['name'],

            'prenom' => $row['prenom'],

            'email' => $row['email'],

            'password' => Hash::make(
                'Gesbsic@2026'  //'Gestok@2026'  
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

        $user->assignRole($roles);

        return $user;
    }
}
