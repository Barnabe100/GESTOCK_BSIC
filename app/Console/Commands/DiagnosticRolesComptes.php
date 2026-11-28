<?php

namespace App\Console\Commands;

use App\Comptes\ClassementComptes;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * F3 et F4 : diagnostic EN LECTURE SEULE des comptes sans aucun rôle et des
 * comptes sans rôle Demandeur. Aucune écriture.
 */
class DiagnosticRolesComptes extends Command
{
    protected $signature = 'gestok:comptes:diagnostic-roles {--json= : Écrire aussi le résultat dans ce fichier JSON}';

    protected $description = 'Diagnostic (lecture seule) des comptes sans rôle et des comptes sans rôle Demandeur';

    public function handle(): int
    {
        $sansRole = [];
        $sansDemandeur = ['eligibles' => [], 'exclus' => []];

        foreach (User::with(['roles', 'agence'])->orderBy('id')->get() as $u) {
            $roles = $u->getRoleNames()->all();
            $fiche = [
                'id' => $u->id,
                'statut' => ClassementComptes::statut($u),
                'particularites' => ClassementComptes::particularites($u),
                'roles' => $roles,
                'agence_id' => $u->agence_id,
                'departement_id' => $u->departement_id,
                'cree_le' => optional($u->created_at)->toDateString(),
            ];

            if ($roles === []) {
                $sansRole[] = $fiche + ['acces_metier' => ClassementComptes::accesMetier($u)];

                continue;
            }

            if (in_array('Demandeur', $roles, true)) {
                continue;
            }

            $motif = match (true) {
                $fiche['statut'] !== 'actif' => 'compte '.$fiche['statut'],
                $fiche['particularites'] !== [] => 'compte particulier : '.implode(', ', $fiche['particularites']),
                default => null,
            };

            if ($motif === null) {
                $sansDemandeur['eligibles'][] = $fiche;
            } else {
                $sansDemandeur['exclus'][] = $fiche + ['motif_exclusion' => $motif];
            }
        }

        $synthese = [
            'comptes' => User::count(),
            'sans_role' => [
                'total' => count($sansRole),
                'actifs' => count(array_filter($sansRole, fn ($f) => $f['statut'] === 'actif')),
                'bloques' => count(array_filter($sansRole, fn ($f) => $f['statut'] === 'bloque')),
                'desactives' => count(array_filter($sansRole, fn ($f) => $f['statut'] === 'desactive')),
                'particuliers' => count(array_filter($sansRole, fn ($f) => $f['particularites'] !== [])),
            ],
            'sans_demandeur_avec_autres_roles' => [
                'eligibles' => count($sansDemandeur['eligibles']),
                'exclus' => count($sansDemandeur['exclus']),
                'identifiants_eligibles' => array_column($sansDemandeur['eligibles'], 'id'),
            ],
        ];

        $this->info('Diagnostic des rôles (lecture seule, aucune écriture)');
        $this->line('Synthèse : '.json_encode($synthese, JSON_UNESCAPED_UNICODE));
        $this->table(['id', 'statut', 'particularités', 'tableau de bord', 'création demande', 'demandes de l\'entité', 'capacités'],
            array_map(fn ($f) => [$f['id'], $f['statut'], implode(', ', $f['particularites']) ?: '—',
                $f['acces_metier']['tableau_de_bord'] ? 'oui' : 'non', $f['acces_metier']['creation_demande'] ? 'oui' : 'non',
                $f['acces_metier']['demandes_de_son_entite'] ? 'oui' : 'non', implode(', ', $f['acces_metier']['capacites']) ?: 'aucune'], $sansRole));
        $this->table(['id', 'rôles', 'statut', 'particularités', 'décision'],
            array_merge(
                array_map(fn ($f) => [$f['id'], implode(', ', $f['roles']), $f['statut'], '—', 'éligible'], $sansDemandeur['eligibles']),
                array_map(fn ($f) => [$f['id'], implode(', ', $f['roles']), $f['statut'], implode(', ', $f['particularites']) ?: '—', 'exclu : '.$f['motif_exclusion']], $sansDemandeur['exclus'])
            ));

        if ($fichier = $this->option('json')) {
            file_put_contents($fichier, json_encode(['synthese' => $synthese, 'sans_role' => $sansRole, 'sans_demandeur' => $sansDemandeur],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return self::SUCCESS;
    }
}
