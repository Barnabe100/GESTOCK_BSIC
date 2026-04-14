<?php

namespace App\Http\Controllers;

use App\Models\Agence;
use Illuminate\Http\Request;

class AgenceController extends Controller
{
    public function index()
    {
        $agences = Agence::where('archiver', 0)->get();
        return view('agences.index', compact('agences'));
    }

    public function create()
    {
        return view('agences.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required',
            'ville' => 'nullable',
            'adresse' => 'nullable',
            'telephone' => 'nullable',
        ]);

        Agence::create([
            'nom' => $request->nom,
            'ville' => $request->ville,
            'adresse' => $request->adresse,
            'telephone' => $request->telephone,
        ]);

        return redirect()->route('agences.index')
            ->with('success', 'Agence créée avec succès');
    }

    public function show($id)
    {
        $agence = Agence::findOrFail($id);
        return view('agences.show', compact('agence'));
    }

    public function edit($id)
    {
        $agence = Agence::findOrFail($id);
        return view('agences.edit', compact('agence'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nom' => 'required',
            'ville' => 'nullable',
            'adresse' => 'nullable',
            'telephone' => 'nullable',
        ]);

        $agence = Agence::findOrFail($id);

        $agence->update([
            'nom' => $request->nom,
            'ville' => $request->ville,
            'adresse' => $request->adresse,
            'telephone' => $request->telephone,
        ]);

        return redirect()->route('agences.index')
            ->with('success', 'Agence modifiée avec succès');
    }

    public function destroy($id)
    {
        $agence = Agence::findOrFail($id);

        $agence->update([
            'archiver' => 1
        ]);

        return redirect()->route('agences.index')
            ->with('success', 'Agence archivée avec succès');
    }
}