<?php

namespace App\Exports;

use App\Models\TypeArticle;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TypeArticlesExport implements FromCollection, WithHeadings, WithStyles
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return TypeArticle::where('archiver', 0)
            ->select(
                'code',
                'libelle',
                'description'
            )
            ->get();
    }

    public function headings(): array
    {
        return [
            'Code',
            'Libellé',
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
