<?php

namespace App\Exports\Etats;

use App\Models\Article;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockParArticleExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return Article::where('archiver', 0)
            ->get()
            ->map(function ($article) {

                return [

                    'code' => $article->code,

                    'designation' => $article->designation,

                    'categorie' =>
                    $article->categorie->libelle ?? '',

                    'stock_disponible' =>
                    $article->stock_disponible,

                    'stock_securite' =>
                    $article->stock_securite,

                    'etat_stock' =>
                    $article->stock_disponible
                        <= $article->stock_securite
                        ? 'Critique'
                        : 'Disponible',

                ];
            });
    }

    public function headings(): array
    {
        return [

            'Code',
            'Désignation',
            'Catégorie',
            'Stock disponible',
            'Stock sécurité',
            'État stock'

        ];
    }

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
