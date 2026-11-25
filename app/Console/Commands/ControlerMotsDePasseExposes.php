<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Lot 1, L1-5 : rotation des mots de passe par défaut exposés.
 *
 * Repère les comptes dont le mot de passe correspond encore à une ancienne
 * valeur exposée et, uniquement sur demande explicite (--appliquer puis
 * confirmation), leur attribue le mot de passe initial configuré
 * (GESTOK_MOT_DE_PASSE_INITIAL) avec obligation de le changer à la prochaine
 * connexion : même procédure que l'accueil d'un nouvel utilisateur.
 *
 * Les valeurs exposées sont saisies de façon masquée et ne sont jamais
 * écrites dans le code, la base ou les journaux.
 */
class ControlerMotsDePasseExposes extends Command
{
    protected $signature = 'gestok:mots-de-passe-exposes
                            {--appliquer : Applique la rotation (par défaut : simulation, aucune modification)}';

    protected $description = 'Repère (simulation par défaut) puis, sur demande explicite, renouvelle les comptes utilisant encore un mot de passe par défaut exposé.';

    public function handle(): int
    {
        $appliquer = (bool) $this->option('appliquer');

        $this->info($appliquer
            ? 'Mode APPLICATION : les comptes concernés seront modifiés après confirmation.'
            : 'Mode SIMULATION : aucune donnée ne sera modifiée.');

        $valeursExposees = $this->saisirValeursExposees();

        if ($valeursExposees === []) {
            $this->error('Aucune valeur saisie : contrôle annulé.');

            return self::FAILURE;
        }

        $nouveauMotDePasse = config('gestok.mot_de_passe_initial');

        if ($appliquer) {
            if (blank($nouveauMotDePasse)) {
                $this->error("GESTOK_MOT_DE_PASSE_INITIAL n'est pas configuré : rotation impossible.");

                return self::FAILURE;
            }

            if (in_array($nouveauMotDePasse, $valeursExposees, true)) {
                $this->error('Le mot de passe initial configuré est une valeur exposée : choisissez une nouvelle valeur.');

                return self::FAILURE;
            }
        }

        $comptes = $this->comptesConcernes($valeursExposees);

        if ($comptes === []) {
            $this->info('Aucun compte n\'utilise une valeur exposée.');

            return self::SUCCESS;
        }

        $this->warn(count($comptes).' compte(s) utilise(nt) encore une valeur exposée :');
        $this->table(['id', 'e-mail'], array_map(fn ($c) => [$c['id'], $c['email']], $comptes));

        if (! $appliquer) {
            $this->info('Simulation terminée : aucune modification. Relancer avec --appliquer pour effectuer la rotation.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Attribuer le nouveau mot de passe initial à ces '.count($comptes).' compte(s), avec changement obligatoire à la prochaine connexion ?', false)) {
            $this->info('Rotation annulée : aucune modification.');

            return self::SUCCESS;
        }

        $empreinte = Hash::make($nouveauMotDePasse);
        $ids = array_column($comptes, 'id');

        User::whereKey($ids)->each(function (User $user) use ($empreinte) {
            $user->forceFill([
                'password' => $empreinte,
                'force_password_change' => true,
                'remember_token' => Str::random(60),
            ])->save();
        });

        Log::warning('gestok:mots-de-passe-exposes : rotation appliquée', ['user_ids' => $ids]);

        $this->info('Rotation appliquée à '.count($ids).' compte(s).');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function saisirValeursExposees(): array
    {
        $valeurs = [];

        while (true) {
            $valeur = $this->secret('Valeur exposée n°'.(count($valeurs) + 1).' (laisser vide pour terminer)');

            if (blank($valeur)) {
                break;
            }

            $valeurs[] = (string) $valeur;
        }

        return array_values(array_unique($valeurs));
    }

    /**
     * @param  list<string>  $valeursExposees
     * @return list<array{id: int, email: string}>
     */
    private function comptesConcernes(array $valeursExposees): array
    {
        $comptes = [];

        User::query()
            ->select(['id', 'email', 'password'])
            ->orderBy('id')
            ->chunkById(200, function ($lot) use ($valeursExposees, &$comptes) {
                foreach ($lot as $user) {
                    foreach ($valeursExposees as $valeur) {
                        if (Hash::check($valeur, $user->password)) {
                            $comptes[] = ['id' => $user->id, 'email' => $user->email];
                            break;
                        }
                    }
                }
            });

        return $comptes;
    }
}
