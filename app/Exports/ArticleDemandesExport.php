<?php

namespace App\Exports;

use App\Models\ArticleDemande;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArticleDemandesExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return ArticleDemande::with([
                'article',
                'demande.user'
            ])
            ->get()
            ->map(function ($ligne) {

                return [

                    'demande' => $ligne->demande->objet ?? '',

                    'utilisateur' => $ligne->demande->user->name ?? '',

                    'article' => $ligne->article->libelle ?? '',

                    'quantite' => $ligne->quantite,

                    'date_demande' => $ligne->demande->date ?? '',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Demande',
            'Utilisateur',
            'Article',
            'Quantité',
            'Date demande'
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