<?php

namespace App\Http\Controllers\Etats;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MouvementStock;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Article;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\Etats\EtatEntreeStockExport;
use App\Exports\Etats\EtatSortieStockExport;
use App\Exports\Etats\EtatStockFinalExport;

class EtatMouvementStockController extends Controller
{
    private function getEtatEntrees(
        $dateDebut,
        $dateFin
    ) {

        $resultats = [];

        $mouvements =

            MouvementStock::with(
                'article'
            )

            ->where(
                'type',
                'ENTREE'
            )

            ->whereBetween(
                'date_mouvement',
                [
                    $dateDebut,
                    $dateFin
                ]
            )

            ->orderBy(
                'date_mouvement'
            )

            ->get();


        foreach (
            $mouvements
            as
            $mouvement
        ) {

            $pu =

                $mouvement
                ->article
                ->prix_unitaire;


            $resultats[] = [

                'date' =>

                $mouvement
                    ->date_mouvement,

                'article' =>

                $mouvement
                    ->article
                    ->libelle,

                'quantite' =>

                $mouvement
                    ->quantite,

                'valeur' =>

                $mouvement
                    ->quantite
                    *
                    $pu

            ];
        }

        return $resultats;
    }

    public function exportExcelEntrees(
        Request $request
    ) {

        $request->validate([

            'date_debut' =>
            'required|date',

            'date_fin' =>
            'required|date'

        ]);


        $data =

            $this->getEtatEntrees(

                $request->date_debut,

                $request->date_fin

            );


        return Excel::download(

            new EtatEntreeStockExport(

                $data,

                $request->date_debut,

                $request->date_fin

            ),

            'etat_entrees_stock.xlsx'

        );
    }

    public function exportPdfEntrees(
        Request $request
    ) {

        $request->validate([

            'date_debut' =>
            'required|date',

            'date_fin' =>
            'required|date'

        ]);


        $data =

            $this->getEtatEntrees(

                $request->date_debut,

                $request->date_fin

            );


        $pdf = Pdf::loadView(

            'etats.stocks.entrees_stock',

            [

                'data' => $data,

                'dateDebut' =>
                $request->date_debut,

                'dateFin' =>
                $request->date_fin

            ]

        );

        return $pdf->stream(
            'etat_entrees_stock.pdf'
        );
    }

    //Sorties
    private function getEtatSorties(
        $dateDebut,
        $dateFin
    ) {

        $resultats = [];

        $mouvements =

            MouvementStock::with(
                'article'
            )

            ->whereIn(
                'type',
                [
                    'SORTIE',
                    'REBUT'
                ]
            )

            ->whereBetween(
                'date_mouvement',
                [
                    $dateDebut,
                    $dateFin
                ]
            )

            ->orderBy(
                'date_mouvement'
            )

            ->get();


        foreach (
            $mouvements
            as
            $mouvement
        ) {

            if (
                !$mouvement->article
            ) {
                continue;
            }

            $resultats[] = [

                'date' =>
                $mouvement->date_mouvement,

                'article' =>
                $mouvement->article->libelle,

                'quantite' =>
                $mouvement->quantite,

                'valeur' =>
                $mouvement->quantite
                    *
                    $mouvement->article->prix_unitaire

            ];
        }

        return $resultats;
    }

    public function exportExcelSorties(
        Request $request
    ) {

        $request->validate([

            'date_debut' =>
            'required|date',

            'date_fin' =>
            'required|date'

        ]);


        $data =

            $this->getEtatSorties(

                $request->date_debut,

                $request->date_fin

            );


        return Excel::download(

            new EtatSortieStockExport(

                $data,

                $request->date_debut,

                $request->date_fin

            ),

            'etat_sorties_stock.xlsx'

        );
    }

    public function exportPdfSorties(
        Request $request
    ) {

        $request->validate([

            'date_debut' =>
            'required|date',

            'date_fin' =>
            'required|date'

        ]);


        $data =

            $this->getEtatSorties(

                $request->date_debut,

                $request->date_fin

            );


        $pdf = Pdf::loadView(

            'etats.stocks.sorties_stock',

            [

                'data' => $data,

                'dateDebut' =>
                $request->date_debut,

                'dateFin' =>
                $request->date_fin

            ]

        );

        return $pdf->stream(
            'etat_sorties_stock.pdf'
        );
    }


    //Etat stock Final
    private function getEtatStockFinal(
        $dateDebut,
        $dateFin
    ) {

        $articles = Article::all();

        $resultats = [];

        foreach (
            $articles as $article
        ) {

            $stock = $article->stock_initial;

            $mouvements =

                MouvementStock::where(
                    'article_id',
                    $article->id
                )

                ->where(
                    'date_mouvement',
                    '<=',
                    $dateFin
                )

                ->get();

            foreach (
                $mouvements
                as
                $mouvement
            ) {

                if (
                    $mouvement->type
                    ==
                    'ENTREE'
                ) {

                    $stock +=
                        $mouvement->quantite;
                }

                if (
                    in_array(
                        $mouvement->type,
                        [
                            'SORTIE',
                            'REBUT'
                        ]
                    )
                ) {

                    $stock -=
                        $mouvement->quantite;
                }
            }

            $resultats[] = [

                'date' =>
                $dateFin,

                'article' =>
                $article->libelle,

                'quantite' =>
                $stock,

                'valeur' =>
                $stock
                    *
                    $article->prix_unitaire

            ];
        }

        return $resultats;
    }

    public function exportExcelStockFinal(
        Request $request
    ) {

        $request->validate([

            'date_debut' =>
            'required|date',

            'date_fin' =>
            'required|date'

        ]);

        $data =

            $this->getEtatStockFinal(

                $request->date_debut,

                $request->date_fin

            );

        return Excel::download(

            new EtatStockFinalExport(

                $data,

                $request->date_debut,

                $request->date_fin

            ),

            'etat_stock_final.xlsx'

        );
    }

    public function exportPdfStockFinal(
        Request $request
    ) {

        $request->validate([

            'date_debut' =>
            'required|date',

            'date_fin' =>
            'required|date'

        ]);

        $data =

            $this->getEtatStockFinal(

                $request->date_debut,

                $request->date_fin

            );

        $pdf = Pdf::loadView(

            'etats.stocks.stock_final',

            [

                'data' => $data,

                'dateDebut' =>
                $request->date_debut,

                'dateFin' =>
                $request->date_fin

            ]

        );

        return $pdf->stream(
            'etat_stock_final.pdf'
        );
    }
}
