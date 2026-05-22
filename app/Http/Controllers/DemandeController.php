<?php

namespace App\Http\Controllers;

use App\Models\Demande;
use App\Models\Article;
use Illuminate\Http\Request;
use App\Exports\DemandesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ArticleDemande;
use Illuminate\Support\Facades\Auth;
use HasRoles;

class DemandeController extends Controller
{

    public function index()
    {
        $estMesDemandes = false;
        $user_id = Auth::user()->id;
        $user = Auth::user();
        if ($user->hasRole(['Gestionnaire', 'SuperAdmin'])) {
            $demandes = Demande::where('archiver', 0)->get();
            return view('demandes.index', compact('demandes'));
        } else {
            $demandes = Demande::where('archiver', 0)->where('id', $user_id)->get();
            return view('demandes.index', compact('demandes', 'estMesDemandes'));
        }
    }

    public function mesDemandes()
    {
        $estMesDemandes = true;
        $user_id = auth()->id();
        $user = Auth::user();
        if ($user->hasRole(['Demandeur'])) {
            $demandes = Demande::where('archiver', 0)
                ->where('user_id', $user_id)
                ->get();

            return view('demandes.index', compact('demandes', 'estMesDemandes'));
        }
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

        return redirect()->route('mes.demandes')
            ->with('success', 'Demande créée');
    }

    public function edit($id)
    {
        if (auth()->user()->hasRole('SuperAdmin')) {
            $demande = Demande::findOrFail($id);
        } else {
            $demande = Demande::where(
                'id',
                $id
            )
                ->where(
                    'user_id',
                    auth()->id()
                )
                ->firstOrFail();
        }

        return view(
            'demandes.edit',
            compact('demande')
        );
    }

    public function update(Request $request, $id)
    {
        $demande = Demande::findOrFail($id);

        $demande->update([
            'date' => $request->date,
            'objet' => $request->objet,
            'description' => $request->description,
        ]);

        return redirect()->route('mes.demandes')
            ->with('success', 'Demande mise à jour');
    }

    // public function show($id)
    // {
    //     $demande = Demande::findOrFail($id);
    //     return view('demandes.show', compact('demande'));
    // }

    // public function show($id)
    // {

    //     $demande = Demande::with('articles.article')->findOrFail($id);
    //     $articles = Article::all();

    //     return view('demandes.show', compact('demande', 'articles'));
    // }

    public function show($id)
    {
        $articles = Article::where(
            'archiver',
            0
        )->get();

        // ADMIN : accès à toutes les demandes
        if (auth()->user()->hasRole(['SuperAdmin', 'Gestionnaire'])) {
            $demande = Demande::with([
                'articles.article'
            ])->findOrFail($id);
        } else {
            $demande = Demande::with([
                'articles.article'
            ])
                ->where('id', $id)
                ->where('user_id', auth()->id())->firstOrFail();
        }
        return view('demandes.show', compact('demande', 'articles'));
    }

    public function destroy($id)
    {
        Demande::findOrFail($id)->update([
            'archiver' => 1
        ]);

        return redirect()->route('demandes.index');
    }

    public function destroyArticle($id)
    {
        ArticleDemande::findOrFail($id)->delete();

        return back()->with('success', 'Article supprimé de la demande');
    }

    //Validation
    public function valider($id)
    {
        $demande = Demande::findOrFail($id);

        if ($demande->validation) {
            return back()->with('error', 'Demande déjà validée');
        }

        $demande->update([
            'validation' => 1
        ]);

        return back()->with('success', 'Demande validée');
    }

    //Livraison
    public function livrer($id)
    {
        //$demande = Demande::findOrFail($id);
        $demande = Demande::with(
            'articles.article'
        )->findOrFail($id);

        // pas encore validée
        if (!$demande->validation) {
            return back()->with('error', 'Validez d’abord la demande');
        }

        // déjà livrée
        if ($demande->livraison) {
            return back()->with('error', 'Demande déjà livrée');
        }



        //Decrementation
        foreach ($demande->articles as $ligne) {
            if (!$ligne->article)
                continue;

            $ligne->article->decrement(
                'stock_disponible',
                $ligne->quantite
            );
        }

        $demande->update([
            'livraison' => 1,
            'date_livraison' => now()
        ]);

        return back()->with('success', 'Demande livrée');
    }

    //Export Excel
    public function exportExcel()
    {
        return Excel::download(
            new DemandesExport,
            'demandes.xlsx'
        );
    }

    //Export CSV
    public function exportCsv()
    {
        return Excel::download(
            new DemandesExport,
            'demandes.csv'
        );
    }
}
