<?php

namespace App\Exports\Etats;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EtatStockValoriseExport implements
    FromArray,
    ShouldAutoSize,
    WithStyles,
    WithTitle
{

    protected $data;

    protected $dateDebut;

    protected $dateFin;

    public function __construct(
        $data,
        $dateDebut,
        $dateFin
    ) {

        $this->data = $data;
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
    }

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Etat Valorisé';
    }

    public function styles(
        Worksheet $sheet
    ) {

        /*
        Titre
        */

        $sheet->mergeCells(
            'A3:J3'
        );

        $sheet->setCellValue(

            'A3',

            'ETAT VALORISE DES STOCKS DU '

                . $this->dateDebut

                . ' AU '

                . $this->dateFin

        );

        /*
        Entêtes fusionnées
        */

        $sheet->mergeCells('A5:A6');
        $sheet->mergeCells('B5:B6');

        $sheet->mergeCells('C5:D5');
        $sheet->mergeCells('E5:F5');
        $sheet->mergeCells('G5:H5');
        $sheet->mergeCells('I5:J5');

        $sheet->setCellValue(
            'A5',
            'DATE'
        );

        $sheet->setCellValue(
            'B5',
            'INTITULE'
        );

        $sheet->setCellValue(
            'C5',
            'STOCK EN DEBUT DE PERIODE'
        );

        $sheet->setCellValue(
            'E5',
            'ENTREE DE LA PERIODE'
        );

        $sheet->setCellValue(
            'G5',
            'SORTIE DE LA PERIODE'
        );

        $sheet->setCellValue(
            'I5',
            'STOCK EN FIN DE PERIODE'
        );

        /*
        Sous-entêtes
        */

        $sheet->setCellValue(
            'C6',
            'QUANTITE'
        );

        $sheet->setCellValue(
            'D6',
            'VALEUR'
        );

        $sheet->setCellValue(
            'E6',
            'QUANTITE'
        );

        $sheet->setCellValue(
            'F6',
            'VALEUR'
        );

        $sheet->setCellValue(
            'G6',
            'QUANTITE'
        );

        $sheet->setCellValue(
            'H6',
            'VALEUR'
        );

        $sheet->setCellValue(
            'I6',
            'QUANTITE'
        );

        $sheet->setCellValue(
            'J6',
            'VALEUR'
        );

        /*
        Données
        */

        $ligne = 7;

        foreach (
            $this->data
            as
            $item
        ) {

            $sheet->setCellValue(
                'A' . $ligne,
                $item['date']
            );

            $sheet->setCellValue(
                'B' . $ligne,
                $item['article']
            );

            $sheet->setCellValue(
                'C' . $ligne,
                $item['stock_debut_qte']
            );

            $sheet->setCellValue(
                'D' . $ligne,
                $item['stock_debut_valeur']
            );

            $sheet->setCellValue(
                'E' . $ligne,
                $item['entree_qte']
            );

            $sheet->setCellValue(
                'F' . $ligne,
                $item['entree_valeur']
            );

            $sheet->setCellValue(
                'G' . $ligne,
                $item['sortie_qte']
            );

            $sheet->setCellValue(
                'H' . $ligne,
                $item['sortie_valeur']
            );

            $sheet->setCellValue(
                'I' . $ligne,
                $item['stock_fin_qte']
            );

            $sheet->setCellValue(
                'J' . $ligne,
                $item['stock_fin_valeur']
            );

            $ligne++;
        }

        //Condition pour verifier si les données sont disponibles
        if (
            count($this->data) == 0
        ) {

            $sheet->mergeCells(
                'A7:D7'
            );

            $sheet->setCellValue(

                'A7',

                'AUCUNE DONNEE DISPONIBLE POUR LA PERIODE SELECTIONNEE'

            );

            $sheet->getStyle(
                'A7'
            )->applyFromArray([

                'font' => [

                    'bold' => true,
                    'size' => 12

                ],

                'alignment' => [

                    'horizontal' => 'center'

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

        /*
        Style titre
        */

        $sheet->getStyle(
            'A3:J3'
        )->applyFromArray([

            'font' => [

                'bold' => true,

                'size' => 14

            ],

            'alignment' => [

                'horizontal' => 'center'

            ]

        ]);

        /*
        Style entêtes
        */

        $sheet->getStyle(
            'A5:J6'
        )->applyFromArray([

            'font' => [

                'bold' => true

            ],

            'alignment' => [

                'horizontal' => 'center',

                'vertical' => 'center'

            ]

        ]);

        /*
        Bordures tableau
        */

        $sheet->getStyle(
            'A5:J' . ($ligne - 1)
        )->applyFromArray([

            'borders' => [

                'allBorders' => [

                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN

                ]

            ]

        ]);

        /*
        Format monétaire
        */

        $sheet->getStyle(
            'D7:D' . ($ligne - 1)
        )->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $sheet->getStyle(
            'F7:F' . ($ligne - 1)
        )->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $sheet->getStyle(
            'H7:H' . ($ligne - 1)
        )->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $sheet->getStyle(
            'J7:J' . ($ligne - 1)
        )->getNumberFormat()
            ->setFormatCode('#,##0.00');
    }
}
