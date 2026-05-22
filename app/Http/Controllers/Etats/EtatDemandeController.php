<?php

namespace App\Http\Controllers\Etats;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Commande;
use App\Models\Demande;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Etats\DemandesParAgenceExport;
use App\Exports\Etats\DemandesValideesExport;
use App\Exports\Etats\DemandesParServiceExport;
use App\Exports\Etats\DemandesParDepartementExport;

class EtatDemandeController extends Controller
{
    // 🔥 PAGE PRINCIPALE
    public function index()
    {
        return view('etats.index');
    }

    // 🔥 EXCEL
    public function demandesParAgenceExcel()
    {
        return Excel::download(
            new DemandesParAgenceExport,
            'demandes_par_agence.xlsx'
        );
    }

    // 🔥 PDF
    public function demandesParAgencePdf()
    {
        $demandes = Demande::with([
            'user.agence'
        ])
            ->where('archiver', 0)
            ->get();

        $pdf = Pdf::loadView(
            'etats.demandes.demandes_par_agence',
            compact('demandes')
        );

        return $pdf->stream(
            'demandes_par_agence.pdf'
        );
    }

    /*
      |--------------------------------------------------------------------------
      | DEMANDES VALIDÉES
      |--------------------------------------------------------------------------
      */

    // 🔥 EXCEL
    public function demandesValideesExcel()
    {
        return Excel::download(
            new DemandesValideesExport,
            'demandes_validees.xlsx'
        );
    }

    // 🔥 PDF
    public function demandesValideesPdf()
    {
        $demandes = Demande::with('user')
            ->where('validation', 1)
            ->where('archiver', 0)
            ->get();

        $pdf = Pdf::loadView(
            'etats.demandes.demandes_validees',
            compact('demandes')
        );

        return $pdf->stream(
            'demandes_validees.pdf'
        );
    }


    public function demandesParServiceExcel()
    {
        return Excel::download(
            new DemandesParServiceExport,
            'demandes_par_service.xlsx'
        );
    }

    public function demandesParServicePdf()
    {
        $demandes = Demande::with([
            'user.service'
        ])
            ->where('archiver', 0)
            ->get();

        $pdf = Pdf::loadView(
            'etats.demandes.demandes_par_service',
            compact('demandes')
        );

        return $pdf->stream(
            'demandes_par_service.pdf'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DEMANDES PAR DEPARTEMENT
    |--------------------------------------------------------------------------
    */

    public function demandesParDepartementExcel()
    {
        return Excel::download(
            new DemandesParDepartementExport,
            'demandes_par_departement.xlsx'
        );
    }

    public function demandesParDepartementPdf()
    {
        $demandes = Demande::with([
            'user.departement'
        ])
            ->where('archiver', 0)
            ->get();

        $pdf = Pdf::loadView(
            'etats.demandes.demandes_par_departement',
            compact('demandes')
        );

        return $pdf->stream(
            'demandes_par_departement.pdf'
        );
    }
}
