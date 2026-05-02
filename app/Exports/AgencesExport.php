<?php

namespace App\Exports;

use App\Models\Agence;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AgencesExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return Agence::where('archiver', 0)
            ->select(
                'code',
                'nom',
                'lieu'
            )
            ->get();
    }

    public function headings(): array
    {
        return [
            'Code',
            'Nom',
            'lieu'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}