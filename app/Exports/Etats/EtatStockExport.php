<?php

namespace App\Exports\Etats;

use Maatwebsite\Excel\Concerns\FromArray;

use Maatwebsite\Excel\Concerns\ShouldAutoSize;

use Maatwebsite\Excel\Concerns\WithStyles;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Maatwebsite\Excel\Concerns\WithTitle;



class EtatStockExport implements

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



    /*
    Données
    */

    public function array(): array
    {

        return [];
    }



    /*
    Nom feuille
    */

    public function title(): string
    {

        return 'Etat Stock';
    }



    /*
    Styles + contenu
    */

    public function styles(

        Worksheet $sheet

    ) {

        /*
        TITRE
        */

        $sheet->mergeCells(
            'A3:E3'
        );



        $sheet->setCellValue(

            'A3',

            'ETAT DES STOCKS EN QUANTITE DU '

                . $this->dateDebut

                . ' AU '

                . $this->dateFin

        );



        /*
        EN-TÊTES
        */

        $sheet->fromArray(

            [

                [

                    // 'DATE ARTICLE',
                    'INTITULE ARTICLE',

                    'STOCK EN DEBUT DE PERIODE',

                    'ENTREE DE LA PERIODE',

                    'SORTIE DE LA PERIODE',

                    'STOCK EN FIN DE PERIODE'

                ]

            ],

            null,

            'A5'

        );



        /*
        DONNÉES
        */

        $sheet->fromArray(

            $this->data,

            null,

            'A6'

        );



        /*
        STYLE TITRE
        */

        $sheet->getStyle(
            'A3'
        )->applyFromArray([

            'font' => [

                'bold' => true,

                'size' => 14

            ],

            'alignment' => [

                'horizontal' =>

                'center'

            ]

        ]);



        /*
        STYLE ENTÊTES
        */

        $sheet->getStyle(
            'A5:E5'
        )->applyFromArray([

            'font' => [

                'bold' => true

            ]

        ]);
    }
}
