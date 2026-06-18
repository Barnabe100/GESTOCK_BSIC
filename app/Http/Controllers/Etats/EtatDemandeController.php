<?php

namespace App\Http\Controllers\Etats;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Commande;
use App\Models\Demande;
use App\Models\Departement;
use App\Models\Agence;
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


        $departements = Departement::orderBy('nom')->get();
        $articles = Article::whereHas(

            'mouvementsStock',

            function ($query) {

                $query->whereIn(
                    'type',
                    [
                        'SORTIE',
                        'REBUT'
                    ]
                );
            }

        )
            ->whereNotNull(
                'prix_unitaire'
            )
            ->where(
                'stock_disponible',
                '>',
                0
            )
            ->orderBy(
                'libelle'
            )
            ->get();

        $agences = Agence::whereHas(
            'users.demandes',

            function ($query) {

                $query->where(
                    'statut',
                    'Livrée - Demande clôturée'
                );
            }

        )
            ->orderBy('nom')
            ->get();
        $agences = Agence::where(
            'archiver',
            0
        )->orderBy('nom')->get();

        return view(
            'etats.index',
            compact('agences', 'articles', 'departements')
        );
    }

    // 🔥 EXCEL
    public function demandesParAgenceExcel(
        Request $request
    ) {
        $request->validate([
            'date_debut' => 'required|date',
            'date_fin'   => 'required|date',
        ]);

        return Excel::download(

            new DemandesParAgenceExport(

                $request->date_debut,

                $request->date_fin,

                $request->agence_id

            ),

            'demandes_par_agence.xlsx'
        );
    }

    // 🔥 PDF
    public function demandesParAgencePdf(
        Request $request
    ) {
        $request->validate([
            'date_debut' => 'required|date',
            'date_fin' => 'required|date',
        ]);

        $query = Demande::with([
            'user.agence',
            'articles.article',
            'articles.article.categorie'
        ])
            ->where('archiver', 0)
            ->where('statut', 'Livrée - Demande clôturée')
            ->whereBetween(
                'date',
                [
                    $request->date_debut,
                    $request->date_fin
                ]
            );

        // filtre agence optionnel
        if (
            !empty($request->agence_id)
        ) {

            $query->whereHas(
                'user',
                function ($q)
                use ($request) {

                    $q->where(
                        'agence_id',
                        $request->agence_id
                    );
                }
            );
        }

        // $demandes = $query->get();

        $demandes = $query
            ->get()
            ->sortBy(function ($demande) {

                return
                    $demande->user->agence->nom
                    ?? '';
            });

        $pdf = Pdf::loadView(
            'etats.demandes.demandes_par_agence',
            [
                'demandes' => $demandes,
                'dateDebut' => $request->date_debut,
                'dateFin' => $request->date_fin,
            ]
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

    public function demandesParDepartementExcel(
        Request $request
    ) {

        $request->validate([

            'date_debut' =>
            'required|date',

            'date_fin' =>
            'required|date'

        ]);

        return Excel::download(

            new DemandesParDepartementExport(

                $request->date_debut,

                $request->date_fin,

                $request->departement_id

            ),

            'demandes_par_departement.xlsx'

        );
    }

    public function demandesParDepartementPdf(
        Request $request
    ) {

        $request->validate([

            'date_debut' => 'required|date',

            'date_fin' => 'required|date'

        ]);

        $query = Demande::with([

            'user.departement',

            'articles.article.categorie'

        ])

            ->where(
                'archiver',
                0
            )

            ->where(
                'statut',
                'Livrée - Demande clôturée'
            )

            ->whereBetween(

                'date',

                [

                    $request->date_debut,

                    $request->date_fin

                ]

            );

        /*
    Filtre département optionnel
    */

        if (
            !empty($request->departement_id)
        ) {

            $query->whereHas(

                'user',

                function ($q)
                use ($request) {

                    $q->where(

                        'departement_id',

                        $request->departement_id

                    );
                }

            );
        }

        $demandes =

            $query

            ->get()

            ->sortBy(

                function ($demande) {

                    return

                        $demande
                        ->user
                        ->departement
                        ->nom

                        ?? '';
                }

            );

        $pdf = Pdf::loadView(

            'etats.demandes.demandes_par_departement',

            [

                'demandes' =>

                $demandes,

                'dateDebut' =>

                $request->date_debut,

                'dateFin' =>

                $request->date_fin

            ]

        );

        return $pdf->stream(

            'demandes_par_departement.pdf'

        );
    }
}
