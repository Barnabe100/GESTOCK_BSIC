<?php

namespace App\Console\Commands;

use App\Autorisation\JournalRoles;
use App\Comptes\ClassementComptes;
use App\Models\HistoriqueRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * F4 : régularisation E11 (rôle Demandeur par défaut) des comptes EXISTANTS.
 *
 * Éligibles : comptes actifs (ni bloqués ni désactivés), qui ont au moins un
 * rôle, pas le rôle Demandeur, et aucune particularité (installation,
 * déclaré technique, sans rattachement). Les comptes sans aucun rôle sont
 * exclus (examen séparé, F3).
 *
 * - Simulation par défaut ; --appliquer écrit, dans une transaction.
 * - N'AJOUTE que le rôle Demandeur ; ne retire ni ne remplace aucun rôle.
 * - Idempotente : un compte qui a déjà Demandeur n'est pas touché.
 * - Traçable : historique_roles, origine « regularisation_e11 ».
 * - --annuler : retire Demandeur aux seuls comptes où cette opération l'a
 *   ajouté, et seulement si aucune affectation ou aucun retrait ultérieur de
 *   ce rôle n'a été fait depuis (une action d'administrateur n'est jamais
 *   défaite).
 */
class AttribuerDemandeurParDefaut extends Command
{
    public const ORIGINE = 'regularisation_e11';

    public const ORIGINE_ANNULATION = 'annulation_regul_e11';

    protected $signature = 'gestok:comptes:attribuer-demandeur
        {--appliquer : Exécuter ; sans cette option, simulation seulement}
        {--annuler : Annuler les attributions faites par cette opération}
        {--par= : Identifiant de l\'administrateur qui exécute (traçabilité)}';

    protected $description = 'Régularisation E11 : attribuer le rôle Demandeur aux comptes actifs éligibles (simulation par défaut)';

    public function handle(JournalRoles $journal): int
    {
        $par = $this->option('par') ? User::find((int) $this->option('par')) : null;

        if ($this->option('par') && ! $par) {
            $this->error('Administrateur inconnu (--par).');

            return self::INVALID;
        }

        return $this->option('annuler') ? $this->annuler($journal, $par) : $this->attribuer($journal, $par);
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    public static function eligibles()
    {
        return User::with('roles')->orderBy('id')->get()->filter(fn (User $u) => ClassementComptes::statut($u) === 'actif'
            && $u->roles->isNotEmpty()
            && ! $u->hasRole('Demandeur')
            && ClassementComptes::particularites($u) === [])->values();
    }

    private function attribuer(JournalRoles $journal, ?User $par): int
    {
        $eligibles = self::eligibles();
        $this->table(['id', 'rôles actuels', 'après'], $eligibles->map(fn (User $u) => [$u->id, $u->getRoleNames()->implode(', '),
            $u->getRoleNames()->push('Demandeur')->implode(', ')])->all());
        $this->line($eligibles->count().' compte(s) éligible(s).');

        if (! $this->option('appliquer')) {
            $this->warn('Simulation : rien n\'a été écrit. Ajoutez --appliquer pour exécuter.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($eligibles, $journal, $par) {
            foreach ($eligibles as $u) {
                $u = User::whereKey($u->id)->lockForUpdate()->firstOrFail();
                $journal->attribuer($u, 'Demandeur', $par, self::ORIGINE);
            }
        });

        $this->info('Rôle Demandeur ajouté à '.$eligibles->count().' compte(s), avec historique.');

        return self::SUCCESS;
    }

    private function annuler(JournalRoles $journal, ?User $par): int
    {
        $aRetirer = [];
        $preserves = [];

        foreach (HistoriqueRole::where('origine', self::ORIGINE)->where('role', 'Demandeur')->where('action', 'attribue')->orderBy('id')->get() as $ligne) {
            $u = $ligne->user_id ? User::find($ligne->user_id) : null;
            $posterieure = HistoriqueRole::where('user_id', $ligne->user_id)->where('role', 'Demandeur')->where('id', '>', $ligne->id)->exists();

            if (! $u || ! $u->hasRole('Demandeur')) {
                continue;
            }

            if ($posterieure) {
                $preserves[] = $u->id;

                continue;
            }

            $aRetirer[] = $u;
        }

        $this->line(count($aRetirer).' attribution(s) annulable(s) ; '.count($preserves).' préservée(s) (action ultérieure sur le rôle) : '.implode(', ', $preserves));

        if (! $this->option('appliquer')) {
            $this->warn('Simulation : rien n\'a été écrit. Ajoutez --appliquer pour annuler.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($aRetirer, $journal, $par) {
            foreach ($aRetirer as $u) {
                $journal->retirer($u, 'Demandeur', $par, self::ORIGINE_ANNULATION);
            }
        });

        $this->info(count($aRetirer).' attribution(s) annulée(s), avec historique.');

        return self::SUCCESS;
    }
}
