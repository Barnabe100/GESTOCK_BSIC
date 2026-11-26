<?php

namespace App\Organisation;

use App\Models\Agence;
use App\Models\HistoriqueEntite;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * N03 : seul point d'écriture du type des entités organisationnelles.
 *
 * Règles validées :
 * - chaque entité est de type SIEGE ou AGENCE, indépendamment de son nom ;
 * - un seul siège actif (non archivé) ; plusieurs agences possibles ;
 * - un renommage ne modifie jamais le type ;
 * - les identifiants et les rattachements (départements, utilisateurs,
 *   demandes) ne sont jamais modifiés par un changement de type.
 *
 * L'unicité est garantie par l'index unique agences_un_seul_siege_actif.
 * Les vérifications faites ici, sous verrou et en transaction, servent à
 * produire un message clair ; une écriture concurrente qui passerait entre
 * deux est de toute façon refusée par la base (QueryException 23000),
 * convertie en refus métier.
 */
class StructureOrganisationnelle
{
    public const MESSAGE_SIEGE_EXISTANT = 'Un siège actif existe déjà. Utilisez « Désigner comme siège » pour changer de siège.';

    public function creer(array $donnees, ?User $par): Agence
    {
        $type = $donnees['type'] ?? Agence::TYPE_AGENCE;
        $this->verifierType($type);

        return $this->ecrire(function () use ($donnees, $type, $par) {
            if ($type === Agence::TYPE_SIEGE) {
                $this->refuserSiUnSiegeActifExiste(null);
            }

            $agence = Agence::create([
                'code' => $donnees['code'],
                'nom' => $donnees['nom'],
                'lieu' => $donnees['lieu'] ?? null,
                'type' => $type,
            ]);

            $this->tracer($agence, 'creation', null, $type, $par);

            return $agence;
        });
    }

    /**
     * Modification d'une entité. Le type n'est modifié que s'il est fourni ;
     * le nom peut changer sans effet sur le type.
     */
    public function modifier(Agence $agence, array $donnees, ?User $par): Agence
    {
        return $this->ecrire(function () use ($agence, $donnees, $par) {
            $agence = Agence::whereKey($agence->id)->lockForUpdate()->firstOrFail();
            $ancienType = $agence->type;
            $nouveauType = array_key_exists('type', $donnees) && $donnees['type'] !== null
                ? $donnees['type']
                : $ancienType;

            if ($nouveauType !== $ancienType) {
                $this->verifierType($nouveauType);

                if ($nouveauType === Agence::TYPE_SIEGE && ! $agence->archiver) {
                    $this->refuserSiUnSiegeActifExiste($agence->id);
                }
            }

            $agence->update([
                'code' => $donnees['code'],
                'nom' => $donnees['nom'],
                'lieu' => $donnees['lieu'] ?? null,
                'type' => $nouveauType,
            ]);

            if ($nouveauType !== $ancienType) {
                $this->tracer($agence, $ancienType === null ? 'qualification' : 'changement_type', $ancienType, $nouveauType, $par);
            }

            return $agence;
        });
    }

    /**
     * Changement de siège : l'actuel siège actif devient une agence, la cible
     * devient le siège, dans une seule transaction.
     */
    public function designerSiege(Agence $cible, ?User $par): Agence
    {
        return $this->ecrire(function () use ($cible, $par) {
            $sieges = Agence::where('type', Agence::TYPE_SIEGE)->where('archiver', 0)
                ->orderBy('id')->lockForUpdate()->get();
            $cible = Agence::whereKey($cible->id)->lockForUpdate()->firstOrFail();

            if ($cible->archiver) {
                throw new RefusOrganisation('Une entité archivée ne peut pas devenir le siège.');
            }

            if ($cible->estSiege()) {
                return $cible;
            }

            foreach ($sieges as $ancien) {
                $ancien->update(['type' => Agence::TYPE_AGENCE]);
                $this->tracer($ancien, 'changement_siege', Agence::TYPE_SIEGE, Agence::TYPE_AGENCE, $par);
            }

            $ancienType = $cible->type;
            $cible->update(['type' => Agence::TYPE_SIEGE]);
            $this->tracer($cible, 'changement_siege', $ancienType, Agence::TYPE_SIEGE, $par);

            return $cible;
        });
    }

    /** Désactivation (archivage) : le type est conservé, les rattachements aussi. */
    public function archiver(Agence $agence, ?User $par): Agence
    {
        return $this->ecrire(function () use ($agence, $par) {
            $agence = Agence::whereKey($agence->id)->lockForUpdate()->firstOrFail();

            if (! $agence->archiver) {
                $agence->update(['archiver' => 1]);
                $this->tracer($agence, $agence->estSiege() ? 'desactivation_siege' : 'desactivation', $agence->type, $agence->type, $par);
            }

            return $agence;
        });
    }

    /** Qualification d'une entité existante (régularisation N03, décision humaine). */
    public function qualifier(Agence $agence, string $type, ?User $par): Agence
    {
        $this->verifierType($type);

        return $this->ecrire(function () use ($agence, $type, $par) {
            $agence = Agence::whereKey($agence->id)->lockForUpdate()->firstOrFail();

            if ($agence->type === $type) {
                return $agence;
            }

            if ($type === Agence::TYPE_SIEGE && ! $agence->archiver) {
                $this->refuserSiUnSiegeActifExiste($agence->id);
            }

            $ancien = $agence->type;
            $agence->update(['type' => $type]);
            $this->tracer($agence, 'qualification', $ancien, $type, $par);

            return $agence;
        });
    }

    private function verifierType(?string $type): void
    {
        if (! in_array($type, Agence::TYPES, true)) {
            throw new RefusOrganisation('Le type de l\'entité doit être « Siège » ou « Agence ».');
        }
    }

    private function refuserSiUnSiegeActifExiste(?int $saufId): void
    {
        $existe = Agence::where('type', Agence::TYPE_SIEGE)->where('archiver', 0)
            ->when($saufId, fn ($q) => $q->where('id', '!=', $saufId))
            ->lockForUpdate()->exists();

        if ($existe) {
            throw new RefusOrganisation(self::MESSAGE_SIEGE_EXISTANT);
        }
    }

    private function ecrire(callable $operation)
    {
        try {
            return DB::transaction($operation);
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'agences_un_seul_siege_actif')) {
                throw new RefusOrganisation(self::MESSAGE_SIEGE_EXISTANT, 0, $e);
            }

            throw $e;
        }
    }

    private function tracer(Agence $agence, string $action, ?string $ancien, ?string $nouveau, ?User $par): void
    {
        HistoriqueEntite::create([
            'agence_id' => $agence->id,
            'action' => $action,
            'ancien_type' => $ancien,
            'nouveau_type' => $nouveau,
            'effectue_par' => $par?->id,
        ]);
    }
}
