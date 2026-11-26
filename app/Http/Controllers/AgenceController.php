<?php

namespace App\Http\Controllers;

use App\Models\Agence;
use App\Organisation\RefusOrganisation;
use App\Organisation\StructureOrganisationnelle;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Exports\AgencesExport;
use Maatwebsite\Excel\Facades\Excel;

class AgenceController extends Controller
{
    public function index()
    {
        $agences = Agence::where('archiver', 0)
            ->latest()
            ->get();
        //$agences = Agence::all();
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
            'type' => ['required', Rule::in(Agence::TYPES)],
        ]);

        // N03 : le type est explicite ; un seul siège actif (garanti par la
        // base, message clair en cas de refus).
        try {
            app(StructureOrganisationnelle::class)->creer($request->only('code', 'nom', 'lieu', 'type'), auth()->user());
        } catch (RefusOrganisation $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

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
            'type' => ['nullable', Rule::in(Agence::TYPES)],
        ]);

        $agence = Agence::findOrFail($id);

        // N03 : un renommage ne change jamais le type ; un changement de type
        // vers « Siège » est refusé si un siège actif existe déjà.
        try {
            app(StructureOrganisationnelle::class)->modifier($agence, $request->only('code', 'nom', 'lieu', 'type'), auth()->user());
        } catch (RefusOrganisation $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('agences.index')
            ->with('success', 'Agence modifiée avec succès');
    }

    // N03 : changement de siège (l'ancien siège devient une agence)
    public function designerSiege($id)
    {
        $agence = Agence::findOrFail($id);

        try {
            app(StructureOrganisationnelle::class)->designerSiege($agence, auth()->user());
        } catch (RefusOrganisation $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('agences.index')
            ->with('success', 'Siège désigné avec succès');
    }

    public function destroy($id)
    {
        $agence = Agence::findOrFail($id);

        // Archivage (désactivation) : type et rattachements conservés.
        app(StructureOrganisationnelle::class)->archiver($agence, auth()->user());

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
