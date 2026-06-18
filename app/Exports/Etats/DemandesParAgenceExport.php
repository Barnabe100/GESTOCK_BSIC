<?php

namespace App\Exports\Etats;

use App\Models\Demande;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DemandesParAgenceExport implements FromCollection, WithHeadings, WithStyles
{
    private $dateDebut;
    private $dateFin;
    private $agenceId;
    private $aucuneDonnee = false;


    public function __construct(
        $dateDebut,
        $dateFin,
        $agenceId = null
    ) {
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->agenceId = $agenceId;
    }

    public function collection()
    {

        $query = Demande::with([
            'user.agence',
            'articles.article.categorie'
        ])

            ->where('archiver', 0)

            ->where(
                'statut',
                'Livrée - Demande clôturée'
            )

            ->whereBetween(
                'date',
                [
                    $this->dateDebut,
                    $this->dateFin
                ]
            );

        if (!empty($this->agenceId)) {

            $query->whereHas(
                'user',
                function ($q) {

                    $q->where(
                        'agence_id',
                        $this->agenceId
                    );
                }
            );
        }

        $demandes = $query
            ->get()
            ->groupBy(
                fn($d) =>
                $d->user->agence->nom
                    ?? 'Sans agence'
            );

        $resultats = collect();

        foreach ($demandes as $agence => $listeDemandes) {

            $resultats->push([

                'date' =>
                'AGENCE : ' . strtoupper($agence),

                'article' => '',

                'utilisateur' => '',

                'qte_demandee' => '',

                'qte_accordee' => '',

                'cmp' => '',

                'valeur' => ''

            ]);

            $categories = [];

            foreach ($listeDemandes as $demande) {

                foreach ($demande->articles as $ligne) {

                    $categorie =
                        $ligne->article->categorie->libelle
                        ?? 'Sans catégorie';

                    $categories[$categorie][] = [

                        'date' =>
                        $demande->date,

                        'article' =>
                        $ligne->article->libelle,

                        'utilisateur' => ($demande->user->prenom ?? '')
                            . ' ' .
                            ($demande->user->name ?? ''),

                        'demande' =>
                        $ligne->quantite,

                        'accorde' =>
                        $ligne->quantite_accordee,

                        'cmp' =>
                        $ligne->article->cmp ?? 0,

                        'valeur' => ($ligne->quantite_accordee ?? 0)
                            *
                            ($ligne->article->cmp ?? 0)

                    ];
                }
            }

            $totalAgence = 0;

            foreach ($categories as $categorie => $lignes) {

                $resultats->push([

                    'date' =>
                    'CATÉGORIE : ' . strtoupper($categorie),

                    'article' => '',

                    'utilisateur' => '',

                    'qte_demandee' => '',

                    'qte_accordee' => '',

                    'cmp' => '',

                    'valeur' => ''

                ]);

                $totalCategorie = 0;

                foreach ($lignes as $ligne) {

                    $resultats->push([

                        'date' =>
                        $ligne['date'],

                        'article' =>
                        $ligne['article'],

                        'utilisateur' =>
                        $ligne['utilisateur'],

                        'qte_demandee' =>
                        $ligne['demande'],

                        'qte_accordee' =>
                        $ligne['accorde'],

                        'cmp' =>
                        $ligne['cmp'],

                        'valeur' =>
                        $ligne['valeur']

                    ]);

                    $totalCategorie +=
                        $ligne['valeur'];
                }

                $resultats->push([

                    'date' =>
                    'TOTAL CATÉGORIE',

                    'article' => '',

                    'utilisateur' => '',

                    'qte_demandee' => '',

                    'qte_accordee' => '',

                    'cmp' => '',

                    'valeur' =>
                    $totalCategorie

                ]);

                $totalAgence +=
                    $totalCategorie;
            }

            $resultats->push([

                'date' =>
                'TOTAL AGENCE',

                'article' => '',

                'utilisateur' => '',

                'qte_demandee' => '',

                'qte_accordee' => '',

                'cmp' => '',

                'valeur' =>
                $totalAgence

            ]);
        }

        if ($resultats->isEmpty()) {

            $this->aucuneDonnee = true;

            $resultats->push([

                'date' =>
                'AUCUNE DONNÉE DISPONIBLE POUR LA PÉRIODE SÉLECTIONNÉE',

                'article' => '',
                'utilisateur' => '',
                'qte_demandee' => '',
                'qte_accordee' => '',
                'cmp' => '',
                'valeur' => ''

            ]);
        }

        return $resultats;
    }
    public function headings(): array
    {
        return [

            'Date',
            'Article',
            'Utilisateur',
            'Nb demandé',
            'Nb accordé',
            'CMP',
            'Valeur'

        ];
    }

    public function styles(
        Worksheet $sheet
    ) {

        $sheet->getStyle('A1:G1')
            ->getFont()
            ->setBold(true);

        if ($this->aucuneDonnee) {

            $sheet->mergeCells(
                'A2:G2'
            );

            $sheet->getStyle(
                'A2:G2'
            )->applyFromArray([

                'font' => [

                    'bold' => true,

                    'size' => 12

                ],

                'alignment' => [

                    'horizontal' =>
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER

                ],

                'fill' => [

                    'fillType' =>
                    \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,

                    'startColor' => [

                        'rgb' => 'D9D9D9'

                    ]

                ]

            ]);
        }
    }
}
