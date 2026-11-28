-- =============================================================================
-- GESTOK : DIAGNOSTIC DES COMPTES (LECTURE SEULE)
-- Version 2 (après audit : docs/AUDIT_SCRIPT_DIAGNOSTIC_COMPTES.md)
-- =============================================================================
--
-- Script STRICTEMENT EN LECTURE SEULE : il ne contient que des requêtes
-- SELECT, sans INTO, sans FOR UPDATE, sans LOCK IN SHARE MODE, sans variable,
-- sans procédure et sans fonction à effet de bord.
-- Mode d'emploi : docs/NOTICE_EXECUTION_DIAGNOSTIC_COMPTES.md
--
-- SCHÉMA DE RÉFÉRENCE
--   Migrations des branches main et develop (identiques) : users (avec bloque),
--   roles, permissions, model_has_roles, model_has_permissions, agences,
--   departements, services, demandes, mouvement_stocks,
--   personal_access_tokens, migrations.
--   users.desactive_le n'existe que si la migration
--   2026_11_27_000002_creer_historique_roles_et_desactivation_comptes a été
--   déployée : la requête 0.5 le dit, la section 7 (en commentaire) l'utilise.
--
-- STATUTS
--   bloqué   : users.bloque non nul et différent de 0 (même test que
--              LoginRequest sur main : « if ($user->bloque) »).
--   actif    : non bloqué (COALESCE(bloque, 0) = 0).
--   désactivé: notion absente du schéma de main ; voir section 7.
--
-- DONNÉES SENSIBLES
--   Jamais lues ni affichées : password, remember_token, token, abilities,
--   name, prenom, email_verified_at, adresse e-mail complète.
--   Affichés : identifiant interne users.id, domaine de l'adresse (partie
--   après @) en section C seulement, indicateurs 0/1, nombres, dates.
--   L'adresse complète n'est utilisée qu'en comparaison interne (WHERE, CASE).
--
-- DOUBLONS
--   Les rôles (R), l'activité (ACT) et les jetons (J) sont agrégés par compte
--   dans des tables dérivées (GROUP BY) AVANT toute jointure : une ligne par
--   compte au plus de chaque côté. Les jointures vers agences, departements et
--   services se font sur leur clé primaire.
--
-- FILTRE DU TYPE POLYMORPHE
--   model_type vaut App\Models\User. Le motif LIKE 'App_Models_User' (« _ »
--   = un caractère quelconque, ici « \ ») évite toute dépendance au mode
--   d'échappement des barres obliques inverses. La requête 5.2 liste les
--   valeurs réellement présentes.
--
-- COMPATIBILITÉ : MySQL 5.7 et plus, MariaDB 10.2 et plus (aucune CTE, aucune
-- fonction de fenêtrage, aucune fonction JSON). Testé localement sur
-- MySQL 8.0.46 (ONLY_FULL_GROUP_BY actif) et MariaDB 10.11.14.
-- =============================================================================


-- =============================================================================
-- 0. CONTEXTE DE L'EXÉCUTION
-- =============================================================================

-- [0.1] Base consultée, version du serveur, date et heure du diagnostic
SELECT DATABASE() AS base_consultee, VERSION() AS version_serveur, NOW() AS date_heure_diagnostic;

-- [0.2] Migrations exécutées
SELECT migration, batch FROM migrations ORDER BY id;

