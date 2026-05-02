<?php

namespace App\Exports;

use App\Models\Departement;
use App\Models\Agence;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DepartementsExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        // return Departement::where('archiver', 0)
        //     ->select(
        //         'code',
        //         'nom',
        //         //'description'
        //     )
        //     ->get();

        return Departement::with(['agence'])
            ->where('archiver', 0)
            ->get()
            ->map(function ($departement) {

                return [

                    'code' => $departement->code,
                    'nom' => $departement->nom,
                    'agence' => $departement->agence->nom ?? '',

                ];
            });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Nom',
            'Agence'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
