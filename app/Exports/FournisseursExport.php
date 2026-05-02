<?php

namespace App\Exports;

use App\Models\Fournisseur;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FournisseursExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return Fournisseur::where('archiver', 0)
            ->select(
                'nom',
                'telephone',
                'email'
            )
            ->get();
    }

    public function headings(): array
    {
        return [
            'Nom',
            'Téléphone',
            'Email'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}