-- [0.3] Colonnes visibles des tables utilisées (avec des droits par colonne,
--       seules les colonnes accordées apparaissent : c'est normal)
SELECT c.table_name AS table_name, c.column_name AS column_name, c.data_type AS data_type,
       c.is_nullable AS is_nullable, c.column_default AS valeur_par_defaut
FROM information_schema.columns c
WHERE c.table_schema = DATABASE()
  AND c.table_name IN ('users', 'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'agences',
                       'departements', 'services', 'demandes', 'mouvement_stocks', 'personal_access_tokens')
  AND c.column_name NOT IN ('password', 'remember_token', 'token', 'abilities')
ORDER BY c.table_name, c.ordinal_position;

-- [0.4] Rôles existants et nombre de comptes EXISTANTS et DISTINCTS par rôle
--       (les affectations orphelines sont comptées à part, voir 5.1)
SELECT r.id AS role_id, r.name AS role, r.guard_name,
       COUNT(DISTINCT u.id) AS nb_comptes
FROM roles r
LEFT JOIN model_has_roles mhr ON mhr.role_id = r.id AND mhr.model_type LIKE 'App_Models_User'
LEFT JOIN users u ON u.id = mhr.model_id
GROUP BY r.id, r.name, r.guard_name
ORDER BY r.name;

-- [0.5] Migrations récentes déjà déployées ? (1 = oui). Si desactivation = 1,
--       la section 7 s'applique. Fonctionne même avec des droits par colonne.
SELECT SUM(CASE WHEN migration = '2026_11_27_000002_creer_historique_roles_et_desactivation_comptes' THEN 1 ELSE 0 END) AS desactivation,
       SUM(CASE WHEN migration = '2026_11_27_000001_ajouter_type_entite_aux_agences' THEN 1 ELSE 0 END) AS type_entite_agences,
       SUM(CASE WHEN migration = '2026_11_28_000001_creer_role_consultant' THEN 1 ELSE 0 END) AS role_consultant
FROM migrations;


-- =============================================================================
-- 1. VUE D'ENSEMBLE
-- =============================================================================

-- [1.1] Totaux (chaque compte compté une seule fois)
SELECT
    COUNT(*)                                                                        AS comptes_examines,
    SUM(CASE WHEN COALESCE(u.bloque, 0) <> 0 THEN 1 ELSE 0 END)                    AS comptes_bloques,
    SUM(CASE WHEN COALESCE(u.bloque, 0) = 0 THEN 1 ELSE 0 END)                     AS comptes_actifs,
    SUM(CASE WHEN R.user_id IS NULL THEN 1 ELSE 0 END)                              AS sans_aucun_role,
    SUM(CASE WHEN R.a_demandeur = 1 THEN 1 ELSE 0 END)                              AS avec_role_demandeur,
    SUM(CASE WHEN R.user_id IS NOT NULL AND R.a_demandeur = 0 THEN 1 ELSE 0 END)   AS avec_roles_sans_demandeur
FROM users u
LEFT JOIN (
    SELECT mhr.model_id AS user_id,
           MAX(CASE WHEN r.name = 'Demandeur' THEN 1 ELSE 0 END) AS a_demandeur
    FROM model_has_roles mhr
    JOIN roles r ON r.id = mhr.role_id
    WHERE mhr.model_type LIKE 'App_Models_User'
    GROUP BY mhr.model_id
) R ON R.user_id = u.id;


-- =============================================================================
-- A. COMPTES SANS AUCUN RÔLE
-- =============================================================================
-- Fait établi : aucune ligne dans model_has_roles pour ce compte (avec le type
-- App\Models\User). Un rôle enregistré avec un autre model_type n'est pas
-- reconnu par l'application : il apparaît en 5.2.
-- L'activité est détaillée en 5.8 (analyse spécifique).

-- [A.1] Liste des comptes sans aucun rôle
SELECT u.id,
       CASE WHEN COALESCE(u.bloque, 0) <> 0 THEN 'bloque' ELSE 'actif' END AS statut,
       u.agence_id, u.departement_id, u.service_id,
       u.superieur AS indicateur_superieur,
       u.force_password_change AS mdp_initial_non_change,
       DATE(u.created_at) AS cree_le,
       COALESCE(ACT.nb_demandes_creees, 0) AS nb_demandes_creees,
       COALESCE(ACT.nb_interventions, 0) AS nb_interventions_sur_demandes,
       COALESCE(ACT.nb_mouvements, 0) AS nb_mouvements_saisis
FROM users u
LEFT JOIN (
    SELECT x.user_id,
           SUM(x.nb_crees) AS nb_demandes_creees,
           SUM(x.nb_superieur + x.nb_gestionnaire + x.nb_validateur_final + x.nb_reception) AS nb_interventions,
           SUM(x.nb_mouvements) AS nb_mouvements
    FROM (
        SELECT user_id, COUNT(*) AS nb_crees, 0 AS nb_superieur, 0 AS nb_gestionnaire, 0 AS nb_validateur_final, 0 AS nb_reception, 0 AS nb_mouvements
          FROM demandes WHERE user_id IS NOT NULL GROUP BY user_id
        UNION ALL SELECT superieur_id, 0, COUNT(*), 0, 0, 0, 0 FROM demandes WHERE superieur_id IS NOT NULL GROUP BY superieur_id
        UNION ALL SELECT gestionnaire_id, 0, 0, COUNT(*), 0, 0, 0 FROM demandes WHERE gestionnaire_id IS NOT NULL GROUP BY gestionnaire_id
        UNION ALL SELECT validateur_final_id, 0, 0, 0, COUNT(*), 0, 0 FROM demandes WHERE validateur_final_id IS NOT NULL GROUP BY validateur_final_id
        UNION ALL SELECT reception_par, 0, 0, 0, 0, COUNT(*), 0 FROM demandes WHERE reception_par IS NOT NULL GROUP BY reception_par
        UNION ALL SELECT user_id, 0, 0, 0, 0, 0, COUNT(*) FROM mouvement_stocks WHERE user_id IS NOT NULL GROUP BY user_id
    ) x
    GROUP BY x.user_id
) ACT ON ACT.user_id = u.id
WHERE NOT EXISTS (
    SELECT 1 FROM model_has_roles mhr
    WHERE mhr.model_type LIKE 'App_Models_User' AND mhr.model_id = u.id
)
ORDER BY u.id;

-- [A.2] Effectifs des comptes sans aucun rôle, par statut et par rattachement
SELECT CASE WHEN COALESCE(u.bloque, 0) <> 0 THEN 'bloque' ELSE 'actif' END AS statut,
       CASE WHEN u.agence_id IS NULL AND u.departement_id IS NULL AND u.service_id IS NULL THEN 'aucun'
            WHEN u.agence_id IS NOT NULL AND u.departement_id IS NOT NULL AND u.service_id IS NOT NULL THEN 'complet'
            ELSE 'partiel' END AS rattachement,
       COUNT(*) AS nb_comptes
FROM users u
WHERE NOT EXISTS (
    SELECT 1 FROM model_has_roles mhr
    WHERE mhr.model_type LIKE 'App_Models_User' AND mhr.model_id = u.id
)
GROUP BY 1, 2
ORDER BY 1, 2;


-- =============================================================================
-- B. COMPTES ACTIFS SANS LE RÔLE DEMANDEUR
-- =============================================================================
-- SITUATION À EXAMINER, PAS UNE ANOMALIE EN SOI. Un compte d'administration,
-- de validation ou de consultation peut légitimement ne pas avoir Demandeur.
-- Contexte (code de main) : la création d'un compte par l'interface attribue
-- Demandeur d'office ; un compte sans Demandeur provient donc plutôt de
-- l'import, du seeder ou d'un retrait volontaire. Sans Demandeur, la page
-- « demandes de l'organisation » est refusée sur main.
-- B.1 : comptes ayant d'autres rôles ; B.2 : comptes sans aucun rôle.

-- [B.1] Comptes actifs avec au moins un rôle, sans Demandeur
SELECT u.id,
       R.roles AS roles_actuels,
       u.agence_id, u.departement_id, u.service_id,
       CASE WHEN LOWER(TRIM(u.email)) IN ('superadmin@test.com', 'admin@test.com') THEN 1 ELSE 0 END AS adresse_installation_seeder,
       R.a_role_admin AS role_administratif
FROM users u
JOIN (
    SELECT mhr.model_id AS user_id,
           GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ', ') AS roles,
           MAX(CASE WHEN r.name = 'Demandeur' THEN 1 ELSE 0 END) AS a_demandeur,
           MAX(CASE WHEN r.name IN ('SuperAdmin', 'Admin') THEN 1 ELSE 0 END) AS a_role_admin
    FROM model_has_roles mhr
    JOIN roles r ON r.id = mhr.role_id
    WHERE mhr.model_type LIKE 'App_Models_User'
    GROUP BY mhr.model_id
) R ON R.user_id = u.id
WHERE COALESCE(u.bloque, 0) = 0
  AND R.a_demandeur = 0
