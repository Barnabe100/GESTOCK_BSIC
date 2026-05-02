<?php

namespace App\Http\Controllers;

use App\Models\Agence;
use Illuminate\Http\Request;
use App\Exports\AgencesExport;
use Maatwebsite\Excel\Facades\Excel;

class AgenceController extends Controller
{
    public function index()
    {
        //$agences = Agence::where('archiver', 0)->get();
        $agences = Agence::all();
        return view('agences.index', compact('agences'));
    }

    public function create()
    {
        return view('agences.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'nom' => 'required',
            'lieu' => 'nullable',
        ]);

        Agence::create([
            'code' => $request->code,
            'nom' => $request->nom,
            'lieu' => $request->lieu,
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
            'code' => 'required',
            'nom' => 'nullable',
            'lieu' => 'nullable',
            
        ]);

        $agence = Agence::findOrFail($id);

        $agence->update([
            'code' => $request->code,
            'nom' => $request->nom,
            'lieu' => $request->lieu,
           
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

    //Export Excel
    public function exportExcel()
    {
        return Excel::download(
            new AgencesExport,
            'agences.xlsx'
        );
    }

    //Export CSV
    public function exportCsv()
    {
        return Excel::download(
            new AgencesExport,
            'agences.csv'
        );
    }
}