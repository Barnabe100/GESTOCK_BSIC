<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\Fournisseur;
use App\Models\Article;
use Illuminate\Http\Request;

class CommandeController extends Controller
{
    public function index()
    {
        $commandes = Commande::with('fournisseur')->where('archiver', 0)->get();
        return view('commandes.index', compact('commandes'));
    }

    public function create()
    {
        $fournisseurs = Fournisseur::where('archiver', 0)->get();
        $articles = Article::where('archiver', 0)->get();

        return view('commandes.create', compact('fournisseurs', 'articles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'numero_bon_commande' => 'required|unique:commandes',
            'date' => 'required',
            'fournisseur_id' => 'required|exists:fournisseurs,id',
        ]);

        $commande = Commande::create([
            'numero_bon_commande' => $request->numero_bon_commande,
            'num_facture_proforma' => $request->num_facture_proforma,
            'date' => $request->date,
            'libelle' => $request->libelle,
            'montant' => $request->montant ?? 0,
            'fournisseur_id' => $request->fournisseur_id,
        ]);

        // 👉 ici tu ajouteras les articles (pivot)
        // article_commande

        return redirect()->route('commandes.index')
            ->with('success', 'Commande créée');
    }

    public function show($id)
    {
        $commande = Commande::with('fournisseur')->findOrFail($id);
        return view('commandes.show', compact('commande'));
    }

    public function destroy($id)
    {
        Commande::findOrFail($id)->update([
            'archiver' => 1
        ]);

        return redirect()->route('commandes.index');
    }
}