ORDER BY u.id;

-- [B.2] Comptes actifs sans aucun rôle (sous-ensemble de A, rappelé ici)
SELECT u.id, u.agence_id, u.departement_id, u.service_id, DATE(u.created_at) AS cree_le
FROM users u
WHERE COALESCE(u.bloque, 0) = 0
  AND NOT EXISTS (
      SELECT 1 FROM model_has_roles mhr
      WHERE mhr.model_type LIKE 'App_Models_User' AND mhr.model_id = u.id
  )
ORDER BY u.id;

-- [B.3] Effectifs des comptes actifs sans Demandeur, par combinaison de rôles
SELECT COALESCE(R.roles, '(aucun role)') AS roles_actuels, COUNT(*) AS nb_comptes_actifs
FROM users u
LEFT JOIN (
    SELECT mhr.model_id AS user_id,
           GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ', ') AS roles,
           MAX(CASE WHEN r.name = 'Demandeur' THEN 1 ELSE 0 END) AS a_demandeur
    FROM model_has_roles mhr
    JOIN roles r ON r.id = mhr.role_id
    WHERE mhr.model_type LIKE 'App_Models_User'
    GROUP BY mhr.model_id
) R ON R.user_id = u.id
WHERE COALESCE(u.bloque, 0) = 0
  AND COALESCE(R.a_demandeur, 0) = 0
GROUP BY 1
ORDER BY nb_comptes_actifs DESC, roles_actuels;


-- =============================================================================
-- C. COMPTES TECHNIQUES OU POTENTIELLEMENT TECHNIQUES
-- =============================================================================
-- Le schéma ne contient AUCUN marqueur de compte technique. Rien ici n'est une
-- preuve : toute qualification exige une validation humaine.
--
-- FAIT (sans conclusion) :
--   adresse_installation_seeder : adresse identique à un compte créé par
--     database/seeders/UserSeeder (superadmin@test.com, admin@test.com).
--     Ce fait ne dit NI que le compte est technique, NI qu'il est inutilisé :
--     le seeder donne au SuperAdmin le nom d'une personne réelle, et ce compte
--     peut être le compte d'administration effectivement utilisé. Les
--     colonnes d'usage (activité, jeton) aident à le savoir.
-- INDICES de nature technique (comptés dans nb_indices) :
--   ind_adresse_demo_seeder : adresse du jeu de démonstration présent en
--     commentaire dans UserSeeder (gestionnaire@, supdep@, supag@, demag@,
--     demdep@, valfinal@, valcom@ test.com)
--   ind_domaine_test        : domaine réservé aux tests ou à la documentation
--   ind_nom_technique       : partie avant @ évocatrice (admin, system, noreply…)
--   ind_jeton_api           : au moins un jeton d'API Sanctum
-- CONTEXTE (affiché, NON compté : ne suggère pas à lui seul un compte technique) :
--   role_administratif, sans_activite, mdp_initial_non_change, sans_rattachement.

-- [C.1] Comptes avec adresse d'installation ou au moins un indice (une ligne par compte)
SELECT T.id, T.statut, T.roles_actuels, T.domaine_email,
       T.adresse_installation_seeder,
       (T.ind_adresse_demo_seeder + T.ind_domaine_test + T.ind_nom_technique + T.ind_jeton_api) AS nb_indices,
       T.ind_adresse_demo_seeder, T.ind_domaine_test, T.ind_nom_technique, T.ind_jeton_api,
       T.dernier_usage_jeton,
       T.role_administratif, T.sans_activite, T.derniere_demande_creee_le, T.dernier_mouvement_le,
       T.mdp_initial_non_change, T.sans_rattachement
