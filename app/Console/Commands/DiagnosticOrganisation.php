<?php

namespace App\Console\Commands;

use App\Models\Agence;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * N03 : diagnostic EN LECTURE SEULE de la structure organisationnelle.
 *
 * Aucun type n'est déduit ni écrit. Les règles historiques fondées sur des
 * noms (« AGENCE PRINCIPALE SIEGE », « Département Réseau ») ne servent ici
 * qu'à SIGNALER des indices et des divergences, pour préparer la décision
 * humaine de qualification (gestok:organisation:qualifier).
 */
class DiagnosticOrganisation extends Command
{
    protected $signature = 'gestok:organisation:diagnostic {--json= : Écrire aussi le résultat dans ce fichier JSON}';

    protected $description = 'N03 : diagnostic en lecture seule des entités (siège, agences) et des rattachements';

    public const NOM_SIEGE_HISTORIQUE = 'AGENCE PRINCIPALE SIEGE';

    public const DEPARTEMENT_RESEAU_HISTORIQUE = 'Département Réseau';

    public function handle(): int
    {
        $typeExiste = Schema::hasColumn('agences', 'type');
        $users = User::with(['agence', 'departement', 'roles'])->get();
        $entites = [];

        foreach (Agence::withCount(['departements', 'users'])->orderBy('id')->get() as $agence) {
            $membres = $users->where('agence_id', $agence->id);
            // Règle historique 1 (DemandeController::store) : nom de l'agence.
            $indiceNom = mb_strtoupper(trim($agence->nom)) === self::NOM_SIEGE_HISTORIQUE;
            // Règle historique 2 (User::estAuSiege d'avant N03) : département
            // renseigné et différent de « Département Réseau ».
            $membresSiegeRegle2 = $membres->filter(fn (User $u) => $u->departement_id !== null
                && optional($u->departement)->nom !== self::DEPARTEMENT_RESEAU_HISTORIQUE)->count();

            if ($indiceNom && $membresSiegeRegle2 === $membres->count()) {
                $classement = 'candidat_siege_concordant';
            } elseif (! $indiceNom && $membresSiegeRegle2 === 0) {
                $classement = 'agence_concordante';
            } else {
                $classement = 'ambigu';
            }

            $entites[] = [
                'id' => $agence->id,
                'code' => $agence->code,
                'nom' => $agence->nom,
                'archivee' => (bool) $agence->archiver,
                'type_actuel' => $typeExiste ? $agence->type : null,
                'departements' => $agence->departements_count,
                'utilisateurs' => $agence->users_count,
                'indice_nom_siege' => $indiceNom,
                'utilisateurs_siege_selon_regle_departement' => $membresSiegeRegle2,
                'classement' => $classement,
            ];
        }

        $candidats = array_values(array_filter($entites, fn ($e) => ! $e['archivee']
            && ($e['indice_nom_siege'] || $e['utilisateurs_siege_selon_regle_departement'] > 0)));
        $siegesActifs = array_values(array_filter($entites, fn ($e) => ! $e['archivee'] && $e['type_actuel'] === Agence::TYPE_SIEGE));

        $rattachements = [
            'departement_d_une_autre_entite' => $users->filter(fn (User $u) => $u->departement && (int) $u->departement->agence_id !== (int) $u->agence_id)->pluck('id')->values()->all(),
            'sans_entite' => $users->whereNull('agence_id')->pluck('id')->values()->all(),
            'entite_non_qualifiee' => $users->filter(fn (User $u) => $u->agence && ! $u->agence->estQualifiee())->pluck('id')->values()->all(),
            'siege_sans_departement' => $users->filter(fn (User $u) => $u->estAuSiege() && $u->departement_id === null)->pluck('id')->values()->all(),
            'regle_historique_differente' => $users->filter(function (User $u) {
                if (! $u->agence || ! $u->agence->estQualifiee()) {
                    return false;
                }
                $ancienSiege = $u->departement_id !== null && optional($u->departement)->nom !== self::DEPARTEMENT_RESEAU_HISTORIQUE;

                return $ancienSiege !== $u->estAuSiege();
            })->pluck('id')->values()->all(),
        ];

        $superieurs = [
            'role_sans_indicateur' => $users->filter(fn (User $u) => $u->hasRole('Superieur') && ! $u->superieur)->pluck('id')->values()->all(),
            'indicateur_sans_role' => $users->filter(fn (User $u) => $u->superieur && ! $u->hasRole('Superieur'))->pluck('id')->values()->all(),
            // Avant qualification, regroupement provisoire par agence.
            'entites_plus_de_deux_superieurs' => $users->filter(fn (User $u) => $u->superieur && ($u->entiteDeValidation() !== null || $u->agence_id !== null))
                ->groupBy(fn (User $u) => implode(':', $u->entiteDeValidation() ?? ['agence_non_qualifiee', $u->agence_id]))
                ->filter(fn ($groupe) => $groupe->count() > 2)
                ->map(fn ($groupe) => $groupe->pluck('id')->all())->all(),
        ];

        $synthese = [
            'organisation' => count($siegesActifs) === 1 ? 'siege_qualifie' : (count($candidats) === 0 ? 'aucun_siege_identifiable' : (count($candidats) > 1 ? 'plusieurs_sieges_potentiels' : 'un_candidat_a_confirmer')),
            'entites' => count($entites),
            'non_qualifiees' => count(array_filter($entites, fn ($e) => $e['type_actuel'] === null)),
            'candidats_siege' => array_column($candidats, 'id'),
            'sieges_actifs' => array_column($siegesActifs, 'id'),
        ];

        $this->info('N03 — Diagnostic (lecture seule, aucune écriture)');
        $this->table(['id', 'code', 'nom', 'archivée', 'type', 'dép.', 'util.', 'indice nom', 'util. « siège » (règle dép.)', 'classement'],
            array_map(fn ($e) => [$e['id'], $e['code'], $e['nom'], $e['archivee'] ? 'oui' : 'non', $e['type_actuel'] ?? '—', $e['departements'], $e['utilisateurs'], $e['indice_nom_siege'] ? 'oui' : 'non', $e['utilisateurs_siege_selon_regle_departement'], $e['classement']], $entites));
        $this->line('Synthèse : '.json_encode($synthese, JSON_UNESCAPED_UNICODE));
        $this->line('Rattachements à examiner : '.json_encode($rattachements, JSON_UNESCAPED_UNICODE));
        $this->line('Supérieurs à examiner : '.json_encode($superieurs, JSON_UNESCAPED_UNICODE));

        if ($fichier = $this->option('json')) {
            file_put_contents($fichier, json_encode(compact('synthese', 'entites', 'rattachements', 'superieurs'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return self::SUCCESS;
    }
}
