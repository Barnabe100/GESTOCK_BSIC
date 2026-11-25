<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDOException;
use Throwable;

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
 *
 * L1-5b : rotation atomique (transaction), lignes verrouillées, mot de passe
 * revérifié juste avant chaque modification, empreinte distincte par compte,
 * journalisation des succès et des échecs sans aucune donnée sensible.
 */
class ControlerMotsDePasseExposes extends Command
{
    private const JOURNAL = 'gestok:mots-de-passe-exposes : ';

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

        $ids = array_column($comptes, 'id');

        Log::info(self::JOURNAL.'rotation démarrée', ['candidats' => $ids]);

        try {
            $resultat = $this->appliquerRotation($ids, $valeursExposees, $nouveauMotDePasse);
        } catch (Throwable $e) {
            // La transaction a été annulée : aucun compte n'est modifié.
            // Le journal (fichier) est écrit APRÈS l'annulation et reste donc
            // disponible ; il ne contient ni mot de passe ni empreinte.
            $detail = self::detailSansSecret($e);

            Log::error(self::JOURNAL.'rotation annulée, aucune modification', [
                'candidats' => $ids,
                'erreur' => get_class($e),
                'detail' => $detail,
            ]);

            $this->error('Rotation annulée : une erreur est survenue, aucun compte n\'a été modifié.');
            $this->line('Détail technique : '.$detail);
            $this->line('Corrigez la cause puis relancez la commande.');

            return self::FAILURE;
        }

        Log::warning(self::JOURNAL.'rotation appliquée', [
            'user_ids' => $resultat['traites'],
            'ignores' => $resultat['ignores'],
        ]);

        $this->info('Rotation appliquée à '.count($resultat['traites']).' compte(s).');

        if ($resultat['ignores'] !== []) {
            $this->warn(count($resultat['ignores']).' compte(s) ignoré(s) : mot de passe modifié (ou compte supprimé) depuis la détection.');
            $this->table(['id'], array_map(fn ($id) => [$id], $resultat['ignores']));
        }

        return self::SUCCESS;
    }

    /**
     * Applique la rotation de façon atomique (tout ou rien).
     *
     * 1. Les nouvelles empreintes sont calculées AVANT la transaction (une par
     *    compte, sel distinct) pour réduire la durée des verrous.
     * 2. Dans une transaction unique, les lignes candidates sont verrouillées
     *    (SELECT ... FOR UPDATE, ordre des identifiants : pas d'interblocage
     *    entre deux exécutions) ; une modification concurrente de ces comptes
     *    attend la fin de la transaction.
     * 3. Le mot de passe ACTUEL de chaque compte est revérifié sous verrou :
     *    un compte qui ne correspond plus à une valeur exposée (mot de passe
     *    changé depuis la détection) ou qui a disparu est ignoré.
     * 4. Toute erreur annule l'ensemble de la transaction.
     *
     * Le paramètre $apresVerrouillage est un point d'observation réservé aux
     * tests (exécuté une fois les lignes verrouillées) ; il vaut null en
     * exploitation.
     *
     * @param  list<int>  $ids
     * @param  list<string>  $valeursExposees
     * @return array{traites: list<int>, ignores: list<int>}
     */
    public function appliquerRotation(array $ids, array $valeursExposees, string $nouveauMotDePasse, ?callable $apresVerrouillage = null): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);

        $empreintes = [];
        foreach ($ids as $id) {
            $empreintes[$id] = Hash::make($nouveauMotDePasse);
        }

        return DB::transaction(function () use ($ids, $valeursExposees, $empreintes, $apresVerrouillage) {
            $verrouilles = User::query()
                ->whereKey($ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($apresVerrouillage !== null) {
                $apresVerrouillage($verrouilles->keys()->all());
            }

            $traites = [];
            $ignores = [];

            foreach ($ids as $id) {
                $user = $verrouilles->get($id);

                if ($user === null || ! self::correspondAUneValeur($user->password, $valeursExposees)) {
                    $ignores[] = $id;

                    continue;
                }

                $user->forceFill([
                    'password' => $empreintes[$id],
                    'force_password_change' => true,
                    'remember_token' => Str::random(60),
                ])->save();

                $traites[] = $id;
            }

            return ['traites' => $traites, 'ignores' => $ignores];
        });
    }

    /**
     * @param  list<string>  $valeursExposees
     */
    private static function correspondAUneValeur(?string $empreinte, array $valeursExposees): bool
    {
        if ($empreinte === null || $empreinte === '') {
            return false;
        }

        foreach ($valeursExposees as $valeur) {
            if (Hash::check($valeur, $empreinte)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Description de l'erreur sans aucune donnée sensible.
     *
     * Le message d'une QueryException contient la requête SQL ET ses valeurs
     * (empreinte du nouveau mot de passe, jeton) : il n'est jamais repris.
     * Seul le message du pilote PDO (code SQLSTATE et texte du serveur, sans
     * les valeurs liées) est conservé ; toute empreinte bcrypt éventuelle est
     * masquée par précaution.
     */
    private static function detailSansSecret(Throwable $e): string
    {
        if ($e instanceof QueryException) {
            $pdo = $e->getPrevious();
            $detail = $pdo instanceof PDOException
                ? $pdo->getMessage()
                : 'Erreur SQL (code '.$e->getCode().')';
        } else {
            $detail = get_class($e);
        }

        $detail = preg_replace('/\$2[abxy]?\$\d{2}\$[.\/A-Za-z0-9]{53}/', '[empreinte masquée]', $detail);

        return mb_substr($detail, 0, 300);
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
                    if (self::correspondAUneValeur($user->password, $valeursExposees)) {
                        $comptes[] = ['id' => $user->id, 'email' => $user->email];
                    }
                }
            });

        return $comptes;
    }
}