FROM (
    SELECT u.id,
           CASE WHEN COALESCE(u.bloque, 0) <> 0 THEN 'bloque' ELSE 'actif' END AS statut,
           COALESCE(R.roles, '(aucun)') AS roles_actuels,
           CASE WHEN LOCATE('@', u.email) > 0 THEN LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', -1))) ELSE '(adresse sans @)' END AS domaine_email,
           CASE WHEN LOWER(TRIM(u.email)) IN ('superadmin@test.com', 'admin@test.com') THEN 1 ELSE 0 END AS adresse_installation_seeder,
           CASE WHEN LOWER(TRIM(u.email)) IN ('gestionnaire@test.com', 'supdep@test.com', 'supag@test.com', 'demag@test.com',
                                        'demdep@test.com', 'valfinal@test.com', 'valcom@test.com') THEN 1 ELSE 0 END AS ind_adresse_demo_seeder,
           CASE WHEN LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', -1))) IN ('test.com', 'example.com', 'example.org', 'example.net', 'test', 'localhost')
                  OR LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', -1))) LIKE '%.test'
                  OR LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', -1))) LIKE '%.local'
                  OR LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', -1))) LIKE '%.example'
                  OR LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', -1))) LIKE '%.invalid'
                THEN 1 ELSE 0 END AS ind_domaine_test,
           CASE WHEN LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', 1)))
                     REGEXP '^(admin|superadmin|administrateur|root|system|systeme|noreply|no-reply|test|demo|robot|service|api|batch|cron|technique)([._0-9-]|$)'
                THEN 1 ELSE 0 END AS ind_nom_technique,
           CASE WHEN J.user_id IS NULL THEN 0 ELSE 1 END AS ind_jeton_api,
           J.dernier_usage AS dernier_usage_jeton,
           COALESCE(R.a_role_admin, 0) AS role_administratif,
           CASE WHEN ACT.user_id IS NULL THEN 1 ELSE 0 END AS sans_activite,
           ACT.derniere_demande_creee_le, ACT.dernier_mouvement_le,
           CASE WHEN COALESCE(u.force_password_change, 0) <> 0 THEN 1 ELSE 0 END AS mdp_initial_non_change,
           CASE WHEN u.agence_id IS NULL AND u.departement_id IS NULL AND u.service_id IS NULL THEN 1 ELSE 0 END AS sans_rattachement
    FROM users u
    LEFT JOIN (
        SELECT mhr.model_id AS user_id,
               GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ', ') AS roles,
               MAX(CASE WHEN r.name IN ('SuperAdmin', 'Admin') THEN 1 ELSE 0 END) AS a_role_admin
        FROM model_has_roles mhr
        JOIN roles r ON r.id = mhr.role_id
        WHERE mhr.model_type LIKE 'App_Models_User'
        GROUP BY mhr.model_id
    ) R ON R.user_id = u.id
    LEFT JOIN (
        SELECT p.tokenable_id AS user_id, MAX(p.last_used_at) AS dernier_usage
        FROM personal_access_tokens p
        WHERE p.tokenable_type LIKE 'App_Models_User'
        GROUP BY p.tokenable_id
    ) J ON J.user_id = u.id
    LEFT JOIN (
        SELECT x.user_id, MAX(x.derniere_demande) AS derniere_demande_creee_le, MAX(x.dernier_mouvement) AS dernier_mouvement_le
        FROM (
            SELECT user_id, MAX(created_at) AS derniere_demande, CAST(NULL AS DATETIME) AS dernier_mouvement
              FROM demandes WHERE user_id IS NOT NULL GROUP BY user_id
            UNION ALL SELECT superieur_id, CAST(NULL AS DATETIME), CAST(NULL AS DATETIME) FROM demandes WHERE superieur_id IS NOT NULL GROUP BY superieur_id
            UNION ALL SELECT gestionnaire_id, CAST(NULL AS DATETIME), CAST(NULL AS DATETIME) FROM demandes WHERE gestionnaire_id IS NOT NULL GROUP BY gestionnaire_id
            UNION ALL SELECT validateur_final_id, CAST(NULL AS DATETIME), CAST(NULL AS DATETIME) FROM demandes WHERE validateur_final_id IS NOT NULL GROUP BY validateur_final_id
            UNION ALL SELECT reception_par, CAST(NULL AS DATETIME), CAST(NULL AS DATETIME) FROM demandes WHERE reception_par IS NOT NULL GROUP BY reception_par
            UNION ALL SELECT user_id, CAST(NULL AS DATETIME), MAX(created_at) FROM mouvement_stocks WHERE user_id IS NOT NULL GROUP BY user_id
        ) x
        GROUP BY x.user_id
    ) ACT ON ACT.user_id = u.id
) T
WHERE T.adresse_installation_seeder = 1
   OR (T.ind_adresse_demo_seeder + T.ind_domaine_test + T.ind_nom_technique + T.ind_jeton_api) > 0
ORDER BY T.adresse_installation_seeder DESC, nb_indices DESC, T.id;

-- [C.2] Répartition des comptes par domaine d'adresse (aucune adresse complète)
SELECT CASE WHEN LOCATE('@', u.email) > 0 THEN LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', -1))) ELSE '(adresse sans @)' END AS domaine_email,
       COUNT(*) AS nb_comptes
FROM users u
GROUP BY 1
ORDER BY nb_comptes DESC, domaine_email;


-- =============================================================================
-- D. COMPTES ACTIFS : RATTACHEMENT ORGANISATIONNEL
-- =============================================================================
-- Structures : users.agence_id → agences ; users.departement_id → departements
-- (departements.agence_id) ; users.service_id → services
-- (services.departement_id).
-- D.1 : INCOHÉRENCES de données (faits vérifiables) ou rattachement à une
--       structure archivée (à vérifier).
-- D.2 : rattachement INCOMPLET. Un rattachement partiel N'EST PAS
--       automatiquement incorrect : la colonne « lecture_code_main » ne
--       rapporte que les deux conséquences établies dans le code de main
--       (DemandeController::store) ; tout le reste est à interpréter.
--         - sans agence valide (absente ou inexistante) : $user->agence->nom
--           échoue, le dépôt d'une demande est impossible ;
--         - agence « AGENCE PRINCIPALE SIEGE » sans département : le
--           supérieur est cherché par département, il est introuvable
--           (« Aucun supérieur défini »).
--       Les autres lignes indiquent seulement comment main cherche le
--       supérieur (par agence hors siège, par département au siège).
--       Le service n'intervient pas dans le circuit des demandes de main.
--       La comparaison porte sur le nom exact de l'agence : une agence siège
--       renommée ne serait pas reconnue (à vérifier en 0.3 / D.3).

