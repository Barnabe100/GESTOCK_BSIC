<?php

namespace App\Services;

use App\Models\HistoriqueSuperieur;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Désignation du supérieur hiérarchique d'une entité (décision validée).
 *
 * L'entité d'un supérieur suit la règle déjà appliquée par la modification
 * d'un utilisateur et par la liste « Demandes à valider » : au siège, son
 * département ; en agence, son agence (User::estAuSiege).
 *
 * Désigner un nouveau supérieur retire la fonction (indicateur et rôle
 * Superieur) au supérieur précédent de la même entité ; chaque changement est
 * enregistré dans historique_superieurs. Les demandes déjà traitées gardent
 * la trace de leur valideur (superieur_id) : rien n'est réécrit.
 */
class DesignationSuperieur
{
    /** @return array{0: string, 1: int}|null */
    public static function entite(User $user): ?array
    {
        if ($user->estAuSiege()) {
            return $user->departement_id ? ['departement', (int) $user->departement_id] : null;
        }

        return $user->agence_id ? ['agence', (int) $user->agence_id] : null;
    }

    public function designer(User $nouveau, User $par): void
    {
        DB::transaction(function () use ($nouveau, $par) {
            $entite = self::entite($nouveau);
            $anciens = collect();

            if ($entite) {
                [$type, $id] = $entite;
                $colonne = $type === 'agence' ? 'agence_id' : 'departement_id';
                $anciens = User::where($colonne, $id)
                    ->where('superieur', 1)
                    ->where('id', '!=', $nouveau->id)
                    ->lockForUpdate()
                    ->get()
                    ->filter(fn (User $u) => self::entite($u) === $entite);
            }

            $dejaSuperieur = (bool) $nouveau->superieur && $nouveau->hasRole('Superieur');

            foreach ($anciens as $ancien) {
                $ancien->update(['superieur' => false]);
                $ancien->removeRole('Superieur');
                $this->tracer($entite, $ancien->id, $nouveau->id, $par->id);
            }

            if (! $nouveau->superieur) {
                $nouveau->update(['superieur' => true]);
            }

            if (! $nouveau->hasRole('Superieur')) {
                $nouveau->assignRole('Superieur');
            }

            if (! $dejaSuperieur && $anciens->isEmpty() && $entite) {
                $this->tracer($entite, null, $nouveau->id, $par->id);
            }
        });
    }

    public function retirer(User $ancien, User $par): void
    {
        DB::transaction(function () use ($ancien, $par) {
            $etaitSuperieur = (bool) $ancien->superieur || $ancien->hasRole('Superieur');

            if ($ancien->superieur) {
                $ancien->update(['superieur' => false]);
            }

            if ($ancien->hasRole('Superieur')) {
                $ancien->removeRole('Superieur');
            }

            $entite = self::entite($ancien);

            if ($etaitSuperieur && $entite) {
                $this->tracer($entite, $ancien->id, null, $par->id);
            }
        });
    }

    private function tracer(?array $entite, ?int $ancienId, ?int $nouveauId, int $parId): void
    {
        if (! $entite) {
            return;
        }

        HistoriqueSuperieur::create([
            'entite_type' => $entite[0],
            'entite_id' => $entite[1],
            'ancien_superieur_id' => $ancienId,
            'nouveau_superieur_id' => $nouveauId,
            'designe_par' => $parId,
        ]);
    }
}
