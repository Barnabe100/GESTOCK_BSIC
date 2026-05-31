<?php

namespace App\Http\Controllers;

use App\Models\Demande;
use App\Models\Article;
use Illuminate\Http\Request;
use App\Exports\DemandesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ArticleDemande;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\MouvementStock;
use HasRoles;

class DemandeController extends Controller
{

    public function index()
    {
        $estMesDemandes = false;
        $user_id = Auth::user()->id;
        $user = Auth::user();
        if ($user->hasRole(['Gestionnaire', 'SuperAdmin'])) {
            $demandes = Demande::where('archiver', 0)->where(
                'validation_superieur',
                1
            )->get();
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

    //Demandes vues par le superieur
    public function demandesSuperieur()
    {
        $demandes = Demande::with([
            'user',
            'user.departement'
        ])
            ->where(
                'superieur_id',
                auth()->id()
            )
            ->where(
                'statut',
                'En attente supérieur'
            )
            ->get();

        return view(
            'demandes.indexSuperieur',
            compact(
                'demandes'
            )
        );
    }

    public function create()
    {
        $articles = Article::where('archiver', 0)->get();
        return view('demandes.create', compact('articles'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $superieur = null;

        if (
            $user->departement->nom != 'Département Réseau'
            &&
            $user->agence->nom != 'Agence du siège'
        ) {
            // supérieur agence

            $superieur = User::where(
                'agence_id',
                $user->agence_id
            )
                ->where(
                    'superieur',
                    1
                )
                ->first();
        } else {
            // supérieur département

            $superieur = User::where(
                'departement_id',
                $user->departement_id
            )
                ->where(
                    'superieur',
                    1
                )
                ->first();
        }


        if (!$superieur) {
            return back()->with(
                'error',
                'Aucun supérieur défini'
            );
        }

        //dd($superieur->id);

        $demande = Demande::create([
            'date' => $request->date,
            'objet' => $request->objet,
            'description' => $request->description,
            'user_id' => auth()->id(),
            'superieur_id' => $superieur->id,
            'statut' => 'En attente supérieur',
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

        $superieur = User::where(
            'departement_id',
            auth()->user()->departement_id
        )
            ->where(
                'superieur',
                1
            )
            ->first();

        if (!$superieur) {
            return back()->with(
                'error',
                'Aucun supérieur défini.'
            );
        }
        $demande = Demande::findOrFail($id);

        $demande->update([
            'date' => $request->date,
            'objet' => $request->objet,
            'description' => $request->description,
            'superieur_id' => $superieur->id,
            //'statut' => 'En attente supérieur',
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
        if (auth()->user()->hasRole(['SuperAdmin', 'Gestionnaire', 'Superieur', 'ValidateurDemande'])) {
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

    //Rejet gestionnaire
    public function rejeterGestionnaire(
        Request $request,
        $id
    ) {
        $demande =
            Demande::findOrFail(
                $id
            );

        $demande->update([

            'validation' => 0,

            'commentaire_gestionnaire' =>
            $request->commentaire,

            'statut' =>
            'Rejetée gestionnaire'



        ]);

        return back()->with(
            'success',
            'Demande rejetée.'
        );
    }


    //Validation
    // public function valider($id)
    // {
    //     $demande = Demande::findOrFail($id);

    //     if ($demande->validation) {
    //         return back()->with('error', 'Demande déjà validée');
    //     }

    //     $demande->update([
    //         'validation_gestionnaire' => 1
    //     ]);

    //     return back()->with('success', 'Demande validée');
    // }

    public function valider(Request $request, $id)
    {
        $demande = Demande::findOrFail($id);

        if ($demande->validation_gestionnaire) {
            return back()->with(
                'error',
                'Demande déjà validée'
            );
        }

        foreach (
            $request->quantite_accordee
            as $articleId => $quantite
        ) {

            DB::table('article_demande')

                ->where(
                    'demande_id',
                    $demande->id
                )

                ->where(
                    'article_id',
                    $articleId
                )

                ->update([

                    'quantite_accordee' => $quantite

                ]);
        }

        $demande->update([

            'validation_gestionnaire' => 1,
            'statut' => 'En attente validation finale'

        ]);

        return back()->with(
            'success',
            'Quantités accordées enregistrées'
        );
    }
    //Livraison
    public function livrer($id)
    {
        $demande = Demande::with(
            'articles.article'
        )->findOrFail($id);

        // validation complète obligatoire

        if (
            !$demande->validation_gestionnaire
            ||
            !$demande->validation_finale
        ) {
            return back()->with(
                'error',
                'La demande n’a pas encore été validée complètement.'
            );
        }
        // déjà livrée

        if ($demande->livraison) {
            return back()->with(
                'error',
                'Demande déjà livrée'
            );
        }


        foreach (
            $demande->articles as $ligne
        ) {
            if (
                !$ligne->article
            ) {
                continue;
            }
            $quantiteLivree = $ligne->quantite_accordee ?? $ligne->quantite;
            if (
                $ligne->article->stock_disponible >= $quantiteLivree
            ) {
                // Décrémentation stock
                $ligne->article->decrement('stock_disponible', $quantiteLivree);

                // Historique mouvement
                MouvementStock::create([
                    'article_id' => $ligne->article->id,
                    'user_id' => auth()->id(),
                    'type' => 'SORTIE',
                    'quantite' => $quantiteLivree,
                    'motif' => 'Livraison demande N° ' . $demande->id,
                    'date_mouvement' => now()
                ]);
            } else {
                return back()->with(
                    'error',
                    'Stock insuffisant pour ' . $ligne->article->libelle
                );
            }
        }


        $demande->update([
            'livraison' => 1,
            'date_livraison' => now(),
            'statut' => 'En attente réception demandeur'
        ]);
        return back()->with(
            'success',
            'Demande livrée'
        );
    }

    //Validation du superieur
    public function validerSuperieur($id)
    {
        $demande = Demande::findOrFail($id);
        $demande->update([
            'validation_superieur' => 1,
            'date_validation_superieur' => now(),
            'statut' => 'En attente gestionnaire'
        ]);

        return back()->with(
            'success',
            'Demande validée.'
        );
    }

    //Rejet du superieur
    public function rejeterSuperieur(Request $request, $id)
    {
        $demande = Demande::findOrFail($id);

        $demande->update([

            'validation_superieur' => 0,
            'commentaire_superieur' => $request->commentaire,
            'statut' => 'Rejetée'

        ]);

        return back()->with(
            'success',
            'Demande rejetée.'
        );
    }

    //Confirmation demandeur
    public function confirmerReception(
        Request $request,
        $id
    ) {
        $demande =
            Demande::findOrFail(
                $id
            );

        $demande->update([

            'confirmation_reception' => 1,
            'commentaire_reception' => $request->commentaire,
            'statut' => 'Livrée - Demande clôturée'

        ]);

        return back()->with(
            'success',
            'Réception confirmée'
        );
    }

    // VUE Validation finale
    public function validationFinaleIndex()
    {
        $demandes = Demande::with(
            'user',
            'articles.article'
        )

            ->where(
                'validation_gestionnaire',
                1
            )

            ->where(
                'validation_finale',
                0
            )

            ->where(
                'archiver',
                0
            )

            ->get();

        return view(
            'demandes.validationFinale.index',
            compact(
                'demandes'
            )
        );
    }

    //Validation finale
    public function validationFinale($id)
    {
        $demande = Demande::findOrFail($id);

        if ($demande->validation_finale) {
            return back()
                ->with(
                    'error',
                    'Demande déjà validée'
                );
        }

        $demande->update([

            'validation_finale' => 1,

            'statut' =>
            'En attente livraison'

        ]);

        return back()
            ->with(
                'success',
                'Validation finale effectuée'
            );
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
