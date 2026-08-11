<?php

namespace App\Exports\Etats;

use App\Models\Demande;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;

class DemandesParDepartementExport implements FromCollection, WithHeadings, WithStyles, WithCustomStartCell
{
    private $dateDebut;
    private $dateFin;
    private $departementId;
    private $aucuneDonnee = false;

    public function __construct(
        $dateDebut,
        $dateFin,
        $departementId = null
    ) {
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->departementId = $departementId;
    }

    public function startCell(): string
    {
        return 'A5';
    }

    public function collection()
    {
        $query = Demande::with([
            'user.departement',
            'articles.article.categorie'
        ])

            ->where(
                'archiver',
                0
            )

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

        if (!empty($this->departementId)) {

            $query->whereHas(
                'user',
                function ($q) {

                    $q->where(
                        'departement_id',
                        $this->departementId
                    );
                }
            );
        }

        $demandes = $query
            ->get()
            ->groupBy(
                fn($d) =>
                $d->user->departement->nom
                    ?? 'Sans département'
            );

        $resultats = collect();

        foreach (
            $demandes
            as $departement => $listeDemandes
        ) {

            $resultats->push([

                'date' =>
                'DEPARTEMENT : '
                    . strtoupper($departement),

                'article' => '',
                'utilisateur' => '',
                'qte_demandee' => '',
                'qte_accordee' => '',
                'cmp' => '',
                'valeur' => ''

            ]);

            $categories = [];

            foreach (
                $listeDemandes
                as $demande
            ) {

                foreach (
                    $demande->articles
                    as $ligne
                ) {

                    $categorie =
                        $ligne->article->categorie->libelle
                        ?? 'Sans catégorie';

                    $categories[$categorie][] = [

                        'date' =>
                        $demande->date,

                        'article' =>
                        $ligne->article->libelle,

                        'utilisateur' => ($demande->user->prenom ?? '')
                            . ' '
                            . ($demande->user->name ?? ''),

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

            $totalDepartement = 0;

            foreach (
                $categories
                as $categorie => $lignes
            ) {

                $resultats->push([

                    'date' =>
                    'CATÉGORIE : '
                        . strtoupper($categorie),

                    'article' => '',
                    'utilisateur' => '',
                    'qte_demandee' => '',
                    'qte_accordee' => '',
                    'cmp' => '',
                    'valeur' => ''

                ]);

                $totalCategorie = 0;

                foreach (
                    $lignes
                    as $ligne
                ) {

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

                $totalDepartement +=
                    $totalCategorie;
            }

            $resultats->push([

                'date' =>
                'TOTAL DEPARTEMENT',

                'article' => '',
                'utilisateur' => '',
                'qte_demandee' => '',
                'qte_accordee' => '',
                'cmp' => '',
                'valeur' =>
                $totalDepartement

            ]);
        }

        if (
            $resultats->isEmpty()
        ) {

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

    public function styles(Worksheet $sheet)
    {
        /*
    =====================================
    TITRE
    =====================================
    */

        $sheet->mergeCells('A3:G3');

        $sheet->setCellValue(
            'A3',
            'ETAT DES DEMANDES PAR DEPARTEMENT DU '
                . Carbon::parse($this->dateDebut)->format('d/m/Y')
                . ' AU '
                . Carbon::parse($this->dateFin)->format('d/m/Y')
        );

        $sheet->getStyle('A3:G3')->applyFromArray([

            'font' => [

                'bold' => true,
                'size' => 14

            ],

            'alignment' => [

                'horizontal' => Alignment::HORIZONTAL_CENTER

            ]

        ]);

        /*
    =====================================
    ENTÊTES
    =====================================
    */

        $sheet->getStyle('A5:G5')->applyFromArray([

            'font' => [

                'bold' => true

            ],

            'alignment' => [

                'horizontal' => Alignment::HORIZONTAL_CENTER

            ]

        ]);

        /*
    =====================================
    BORDURES
    =====================================
    */

        $derniereLigne = $sheet->getHighestRow();

        $sheet->getStyle('A5:G' . $derniereLigne)
            ->applyFromArray([

                'borders' => [

                    'allBorders' => [

                        'borderStyle' =>
                        \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN

                    ]

                ]

            ]);

        /*
    =====================================
    FORMAT DES MONTANTS
    =====================================
    */

        $sheet->getStyle('F6:G' . $derniereLigne)
            ->getNumberFormat()
            ->setFormatCode('#,##0');

        /*
    =====================================
    AUCUNE DONNÉE
    =====================================
    */

        if ($this->aucuneDonnee) {

            $sheet->mergeCells('A6:G6');

            $sheet->getStyle('A6:G6')->applyFromArray([

                'font' => [

                    'bold' => true,
                    'size' => 12

                ],

                'alignment' => [

                    'horizontal' => Alignment::HORIZONTAL_CENTER

                ],

                'fill' => [

                    'fillType' => Fill::FILL_SOLID,

                    'startColor' => [

                        'rgb' => 'D9D9D9'

                    ]

                ]

            ]);
        }
    }
}
