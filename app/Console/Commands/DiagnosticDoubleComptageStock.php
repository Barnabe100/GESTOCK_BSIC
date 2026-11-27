<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\MouvementStock;
use Illuminate\Console\Command;

/**
 * P1-04 : diagnostic EN LECTURE SEULE des articles dont le stock initial a
 * pu être gonflé par l'ancienne règle de première réception (stock_initial
 * augmenté en plus du mouvement ENTREE).
 *
 * Rien n'est corrigé : toute régularisation exige une décision explicite.
 */
class DiagnosticDoubleComptageStock extends Command
{
    protected $signature = 'gestok:stock:diagnostic-p104 {--json= : Écrire aussi le résultat dans ce fichier JSON}';

    protected $description = 'P1-04 : articles suspects de double comptage à la première réception (lecture seule)';

    public function handle(): int
    {
        $suspects = [];

        foreach (Article::where('stock_initial', '>', 0)->orderBy('id')->get() as $article) {
            $mouvements = MouvementStock::where('article_id', $article->id)->orderBy('id')->get();
            $premier = $mouvements->first();
            $entrees = (int) $mouvements->where('type', 'ENTREE')->sum('quantite');
            $sorties = (int) $mouvements->whereIn('type', ['SORTIE', 'REBUT'])->sum('quantite');
            $selonEtats = (int) $article->stock_initial + $entrees - $sorties;

            // Signature de l'ancienne règle : le premier mouvement est une
            // réception de commande de la même quantité que le stock initial.
            $signature = $premier
                && $premier->type === 'ENTREE'
                && str_starts_with((string) $premier->motif, 'Réception commande')
                && (int) $premier->quantite === (int) $article->stock_initial;

            if (! $signature) {
                continue;
            }

            $suspects[] = [
                'article_id' => $article->id,
                'code' => $article->code,
                'libelle' => $article->libelle,
                'stock_initial' => (int) $article->stock_initial,
                'premiere_reception' => (int) $premier->quantite,
                'date_premiere_reception' => (string) $premier->date_mouvement,
                'stock_disponible' => (int) $article->stock_disponible,
                'stock_selon_etats' => $selonEtats,
                'ecart_etats_moins_disponible' => $selonEtats - (int) $article->stock_disponible,
                // Écart égal au stock initial : double comptage très probable.
                'double_comptage_probable' => $selonEtats - (int) $article->stock_disponible === (int) $article->stock_initial,
            ];
        }

        $this->info('P1-04 — Diagnostic (lecture seule, aucune écriture)');
        $this->table(array_keys($suspects[0] ?? ['article_id' => 0]), array_map(fn ($l) => array_map(fn ($v) => is_bool($v) ? ($v ? 'oui' : 'non') : $v, $l), $suspects));
        $this->line(count($suspects).' article(s) présentant la signature de l\'ancienne règle.');

        if ($fichier = $this->option('json')) {
            file_put_contents($fichier, json_encode($suspects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return self::SUCCESS;
    }
}
