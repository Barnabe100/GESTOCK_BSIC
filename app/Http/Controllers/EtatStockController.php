<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MouvementStock;
use App\Exports\Etats\EtatStockExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Article;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\Etats\EtatStockValoriseExport;

class EtatStockController extends Controller
{

    public function index()
    {

        return view(
            'etats.stocks.index'
        );
    }
    private function getEtatStock(
        $dateDebut,
        $dateFin
    ) {

        $articles = Article::all();
        $resultats = [];
        foreach (
            $articles
            as
            $article
        ) {

            $dernierMouvement = MouvementStock::where(
                'article_id',
                $article->id
            )
                ->whereBetween(
                    'date_mouvement',
                    [
                        $dateDebut,
                        $dateFin
                    ]
                )
                ->orderByDesc(
                    'date_mouvement'
                )
                ->first();


            //Stock début période
            $stockDebut = $article->stock_initial;
            $mouvementsAvant =
                MouvementStock::where(
                    'article_id',
                    $article->id
                )
                ->where(
                    'date_mouvement',
                    '<',
                    $dateDebut
                )->get();

            foreach (
                $mouvementsAvant
                as
                $mouvement
            ) {

                if (
                    $mouvement->type
                    ==
                    'ENTREE'
                ) {
                    $stockDebut +=
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
                    $stockDebut -= $mouvement->quantite;
                }
            }
            //Entrées période

            $entrees =

                MouvementStock::where(
                    'article_id',
                    $article->id
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

                ->sum(
                    'quantite'
                );



            /*
            Sorties période
            */

            $sorties =

                MouvementStock::where(
                    'article_id',
                    $article->id
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

                ->sum(
                    'quantite'
                );



            /*
            Stock fin période
            */

            $stockFin = $stockDebut + $entrees - $sorties;
            $resultats[] = [
                //'date' => date_mouvement,
                // 'date' => $dernierMouvement
                //     ? $dernierMouvement->date_mouvement
                //     : null,
                'article' => $article->libelle,
                'stock_debut' => $stockDebut,
                'entrees' => $entrees,
                'sorties' => $sorties,
                'stock_fin' => $stockFin
            ];

            if (
                $stockDebut == 0
                &&
                $entrees == 0
                &&
                $sorties == 0
                &&
                $stockFin == 0
            ) {
                continue;
            }
        }

        return $resultats;
    }


    public function exportExcel(
        Request $request
    ) {

        $data =

            $this->getEtatStock(

                $request->date_debut,
                $request->date_fin

            );
        return Excel::download(

            new EtatStockExport(

                $data,
                $request->date_debut,
                $request->date_fin
            ),

            'etat_stock.xlsx'

        );
    }


    public function exportPdf(
        Request $request
    ) {

        $request->validate([

            'date_debut' =>
            'required|date',

            'date_fin' =>
            'required|date'

        ]);

        $data =

            $this->getEtatStock(

                $request->date_debut,
                $request->date_fin

            );


        $pdf = PDF::loadView(

            'etats.stocks.etat_periode',

            [

                'data' => $data,

                'dateDebut' =>

                $request->date_debut,

                'dateFin' =>

                $request->date_fin

            ]

        );


        return $pdf->stream(
            'etat_stock.pdf'
        );
    }


    //Etat avec valeur
    private function getEtatStockValorise(
        $dateDebut,
        $dateFin
    ) {
        $articles = Article::all();

        $resultats = [];

        foreach (
            $articles as $article
        ) {

            $stockDebut =
                $article->stock_initial;

            $mouvementsAvant =
                MouvementStock::where(
                    'article_id',
                    $article->id
                )
                ->where(
                    'date_mouvement',
                    '<',
                    $dateDebut
                )
                ->get();

            foreach (
                $mouvementsAvant
                as
                $mouvement
            ) {

                if (
                    $mouvement->type
                    == 'ENTREE'
                ) {

                    $stockDebut +=
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

                    $stockDebut -=
                        $mouvement->quantite;
                }
            }

            $entrees =
                MouvementStock::where(
                    'article_id',
                    $article->id
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
                ->sum(
                    'quantite'
                );

            $entreeValeur = MouvementStock::where(
                'article_id',
                $article->id
            )
                ->where(
                    'type',
                    'ENTREE'
                )
                ->whereBetween(
                    'date_mouvement',
                    [$dateDebut, $dateFin]
                )
                ->get()
                ->sum(function ($mouvement) {

                    return
                        $mouvement->quantite
                        *
                        $mouvement->prix_unitaire;
                });

            $sorties =
                MouvementStock::where(
                    'article_id',
                    $article->id
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
                ->sum(
                    'quantite'
                );

            $stockFin =
                $stockDebut
                + $entrees
                - $sorties;

            // $pu = $article->prix_unitaire;
            $cmp = $article->cmp;

            $resultats[] = [

                //'date' =>$dateFin,

                //'date' => $mouvement->date_mouvement,

                'article' =>
                $article->libelle,

                'cmp' => $cmp,

                'stock_debut_qte' =>
                $stockDebut,

                'stock_debut_valeur' =>
                $stockDebut * $cmp,

                'entree_qte' =>
                $entrees,

                'entree_valeur' => $entreeValeur,

                'sortie_qte' =>
                $sorties,

                'sortie_valeur' =>
                $sorties * $cmp,

                'stock_fin_qte' =>
                $stockFin,

                'stock_fin_valeur' =>
                $stockFin * $cmp,

            ];
            if (
                empty($article->prix_unitaire)
                &&
                $stockDebut == 0
                &&
                $entrees == 0
                &&
                $sorties == 0
                &&
                $stockFin == 0
            ) {
                continue;
            }
        }

        return $resultats;
    }

    public function exportExcelValorise(
        Request $request
    ) {

        $request->validate([

            'date_debut' =>
            'required|date',

            'date_fin' =>
            'required|date'

        ]);



        $data =

            $this->getEtatStockValorise(

                $request->date_debut,

                $request->date_fin

            );



        return Excel::download(

            new EtatStockValoriseExport(

                $data,

                $request->date_debut,

                $request->date_fin

            ),

            'etat_stock_valorise.xlsx'

        );
    }

    public function exportPdfValorise(
        Request $request
    ) {

        $request->validate([

            'date_debut' =>
            'required|date',

            'date_fin' =>
            'required|date'

        ]);


        $data =

            $this->getEtatStockValorise(

                $request->date_debut,

                $request->date_fin

            );


        $pdf = Pdf::loadView(

            'etats.stocks.etat_valorise',

            [

                'data' => $data,

                'dateDebut' =>
                $request->date_debut,

                'dateFin' =>
                $request->date_fin

            ]

        );


        return $pdf->stream(
            'etat_stock_valorise.pdf'
        );
    }
}
