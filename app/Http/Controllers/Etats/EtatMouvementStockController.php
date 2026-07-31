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
use Carbon\Carbon;

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

            // $pu =

            //     $mouvement
            //     ->article
            //     ->prix_unitaire;

            $pu = $mouvement->prix_unitaire;

            if ($pu === null) {
                continue;
            }

            $resultats[] = [

                'date' =>

                $mouvement
                    ->date_mouvement,

                'article' =>

                $mouvement
                    ->article
                    ->libelle,

                'quantite' =>

                $mouvement->quantite,

                'valeur' => $mouvement->quantite * $mouvement->prix_unitaire

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

            'etat_entrees_stock_' . Carbon::now()->format('Ymd_His') . '.xlsx'

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

        $canvas = $pdf->getDomPDF()->getCanvas();

        $canvas->page_text(
            25,
            820,
            "Généré le : " . now()->format('d/m/Y à H:i:s'),
            null,
            9,
            [0, 0, 0]
        );

        $canvas->page_text(
            500,
            820,
            "Page {PAGE_NUM}/{PAGE_COUNT}",
            null,
            9,
            [0, 0, 0]
        );

        return $pdf->stream(
            'etat_entrees_stock_' . Carbon::now()->format('Ymd_His') . '.pdf'
        );
    }

    //Etat des sorties
    private function getEtatSorties(
        $dateDebut,
        $dateFin,
        $articleId = null
    ) {

        $resultats = [];

        $query = MouvementStock::with(
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
            );

        /*
    Filtre article optionnel
    */

        if (!empty($articleId)) {

            $query->where(
                'article_id',
                $articleId
            );
        }

        $mouvements = $query

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
                ||
                $mouvement->article->cmp === null
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

                'cmp' =>

                $mouvement->article->cmp,

                'valeur' =>

                $mouvement->quantite
                    *
                    $mouvement->article->cmp

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

                $request->date_fin,
                $request->article_id

            );


        return Excel::download(

            new EtatSortieStockExport(

                $data,

                $request->date_debut,

                $request->date_fin

            ),

            'etat_sorties_stock_' . Carbon::now()->format('Ymd_His') . '.xlsx'

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

                $request->date_fin,
                $request->article_id

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

        $canvas = $pdf->getDomPDF()->getCanvas();

        $canvas->page_text(
            25,
            820,
            "Généré le : " . now()->format('d/m/Y à H:i:s'),
            null,
            9,
            [0, 0, 0]
        );

        $canvas->page_text(
            500,
            820,
            "Page {PAGE_NUM}/{PAGE_COUNT}",
            null,
            9,
            [0, 0, 0]
        );

        //dd($request->all());

        return $pdf->stream(
            'etat_sorties_stock_' . Carbon::now()->format('Ymd_His') . '.pdf'
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

            if (
                $article->prix_unitaire === null
                &&
                $stock == 0
            ) {
                continue;
            }

            $resultats[] = [

                'date' =>
                $dateFin,

                'article' =>
                $article->libelle,

                'quantite' =>
                $stock,

                // 'valeur' =>
                // $stock
                //     *
                //     $article->prix_unitaire

                'cmp' =>
                $article->cmp,

                'valeur' =>
                $stock * $article->cmp

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

            'etat_stock_final_' . Carbon::now()->format('Ymd_His') . '.xlsx'

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

        $canvas = $pdf->getDomPDF()->getCanvas();

        $canvas->page_text(
            25,
            820,
            "Généré le : " . now()->format('d/m/Y à H:i:s'),
            null,
            9,
            [0, 0, 0]
        );

        $canvas->page_text(
            500,
            820,
            "Page {PAGE_NUM}/{PAGE_COUNT}",
            null,
            9,
            [0, 0, 0]
        );

        return $pdf->stream(
            'etat_stock_final_' . Carbon::now()->format('Ymd_His') . '.pdf'
        );
    }
}
