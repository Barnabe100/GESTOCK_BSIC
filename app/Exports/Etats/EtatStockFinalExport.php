<?php

namespace App\Exports\Etats;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class EtatStockFinalExport implements
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
        return 'Stock Final';
    }

    public function styles(
        Worksheet $sheet
    ) {

        /*
        Titre
        */

        $sheet->mergeCells(
            'A3:D3'
        );

        $sheet->setCellValue(

            'A3',

            'ETAT DE STOCK FINAL DU '

                . $this->dateDebut

                . ' AU '

                . $this->dateFin

        );

        /*
        Entêtes fusionnés
        */

        $sheet->mergeCells(
            'A5:A6'
        );

        $sheet->mergeCells(
            'B5:B6'
        );

        $sheet->mergeCells(
            'C5:D5'
        );

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
            'STOCK EN FIN DE LA PERIODE'
        );

        $sheet->setCellValue(
            'C6',
            'QUANTITE'
        );

        $sheet->setCellValue(
            'D6',
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
                $item['quantite']
            );

            $sheet->setCellValue(
                'D' . $ligne,
                $item['valeur']
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
            'A3:D3'
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
            'A5:D6'
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
        Bordures
        */

        $sheet->getStyle(
            'A5:D' . ($ligne - 1)
        )->applyFromArray([

            'borders' => [

                'allBorders' => [

                    'borderStyle' =>
                    Border::BORDER_THIN

                ]

            ]

        ]);

        /*
        Format valeur monétaire
        */

        $sheet->getStyle(
            'D7:D' . ($ligne - 1)
        )
            ->getNumberFormat()
            ->setFormatCode(
                '#,##0.00'
            );

        /*
        Centrage du bloc fusionné
        */

        $sheet->getStyle(
            'C5:D5'
        )->applyFromArray([

            'alignment' => [

                'horizontal' => 'center',
                'vertical' => 'center'

            ]

        ]);
    }
}
