<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\Fournisseur;
use Illuminate\Http\Request;
use App\Models\Article;

class CommandeController extends Controller
{
    public function index()
    {
        $commandes = Commande::with('fournisseur')
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
        $commande = Commande::with('fournisseur')->findOrFail($id);
        $articles = Article::where('archiver', 0)->get();
        return view('commandes.show', compact('commande','articles'));
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
    public function livrer($id)
    {
        $commande = Commande::findOrFail($id);

        if (!$commande->validation) {
            return back()->with('error', 'Validez d’abord la commande');
        }

        if ($commande->livraison) {
            return back()->with('error', 'Déjà livrée');
        }

        foreach ($commande->articles as $ligne) 
        {
            if (!$ligne->article) continue;
            $ligne->article->increment('stock_disponible', $ligne->quantite);
        }  
        $commande->update([
            'livraison' => 1,
            'date_livraison' => now()
        ]);

        return back()->with('success', 'Commande livrée');
    }
}