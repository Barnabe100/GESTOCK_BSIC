<?php

namespace App\Http\Controllers;

use App\Models\Fournisseur;
use Illuminate\Http\Request;

class FournisseurController extends Controller
{
    public function index()
    {
        $fournisseurs = Fournisseur::where('archiver', 0)->get();
        return view('fournisseurs.index', compact('fournisseurs'));
    }

    public function create()
    {
        return view('fournisseurs.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:fournisseurs',
            'nom' => 'required',
        ]);

        Fournisseur::create([
            'code' => $request->code,
            'nom' => $request->nom,
            'telephone' => $request->telephone,
            'email' => $request->email,
            'adresse' => $request->adresse,
        ]);

        return redirect()->route('fournisseurs.index')
            ->with('success', 'Fournisseur créé');
    }

    public function edit($id)
    {
        $fournisseur = Fournisseur::findOrFail($id);
        return view('fournisseurs.edit', compact('fournisseur'));
    }

    public function update(Request $request, $id)
    {
        $fournisseur = Fournisseur::findOrFail($id);

        $fournisseur->update($request->all());

        return redirect()->route('fournisseurs.index')
            ->with('success', 'Fournisseur modifié');
    }

    public function destroy($id)
    {
        Fournisseur::findOrFail($id)->update([
            'archiver' => 1
        ]);

        return redirect()->route('fournisseurs.index')
            ->with('success', 'Fournisseur archivé');
    }
}