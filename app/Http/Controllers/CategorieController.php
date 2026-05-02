<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use Illuminate\Http\Request;
use App\Exports\CategoriesExport;
use Maatwebsite\Excel\Facades\Excel;

class CategorieController extends Controller
{
    public function index()
    {
        $categories = Categorie::where('archiver', 0)->get();
        return view('categories.index', compact('categories'))
        ->with('success', 'Catégorie créée avec succès 👍');
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:categories',
            'libelle' => 'required',
            'description' => 'nullable',
        ]);

        Categorie::create([
            'code' => $request->code,
            'libelle' => $request->libelle,
            'description' => $request->description,
        ]);

        return redirect()->route('categories.index')
            ->with('success', 'Catégorie créée avec succès');
    }

    public function show($id)
    {
        $categorie = Categorie::findOrFail($id);
        return view('categories.show', compact('categorie'));
    }

    public function edit($id)
    {
        $categorie = Categorie::findOrFail($id);
        return view('categories.edit', compact('categorie'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'code' => 'required',
            'libelle' => 'required',
            'description' => 'nullable',
        ]);

        $categorie = Categorie::findOrFail($id);

        $categorie->update([
            'code' => $request->code,
            'libelle' => $request->libelle,
            'description' => $request->description,
        ]);

        return redirect()->route('categories.index')
            ->with('success', 'Catégorie modifiée avec succès');
    }

    public function destroy($id)
    {
        $categorie = Categorie::findOrFail($id);

        $categorie->update([
            'archiver' => 1
        ]);

        return redirect()->route('categories.index')
            ->with('success', 'Catégorie archivée avec succès');
    }

    //Export Excel
    public function exportExcel()
    {
        return Excel::download(
            new CategoriesExport,
            'categories.xlsx'
        );
    }

    //Export CSV
    public function exportCsv()
    {
        return Excel::download(
            new CategoriesExport,
            'categories.csv'
        );
    }
}