-- [D.1] Comptes actifs avec une incohérence de rattachement ou une structure archivée
SELECT u.id,
       u.agence_id, u.departement_id, u.service_id,
       CASE WHEN u.agence_id IS NOT NULL AND a.id IS NULL THEN 1 ELSE 0 END AS agence_inexistante,
       CASE WHEN u.departement_id IS NOT NULL AND d.id IS NULL THEN 1 ELSE 0 END AS departement_inexistant,
       CASE WHEN u.service_id IS NOT NULL AND s.id IS NULL THEN 1 ELSE 0 END AS service_inexistant,
       CASE WHEN d.id IS NOT NULL AND u.agence_id IS NOT NULL AND d.agence_id <> u.agence_id THEN 1 ELSE 0 END AS departement_d_une_autre_agence,
       CASE WHEN s.id IS NOT NULL AND u.departement_id IS NOT NULL AND s.departement_id <> u.departement_id THEN 1 ELSE 0 END AS service_d_un_autre_departement,
       CASE WHEN COALESCE(a.archiver, 0) <> 0 OR COALESCE(d.archiver, 0) <> 0 OR COALESCE(s.archiver, 0) <> 0 THEN 1 ELSE 0 END AS structure_archivee
FROM users u
LEFT JOIN agences a ON a.id = u.agence_id
LEFT JOIN departements d ON d.id = u.departement_id
LEFT JOIN services s ON s.id = u.service_id
WHERE COALESCE(u.bloque, 0) = 0
  AND (   (u.agence_id IS NOT NULL AND a.id IS NULL)
       OR (u.departement_id IS NOT NULL AND d.id IS NULL)
       OR (u.service_id IS NOT NULL AND s.id IS NULL)
       OR (d.id IS NOT NULL AND u.agence_id IS NOT NULL AND d.agence_id <> u.agence_id)
       OR (s.id IS NOT NULL AND u.departement_id IS NOT NULL AND s.departement_id <> u.departement_id)
       OR COALESCE(a.archiver, 0) <> 0 OR COALESCE(d.archiver, 0) <> 0 OR COALESCE(s.archiver, 0) <> 0)
ORDER BY u.id;

-- [D.2] Comptes actifs au rattachement incomplet, avec la lecture du code de main
SELECT u.id,
       CASE WHEN u.agence_id IS NULL AND u.departement_id IS NULL AND u.service_id IS NULL THEN 'aucun'
            ELSE CONCAT('partiel : ', CONCAT_WS(' + ',
                     CASE WHEN u.agence_id IS NOT NULL THEN 'agence' END,
                     CASE WHEN u.departement_id IS NOT NULL THEN 'departement' END,
                     CASE WHEN u.service_id IS NOT NULL THEN 'service' END)) END AS rattachement,
       u.agence_id, u.departement_id, u.service_id,
       CASE WHEN a.nom = 'AGENCE PRINCIPALE SIEGE' THEN 1 ELSE 0 END AS agence_nommee_siege,
       CASE WHEN a.id IS NULL THEN 'sans agence valide : depot de demande impossible'
            WHEN a.nom = 'AGENCE PRINCIPALE SIEGE' AND u.departement_id IS NULL THEN 'siege sans departement : superieur introuvable'
            WHEN a.nom = 'AGENCE PRINCIPALE SIEGE' THEN 'siege : superieur cherche par departement'
            ELSE 'hors siege : superieur cherche par agence' END AS lecture_code_main
FROM users u
LEFT JOIN agences a ON a.id = u.agence_id
WHERE COALESCE(u.bloque, 0) = 0
  AND (u.agence_id IS NULL OR u.departement_id IS NULL OR u.service_id IS NULL)
ORDER BY u.id;

-- [D.3] Effectifs des comptes actifs par nature de rattachement
SELECT CASE WHEN u.agence_id IS NULL AND u.departement_id IS NULL AND u.service_id IS NULL THEN 'aucun'
            WHEN u.agence_id IS NOT NULL AND u.departement_id IS NOT NULL AND u.service_id IS NOT NULL THEN 'complet'
            ELSE CONCAT('partiel : ', CONCAT_WS(' + ',
                     CASE WHEN u.agence_id IS NOT NULL THEN 'agence' END,
                     CASE WHEN u.departement_id IS NOT NULL THEN 'departement' END,
                     CASE WHEN u.service_id IS NOT NULL THEN 'service' END)) END AS rattachement,
       COUNT(*) AS nb_comptes_actifs
FROM users u
WHERE COALESCE(u.bloque, 0) = 0
GROUP BY 1
ORDER BY nb_comptes_actifs DESC, rattachement;


-- =============================================================================
-- 5. CONTRÔLES DE COHÉRENCE (cas à examiner, aucune correction automatique)
-- =============================================================================

-- [5.1] Affectations de rôles orphelines (compte inexistant)
SELECT mhr.model_id AS user_id_inexistant, r.name AS role
FROM model_has_roles mhr
JOIN roles r ON r.id = mhr.role_id
LEFT JOIN users u ON u.id = mhr.model_id
WHERE mhr.model_type LIKE 'App_Models_User'
  AND u.id IS NULL
ORDER BY mhr.model_id, r.name;

-- [5.2] Types de modèles présents dans les tables de liaison Spatie
--       (attendu : App\Models\User seulement)
SELECT 'model_has_roles' AS table_liaison, model_type, COUNT(*) AS nb_lignes FROM model_has_roles GROUP BY model_type
UNION ALL
SELECT 'model_has_permissions', model_type, COUNT(*) FROM model_has_permissions GROUP BY model_type;

-- [5.3] Rôles et permissions dont la garde n'est pas « web » (attendu : vide)
SELECT 'role' AS nature, id, name, guard_name FROM roles WHERE guard_name <> 'web'
UNION ALL
SELECT 'permission', id, name, guard_name FROM permissions WHERE guard_name <> 'web';

-- [5.4] Permissions attribuées DIRECTEMENT à des comptes (hors rôles)
SELECT mhp.model_id AS user_id, p.name AS permission_directe
FROM model_has_permissions mhp
JOIN permissions p ON p.id = mhp.permission_id
WHERE mhp.model_type LIKE 'App_Models_User'
ORDER BY mhp.model_id, p.name;

