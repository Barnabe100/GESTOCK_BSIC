<?php

namespace App\Exports;

use App\Models\Service;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ServicesExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return Service::where('archiver', 0)
            ->select(
                'code',
                'nom',
                'localisation'
            )
            ->get();
    }

    public function headings(): array
    {
        return [
            'Code',
            'Nom',
            'Localisation'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}