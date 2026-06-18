<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ArticleDemande;
use App\Models\Article;
use App\Exports\ArticleDemandesExport;
use Maatwebsite\Excel\Facades\Excel;


class ArticleDemandeController extends Controller
{
    /**
     * Ajouter un article à une demande
     */
    public function store(Request $request)
    {
        $article = Article::findOrFail($request->article_id);

        // Vérifier stock
        if ($request->quantite > $article->stock_disponible) {
            return back()->with('error', 'Stock insuffisant');
        }

        // Créer la ligne
        ArticleDemande::create([
            'demande_id' => $request->demande_id,
            'article_id' => $request->article_id,
            'quantite' => $request->quantite,
            'archiver' => 0
        ]);

        // 🔥 DÉCRÉMENTER STOCK
        // $article->decrement('stock_disponible', $request->quantite);

        return back()->with('success', 'Article ajouté et stock mis à jour');
    }

    public function update(
        Request $request,
        $id
    ) {

        $ligne = ArticleDemande::with(
            'demande',
            'article'
        )->findOrFail($id);

        $request->validate([

            'article_id' => 'required|exists:articles,id',

            'quantite' => 'required|integer|min:1'

        ]);

        // Empêcher modification après validation

        if ($ligne->demande->validation) {

            return back()->with(

                'error',

                'Cette demande ne peut plus être modifiée.'

            );
        }

        // Vérifier si l'article existe déjà
        // dans la même demande

        $existe = ArticleDemande::where(

            'demande_id',

            $ligne->demande_id

        )
            ->where(

                'article_id',

                $request->article_id

            )
            ->where(

                'id',

                '!=',

                $ligne->id

            )
            ->exists();

        if ($existe) {

            return back()->with(

                'error',

                'Cet article existe déjà dans la demande.'

            );
        }

        $article = Article::findOrFail(
            $request->article_id
        );

        // Vérifier stock disponible

        if (

            $request->quantite >
            $article->stock_disponible

        ) {

            return back()->with(

                'error',

                'Stock insuffisant pour cet article.'

            );
        }

        $ligne->update([

            'article_id' =>
            $request->article_id,

            'quantite' =>
            $request->quantite

        ]);

        return back()->with(

            'success',

            'Article modifié avec succès.'

        );
    }

    /**
     * Supprimer un article de la demande
     */
    public function destroy($id)
    {
        $ligne = ArticleDemande::with('article', 'demande')->findOrFail($id);

        // 🔥 Si la demande est déjà validée → remettre le stock
        if (!$ligne->demande->validation) {
            $ligne->article->increment('stock_disponible', $ligne->quantite);
        }

        $ligne->delete();

        return back()->with('success', 'Article supprimé et stock mis à jour');
    }


    //Export Excel
    public function exportExcel()
    {
        return Excel::download(
            new ArticleDemandesExport,
            'articles-demandes.xlsx'
        );
    }

    //Export CSV
    public function exportCsv()
    {
        return Excel::download(
            new ArticleDemandesExport,
            'articles-demandes.csv'
        );
    }
}
