<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return User::with([
            'agence',
            'departement',
            'service',
            'roles'
        ])
            ->get()
            ->map(function ($user) {

                return [

                    'nom' => $user->name,

                    'prenom' => $user->prenom,

                    'email' => $user->email,

                    'agence' => $user->agence->nom ?? '',

                    'departement' => $user->departement->nom ?? '',

                    'service' => $user->service->nom ?? '',

                    'roles' => $user->roles
                        ->pluck('name')
                        ->implode(', ')
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Nom',
            'Prenom',
            'Email',
            'Agence',
            'Département',
            'Service',
            'Rôles'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
