<?php

namespace App\Console\Commands;

use App\Models\Agence;
use App\Organisation\RefusOrganisation;
use App\Organisation\StructureOrganisationnelle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * N03 : régularisation des entités existantes, sur DÉCISION HUMAINE.
 *
 * Les identifiants sont fournis explicitement : rien n'est déduit du nom.
 * Par défaut, la commande affiche seulement le plan (simulation) ; --appliquer
 * l'exécute en une transaction (tout ou rien), avec historique.
 *
 * Exemple : gestok:organisation:qualifier --siege=3 --agences=1,2,4 --appliquer
 */
class QualifierOrganisation extends Command
{
    protected $signature = 'gestok:organisation:qualifier
        {--siege= : Identifiant de l\'entité qualifiée « Siège » (décision humaine)}
        {--agences= : Identifiants (séparés par des virgules) des entités qualifiées « Agence »}
        {--appliquer : Exécuter le plan ; sans cette option, simulation seulement}';

    protected $description = 'N03 : qualifier explicitement les entités existantes (siège, agences)';

    public function handle(StructureOrganisationnelle $structure): int
    {
        $siege = $this->option('siege') !== null ? (int) $this->option('siege') : null;
        $agences = array_values(array_filter(array_map('intval', explode(',', (string) $this->option('agences')))));

        if ($siege === null && $agences === []) {
            $this->error('Indiquez --siege et/ou --agences : aucune entité n\'est qualifiée automatiquement.');

            return self::INVALID;
        }

        if ($siege !== null && in_array($siege, $agences, true)) {
            $this->error('Une même entité ne peut pas être à la fois le siège et une agence.');

            return self::INVALID;
        }

        $plan = array_merge($siege !== null ? [[$siege, Agence::TYPE_SIEGE]] : [], array_map(fn ($id) => [$id, Agence::TYPE_AGENCE], $agences));
        $inconnues = array_diff(array_column($plan, 0), Agence::whereIn('id', array_column($plan, 0))->pluck('id')->all());

        if ($inconnues !== []) {
            $this->error('Entités inconnues : '.implode(', ', $inconnues));

            return self::INVALID;
        }

        $this->table(['id', 'nom', 'type actuel', 'type décidé'], array_map(function ($ligne) {
            $a = Agence::find($ligne[0]);

            return [$a->id, $a->nom, $a->type ?? '—', $ligne[1]];
        }, $plan));

        if (! $this->option('appliquer')) {
            $this->warn('Simulation : rien n\'a été écrit. Ajoutez --appliquer pour exécuter ce plan.');

            return self::SUCCESS;
        }

        try {
            DB::transaction(function () use ($plan, $structure) {
                // Les agences d'abord : un ancien siège éventuel est requalifié
                // avant la qualification du nouveau.
                usort($plan, fn ($a, $b) => strcmp($a[1], $b[1]));
                foreach ($plan as [$id, $type]) {
                    $structure->qualifier(Agence::findOrFail($id), $type, null);
                }
            });
        } catch (RefusOrganisation $e) {
            $this->error('Plan refusé, rien n\'a été écrit : '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Qualification appliquée, avec historique.');

        return self::SUCCESS;
    }
}
