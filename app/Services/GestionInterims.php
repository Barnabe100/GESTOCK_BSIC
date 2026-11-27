<?php

namespace App\Services;

use App\Models\Agence;
use App\Models\Departement;
use App\Models\InterimValidation;
use App\Models\User;
use App\Organisation\RefusOrganisation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Q3 (§4.3) : intérims de validation supérieure.
 *
 * - Réservé aux entités qui n'ont pas deux validateurs supérieurs titulaires.
 * - Le remplaçant est un supérieur désigné et actif d'une AUTRE entité.
 * - Périmètre limité à UNE entité, pour une période bornée (date de fin
 *   obligatoire) ; un seul intérim en vigueur par entité.
 * - Révocable ; rien n'est jamais supprimé (historique).
 * - Le remplaçant ne peut jamais valider ni rejeter sa propre demande
 *   (règle générale de DemandePolicy).
 */
class GestionInterims
{
    public function designer(array $entite, User $remplacant, string $debut, string $fin, ?string $motif, User $par): InterimValidation
    {
        return DB::transaction(function () use ($entite, $remplacant, $debut, $fin, $motif, $par) {
            $this->verrouillerEntite($entite);

            if (DesignationSuperieur::titulaires($entite)->count() >= DesignationSuperieur::MAX_TITULAIRES) {
                throw new RefusOrganisation('Cette entité dispose déjà de deux validateurs supérieurs : un intérim n\'est pas nécessaire.');
            }

            $remplacant = User::whereKey($remplacant->id)->firstOrFail();

            if (! $remplacant->superieur || ! $remplacant->hasRole('Superieur') || $remplacant->estDesactive() || $remplacant->bloque) {
                throw new RefusOrganisation('Le remplaçant doit être un validateur supérieur désigné et actif.');
            }

            if ($remplacant->entiteDeValidation() === $entite) {
                throw new RefusOrganisation('Le remplaçant appartient à cette entité : désignez-le plutôt comme deuxième validateur.');
            }

            if (InterimValidation::pourEntite($entite)->whereNull('revoque_le')->whereDate('fin_le', '>=', now()->toDateString())->exists()) {
                throw new RefusOrganisation('Un intérim est déjà prévu ou en vigueur pour cette entité : révoquez-le d\'abord.');
            }

            return InterimValidation::create([
                'entite_type' => $entite[0],
                'entite_id' => $entite[1],
                'user_id' => $remplacant->id,
                'debut_le' => $debut,
                'fin_le' => $fin,
                'motif' => $motif,
                'designe_par' => $par->id,
            ]);
        });
    }

    public function revoquer(InterimValidation $interim, User $par): void
    {
        DB::transaction(function () use ($interim, $par) {
            $interim = InterimValidation::whereKey($interim->id)->lockForUpdate()->firstOrFail();

            if ($interim->revoque_le === null) {
                $interim->update(['revoque_le' => now(), 'revoque_par' => $par->id]);
            }
        });
    }

    /** Remplaçant en vigueur pour une entité, s'il est toujours habilité. */
    public static function remplacantEnVigueur(array $entite): ?User
    {
        $interim = InterimValidation::with('remplacant')->pourEntite($entite)->enVigueur()->latest('id')->first();
        $u = $interim?->remplacant;

        return $u && $u->superieur && $u->hasRole('Superieur') && ! $u->estDesactive() && ! $u->bloque ? $u : null;
    }

    /** Entités dont $user assure l'intérim aujourd'hui. */
    public static function entitesEnInterim(User $user): array
    {
        if (! $user->superieur || ! $user->hasRole('Superieur') || $user->estDesactive()) {
            return [];
        }

        return InterimValidation::where('user_id', $user->id)->enVigueur()->get()
            ->map(fn (InterimValidation $i) => $i->entite())->unique()->values()->all();
    }

    /** Entités de validation qui comptent exactement un titulaire actif (à signaler). */
    public static function entitesAvecUnSeulValidateur(): Collection
    {
        return User::with('agence')->where('superieur', 1)->whereNull('desactive_le')->get()
            ->filter(fn (User $u) => $u->entiteDeValidation() !== null)
            ->groupBy(fn (User $u) => implode(':', $u->entiteDeValidation()))
            ->filter(fn ($groupe) => $groupe->count() === 1)
            ->map(fn ($groupe, $cle) => [
                'entite' => [explode(':', $cle)[0], (int) explode(':', $cle)[1]],
                'libelle' => InterimValidation::libelle([explode(':', $cle)[0], (int) explode(':', $cle)[1]]),
                'titulaire' => $groupe->first(),
                'interim' => self::remplacantEnVigueur([explode(':', $cle)[0], (int) explode(':', $cle)[1]]),
            ])->values();
    }

    /** Entités de validation qualifiées (pour le choix du périmètre). */
    public static function entitesDisponibles(): array
    {
        $entites = [];

        foreach (Departement::whereHas('agence', fn ($q) => $q->where('type', Agence::TYPE_SIEGE))->orderBy('nom')->get() as $d) {
            $entites['departement:'.$d->id] = $d->nom.' (siège)';
        }

        foreach (Agence::where('type', Agence::TYPE_AGENCE)->where('archiver', 0)->orderBy('nom')->get() as $a) {
            $entites['agence:'.$a->id] = $a->nom;
        }

        return $entites;
    }

    private function verrouillerEntite(array $entite): void
    {
        [$type, $id] = $entite;
        $ligne = ($type === 'agence' ? Agence::query() : Departement::query())->whereKey($id)->lockForUpdate()->first();

        if (! $ligne) {
            throw new RefusOrganisation('Entité inconnue.');
        }
    }
}