-- [5.5] Doublons d'affectation (même compte, même rôle). La clé primaire
--       (role_id, model_id, model_type) les interdit : résultat attendu vide ;
--       une ligne signalerait une clé primaire absente en production.
SELECT mhr.model_id AS user_id, mhr.role_id, COUNT(*) AS occurrences
FROM model_has_roles mhr
WHERE mhr.model_type LIKE 'App_Models_User'
GROUP BY mhr.model_id, mhr.role_id
HAVING COUNT(*) > 1;

-- [5.6] Divergences entre l'indicateur users.superieur et le rôle Superieur
--       CAS À EXAMINER. Sur main, l'interface synchronise les deux ; l'indicateur
--       désigne le valideur dans DemandeController::store, le rôle affiche le
--       menu des demandes à valider. Une divergence vient plutôt de l'import ou
--       d'une modification directe. Aucune correction n'est déduite ici.
SELECT u.id,
       COALESCE(u.superieur, 0) AS indicateur_superieur,
       CASE WHEN S.user_id IS NULL THEN 0 ELSE 1 END AS role_superieur,
       CASE WHEN COALESCE(u.bloque, 0) <> 0 THEN 'bloque' ELSE 'actif' END AS statut,
       u.agence_id, u.departement_id,
       CASE WHEN COALESCE(u.superieur, 0) <> 0 THEN 'designe comme superieur, sans le role'
            ELSE 'role Superieur, non designe comme superieur' END AS nature_divergence
FROM users u
LEFT JOIN (
    SELECT DISTINCT mhr.model_id AS user_id
    FROM model_has_roles mhr
    JOIN roles r ON r.id = mhr.role_id
    WHERE mhr.model_type LIKE 'App_Models_User' AND r.name = 'Superieur'
) S ON S.user_id = u.id
WHERE (COALESCE(u.superieur, 0) <> 0 AND S.user_id IS NULL)
   OR (COALESCE(u.superieur, 0) = 0 AND S.user_id IS NOT NULL)
ORDER BY u.id;

-- [5.7] Comptes bloqués détenant encore un rôle administratif (SuperAdmin, Admin)
SELECT u.id, GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ', ') AS roles
FROM users u
JOIN model_has_roles mhr ON mhr.model_id = u.id AND mhr.model_type LIKE 'App_Models_User'
JOIN roles r ON r.id = mhr.role_id
WHERE COALESCE(u.bloque, 0) <> 0
GROUP BY u.id
HAVING SUM(CASE WHEN r.name IN ('SuperAdmin', 'Admin') THEN 1 ELSE 0 END) > 0
ORDER BY u.id;

-- [5.8] ANALYSE SPÉCIFIQUE : comptes SANS RÔLE mais ayant une activité métier
--       Détail par nature d'intervention et dates. Un compte peut intervenir à
--       plusieurs titres sur une même demande : chaque colonne compte les
--       demandes où il tient CE rôle. Le schéma de main ne conserve aucun
--       historique des rôles : la cause (rôle retiré, import, modification
--       directe) ne peut pas être établie par SQL.
SELECT u.id,
       CASE WHEN COALESCE(u.bloque, 0) <> 0 THEN 'bloque' ELSE 'actif' END AS statut,
       u.agence_id, u.departement_id,
       ACT.nb_demandes_creees, ACT.nb_comme_superieur, ACT.nb_comme_gestionnaire,
       ACT.nb_comme_validateur_final, ACT.nb_comme_receptionnaire, ACT.nb_mouvements,
       ACT.premiere_demande_creee_le, ACT.derniere_demande_creee_le, ACT.dernier_mouvement_le,
       DATE(u.updated_at) AS compte_modifie_le
FROM users u
JOIN (
    SELECT x.user_id,
           SUM(x.c) AS nb_demandes_creees, SUM(x.sup) AS nb_comme_superieur, SUM(x.ges) AS nb_comme_gestionnaire,
           SUM(x.vf) AS nb_comme_validateur_final, SUM(x.rec) AS nb_comme_receptionnaire, SUM(x.mvt) AS nb_mouvements,
           MIN(x.premiere) AS premiere_demande_creee_le, MAX(x.derniere) AS derniere_demande_creee_le, MAX(x.dernier_mvt) AS dernier_mouvement_le
    FROM (
        SELECT user_id, COUNT(*) AS c, 0 AS sup, 0 AS ges, 0 AS vf, 0 AS rec, 0 AS mvt,
               MIN(created_at) AS premiere, MAX(created_at) AS derniere, CAST(NULL AS DATETIME) AS dernier_mvt
          FROM demandes WHERE user_id IS NOT NULL GROUP BY user_id
        UNION ALL SELECT superieur_id, 0, COUNT(*), 0, 0, 0, 0, CAST(NULL AS DATETIME), CAST(NULL AS DATETIME), CAST(NULL AS DATETIME)
          FROM demandes WHERE superieur_id IS NOT NULL GROUP BY superieur_id
        UNION ALL SELECT gestionnaire_id, 0, 0, COUNT(*), 0, 0, 0, CAST(NULL AS DATETIME), CAST(NULL AS DATETIME), CAST(NULL AS DATETIME)
          FROM demandes WHERE gestionnaire_id IS NOT NULL GROUP BY gestionnaire_id
        UNION ALL SELECT validateur_final_id, 0, 0, 0, COUNT(*), 0, 0, CAST(NULL AS DATETIME), CAST(NULL AS DATETIME), CAST(NULL AS DATETIME)
          FROM demandes WHERE validateur_final_id IS NOT NULL GROUP BY validateur_final_id
        UNION ALL SELECT reception_par, 0, 0, 0, 0, COUNT(*), 0, CAST(NULL AS DATETIME), CAST(NULL AS DATETIME), CAST(NULL AS DATETIME)
          FROM demandes WHERE reception_par IS NOT NULL GROUP BY reception_par
        UNION ALL SELECT user_id, 0, 0, 0, 0, 0, COUNT(*), CAST(NULL AS DATETIME), CAST(NULL AS DATETIME), MAX(created_at)
          FROM mouvement_stocks WHERE user_id IS NOT NULL GROUP BY user_id
    ) x
    GROUP BY x.user_id
) ACT ON ACT.user_id = u.id
WHERE NOT EXISTS (
    SELECT 1 FROM model_has_roles mhr
    WHERE mhr.model_type LIKE 'App_Models_User' AND mhr.model_id = u.id
)
ORDER BY u.id;

