<?php

namespace App\Http\Controllers;

use App\Models\TypeArticle;
use Illuminate\Http\Request;
use App\Exports\TypeArticlesExport;
use Maatwebsite\Excel\Facades\Excel;


class TypeArticleController extends Controller
{
    public function index()
    {
        $typeArticles = TypeArticle::where('archiver', 0)->get();

        return view('type_articles.index', compact('typeArticles'))
            ->with('success', 'Type d\'article créé avec succès 👍');
    }

    public function create()
    {
        return view('type_articles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:type_articles',
            'libelle' => 'required',
            'description' => 'nullable',
        ]);

        TypeArticle::create([
            'code' => $request->code,
            'libelle' => $request->libelle,
            'description' => $request->description,
        ]);

        return redirect()->route('type-articles.index')
            ->with('success', 'Type d\'article créé avec succès');
    }

    public function show($id)
    {
        $typeArticle = TypeArticle::findOrFail($id);

        return view('type_articles.show', compact('typeArticle'));
    }

    public function edit($id)
    {
        $typeArticle = TypeArticle::findOrFail($id);

        return view('type_articles.edit', compact('typeArticle'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'code' => 'required',
            'libelle' => 'required',
            'description' => 'nullable',
        ]);

        $typeArticle = TypeArticle::findOrFail($id);

        $typeArticle->update([
            'code' => $request->code,
            'libelle' => $request->libelle,
            'description' => $request->description,
        ]);

        return redirect()->route('type-articles.index')
            ->with('success', 'Type d\'article modifié avec succès');
    }

    public function destroy($id)
    {
        $typeArticle = TypeArticle::findOrFail($id);

        $typeArticle->update([
            'archiver' => 1,
        ]);

        return redirect()->route('type-articles.index')
            ->with('success', 'Type d\'article archivé avec succès');
    }

    // Export Excel
    public function exportExcel()
    {
        return Excel::download(
            new TypeArticlesExport,
            'types_articles.xlsx'
        );
    }

    // Export CSV
    public function exportCsv()
    {
        return Excel::download(
            new TypeArticlesExport,
            'types_articles.csv'
        );
    }
}
