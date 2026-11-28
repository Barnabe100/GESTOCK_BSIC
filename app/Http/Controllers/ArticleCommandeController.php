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
        // Quantité et prix contrôlés côté serveur (plus de ligne à −5).
        $request->validate([
            'commande_id' => 'required|integer|exists:commandes,id',
            'article_id' => 'required|integer|exists:articles,id',
            'quantite' => 'required|integer|min:1',
            'prix_unitaire' => 'required|numeric|min:0',
        ]);

        $existe = ArticleCommande::where('commande_id', $request->commande_id)
            ->where('article_id', $request->article_id)
            ->exists();

        if ($existe) {
            return back()
                ->withInput()
                ->with('error', 'Cet article est déjà présent dans cette commande.');
        }

        $commande = Commande::with('articles')
            ->findOrFail(
                $request->commande_id
            );

        // Socle des droits : aucune ligne ajoutée à une commande validée
        // (condition de l'interface), message existant.
        if ($commande->validation) {
            return back()->with('error', 'Commande déjà validée');
        }

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

        // Aucune modification de quantité ou de prix après la validation
        // (message existant).
        if ($commande->validation) {
            return back()->with('error', 'Commande déjà validée');
        }

        $request->validate([

            'quantite' => 'required|integer|min:1',

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

        // Socle des droits : aucune ligne supprimée d'une commande validée
        // (condition de l'interface), message existant.
        if ($ligne->commande && $ligne->commande->validation) {
            return back()->with('error', 'Commande déjà validée');
        }

        $ligne->delete();

        return back()->with(
            'success',
            'Article supprimé'
        );
    }




    //Export Excel
    public function exportExcel()
    {
        // Arbitrage A : sans l'accès complet au module, export limité aux
        // commandes entièrement réceptionnées (filtre côté serveur).
        return Excel::download(
            new ArticleCommandesExport(! \App\Autorisation\Capacites::autorise(auth()->user(), 'acceder-commandes')),
            'articles-commandes.xlsx'
        );
    }

    //Export CSV
    public function exportCsv()
    {
        // Arbitrage A : sans l'accès complet au module, export limité aux
        // commandes entièrement réceptionnées (filtre côté serveur).
        return Excel::download(
            new ArticleCommandesExport(! \App\Autorisation\Capacites::autorise(auth()->user(), 'acceder-commandes')),
            'articles-commandes.csv'
        );
    }
}