-- [5.9] Entités ayant plusieurs comptes actifs désignés comme supérieur
--       CAS À EXAMINER. DemandeController::store de main prend le premier
--       trouvé, sans ordre défini (et sans exclure les comptes bloqués). Le
--       niveau « agence » compte pour les demandeurs hors siège, le niveau
--       « departement » pour ceux du siège. La liste d'identifiants est courte
--       par construction (quelques supérieurs par entité).
SELECT 'agence' AS niveau, u.agence_id AS entite_id, COUNT(*) AS nb_superieurs_actifs,
       GROUP_CONCAT(u.id ORDER BY u.id SEPARATOR ', ') AS identifiants
FROM users u
WHERE COALESCE(u.superieur, 0) <> 0 AND COALESCE(u.bloque, 0) = 0 AND u.agence_id IS NOT NULL
GROUP BY u.agence_id
HAVING COUNT(*) > 1
UNION ALL
SELECT 'departement', u.departement_id, COUNT(*), GROUP_CONCAT(u.id ORDER BY u.id SEPARATOR ', ')
FROM users u
WHERE COALESCE(u.superieur, 0) <> 0 AND COALESCE(u.bloque, 0) = 0 AND u.departement_id IS NOT NULL
GROUP BY u.departement_id
HAVING COUNT(*) > 1;


-- =============================================================================
-- 6. CHEVAUCHEMENTS ENTRE CATÉGORIES
-- =============================================================================
-- A : sans aucun rôle (tout statut) ;
-- B : actif sans Demandeur (avec ou sans autre rôle) ;
-- C : adresse d'installation ou au moins un indice technique (définition C.1) ;
-- D : actif avec incohérence (D.1) ou rattachement incomplet (D.2).
-- 6.1 donne les effectifs ; 6.2 donne la liste, une ligne par compte (pas de
-- GROUP_CONCAT, donc aucune troncature).

-- [6.1] Effectifs par combinaison de catégories (chaque compte une seule fois)
SELECT CONCAT_WS(' + ',
           CASE WHEN F.cat_a = 1 THEN 'A' END,
           CASE WHEN F.cat_b = 1 THEN 'B' END,
           CASE WHEN F.cat_c = 1 THEN 'C' END,
           CASE WHEN F.cat_d = 1 THEN 'D' END) AS categories,
       COUNT(*) AS nb_comptes
FROM (
    SELECT u.id,
           CASE WHEN R.user_id IS NULL THEN 1 ELSE 0 END AS cat_a,
           CASE WHEN COALESCE(u.bloque, 0) = 0 AND COALESCE(R.a_demandeur, 0) = 0 THEN 1 ELSE 0 END AS cat_b,
           CASE WHEN LOWER(TRIM(u.email)) IN ('superadmin@test.com', 'admin@test.com', 'gestionnaire@test.com', 'supdep@test.com', 'supag@test.com',
                                        'demag@test.com', 'demdep@test.com', 'valfinal@test.com', 'valcom@test.com')
                  OR LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', -1))) IN ('test.com', 'example.com', 'example.org', 'example.net', 'test', 'localhost')
                  OR LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', -1))) LIKE '%.test'
                  OR LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', -1))) LIKE '%.local'
                  OR LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', -1))) LIKE '%.example'
                  OR LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', -1))) LIKE '%.invalid'
                  OR LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', 1)))
                     REGEXP '^(admin|superadmin|administrateur|root|system|systeme|noreply|no-reply|test|demo|robot|service|api|batch|cron|technique)([._0-9-]|$)'
                  OR J.user_id IS NOT NULL
                THEN 1 ELSE 0 END AS cat_c,
           CASE WHEN COALESCE(u.bloque, 0) = 0 AND (
                     u.agence_id IS NULL OR u.departement_id IS NULL OR u.service_id IS NULL
                  OR (u.agence_id IS NOT NULL AND a.id IS NULL)
                  OR (u.departement_id IS NOT NULL AND d.id IS NULL)
                  OR (u.service_id IS NOT NULL AND s.id IS NULL)
                  OR (d.id IS NOT NULL AND u.agence_id IS NOT NULL AND d.agence_id <> u.agence_id)
                  OR (s.id IS NOT NULL AND u.departement_id IS NOT NULL AND s.departement_id <> u.departement_id)
                  OR COALESCE(a.archiver, 0) <> 0 OR COALESCE(d.archiver, 0) <> 0 OR COALESCE(s.archiver, 0) <> 0)
                THEN 1 ELSE 0 END AS cat_d
    FROM users u
    LEFT JOIN agences a ON a.id = u.agence_id
    LEFT JOIN departements d ON d.id = u.departement_id
    LEFT JOIN services s ON s.id = u.service_id
    LEFT JOIN (
        SELECT mhr.model_id AS user_id,
               MAX(CASE WHEN r.name = 'Demandeur' THEN 1 ELSE 0 END) AS a_demandeur
        FROM model_has_roles mhr
        JOIN roles r ON r.id = mhr.role_id
        WHERE mhr.model_type LIKE 'App_Models_User'
        GROUP BY mhr.model_id
    ) R ON R.user_id = u.id
    LEFT JOIN (
        SELECT DISTINCT p.tokenable_id AS user_id
        FROM personal_access_tokens p
        WHERE p.tokenable_type LIKE 'App_Models_User'
    ) J ON J.user_id = u.id
) F
WHERE F.cat_a + F.cat_b + F.cat_c + F.cat_d > 0
GROUP BY 1
ORDER BY nb_comptes DESC, categories;

