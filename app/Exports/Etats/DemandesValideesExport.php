<?php

namespace App\Exports\Etats;

use App\Models\Demande;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DemandesValideesExport implements FromCollection, WithHeadings, WithStyles
{
    /**
     * 🔥 DONNÉES
     */
    public function collection()
    {
        return Demande::with([
            'user'
        ])
            ->where('validation', 1)
            ->where('archiver', 0)
            ->get()
            ->map(function ($demande) {

                return [

                    'objet' => $demande->objet,

                    'date' => $demande->date,

                    'utilisateur' => ($demande->user->prenom ?? '')
                        . ' '
                        . ($demande->user->name ?? ''),

                    'validation' => 'Validée'

                ];
            });
    }

    /**
     * 🔥 ENTÊTES
     */
    public function headings(): array
    {
        return [

            'Objet',
            'Date',
            'Utilisateur',
            'Validation'

        ];
    }

    /**
     * 🔥 STYLE
     */
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
