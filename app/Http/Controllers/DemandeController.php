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
use Illuminate\Http\Exceptions\HttpResponseException;
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
            )
                ->latest()
                ->get();
            return view('demandes.index', compact('demandes'));
        } else {
            // Socle des droits (N-04) : les demandes de l'utilisateur (user_id), et
            // non la demande dont l'identifiant est égal au sien.
            $demandes = Demande::where('archiver', 0)->where('user_id', $user_id)->get();
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
                ->latest()
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
                ->latest()
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
                ->latest()
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
                ->latest()
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
                ->latest()
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
            ->latest()
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
            ->latest()
            ->get();

        $validees = (clone $baseQuery)
            ->where(
                'validation_superieur',
                1
            )->where(
                'archiver',
                0
            )
            ->latest()
            ->get();

        $rejetees = (clone $baseQuery)
            ->where(
                'statut',
                'Rejetée'
            )->where(
                'archiver',
                0
            )
            ->latest()
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
        $demande = Demande::findOrFail($id);

        // Socle des droits : propriétaire ou SuperAdmin (règle de l'interface),
        // tant que le supérieur n'a pas validé.
        $this->authorize('update', $demande);

        return view(
            'demandes.edit',
            compact('demande')
        );
    }

    public function update(Request $request, $id)
    {
        // Socle des droits (P0-04) : propriétaire ou SuperAdmin, avant
        // validation du supérieur.
        $this->authorize('update', Demande::findOrFail($id));


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
        $demande = Demande::findOrFail($id);

        // Socle des droits (P0-04) : archivage réservé au SuperAdmin
        // (règle de l'interface).
        $this->authorize('delete', $demande);

        $demande->update([
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
        return DB::transaction(function () use ($request, $id) {
            $demande = Demande::whereKey($id)->lockForUpdate()->firstOrFail();

            // Socle des droits : gestionnaire (ou SuperAdmin), jamais sur sa
            // propre demande ; seulement en attente du gestionnaire (interface).
            $this->authorize('deciderGestionnaire', $demande);
            abort_unless($demande->statut === 'En attente gestionnaire', 403);

            // Décision validée : une demande rejetée retourne « En préparation »
            // pour correction et nouvelle soumission.
            $demande->update([
                'commentaire_gestionnaire' =>
                $request->commentaire,
                'statut' => 'En préparation',
                'validation_superieur' => 0,
                'validation_gestionnaire' => 0,
                'validation_finale' => 0,
            ]);

            return back()->with(
                'success',
                'Demande rejetée.'
            );
        });
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
        return DB::transaction(function () use ($request, $id) {
            $demande = Demande::whereKey($id)->lockForUpdate()->firstOrFail();

            // Socle des droits : gestionnaire (ou SuperAdmin), jamais sur sa
            // propre demande.
            $this->authorize('deciderGestionnaire', $demande);

            // La demande doit avoir été validée par le supérieur.
            if (! $demande->validation_superieur) {
                return back()->with(
                    'error',
                    'La demande n’a pas encore été validée complètement.'
                );
            }

            // D-4 : plus aucune modification des quantités après la livraison.
            if ($demande->livraison) {
                return back()->with(
                    'error',
                    'La demande est déjà validée définitivement'
                );
            }

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
        });
    }

    //Livraison
    /**
     * L1-4b : livraison atomique, intégrale, unique et sûre en concurrence.
     *
     * Le système exécute exactement les quantités accordées par le gestionnaire
     * (à défaut, la quantité demandée, règle existante) ; il ne les modifie
     * jamais. Tout est contrôlé, sous verrou, avant la première écriture, et
     * toute erreur annule l'ensemble (une seule transaction).
     *
     * Ordre des verrous : demande, puis lignes, puis articles par identifiant
     * croissant (même ordre que la gestion des lignes, L1-4a).
     */
    public function livrer($id)
    {
        return DB::transaction(function () use ($id) {

            // Demande relue sous verrou exclusif : une seconde livraison
            // simultanée attend ici, puis relit l'état réel.
            $demande = Demande::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            // Socle des droits : livraison réservée aux rôles de l'interface
            // (Gestionnaire, SuperAdmin).
            $this->authorize('livrer', $demande);

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

            // Rejeu : une sortie de cette demande existe déjà (indicateur
            // « livraison » remis à zéro, ou livraison partielle de l'ancien
            // code). Motif exact, tel qu'écrit plus bas depuis le 31/05/2026.
            $motif = 'Livraison demande N° ' . $demande->id;

            if (
                MouvementStock::where('type', 'SORTIE')
                    ->where('motif', $motif)
                    ->exists()
            ) {
                return back()->with(
                    'error',
                    'Demande déjà livrée'
                );
            }

            // Lignes relues sous le verrou, dans leur ordre historique
            $lignes = ArticleDemande::where('demande_id', $demande->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($lignes->isEmpty()) {
                return back()->with(
                    'error',
                    'Cette demande ne contient aucun article.'
                );
            }

            // Quantité à livrer : la quantité accordée, à défaut la quantité
            // demandée. Négative : refus de toute la livraison, sans message.
            // Nulle : ligne ignorée, aucune sortie. Si toutes les lignes sont
            // nulles, la demande est livrée sans aucun mouvement de stock.
            $aLivrer = [];

            foreach ($lignes as $ligne) {
                $quantiteLivree = (int) ($ligne->quantite_accordee ?? $ligne->quantite);

                if ($quantiteLivree < 0) {
                    return back();
                }

                if ($quantiteLivree > 0) {
                    $aLivrer[] = [$ligne, $quantiteLivree];
                }
            }

            // Besoin total par article (plusieurs lignes du même article)
            $besoins = [];

            foreach ($aLivrer as [$ligne, $quantiteLivree]) {
                $besoins[$ligne->article_id] =
                    ($besoins[$ligne->article_id] ?? 0) + $quantiteLivree;
            }

            // Articles relus sous verrou, par identifiant croissant
            $articles = Article::whereIn('id', array_keys($besoins))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Contrôle de TOUS les besoins avant la moindre écriture
            foreach ($aLivrer as [$ligne, $quantiteLivree]) {
                $article = $articles->get($ligne->article_id);

                if (
                    $article
                    &&
                    $article->stock_disponible < $besoins[$ligne->article_id]
                ) {
                    return back()->with(
                        'error',
                        'Stock insuffisant pour ' . $article->libelle
                    );
                }
            }

            // Écritures : une sortie par ligne, au format historique
            foreach ($aLivrer as [$ligne, $quantiteLivree]) {
                $article = $articles->get($ligne->article_id);

                if (!$article) {
                    continue;
                }

                // Décrément conditionnel (défense) : jamais de stock négatif
                $decremente = Article::whereKey($article->id)
                    ->where('stock_disponible', '>=', $quantiteLivree)
                    ->decrement('stock_disponible', $quantiteLivree);

                if ($decremente !== 1) {
                    // Annule toute la transaction, puis affiche le message existant
                    throw new HttpResponseException(
                        back()->with(
                            'error',
                            'Stock insuffisant pour ' . $article->libelle
                        )
                    );
                }

                // Historique mouvement
                MouvementStock::create([
                    'article_id' => $article->id,
                    'user_id' => auth()->id(),
                    'type' => 'SORTIE',
                    'quantite' => $quantiteLivree,
                    'motif' => $motif,
                    'date_mouvement' => now()
                ]);
            }


            $demande->update([
                'livraison' => 1,
                'date_livraison' => now(),
                'statut' => 'En attente réception demandeur',
                'commentaire_superieur' => null,
                'commentaire_gestionnaire' => null,
                'commentaire_validation_finale' => null,
            ]);
            return back()->with(
                'success',
                'Demande livrée'
            );
        }, 3);
    }

    //Validation du superieur
    public function validerSuperieur($id)
    {
        return DB::transaction(function () use ($id) {
            $demande = Demande::with('user')->whereKey($id)->lockForUpdate()->firstOrFail();

            // Socle des droits : supérieur hiérarchique du demandeur (rôle
            // Superieur, même entité), jamais sur sa propre demande.
            $this->authorize('deciderSuperieur', $demande);

            if ($demande->validation_superieur) {
                return back()->with(
                    'error',
                    'Demande déjà validée'
                );
            }

            abort_unless($demande->statut === 'En attente supérieur', 403);

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
        });
    }

    //Rejet du superieur
    public function rejeterSuperieur(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $demande = Demande::with('user')->whereKey($id)->lockForUpdate()->firstOrFail();

            $this->authorize('deciderSuperieur', $demande);
            abort_unless($demande->statut === 'En attente supérieur', 403);

            // Décision validée : retour « En préparation » après un rejet.
            $demande->update([
                'validation_superieur' => 0,
                'commentaire_superieur' => $request->commentaire,
                'statut' => 'En préparation'
            ]);

            return back()->with(
                'success',
                'Demande rejetée.'
            );
        });
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
        return DB::transaction(function () use ($request, $id) {
            $demande = Demande::whereKey($id)->lockForUpdate()->firstOrFail();

            // Socle des droits : valideur final, jamais sur sa propre demande ;
            // seulement en attente de validation finale, jamais après livraison.
            $this->authorize('deciderFinale', $demande);
            abort_unless(
                $demande->statut === 'En attente validation finale'
                && ! $demande->validation_finale
                && ! $demande->livraison,
                403
            );

            // Décision validée : retour « En préparation » après un rejet.
            $demande->update([
                'validation_finale' => 0,
                'validation_gestionnaire' => 0,
                'validation_superieur' => 0,
                'commentaire_validation_finale' =>
                $request->motif_rejet,
                'statut' => 'En préparation'
            ]);

            return back()->with(
                'success',
                'Demande rejetée.'
            );
        });
    }

    //Validation finale
    public function validationFinale($id)
    {
        return DB::transaction(function () use ($id) {
            $demande = Demande::whereKey($id)->lockForUpdate()->firstOrFail();

            // Socle des droits : valideur final, jamais sur sa propre demande.
            $this->authorize('deciderFinale', $demande);

            if ($demande->validation_finale || $demande->livraison) {
                return back()
                    ->with(
                        'error',
                        'Demande déjà validée'
                    );
            }

            // La validation du gestionnaire doit précéder la validation finale.
            if (! $demande->validation_gestionnaire) {
                return back()->with(
                    'error',
                    'La demande n’a pas encore été validée complètement.'
                );
            }

            abort_unless($demande->statut === 'En attente validation finale', 403);

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
        });
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
