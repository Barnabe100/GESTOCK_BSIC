<?php

namespace App\Comptes;

use App\Autorisation\Capacites;
use App\Models\User;
use App\Services\DesignationSuperieur;
use App\Services\GestionInterims;

/**
 * Classement des comptes pour les diagnostics F3 (comptes sans rôle) et F4
 * (comptes sans rôle Demandeur). Lecture seule : aucune écriture.
 *
 * - statut : actif, bloqué, désactivé ;
 * - particularités (jamais déduites du nom, sauf la liste fixe des comptes
 *   d'installation créés par UserSeeder) :
 *     compte_installation : comptes créés par l'installation ;
 *     declare_technique   : déclaré dans GESTOK_COMPTES_TECHNIQUES ;
 *     sans_rattachement   : aucune entité (agence) renseignée.
 */
class ClassementComptes
{
    /** Comptes créés par database/seeders/UserSeeder. */
    public const COMPTES_INSTALLATION = ['superadmin@test.com', 'admin@test.com'];

    public static function statut(User $u): string
    {
        if ($u->desactive_le !== null) {
            return 'desactive';
        }

        return $u->bloque ? 'bloque' : 'actif';
    }

    /** @return list<string> */
    public static function particularites(User $u): array
    {
        $declares = array_map('mb_strtolower', config('gestok.comptes_techniques', []));
        $particularites = [];

        if (in_array(mb_strtolower($u->email), self::COMPTES_INSTALLATION, true)) {
            $particularites[] = 'compte_installation';
        }

        if (in_array(mb_strtolower($u->email), $declares, true) || in_array((string) $u->id, $declares, true)) {
            $particularites[] = 'declare_technique';
        }

        if ($u->agence_id === null) {
            $particularites[] = 'sans_rattachement';
        }

        return $particularites;
    }

    /**
     * Accès métier réellement disponibles aujourd'hui, selon les règles en
     * vigueur (aucun rôle n'est supposé).
     */
    public static function accesMetier(User $u): array
    {
        $entite = $u->entiteDeValidation();
        $valideurPossible = $entite !== null && (
            DesignationSuperieur::titulaires($entite, $u->id)->isNotEmpty()
            || GestionInterims::remplacantEnVigueur($entite) !== null
            || DesignationSuperieur::titulaires($entite)->pluck('id')->all() === [$u->id]
        );

        return [
            'tableau_de_bord' => ! $u->getRoleNames()->isEmpty(),
            'mes_demandes' => true,
            'creation_demande' => $valideurPossible,
            'demandes_de_son_entite' => $entite !== null,
            'capacites' => array_values(array_filter(Capacites::noms(), fn ($c) => Capacites::autorise($u, $c))),
        ];
    }
}
