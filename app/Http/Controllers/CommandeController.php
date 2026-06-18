<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Commande;
use App\Models\Demande;
use App\Models\Fournisseur;
use Illuminate\Http\Request;
use App\Models\Article;
use App\Exports\CommandesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\MouvementStock;

class CommandeController extends Controller
{


    public function index()
    {

        // $commandes = Commande::with('fournisseur')
        //     ->where('archiver', 0)
        //     ->get();

        $commandes = Commande::with([
            'fournisseur',
            'articles.article'
        ])
            ->where('archiver', 0)
            ->get();

        return view('commandes.index', compact('commandes'));
    }

    public function create()
    {
        $fournisseurs = Fournisseur::where('archiver', 0)->get();

        return view('commandes.create', compact('fournisseurs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'numero_bon_commande' => 'required|unique:commandes',
            'date' => 'required',
            'fournisseur_id' => 'required|exists:fournisseurs,id',
        ]);

        Commande::create([
            'numero_bon_commande' => $request->numero_bon_commande,
            'num_facture_proforma' => $request->num_facture_proforma,
            'date' => $request->date,
            'libelle' => $request->libelle,
            'montant' => $request->montant ?? 0,
            'fournisseur_id' => $request->fournisseur_id,
            'validation' => 0,
            'livraison' => 0,
            'archiver' => 0
        ]);

        return redirect()->route('commandes.index')
            ->with('success', 'Commande créée');
    }

    public function show($id)
    {
        //$commande = Commande::with('fournisseur')->findOrFail($id);
        $commande = Commande::with([
            'fournisseur',
            'articles.article'
        ])->findOrFail($id);

        //$articles = Article::where('archiver', 0)->get();
        $articles = Article::whereNotNull('prix_unitaire')
            ->where('archiver', 0)
            ->orderBy('libelle')
            ->get();
        return view('commandes.show', compact('commande', 'articles'));
    }

    // 🔥 EDIT
    public function edit($id)
    {
        $commande = Commande::findOrFail($id);
        $fournisseurs = Fournisseur::where('archiver', 0)->get();

        return view('commandes.edit', compact('commande', 'fournisseurs'));
    }

    // 🔥 UPDATE
    public function update(Request $request, $id)
    {
        $commande = Commande::findOrFail($id);

        // 🔥 bloquer si déjà validée
        if ($commande->validation) {
            return back()->with('error', 'Commande déjà validée');
        }

        $request->validate([
            'numero_bon_commande' => 'required',
            'num_facture_proforma' => 'required',
            'date' => 'required',
            'libelle' => 'required',
            'montant' => 'required',
            'fournisseur_id' => 'required',
        ]);


        $commande->update([
            'numero_bon_commande' => $request->numero_bon_commande,
            'num_facture_proforma' => $request->num_facture_proforma,
            'date' => $request->date,
            'libelle' => $request->libelle,
            'montant' => $request->montant,
            'fournisseur_id' => $request->fournisseur_id,
        ]);

        return redirect()->route('commandes.index', $id)
            ->with('success', 'Commande modifiée');
    }

    // 🔥 SUPPRESSION LOGIQUE
    public function destroy($id)
    {
        $commande = Commande::findOrFail($id);

        if ($commande->validation) {
            return back()->with('error', 'Impossible de supprimer une commande validée');
        }

        $commande->update([
            'archiver' => 1
        ]);

        return redirect()->route('commandes.index')
            ->with('success', 'Commande supprimée');
    }

    // 🔥 VALIDATION
    public function valider($id)
    {
        $commande = Commande::findOrFail($id);

        if ($commande->validation) {
            return back()->with('error', 'Déjà validée');
        }

        $commande->update([
            'validation' => 1
        ]);

        return back()->with('success', 'Commande validée');
    }

    // 🔥 LIVRAISON
    public function livrer(
        Request $request,
        $id
    ) {

        $commande = Commande::with(
            'articles.article'
        )->findOrFail($id);

        if (
            !$commande->validation
        ) {

            return back()->with(
                'error',
                'Validez d’abord la commande'
            );
        }

        $dateReception =
            $request->date_livraison;

        foreach (
            $commande->articles
            as
            $ligne
        ) {

            $quantiteRecue =

                intval(

                    $request
                        ->quantites[$ligne->id]
                        ?? 0

                );

            /*
        Rien reçu
        */

            if (
                $quantiteRecue <= 0
            ) {

                continue;
            }

            /*
        Sécurité
        */

            if (
                $quantiteRecue
                >
                $ligne->quantite_restante
            ) {

                return back()->with(

                    'error',

                    'Quantité reçue supérieure à la quantité restante pour '
                        .
                        $ligne->article->libelle

                );
            }

            /*
        Mise à jour ligne commande
        */

            $ligne->update([

                'quantite_livree' =>

                $ligne->quantite_livree
                    +
                    $quantiteRecue,

                'quantite_restante' =>

                $ligne->quantite_restante
                    -
                    $quantiteRecue

            ]);

            /*
        Première entrée stock
        */

            if (
                $ligne->article->stock_initial
                == 0
            ) {

                $ligne->article->increment(

                    'stock_initial',

                    $quantiteRecue

                );
            }

            /*
        Stock disponible
        */

            $ligne->article->increment(

                'stock_disponible',

                $quantiteRecue

            );

            /*
        Historique mouvement
        */

            MouvementStock::create([

                'article_id' => $ligne->article->id,
                'user_id' => auth()->id(),
                'type' => 'ENTREE',
                'quantite' => $quantiteRecue,
                'prix_unitaire' => $ligne->prix_unitaire,
                'motif' => 'Réception commande N° ' . $commande->numero_bon_commande,
                'date_mouvement' => $dateReception

            ]);
        }

        /*
    Recharger la commande
    */

        $commande->refresh();

        /*
    Commande totalement reçue ?
    */

        if (
            $commande
            ->articles
            ->sum(
                'quantite_restante'
            )
            ==
            0
        ) {

            $commande->update([

                'livraison' => 1,

                'date_livraison' =>

                $dateReception

            ]);
        }

        return back()->with(

            'success',

            'Réception enregistrée'

        );
    }


    //Export Excel
    public function exportExcel()
    {
        return Excel::download(
            new CommandesExport,
            'commandes.xlsx'
        );
    }

    //Export CSV
    public function exportCsv()
    {
        return Excel::download(
            new CommandesExport,
            'commandes.csv'
        );
    }
}
