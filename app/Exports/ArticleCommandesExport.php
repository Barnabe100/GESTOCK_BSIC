<?php

namespace App\Exports;

use App\Models\ArticleCommande;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArticleCommandesExport implements FromCollection, WithHeadings, WithStyles
{
    /**
     * @param  bool  $receptionneesSeulement  Arbitrage A (Consultant) : seules les
     *         lignes des commandes entièrement réceptionnées (livraison = 1).
     */
    public function __construct(private bool $receptionneesSeulement = false)
    {
    }

    public function collection()
    {
        return ArticleCommande::with([
                'article',
                'commande.fournisseur'
            ])
            ->when($this->receptionneesSeulement, fn ($q) => $q->whereHas('commande', fn ($c) => $c->where('livraison', 1)))
            ->get()
            ->map(function ($ligne) {

                return [

                    'numero_bc' => $ligne->commande->numero_bon_commande ?? '',

                    'fournisseur' => $ligne->commande->fournisseur->nom ?? '',

                    'article' => $ligne->article->libelle ?? '',

                    'quantite' => $ligne->quantite,

                    'prix' => $ligne->prix,

                    'total' => $ligne->quantite * $ligne->prix,

                    'date_commande' => $ligne->commande->date ?? '',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Numéro BC',
            'Fournisseur',
            'Article',
            'Quantité',
            'Prix',
            'Total',
            'Date commande'
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