<?php

namespace App\Exports;

use App\Models\Demande;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DemandesExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return Demande::with('user')
            ->where('archiver', 0)
            ->get()
            ->map(function ($demande) {

                return [

                    'objet' => $demande->objet,

                    'date' => $demande->date,

                    'utilisateur' => $demande->user->name ?? '',

                    'validation' => $demande->validation
                        ? 'Validée'
                        : 'En attente',

                    'description' => $demande->description,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Objet',
            'Date',
            'Utilisateur',
            'Validation',
            'Description'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true
                ]
            ],
        ];
    }
}