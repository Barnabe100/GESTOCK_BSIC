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

        $sheet->mergeCells('A3:I3');

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

        /*
Entêtes fusionnées
*/

        $sheet->mergeCells('A5:A6');

        $sheet->mergeCells('B5:C5');
        $sheet->mergeCells('D5:E5');
        $sheet->mergeCells('F5:G5');
        $sheet->mergeCells('H5:I5');

        $sheet->setCellValue(
            'A5',
            'INTITULE'
        );

        $sheet->setCellValue(
            'B5',
            'STOCK EN DEBUT DE PERIODE'
        );

        $sheet->setCellValue(
            'D5',
            'ENTREE DE LA PERIODE'
        );

        $sheet->setCellValue(
            'F5',
            'SORTIE DE LA PERIODE'
        );

        $sheet->setCellValue(
            'H5',
            'STOCK EN FIN DE PERIODE'
        );

        /*
        Sous-entêtes
        */

        $sheet->setCellValue(
            'B6',
            'QUANTITE'
        );

        $sheet->setCellValue(
            'C6',
            'VALEUR'
        );

        $sheet->setCellValue(
            'D6',
            'QUANTITE'
        );

        $sheet->setCellValue(
            'E6',
            'VALEUR'
        );

        $sheet->setCellValue(
            'F6',
            'QUANTITE'
        );

        $sheet->setCellValue(
            'G6',
            'VALEUR'
        );

        $sheet->setCellValue(
            'H6',
            'QUANTITE'
        );

        $sheet->setCellValue(
            'I6',
            'VALEUR'
        );

        /*
        Données
        */

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
                $item['article']
            );

            $sheet->setCellValue(
                'B' . $ligne,
                $item['stock_debut_qte']
            );

            $sheet->setCellValue(
                'C' . $ligne,
                $item['stock_debut_valeur']
            );

            $sheet->setCellValue(
                'D' . $ligne,
                $item['entree_qte']
            );

            $sheet->setCellValue(
                'E' . $ligne,
                $item['entree_valeur']
            );

            $sheet->setCellValue(
                'F' . $ligne,
                $item['sortie_qte']
            );

            $sheet->setCellValue(
                'G' . $ligne,
                $item['sortie_valeur']
            );

            $sheet->setCellValue(
                'H' . $ligne,
                $item['stock_fin_qte']
            );

            $sheet->setCellValue(
                'I' . $ligne,
                $item['stock_fin_valeur']
            );

            $ligne++;
        }

        //Condition pour verifier si les données sont disponibles
        if (
            count($this->data) == 0
        ) {

            $sheet->mergeCells('A7:I7');

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
            'A3:I3'
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
            'A5:I6'
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
            'A5:I' . ($ligne - 1)
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
            'C7:C' . ($ligne - 1)
        )->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $sheet->getStyle(
            'E7:E' . ($ligne - 1)
        )->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $sheet->getStyle(
            'G7:G' . ($ligne - 1)
        )->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $sheet->getStyle(
            'I7:I' . ($ligne - 1)
        )->getNumberFormat()
            ->setFormatCode('#,##0.00');
    }
}
