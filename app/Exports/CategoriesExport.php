<?php

namespace App\Exports;

use App\Models\Categorie;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CategoriesExport implements FromCollection, WithHeadings, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    // public function collection()
    // {
    //     //return Categorie::all();
    //     return Categorie::where('archiver', 0)->get();
    // }

    public function collection()
    {
        return Categorie::where('archiver', 0)
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
            1 => ['font' => ['bold' => true]],
        ];
    }
}
