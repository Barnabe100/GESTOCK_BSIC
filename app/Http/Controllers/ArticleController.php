<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Categorie;
use Illuminate\Http\Request;
use App\Exports\ArticlesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\MouvementStock;

class ArticleController extends Controller
{
    public function index()
    {
        $articles = Article::with('categorie')
            ->where('archiver', 0)
            ->get();

        return view('articles.index', compact('articles'));
    }

    public function create()
    {
        $categories = Categorie::where('archiver', 0)->get();
        return view('articles.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:articles',
            'libelle' => 'required',
            'prix_unitaire' => 'nullable|numeric',
            'stock_disponible' => 'nullable|integer',
            'stock_securite' => 'nullable|integer',
            'categorie_id' => 'required|exists:categories,id',
        ]);

        Article::create([
            'code' => $request->code,
            'libelle' => $request->libelle,
            'prix_unitaire' => $request->prix_unitaire,
            'stock_initial' => 0,
            'stock_disponible' => $request->stock_disponible ?? 0,
            'stock_securite' => $request->stock_securite ?? 0,
            'categorie_id' => $request->categorie_id,
        ]);

        return redirect()
            ->route('articles.index')
            ->with(
                'success',
                'Article créé avec succès'
            );
    }

    public function show($id)
    {
        $article = Article::with('categorie')->findOrFail($id);
        return view('articles.show', compact('article'));
    }

    public function edit($id)
    {
        $article = Article::findOrFail($id);
        $categories = Categorie::where('archiver', 0)->get();

        return view('articles.edit', compact('article', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'code' => 'required',
            'libelle' => 'required',
            'prix_unitaire' => 'required|numeric',
            'stock_disponible' => 'required|integer',
            'stock_securite' => 'required|integer',
            'categorie_id' => 'required|exists:categories,id',
        ]);

        $article = Article::findOrFail($id);

        $article->update([
            'code' => $request->code,
            'libelle' => $request->libelle,
            'prix_unitaire' => $request->prix_unitaire,
            'stock_disponible' => $request->stock_disponible,
            'stock_securite' => $request->stock_securite,
            'categorie_id' => $request->categorie_id,
        ]);

        return redirect()->route('articles.index')
            ->with('success', 'Article modifié avec succès');
    }

    public function destroy($id)
    {
        $article = Article::findOrFail($id);

        $article->update([
            'archiver' => 1
        ]);

        return redirect()->route('articles.index')
            ->with('success', 'Article archivé avec succès');
    }

    public function miseRebut(
        Request $request,
        $id
    ) {
        $request->validate([

            'quantite' =>

            'required|integer|min:1',

            'motif' =>

            'required',

            'date_rebut' =>

            'required|date'

        ]);


        $article =

            Article::findOrFail(
                $id
            );


        if (
            $article
            ->stock_disponible
            <
            $request->quantite
        ) {
            return back()->with(

                'error',

                'Stock insuffisant'

            );
        }


        $article->decrement(

            'stock_disponible',

            $request->quantite

        );


        MouvementStock::create([

            'article_id' =>

            $article->id,

            'user_id' =>

            auth()->id(),

            'type' =>

            'REBUT',

            'quantite' =>

            $request->quantite,

            'motif' =>

            $request->motif,

            'date_mouvement' =>

            $request->date_rebut

        ]);


        return back()->with(

            'success',

            'Article mis au rebut'

        );
    }



    //Export Excel
    public function exportExcel()
    {
        return Excel::download(
            new ArticlesExport,
            'articles.xlsx'
        );
    }

    //Export CSV
    public function exportCsv()
    {
        return Excel::download(
            new ArticlesExport,
            'articles.csv'
        );
    }
}
