<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Categorie;
use Illuminate\Http\Request;

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
            'prix_unitaire' => 'required|numeric',
            'stock_disponible' => 'required|integer',
            'stock_securite' => 'required|integer',
            'categorie_id' => 'required|exists:categories,id',
        ]);

        Article::create([
            'code' => $request->code,
            'libelle' => $request->libelle,
            'prix_unitaire' => $request->prix_unitaire,
            'stock_disponible' => $request->stock_disponible,
            'stock_securite' => $request->stock_securite,
            'categorie_id' => $request->categorie_id,
        ]);

        return redirect()->route('articles.index')
            ->with('success', 'Article créé avec succès');
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
}