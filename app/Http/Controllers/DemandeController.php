<?php

namespace App\Http\Controllers;

use App\Models\Demande;
use App\Models\Article;
use Illuminate\Http\Request;

class DemandeController extends Controller
{
    public function index()
    {
        $demandes = Demande::where('archiver', 0)->get();
        return view('demandes.index', compact('demandes'));
    }

    public function create()
    {
        $articles = Article::where('archiver', 0)->get();
        return view('demandes.create', compact('articles'));
    }

    public function store(Request $request)
    {
        $demande = Demande::create([
            'date' => $request->date,
            'objet' => $request->objet,
            'description' => $request->description,
            'user_id' => auth()->id(),
        ]);

        // pivot article_demande à gérer après

        return redirect()->route('demandes.index')
            ->with('success', 'Demande créée');
    }

    public function show($id)
    {
        $demande = Demande::findOrFail($id);
        return view('demandes.show', compact('demande'));
    }

    public function destroy($id)
    {
        Demande::findOrFail($id)->update([
            'archiver' => 1
        ]);

        return redirect()->route('demandes.index');
    }
}