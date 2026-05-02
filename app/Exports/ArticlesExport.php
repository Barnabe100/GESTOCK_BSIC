<?php

namespace App\Exports;

use App\Models\Article;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArticlesExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return Article::with(['categorie'])
            ->where('archiver', 0)
            ->get()
            ->map(function ($article) {

                return [

                    'code' => $article->code,

                    'libelle' => $article->libelle,

                    'categorie' => $article->categorie->libelle ?? '',

                    'stock_disponible' => $article->stock_disponible,

                    'stock_securite' => $article->stock_securite,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Libellé',
            'Catégorie',
            'Stock disponible',
            'Stock sécurité'
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