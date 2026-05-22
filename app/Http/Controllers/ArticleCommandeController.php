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
        ArticleCommande::create([
            'commande_id' => $request->commande_id,
            'article_id' => $request->article_id,
            'quantite' => $request->quantite,
            'prix_unitaire' => $request->prix_unitaire,
        ]);

        // 🔥 recalcul montant
        $commande = Commande::with('articles')->find($request->commande_id);

        $total = $commande->articles->sum(function ($item) {
            return $item->quantite * $item->prix;
        });

        $commande->update(['montant' => $total]);

        return back()->with('success', 'Article ajouté');
    }

    public function destroy($id)
    {
        $ligne = ArticleCommande::findOrFail($id);
        $commande = $ligne->commande;

        $ligne->delete();

        // 🔥 recalcul montant
        $total = $commande->articles->sum(function ($item) {
            return $item->quantite * $item->prix_unitaire;
        });

        $commande->update(['montant' => $total]);

        return back();
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
