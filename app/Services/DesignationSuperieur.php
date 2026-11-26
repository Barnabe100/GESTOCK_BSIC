<?php

namespace App\Services;

use App\Autorisation\JournalRoles;
use App\Models\Agence;
use App\Models\Departement;
use App\Models\HistoriqueSuperieur;
use App\Models\User;
use App\Organisation\RefusOrganisation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Désignation des supérieurs hiérarchiques (décisions validées Q3 et
 * remplacement par l'administrateur).
 *
 * - Une entité de validation (au siège : le département ; en agence :
 *   l'agence, selon le type explicite N03) compte au plus DEUX supérieurs
 *   désignés. Ils sont des valideurs alternatifs de l'étape « supérieur ».
 * - Désigner un supérieur alors que deux sont déjà en fonction exige
 *   d'indiquer celui qui est remplacé ; le remplacé perd la fonction
 *   (indicateur et rôle Superieur).
 * - Chaque changement est enregistré dans historique_superieurs, et chaque
 *   changement du rôle Superieur dans historique_roles. Les demandes déjà
 *   traitées gardent leur valideur : rien n'est réécrit.
 * - Concurrence : la ligne de l'entité (département ou agence) est verrouillée
 *   pendant la désignation, ce qui sérialise deux désignations simultanées
 *   dans la même entité.
 */
class DesignationSuperieur
{
    public const MAX_TITULAIRES = 2;

    public const MESSAGE_COMPLET = 'Deux supérieurs sont déjà désignés pour cette entité. Indiquez le supérieur remplacé.';

    public const MESSAGE_RATTACHEMENT = 'Le rattachement de cet utilisateur (entité non qualifiée ou département manquant) ne permet pas de le désigner supérieur.';

    public function __construct(private JournalRoles $journal)
    {
    }

    /** @return array{0: string, 1: int}|null */
    public static function entite(User $user): ?array
    {
        return $user->entiteDeValidation();
    }

    /** Supérieurs désignés et actifs d'une entité de validation. */
    public static function titulaires(array $entite, ?int $saufId = null): Collection
    {
        [$type, $id] = $entite;
        $colonne = $type === 'agence' ? 'agence_id' : 'departement_id';

        return User::with('agence')
            ->where($colonne, $id)
            ->where('superieur', 1)
            ->whereNull('desactive_le')
            ->when($saufId, fn ($q) => $q->where('id', '!=', $saufId))
            ->orderBy('id')
            ->get()
            ->filter(fn (User $u) => $u->entiteDeValidation() === $entite)
            ->values();
    }

    public function designer(User $nouveau, User $par, ?User $remplace = null): void
    {
        DB::transaction(function () use ($nouveau, $par, $remplace) {
            $nouveau = User::whereKey($nouveau->id)->lockForUpdate()->firstOrFail();
            $entite = self::entite($nouveau);

            if ($entite === null) {
                throw new RefusOrganisation(self::MESSAGE_RATTACHEMENT);
            }

            $this->verrouillerEntite($entite);
            $titulaires = self::titulaires($entite, $nouveau->id);
            $dejaTitulaire = (bool) $nouveau->superieur && $nouveau->hasRole('Superieur');
            $dejaTrace = false;

            if ($remplace !== null && (int) $remplace->id !== (int) $nouveau->id) {
                $remplace = $titulaires->firstWhere('id', $remplace->id);

                if ($remplace === null) {
                    throw new RefusOrganisation('Le supérieur remplacé doit être un supérieur en fonction de la même entité.');
                }

                $this->retirerFonction($remplace, $par);
                $this->tracer($entite, $remplace->id, $nouveau->id, $par->id);
                $titulaires = $titulaires->reject(fn (User $u) => $u->id === $remplace->id);
                $dejaTrace = true;
            } elseif ($titulaires->count() >= self::MAX_TITULAIRES) {
                throw new RefusOrganisation(self::MESSAGE_COMPLET);
            }

            if (! $nouveau->superieur) {
                $nouveau->update(['superieur' => true]);
            }

            $this->journal->attribuer($nouveau, 'Superieur', $par, 'designation');

            if (! $dejaTitulaire && ! $dejaTrace) {
                $this->tracer($entite, null, $nouveau->id, $par->id);
            }
        });
    }

    public function retirer(User $ancien, User $par): void
    {
        DB::transaction(function () use ($ancien, $par) {
            $etaitSuperieur = (bool) $ancien->superieur || $ancien->hasRole('Superieur');
            $this->retirerFonction($ancien, $par);
            $entite = self::entite($ancien);

            if ($etaitSuperieur && $entite) {
                $this->tracer($entite, $ancien->id, null, $par->id);
            }
        });
    }

    private function retirerFonction(User $user, User $par): void
    {
        if ($user->superieur) {
            $user->update(['superieur' => false]);
        }

        $this->journal->retirer($user, 'Superieur', $par, 'designation');
    }

    private function verrouillerEntite(array $entite): void
    {
        [$type, $id] = $entite;
        ($type === 'agence' ? Agence::query() : Departement::query())->whereKey($id)->lockForUpdate()->first();
    }

    private function tracer(array $entite, ?int $ancienId, ?int $nouveauId, int $parId): void
    {
        HistoriqueSuperieur::create([
            'entite_type' => $entite[0],
            'entite_id' => $entite[1],
            'ancien_superieur_id' => $ancienId,
            'nouveau_superieur_id' => $nouveauId,
            'designe_par' => $parId,
        ]);
    }
}
