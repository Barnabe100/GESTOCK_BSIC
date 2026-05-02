<?php

namespace App\Exports;

use App\Models\Departement;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DepartementsExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return Departement::where('archiver', 0)
            ->select(
                'code',
                'nom',
                //'description'
            )
            ->get();
    }

    public function headings(): array
    {
        return [
            'Code',
            'Nom',
            //'Description'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}