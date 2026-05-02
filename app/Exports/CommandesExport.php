<?php

namespace App\Exports;

use App\Models\Commande;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CommandesExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return Commande::with('fournisseur')
            ->where('archiver', 0)
            ->get()
            ->map(function ($commande) {

                return [

                    'numero_bc' => $commande->numero_bon_commande,

                    'facture_proforma' => $commande->num_facture_proforma,

                    'date' => $commande->date,

                    'fournisseur' => $commande->fournisseur->nom ?? '',

                    'montant' => $commande->montant,

                    'validation' => $commande->validation
                        ? 'Validée'
                        : 'En attente',

                    'livraison' => $commande->livraison
                        ? 'Livrée'
                        : 'Non livrée',

                    'libelle' => $commande->libelle,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Numéro BC',
            'Facture Proforma',
            'Date',
            'Fournisseur',
            'Montant',
            'Validation',
            'Livraison',
            'Libellé'
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