-- [6.2] Liste des comptes relevant d'au moins une catégorie (une ligne par compte)
SELECT F.id, F.statut, F.cat_a, F.cat_b, F.cat_c, F.cat_d
FROM (
    SELECT u.id,
           CASE WHEN COALESCE(u.bloque, 0) <> 0 THEN 'bloque' ELSE 'actif' END AS statut,
           CASE WHEN R.user_id IS NULL THEN 1 ELSE 0 END AS cat_a,
           CASE WHEN COALESCE(u.bloque, 0) = 0 AND COALESCE(R.a_demandeur, 0) = 0 THEN 1 ELSE 0 END AS cat_b,
           CASE WHEN LOWER(TRIM(u.email)) IN ('superadmin@test.com', 'admin@test.com', 'gestionnaire@test.com', 'supdep@test.com', 'supag@test.com',
                                        'demag@test.com', 'demdep@test.com', 'valfinal@test.com', 'valcom@test.com')
                  OR LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', -1))) IN ('test.com', 'example.com', 'example.org', 'example.net', 'test', 'localhost')
                  OR LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', -1))) LIKE '%.test'
                  OR LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', -1))) LIKE '%.local'
                  OR LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', -1))) LIKE '%.example'
                  OR LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', -1))) LIKE '%.invalid'
                  OR LOWER(TRIM(SUBSTRING_INDEX(u.email, '@', 1)))
                     REGEXP '^(admin|superadmin|administrateur|root|system|systeme|noreply|no-reply|test|demo|robot|service|api|batch|cron|technique)([._0-9-]|$)'
                  OR J.user_id IS NOT NULL
                THEN 1 ELSE 0 END AS cat_c,
           CASE WHEN COALESCE(u.bloque, 0) = 0 AND (
                     u.agence_id IS NULL OR u.departement_id IS NULL OR u.service_id IS NULL
                  OR (u.agence_id IS NOT NULL AND a.id IS NULL)
                  OR (u.departement_id IS NOT NULL AND d.id IS NULL)
                  OR (u.service_id IS NOT NULL AND s.id IS NULL)
                  OR (d.id IS NOT NULL AND u.agence_id IS NOT NULL AND d.agence_id <> u.agence_id)
                  OR (s.id IS NOT NULL AND u.departement_id IS NOT NULL AND s.departement_id <> u.departement_id)
                  OR COALESCE(a.archiver, 0) <> 0 OR COALESCE(d.archiver, 0) <> 0 OR COALESCE(s.archiver, 0) <> 0)
                THEN 1 ELSE 0 END AS cat_d
    FROM users u
    LEFT JOIN agences a ON a.id = u.agence_id
    LEFT JOIN departements d ON d.id = u.departement_id
    LEFT JOIN services s ON s.id = u.service_id
    LEFT JOIN (
        SELECT mhr.model_id AS user_id,
               MAX(CASE WHEN r.name = 'Demandeur' THEN 1 ELSE 0 END) AS a_demandeur
        FROM model_has_roles mhr
        JOIN roles r ON r.id = mhr.role_id
        WHERE mhr.model_type LIKE 'App_Models_User'
        GROUP BY mhr.model_id
    ) R ON R.user_id = u.id
    LEFT JOIN (
        SELECT DISTINCT p.tokenable_id AS user_id
        FROM personal_access_tokens p
        WHERE p.tokenable_type LIKE 'App_Models_User'
    ) J ON J.user_id = u.id
) F
WHERE F.cat_a + F.cat_b + F.cat_c + F.cat_d > 0
ORDER BY F.id;


-- =============================================================================
-- 7. VARIANTE : SI LA COLONNE users.desactive_le EXISTE (0.5 : desactivation = 1)
-- =============================================================================
-- Sans cette colonne, ces requêtes échoueraient : elles sont en commentaire.
-- Ne les décommenter QUE si 0.5 renvoie desactivation = 1 (et, avec des droits
-- par colonne, après avoir accordé SELECT sur users.desactive_le).
-- Les sections B, D et 6 restent inchangées : elles comptent comme « actif »
-- tout compte non bloqué. La requête 7.2 liste les comptes qu'il faut alors
-- retirer de ces résultats, sans modifier les requêtes principales.
--
-- [7.1] Statut à trois états (actif, bloqué, désactivé)
-- SELECT CASE WHEN u.desactive_le IS NOT NULL THEN 'desactive'
--             WHEN COALESCE(u.bloque, 0) <> 0 THEN 'bloque'
--             ELSE 'actif' END AS statut,
--        COUNT(*) AS nb_comptes,
--        SUM(CASE WHEN mhr_any.user_id IS NULL THEN 1 ELSE 0 END) AS dont_sans_aucun_role
-- FROM users u
-- LEFT JOIN (SELECT DISTINCT model_id AS user_id FROM model_has_roles
--            WHERE model_type LIKE 'App_Models_User') mhr_any ON mhr_any.user_id = u.id
-- GROUP BY 1
-- ORDER BY 1;
--
-- [7.2] Comptes non bloqués mais désactivés : à retirer des sections B, D et 6
-- SELECT u.id, DATE(u.desactive_le) AS desactive_le
-- FROM users u
-- WHERE COALESCE(u.bloque, 0) = 0 AND u.desactive_le IS NOT NULL
-- ORDER BY u.id;

-- Fin du script : aucune écriture n'a été demandée au serveur.
