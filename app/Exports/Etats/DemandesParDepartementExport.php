<?php

namespace App\Exports\Etats;

use App\Models\Demande;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DemandesParDepartementExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return Demande::with([
            'user.departement'
        ])
            ->where('archiver', 0)
            ->get()
            ->map(function ($demande) {

                return [

                    'objet' => $demande->objet,

                    'date' => $demande->date,

                    'utilisateur' => ($demande->user->prenom ?? '')
                        . ' '
                        . ($demande->user->name ?? ''),

                    'departement' =>
                    $demande->user->departement->nom ?? '',

                    'validation' =>
                    $demande->validation
                        ? 'Validée'
                        : 'Non validée',

                ];
            });
    }

    public function headings(): array
    {
        return [

            'Objet',
            'Date',
            'Utilisateur',
            'Département',
            'Validation'

        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [

            1 => [
                'font' => [
                    'bold' => true
                ]
            ]

        ];
    }
}
