<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\Agence;
use Illuminate\Http\Request;
use App\Exports\DepartementsExport;
use Maatwebsite\Excel\Facades\Excel;

class DepartementController extends Controller
{
    public function index()
    {
        $departements = Departement::all();
        return view('departements.index', compact('departements'));
    }

    public function create()
    {
        $agences = Agence::all();
        return view('departements.create', compact('agences'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:departements',
            'nom' => 'required',
            'agence_id' => 'required|exists:agences,id',
        ]);

        Departement::create([
            'code' => $request->code,
            'nom' => $request->nom,
            'agence_id' => $request->agence_id,
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
        $agences = Agence::all();
        return view('departements.edit', compact('departement', 'agences'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'code' => 'required',
            'nom' => 'required',
            'agence_id' => 'required|exists:agences,id',
        ]);

        $departement = Departement::findOrFail($id);

        $departement->update([
            'code' => $request->code,
            'nom' => $request->nom,
            'agence_id' => $request->agence_id,
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

    //Export Excel
    public function exportExcel()
    {
        return Excel::download(
            new DepartementsExport,
            'departements.xlsx'
        );
    }

    //Export CSV
    public function exportCsv()
    {
        return Excel::download(
            new DepartementsExport,
            'departements.csv'
        );
    }
}
