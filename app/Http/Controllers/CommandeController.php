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
use App\Models\ArticleCommande;
use App\Models\ReceptionCommande;

class CommandeController extends Controller
{


    // public function __construct()
    // {
    //     $this->middleware('permission:lister-commande')->only(['index', 'show']);

    //     $this->middleware('permission:creer-commande')->only([
    //         'create',
    //         'store',
    //         'soumettre',
    //     ]);

    //     $this->middleware('permission:modifier-commande')->only([
    //         'edit',
    //         'update',
    //     ]);

    //     $this->middleware('permission:supprimer-commande')->only([
    //         'destroy',
    //     ]);

    //     $this->middleware('permission:valider-commande')->only([
    //         'valider',
    //     ]);

    //     $this->middleware('permission:livrer-commande')->only([
    //         'livrer',
    //     ]);
    // }

    /**
     * Socle des droits : la route du module exige « acceder-commandes » ;
     * la préparation (création, modification) est réservée aux rôles de
     * l'interface (Gestionnaire, SuperAdmin) et l'archivage au SuperAdmin.
     */
    public function __construct()
    {
        $this->middleware('can:preparer-commandes')->only(['create', 'store', 'edit', 'update']);
        $this->middleware('can:supprimer-commandes')->only(['destroy']);
    }

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
            ->latest()
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
            'statut' => 'En préparation',
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
        return DB::transaction(function () use ($id) {
            $commande = Commande::whereKey($id)->lockForUpdate()->firstOrFail();

            if ($commande->validation) {
                return back()->with('error', 'Déjà validée');
            }

            // Validation d'une commande soumise uniquement (statut « Prête pour
            // validation »).
            abort_unless($commande->statut === 'Prête pour validation', 403);

            // Décision 3.6 : la soumission et la validation d'une même commande
            // sont faites par deux personnes différentes.
            if ((int) $commande->soumise_par === (int) auth()->id()) {
                abort(403);
            }

            $commande->update([
                'validation' => 1,
                'validee_par' => auth()->id(),
                'validee_le' => now(),
            ]);

            return back()->with('success', 'Commande validée');
        });
    }

    /**
     * Réception (totale ou partielle) d'une commande validée.
     *
     * Atomique : tout est contrôlé AVANT la moindre écriture (commande
     * validée, date, quantités entières positives ou nulles, au moins une
     * quantité reçue, aucune quantité au-delà du reste à livrer), puis tout est
     * écrit dans une seule transaction, sous verrou de la commande, de ses
     * lignes et des articles. Le jeton du formulaire est enregistré avec une
     * contrainte d'unicité : une réception rejouée n'est jamais comptée ni
     * mouvementée deux fois.
     */
    public function livrer(
        Request $request,
        $id
    ) {
        $request->validate([
            'date_livraison' => 'required|date',
            'quantites' => 'nullable|array',
            'quantites.*' => 'nullable|integer|min:0',
            'jeton_reception' => 'required|string|max:64',
        ], [
            'date_livraison.required' => 'La date de réception est obligatoire.',
            'date_livraison.date' => 'La date de réception est invalide.',
            'quantites.*.integer' => 'Les quantités reçues doivent être des nombres entiers.',
            'quantites.*.min' => 'Les quantités reçues ne peuvent pas être négatives.',
        ]);

        $jeton = (string) $request->input('jeton_reception');

        return DB::transaction(function () use ($request, $id, $jeton) {
            $commande = Commande::whereKey($id)->lockForUpdate()->firstOrFail();

            if (! $commande->validation) {
                return back()->with('error', 'Validez d’abord la commande');
            }

            if (ReceptionCommande::where('jeton', $jeton)->exists()) {
                return back()->with('error', 'Cette réception a déjà été enregistrée.');
            }

            $lignes = ArticleCommande::with('article')
                ->where('commande_id', $commande->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $quantites = (array) $request->input('quantites', []);
            $aRecevoir = [];

            // 1. Contrôles complets, sans aucune écriture.
            foreach ($quantites as $ligneId => $valeur) {
                if (! $lignes->contains('id', (int) $ligneId)) {
                    abort(403);
                }
            }

            foreach ($lignes as $ligne) {
                $quantiteRecue = (int) ($quantites[$ligne->id] ?? 0);

                if ($quantiteRecue <= 0) {
                    continue;
                }

                if ($quantiteRecue > $ligne->quantite_restante) {
                    return back()->with(
                        'error',
                        'Quantité reçue supérieure à la quantité restante pour '.$ligne->article->libelle
                    );
                }

                $aRecevoir[] = [$ligne, $quantiteRecue];
            }

            if ($aRecevoir === []) {
                return back()->with('error', 'Aucune quantité reçue n’a été saisie.');
            }

            $dateReception = $request->date_livraison;

            ReceptionCommande::create([
                'commande_id' => $commande->id,
                'jeton' => $jeton,
                'date_reception' => $dateReception,
                'quantite_totale' => array_sum(array_column($aRecevoir, 1)),
                'user_id' => auth()->id(),
            ]);

            // 2. Écritures (règles de stock existantes inchangées).
            foreach ($aRecevoir as [$ligne, $quantiteRecue]) {
                $article = Article::whereKey($ligne->article_id)->lockForUpdate()->firstOrFail();

                $ligne->update([
                    'quantite_livree' => $ligne->quantite_livree + $quantiteRecue,
                    'quantite_restante' => $ligne->quantite_restante - $quantiteRecue,
                ]);

                // P1-04 : la réception ne modifie plus stock_initial. Les états
                // calculent « stock_initial + entrées − sorties » : augmenter
                // stock_initial ET créer le mouvement ENTREE comptait deux fois
                // la première réception. La quantité reçue est prise en compte
                // une seule fois, par le mouvement ENTREE ci-dessous.

                $article->increment('stock_disponible', $quantiteRecue);

                MouvementStock::create([
                    'article_id' => $article->id,
                    'user_id' => auth()->id(),
                    'type' => 'ENTREE',
                    'quantite' => $quantiteRecue,
                    'prix_unitaire' => $ligne->prix_unitaire,
                    'motif' => 'Réception commande N° '.$commande->numero_bon_commande,
                    'date_mouvement' => $dateReception,
                ]);
            }

            // Commande totalement reçue ?
            if (ArticleCommande::where('commande_id', $commande->id)->sum('quantite_restante') == 0) {
                $commande->update([
                    'livraison' => 1,
                    'date_livraison' => $dateReception,
                ]);
            }

            return back()->with('success', 'Réception enregistrée');
        });
    }

    public function soumettre($id)
    {
        $commande = Commande::with('articles')->findOrFail($id);

        // La commande doit être en préparation
        if ($commande->statut != 'En préparation') {
            return back()->with(
                'error',
                'Cette commande ne peut plus être soumise.'
            );
        }

        // Au moins un article
        if ($commande->articles->isEmpty()) {
            return back()->with(
                'error',
                'Veuillez ajouter au moins un article.'
            );
        }

        // Vérification du montant utilisé
        $montantUtilise = $commande->articles->sum(function ($item) {
            return $item->quantite * $item->prix_unitaire;
        });

        if ($montantUtilise != $commande->montant) {
            return back()->with(
                'error',
                'Le montant de la commande doit être entièrement réparti avant la soumission.'
            );
        }

        $commande->update([
            'statut' => 'Prête pour validation',
            'soumise_par' => auth()->id(),
            'soumise_le' => now(),
        ]);

        return redirect()
            ->route('commandes.index')
            ->with(
                'success',
                'Commande soumise avec succès.'
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
