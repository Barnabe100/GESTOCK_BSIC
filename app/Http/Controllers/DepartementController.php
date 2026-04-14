<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use Illuminate\Http\Request;

class DepartementController extends Controller
{
    public function index()
    {
        $departements = Departement::all();
        return view('departements.index', compact('departements'));
    }

    public function create()
    {
        return view('departements.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:departements',
            'nom' => 'required',
        ]);

        Departement::create([
            'code' => $request->code,
            'nom' => $request->nom,
        ]);

        return redirect()->route('departements.index')
            ->with('success', 'Département créé avec succès');
    }

    public function show($id)
    {
        $departement = Departement::findOrFail($id);
        return view('departements.show', compact('departement'));
    }

    public function edit($id)
    {
        $departement = Departement::findOrFail($id);
        return view('departements.edit', compact('departement'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'code' => 'required',
            'nom' => 'required',
        ]);

        $departement = Departement::findOrFail($id);

        $departement->update([
            'code' => $request->code,
            'nom' => $request->nom,
        ]);

        return redirect()->route('departements.index')
            ->with('success', 'Département modifié avec succès');
    }

    public function destroy($id)
    {
        $departement = Departement::findOrFail($id);

        $departement->delete(); // pas archiver ici (tu peux adapter si tu veux)

        return redirect()->route('departements.index')
            ->with('success', 'Département supprimé avec succès');
    }
}