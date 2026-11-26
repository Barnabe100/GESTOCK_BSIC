<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ArticleDemande;
use App\Models\Article;
use App\Models\Demande;
use App\Exports\ArticleDemandesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;


class ArticleDemandeController extends Controller
{
    /**
     * Ajouter un article à une demande
     *
     * L1-4 : réservé au propriétaire de la demande, tant que le supérieur n'a
     * pas validé ; quantité entière strictement positive ; aucun mouvement de
     * stock. La demande est verrouillée pendant l'opération, ce qui la
     * sérialise avec la validation du supérieur et avec un ajout simultané.
     */
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {

            $demande = $this->demandeVerrouillee($request->demande_id);

            if ($refus = $this->refusSiNonModifiable($demande)) {
                return $refus;
            }

            $request->validate([
                'quantite' => 'required|integer|min:1'
            ]);

            $article = Article::findOrFail($request->article_id);

            $existe = ArticleDemande::where('demande_id', $demande->id)
                ->where('article_id', $request->article_id)
                ->exists();

            if ($existe) {
                return back()
                    ->withInput()
                    ->with('error', 'Cet article est déjà présent dans cette demande.');
            }

            // Vérifier stock (contrôle existant, inchangé : indicatif, sans réservation)
            if ($request->quantite > $article->stock_disponible) {
                return back()->with('error', 'Stock insuffisant');
            }

            // Créer la ligne, sans aucun mouvement de stock
            ArticleDemande::create([
                'demande_id' => $demande->id,
                'article_id' => $request->article_id,
                'quantite' => $request->quantite,
                'archiver' => 0
            ]);

            return back()->with('success', 'Article ajouté et stock mis à jour');
        });
    }

    /**
     * Modifier un article de la demande
     *
     * L1-4 : mêmes conditions que l'ajout ; aucun mouvement de stock.
     */
    public function update(
        Request $request,
        $id
    ) {
        $demandeId = $this->demandeDeLaLigne($id);

        return DB::transaction(function () use ($request, $id, $demandeId) {

            $demande = $this->demandeVerrouillee($demandeId);
            $ligne = $this->ligneVerrouillee($id, $demande);

            if ($refus = $this->refusSiNonModifiable($demande)) {
                return $refus;
            }

            $request->validate([

                'article_id' => 'required|exists:articles,id',

                'quantite' => 'required|integer|min:1'

            ]);

            // Vérifier si l'article existe déjà
            // dans la même demande

            $existe = ArticleDemande::where(

                'demande_id',

                $ligne->demande_id

            )
                ->where(

                    'article_id',

                    $request->article_id

                )
                ->where(

                    'id',

                    '!=',

                    $ligne->id

                )
                ->exists();

            if ($existe) {

                return back()->with(

                    'error',

                    'Cet article existe déjà dans la demande.'

                );
            }

            $article = Article::findOrFail(
                $request->article_id
            );

            // Vérifier stock disponible (contrôle existant, inchangé)

            if (

                $request->quantite >
                $article->stock_disponible

            ) {

                return back()->with(

                    'error',

                    'Stock insuffisant pour cet article.'

                );
            }

            $ligne->update([

                'article_id' =>
                $request->article_id,

                'quantite' =>
                $request->quantite

            ]);

            return back()->with(

                'success',

                'Article modifié avec succès.'

            );
        });
    }

    /**
     * Supprimer un article de la demande
     *
     * L1-4 : mêmes conditions que l'ajout. La création d'une ligne ne retire
     * rien du stock : sa suppression ne lui restitue donc rien (l'ancienne
     * réincrémentation créait du stock fantôme).
     */
    public function destroy($id)
    {
        $demandeId = $this->demandeDeLaLigne($id);

        return DB::transaction(function () use ($id, $demandeId) {

            $demande = $this->demandeVerrouillee($demandeId);
            $ligne = $this->ligneVerrouillee($id, $demande);

            if ($refus = $this->refusSiNonModifiable($demande)) {
                return $refus;
            }

            $ligne->delete();

            return back()->with('success', 'Article supprimé et stock mis à jour');
        });
    }

    /**
     * Relit la demande avec un verrou exclusif (SELECT ... FOR UPDATE),
     * jusqu'à la fin de la transaction en cours. 404 si elle n'existe pas.
     */
    private function demandeVerrouillee($demandeId): Demande
    {
        return Demande::whereKey($demandeId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Demande à laquelle appartient la ligne (404 si la ligne n'existe pas).
     * Lecture faite AVANT la transaction : une lecture simple dans la
     * transaction fixerait sa vue des données avant la pose du verrou.
     */
    private function demandeDeLaLigne($id)
    {
        $demandeId = ArticleDemande::whereKey($id)->value('demande_id');

        if ($demandeId === null) {
            abort(404);
        }

        return $demandeId;
    }

    /**
     * Relit la ligne SOUS le verrou de sa demande, par une lecture
     * verrouillante (dernière version validée) : elle a pu être supprimée
     * entre-temps. 404 sinon.
     */
    private function ligneVerrouillee($id, Demande $demande): ArticleDemande
    {
        return ArticleDemande::whereKey($id)
            ->where('demande_id', $demande->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Règle de l'interface (demandes/show.blade.php), appliquée côté serveur :
     * seul le propriétaire agit sur les lignes, sans exception, et seulement
     * tant que le supérieur n'a pas validé.
     */
    private function refusSiNonModifiable(Demande $demande)
    {
        if ((int) $demande->user_id !== (int) auth()->id()) {
            abort(403);
        }

        if ($demande->validation_superieur) {
            return back()->with(

                'error',

                'Cette demande ne peut plus être modifiée.'

            );
        }

        return null;
    }


    //Export Excel
    public function exportExcel()
    {
        return Excel::download(
            new ArticleDemandesExport,
            'articles-demandes.xlsx'
        );
    }

    //Export CSV
    public function exportCsv()
    {
        return Excel::download(
            new ArticleDemandesExport,
            'articles-demandes.csv'
        );
    }
}
