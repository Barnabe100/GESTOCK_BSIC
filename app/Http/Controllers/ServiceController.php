<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Departement;
use Illuminate\Http\Request;
use App\Exports\ServicesExport;
use Maatwebsite\Excel\Facades\Excel;

class ServiceController extends Controller
{
    public function index()
    {
        //$services = Service::with('departement')->get();
        $services = Service::where('archiver', 0)
            ->latest()
            ->get();
        return view('services.index', compact('services'));
    }

    public function create()
    {
        $departements = Departement::all();
        return view('services.create', compact('departements'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:services',
            'nom' => 'required',
            'departement_id' => 'required|exists:departements,id',
        ]);

        Service::create([
            'code' => $request->code,
            'nom' => $request->nom,
            'localisation' => $request->localisation,
            'departement_id' => $request->departement_id,
        ]);

        return redirect()->route('services.index')
            ->with('success', 'Service créé avec succès');
    }

    public function show($id)
    {
        $service = Service::with('departement')->findOrFail($id);
        return view('services.show', compact('service'));
    }

    public function edit($id)
    {
        $service = Service::findOrFail($id);
        $departements = Departement::all();

        return view('services.edit', compact('service', 'departements'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'code' => 'required',
            'nom' => 'required',
            'departement_id' => 'required|exists:departements,id',
        ]);

        $service = Service::findOrFail($id);

        $service->update([
            'code' => $request->code,
            'nom' => $request->nom,
            'localisation' => $request->localisation,
            'departement_id' => $request->departement_id,
        ]);

        return redirect()->route('services.index')
            ->with('success', 'Service modifié avec succès');
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);

        $service->delete();

        return redirect()->route('services.index')
            ->with('success', 'Service supprimé avec succès');
    }

    //Export Excel
    public function exportExcel()
    {
        return Excel::download(
            new ServicesExport,
            'services.xlsx'
        );
    }

    //Export CSV
    public function exportCsv()
    {
        return Excel::download(
            new ServicesExport,
            'services.csv'
        );
    }

    public function services($id)
    {
        return Service::where(
            'departement_id',
            $id
        )->get();
    }
}
