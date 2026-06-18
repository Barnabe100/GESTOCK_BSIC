<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ArticleCommande;
use App\Models\Commande;
use App\Models\Article;
use App\Exports\ArticleCommandesExport;
use Maatwebsite\Excel\Facades\Excel;



class ArticleCommandeController extends Controller
{


    public function store(Request $request)
    {
        $commande = Commande::with('articles')
            ->findOrFail(
                $request->commande_id
            );

        $montantUtilise =
            $commande->articles->sum(
                function ($item) {
                    return
                        $item->quantite
                        *
                        $item->prix_unitaire;
                }
            );

        $montantNouvelArticle =
            $request->quantite
            *
            $request->prix_unitaire;

        if (
            ($montantUtilise + $montantNouvelArticle)
            >
            $commande->montant
        ) {

            $reste =
                $commande->montant
                -
                $montantUtilise;

            return back()->with(

                'error',

                'Montant dépassé. Reste disponible : '
                    . number_format(
                        $reste,
                        2,
                        ',',
                        ' '
                    )

            );
        }

        ArticleCommande::create([

            'commande_id' =>
            $request->commande_id,

            'article_id' =>
            $request->article_id,

            'quantite' =>
            $request->quantite,

            'prix_unitaire' =>
            $request->prix_unitaire,

            'quantite_livree' => 0,

            'quantite_restante' =>
            $request->quantite

        ]);

        return back()->with(
            'success',
            'Article ajouté'
        );
    }

    public function update(
        Request $request,
        $id
    ) {

        $ligne = ArticleCommande::findOrFail($id);

        $commande = $ligne->commande;

        $request->validate([

            'quantite' => 'required|numeric|min:1',

            'prix_unitaire' => 'required|numeric|min:0',

        ]);

        $montantUtilise =
            $commande->articles
            ->where('id', '!=', $ligne->id)
            ->sum(function ($item) {

                return
                    $item->quantite
                    *
                    $item->prix_unitaire;
            });

        $nouveauMontant =
            $request->quantite
            *
            $request->prix_unitaire;

        if (
            ($montantUtilise + $nouveauMontant)
            >
            $commande->montant
        ) {

            $reste =
                $commande->montant
                -
                $montantUtilise;

            return back()->with(

                'error',

                'Montant dépassé. Disponible : '
                    . number_format(
                        $reste,
                        2,
                        ',',
                        ' '
                    )

            );
        }

        $ligne->update([

            'quantite' =>
            $request->quantite,

            'prix_unitaire' =>
            $request->prix_unitaire,

            'quantite_restante' =>
            $request->quantite
                -
                $ligne->quantite_livree

        ]);

        return back()->with(

            'success',

            'Ligne modifiée avec succès'

        );
    }

    public function destroy($id)
    {
        $ligne = ArticleCommande::findOrFail($id);

        $ligne->delete();

        return back()->with(
            'success',
            'Article supprimé'
        );
    }




    //Export Excel
    public function exportExcel()
    {
        return Excel::download(
            new ArticleCommandesExport,
            'articles-commandes.xlsx'
        );
    }

    //Export CSV
    public function exportCsv()
    {
        return Excel::download(
            new ArticleCommandesExport,
            'articles-commandes.csv'
        );
    }
}
