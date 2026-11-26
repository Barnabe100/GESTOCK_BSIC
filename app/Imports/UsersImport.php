<?php

namespace App\Imports;

use App\Autorisation\JournalRoles;
use App\Models\Agence;
use App\Models\Departement;
use App\Models\Service;
use App\Models\User;
use App\Organisation\RefusOrganisation;
use App\Policies\UserPolicy;
use App\Services\DesignationSuperieur;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import des utilisateurs (fichier xlsx, xls ou csv avec ligne d'en-tête :
 * name, prenom, email, agence_id, departement_id, service_id, superieur, roles).
 *
 * Tout ou rien, en deux temps :
 * 1. TOUTES les lignes sont contrôlées avant la moindre écriture : cellules
 *    obligatoires (name, prenom, email), format de l'e-mail, doublons (dans
 *    le fichier et avec les comptes existants), existence des entités,
 *    valeur de « superieur » (1/0, oui/non, vide = non), rôles existants.
 *    Les erreurs sont rassemblées, avec leur numéro de ligne, dans
 *    ImportInvalide : aucune cellule vide ou invalide ne provoque d'erreur 500.
 * 2. Les comptes sont créés ; les rôles passent par JournalRoles (historique)
 *    et l'indicateur « superieur » par le mécanisme officiel de désignation
 *    (DesignationSuperieur : au plus deux supérieurs par entité, historique).
 *
 * L'appelant (UserController::import) exécute l'ensemble dans une transaction.
 * Un rôle que l'importateur n'a pas le droit d'attribuer (SuperAdmin par un
 * Admin) est une tentative d'élévation : refus 403 de tout l'import.
 */
class UsersImport implements ToCollection, WithHeadingRow
{
    public function __construct(private ?User $acteur = null)
    {
    }

    public function collection(Collection $lignes): void
    {
        if (! $this->acteur) {
            abort(403);
        }

        $erreurs = [];
        $prepares = [];
        $emailsVus = [];
        $rolesExistants = UserPolicy::rolesExistants();

        foreach ($lignes as $index => $ligne) {
            $numero = $index + 2; // ligne 1 = en-tête
            $valeur = fn (string $cle) => trim((string) ($ligne[$cle] ?? ''));

            if (collect($ligne)->filter(fn ($v) => trim((string) $v) !== '')->isEmpty()) {
                continue; // ligne entièrement vide : ignorée
            }

            $nom = $valeur('name');
            $prenom = $valeur('prenom');
            $email = mb_strtolower($valeur('email'));

            foreach (['name' => $nom, 'prenom' => $prenom, 'email' => $email] as $colonne => $contenu) {
                if ($contenu === '') {
                    $erreurs[] = "Ligne {$numero} : la cellule « {$colonne} » est obligatoire.";
                }
            }

            if ($email !== '') {
                if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    $erreurs[] = "Ligne {$numero} : l'adresse e-mail « {$email} » est invalide.";
                } elseif (isset($emailsVus[$email])) {
                    $erreurs[] = "Ligne {$numero} : l'adresse e-mail « {$email} » figure déjà à la ligne {$emailsVus[$email]}.";
                } elseif (User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                    $erreurs[] = "Ligne {$numero} : un compte existe déjà avec l'adresse e-mail « {$email} ».";
                }
                $emailsVus[$email] ??= $numero;
            }

            $ids = [];
            foreach (['agence_id' => Agence::class, 'departement_id' => Departement::class, 'service_id' => Service::class] as $colonne => $modele) {
                $brut = $valeur($colonne);
                $ids[$colonne] = null;

                if ($brut === '') {
                    continue;
                }

                // Entier strictement positif (un tableur peut fournir « 3.0 »).
                if (! is_numeric($brut) || (float) $brut != (int) $brut || (int) $brut < 1) {
                    $erreurs[] = "Ligne {$numero} : la cellule « {$colonne} » doit être un identifiant numérique.";

                    continue;
                }

                $ids[$colonne] = (int) $brut;

                if (! $modele::whereKey($ids[$colonne])->exists()) {
                    $erreurs[] = "Ligne {$numero} : « {$colonne} » {$ids[$colonne]} n'existe pas.";
                }
            }

            $superieurBrut = mb_strtolower($valeur('superieur'));
            $superieur = match (true) {
                in_array($superieurBrut, ['', '0', 'non', 'false'], true) => false,
                in_array($superieurBrut, ['1', 'oui', 'true'], true) => true,
                default => null,
            };

            if ($superieur === null) {
                $erreurs[] = "Ligne {$numero} : la cellule « superieur » doit valoir 1 ou 0 (oui ou non).";
            }

            $roles = array_values(array_unique(array_filter(array_map('trim', explode(';', $valeur('roles'))), fn ($r) => $r !== '')));
            $roles = $roles === [] ? ['Demandeur'] : $roles;
            // Le rôle Superieur suit l'indicateur « superieur » (désignation).
            $roles = array_values(array_diff($roles, ['Superieur']));

            foreach (array_diff($roles, $rolesExistants) as $inconnu) {
                $erreurs[] = "Ligne {$numero} : le rôle « {$inconnu} » n'existe pas.";
            }

            $prepares[] = compact('numero', 'nom', 'prenom', 'email', 'ids', 'superieur', 'roles');
        }

        // Tentative d'attribution d'un rôle réservé : refus de tout l'import.
        $tousLesRoles = array_values(array_unique(array_merge([], ...array_column($prepares, 'roles'))));
        if (Gate::forUser($this->acteur)->denies('attribuerRolesALaCreation', [User::class, array_values(array_intersect($tousLesRoles, $rolesExistants))])) {
            abort(403);
        }

        if ($prepares === [] && $erreurs === []) {
            $erreurs[] = 'Le fichier ne contient aucune ligne à importer.';
        }

        if ($erreurs !== []) {
            throw new ImportInvalide($erreurs);
        }

        $journal = app(JournalRoles::class);
        $designation = app(DesignationSuperieur::class);

        foreach ($prepares as $p) {
            $user = User::create([
                'name' => $p['nom'],
                'prenom' => $p['prenom'],
                'email' => $p['email'],
                'password' => Hash::make(config('gestok.mot_de_passe_initial')),
                'force_password_change' => true,
                'bloque' => false,
                'agence_id' => $p['ids']['agence_id'],
                'departement_id' => $p['ids']['departement_id'],
                'service_id' => $p['ids']['service_id'],
                'superieur' => false,
            ]);

            $journal->attribuer($user, $p['roles'], $this->acteur, 'import');

            if ($p['superieur']) {
                try {
                    $designation->designer($user, $this->acteur);
                } catch (RefusOrganisation $e) {
                    throw new ImportInvalide(["Ligne {$p['numero']} : ".$e->getMessage()]);
                }
            }
        }
    }
}
