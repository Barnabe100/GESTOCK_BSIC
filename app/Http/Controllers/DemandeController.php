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
use Carbon\Carbon;
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

    /*public function mesDemandes()
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
    }*/


    public function mesDemandes()
    {
        $demandes = Demande::with([
            'user',
            'user.agence',
            'user.departement'
        ])
            ->where('user_id', auth()->id())
            ->where('archiver', 0)
            ->latest()
            ->get();

        return view(
            'demandes.index',
            [
                'demandes' => $demandes,
                'estMesDemandes' => true
            ]
        );
    }




    //Demandes collegues
    public function demandesOrganisation()
    {
        $user = auth()->user();

        if (!$user->hasRole('Demandeur')) {
            abort(403);
        }

        $demandes = Demande::with([
            'user',
            'user.agence',
            'user.departement',
            'articles.article'
        ])
            ->where('archiver', 0)
            ->where('statut', '!=', 'En préparation')
            ->whereHas('user', function ($q) use ($user) {

                if ($user->estAuSiege()) {
                    $q->where('departement_id', $user->departement_id);
                } else {
                    $q->where('agence_id', $user->agence_id);
                }
            })
            ->latest()
            ->get();



        return view('demandes.indexOrganisation', compact('demandes'));
    }

    public function indexGestionnaire()
    {
        $user = Auth::user();
        if ($user->hasRole(['Gestionnaire'])) {

            $enAttente = Demande::with(
                'user',
                'articles.article'
            )
                ->where(
                    'statut',
                    'En attente gestionnaire'
                )
                ->where(
                    'archiver',
                    0
                )
                ->get();

            $traitees = Demande::with(
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

            $rejetees = Demande::with(
                'user',
                'articles.article'
            )
                ->where(
                    'statut',
                    'Rejetée gestionnaire'
                )
                ->where(
                    'archiver',
                    0
                )
                ->get();

            $enAttenteLivraison = Demande::with(
                'user',
                'articles.article'
            )
                ->where(
                    'validation_finale',
                    1
                )
                ->where(
                    'archiver',
                    0
                )
                ->where(
                    'statut',
                    'En attente livraison'
                )
                ->get();

            $enAttenteReception = Demande::with(
                'user',
                'articles.article'
            )
                ->where(
                    'validation_finale',
                    1
                )
                ->where(
                    'archiver',
                    0
                )
                ->where(
                    'statut',
                    'En attente réception demandeur'
                )
                ->get();

            $cloturees = Demande::with(
                'user',
                'articles.article'
            )
                ->where(
                    'validation_finale',
                    1
                )
                ->where(
                    'archiver',
                    0
                )
                ->where(
                    'statut',
                    'Livrée - Demande clôturée'
                )
                ->get();

            return view(
                'demandes.gestionnaire',
                compact(
                    'enAttente',
                    'traitees',
                    'rejetees',
                    'cloturees',
                    'enAttenteLivraison',
                    'enAttenteReception'
                )
            );
        }
    }

    //Demandes vues par le superieur
    public function demandesSuperieur()
    {
        $superieur = auth()->user();

        $baseQuery = Demande::with([
            'user',
            'user.agence',
            'user.departement'
        ])
            ->whereHas('user', function ($q) use ($superieur) {
                if ($superieur->estAuSiege()) {
                    $q->where('departement_id', $superieur->departement_id);
                } else {
                    $q->where('agence_id', $superieur->agence_id);
                }
            });

        $enAttente = (clone $baseQuery)
            ->where(
                'statut',
                'En attente supérieur'
            )->where(
                'archiver',
                0
            )
            ->get();

        $validees = (clone $baseQuery)
            ->where(
                'validation_superieur',
                1
            )->where(
                'archiver',
                0
            )
            ->get();

        $rejetees = (clone $baseQuery)
            ->where(
                'statut',
                'Rejetée'
            )->where(
                'archiver',
                0
            )
            ->get();

        return view(
            'demandes.indexSuperieur',
            compact(
                'enAttente',
                'validees',
                'rejetees'
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
            // $user->departement->nom != 'Département Réseau'
            // &&
            $user->agence->nom != 'AGENCE PRINCIPALE SIEGE'
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
            //'date' => $request->date,
            'date' => Carbon::today(),
            'objet' => $request->objet,
            'description' => $request->description,
            'user_id' => auth()->id(),
            'superieur_id' => $superieur->id,
            //'statut' => 'En attente supérieur',
            'statut' => 'En préparation',
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
            //'date' => $request->date,
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
        $user = auth()->user();

        $articles = Article::whereNotNull('prix_unitaire')
            ->whereNotNull('stock_securite')
            ->where('stock_disponible', '>', 0)
            ->where('archiver', 0)
            ->orderBy('libelle')
            ->get();

        $demande = Demande::with([
            'user',
            'articles.article'
        ])->findOrFail($id);

        abort_unless(
            $user->peutVoirDemande($demande),
            403,
            'Vous n\'êtes pas autorisé à consulter cette demande.'
        );

        return view(
            'demandes.show',
            compact(
                'demande',
                'articles'
            )
        );
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

    //Validation gestionnaire
    public function valider(Request $request, $id)
    {
        $demande = Demande::findOrFail($id);

        /*
        Interdire uniquement si
        validation finale déjà faite
        */

        if ($demande->validation_finale) {

            return back()->with(
                'error',
                'La demande est déjà validée définitivement'
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

            $demande->update([

                'statut' => 'En attente validation finale',
                'commentaire_validation_finale' => null,
                'validation_finale' => false,

            ]);
        }

        /*
        Première validation seulement
        */

        if (
            !$demande->validation_gestionnaire
        ) {

            $demande->update([

                'validation_gestionnaire' => 1,

                'gestionnaire_id' => auth()->id(),

                'date_validation_gestionnaire' => now(),

                'statut' => 'En attente validation finale',

                'commentaire_validation_finale' => null,
                'validation_finale' => false,

            ]);
        }

        return back()->with(

            'success',

            $demande->validation_gestionnaire
                ? 'Quantités accordées modifiées'
                : 'Quantités accordées enregistrées'

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

            'superieur_id' => auth()->id(),

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
    public function confirmerReception(Request $request, $id)
    {
        $request->validate([
            'commentaire' => 'required|string|max:1000',
        ]);

        $user = auth()->user();

        $demande = Demande::with('user')->findOrFail($id);

        abort_unless(
            $user->peutConfirmerReception($demande),
            403,
            'Vous n\'êtes pas autorisé à confirmer cette réception.'
        );

        $demande->update([
            'confirmation_reception' => 1,
            'date_reception'         => now(),
            'reception_par'          => auth()->id(),
            'commentaire_reception' => $request->commentaire,
            'statut' => 'Livrée - Demande clôturée'
        ]);

        return back()->with(
            'success',
            'Réception confirmée avec succès.'
        );
    }

    // VUE Validation finale
    public function validationFinaleIndex()
    {
        $enAttente = Demande::with(
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
            )->whereNotIn(
                'statut',
                [
                    'Rejetée validation finale',
                    'Rejetée gestionnaire'
                ]
            )
            ->get();

        $validees = Demande::with(
            'user',
            'articles.article'
        )
            ->where(
                'validation_finale',
                1
            )
            ->where(
                'archiver',
                0
            )
            ->get();

        $rejetees = Demande::with(
            'user',
            'articles.article'
        )
            ->where(
                'statut',
                'Rejetée validation finale'
            )
            ->where(
                'archiver',
                0
            )
            ->get();

        return view(
            'demandes.validationFinale.index',
            compact(
                'enAttente',
                'validees',
                'rejetees'
            )
        );
    }

    //Rejet validateur finale
    public function rejeterFinale(
        Request $request,
        $id
    ) {
        $demande =
            Demande::findOrFail(
                $id
            );

        $demande->update([

            'validation_finale' => 0,

            'commentaire_validation_finale' =>
            $request->motif_rejet,

            'statut' =>
            'Rejetée validation finale'

        ]);

        return back()->with(

            'success',

            'Demande rejetée.'

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

            'validateur_final_id' => auth()->id(),

            'date_validation_finale' => now(),

            'statut' => 'En attente livraison'

        ]);

        return back()
            ->with(
                'success',
                'Validation finale effectuée'
            );
    }

    //Soumettre une demande
    public function soumettre($id)
    {
        $demande = Demande::with('articles')->findOrFail($id);

        // Vérifier que l'utilisateur est le propriétaire
        if ($demande->user_id != auth()->id()) {
            abort(403);
        }

        // Vérifier que la demande est encore en préparation
        if ($demande->statut != 'En préparation') {
            return redirect()->back()->with(
                'error',
                'Cette demande ne peut plus être soumise.'
            );
        }

        // Vérifier qu'il existe au moins un article
        if ($demande->articles->isEmpty()) {
            return redirect()->back()->with(
                'error',
                'Veuillez ajouter au moins un article avant de soumettre la demande.'
            );
        }

        // Soumettre la demande
        $demande->update([
            'statut' => 'En attente supérieur',
        ]);

        return redirect()->route('mes.demandes')->with(
            'success',
            'La demande a été soumise au supérieur hiérarchique avec succès.'
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
