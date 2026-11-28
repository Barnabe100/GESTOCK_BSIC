# GESTOK : audit du script SQL de diagnostic des comptes avant exécution

**Date :** 28/11/2026

**Branche :** `feature/consultant-regles-acces` (HEAD `48576b0`, fichiers non commités)

**Objets audités :**
- `database/diagnostics/diagnostic_comptes_production.sql` (version 1 : 23 requêtes) ;
- `docs/NOTICE_EXECUTION_DIAGNOSTIC_COMPTES.md`.

**Résultat :** **version 2** du script (27 requêtes) et notice mises à jour. **Treize anomalies** relevées et corrigées : aucune bloquante, quatre importantes (§ 4).

**Aucune connexion à la production, aucune exécution sur la production, aucune modification de données réelles.** Les tests ont porté sur des bases locales jetables, avec des données fictives (§ 5). La base de test habituelle `gestok_test` n'a pas été utilisée.

---

## 1. Méthode

1. **Schéma de référence revérifié** dans les migrations de `main` et de `develop`. Leurs 18 migrations sont identiques.
2. **Règles métier recherchées dans le code de `main`**, réputé déployé : `LoginRequest`, `DemandeController`, `UserController`, `User`, `UserSeeder`, `UsersImport`, menu latéral.
3. **Relecture de chaque requête** selon les critères de la mission : syntaxe, noms, jointures, comptages, statuts, `NULL`, `GROUP_CONCAT`, performance.
4. **Exécution sur des bases locales jetables :**
   - schéma exact de `main`, sous MySQL 8.0.46 (`ONLY_FULL_GROUP_BY` actif) et sous MariaDB 10.11.14 (`ONLY_FULL_GROUP_BY` ajouté pour le test) ;
   - jeu fictif de 23 comptes, dont chacun illustre un cas ;
   - exécution par un compte local limité aux droits recommandés.
5. **Tests complémentaires :**
   - section 7 sur le schéma de la branche ;
   - performance sur un volume fictif ;
   - contrôle statique de sécurité ;
   - empreinte des tables avant et après exécution.

## 2. Vérification du périmètre

### 2.1 Couverture des catégories

| Catégorie | Version 1 | Version 2 | Couverture |
|---|---|---|---|
| Contexte | 0.1 à 0.4 | 0.1 à **0.5** | 0.5 ajoutée : détection des migrations récentes, utilisable même avec des droits par colonne |
| Vue d'ensemble | 1.1 | 1.1 | Complète |
| A. Sans aucun rôle | A.1, A.2 | A.1, A.2 | Complète ; activité détaillée en 5.8 |
| B. Actifs sans Demandeur | B.1 à B.3 | B.1 à B.3 | Complète ; B.3 détaille désormais les combinaisons de rôles |
| C. Comptes techniques | C.1, C.2 | C.1, C.2 | Restructurée (§ 4, anomalie 2) |
| D. Rattachement | D.1, D.2 | D.1 à **D.3** | Restructurée : incohérences d'un côté, rattachements incomplets de l'autre (anomalie 3) |
| Cohérence | 5.1 à 5.8 | 5.1 à **5.9** | 5.8 enrichie (analyse spécifique) ; 5.9 ajoutée (plusieurs supérieurs dans une entité) |
| Chevauchements | 6.1 | 6.1, **6.2** | 6.2 ajoutée : liste d'une ligne par compte, sans troncature possible |
| Désactivation | 7.1 (en commentaire) | 7.1, **7.2** (en commentaire) | 7.2 remplace la modification manuelle des requêtes principales |

**Total : 27 requêtes exécutables, plus 2 en commentaire.**

### 2.2 Définitions comparées au schéma et aux règles établies

| Définition du script | Source vérifiée | Conformité |
|---|---|---|
| Bloqué = `bloque` non nul et ≠ 0 | `main` : `LoginRequest.php:67`, `if ($user && $user->bloque)` | ✅ v2 (la v1 testait `= 1`) |
| Actif = non bloqué | Seul statut du schéma de `main` | ✅ ; `desactive_le` traitée en section 7 |
| Désactivé = `desactive_le` non nul | Branche : `CompteBloque.php:24`, `ClassementComptes.php:28` | ✅ section 7, testée |
| Rôle reconnu = ligne `model_has_roles` avec le type `App\Models\User` | Spatie, `model_morph_key = model_id` | ✅ ; un autre type n'est pas reconnu par l'application, d'où la 5.2 |
| Adresses d'installation `superadmin@test.com`, `admin@test.com` | `main` : `UserSeeder`, seuls comptes actifs du seeder | ✅ |
| Adresses de démonstration (7) | `main` : `UserSeeder`, bloc en commentaire | ✅ indice ajouté en v2 |
| Activité = demandes créées ou traitées, mouvements | Toutes les références à `users` du schéma de `main` : 5 colonnes de `demandes`, `mouvement_stocks.user_id` (recensement exhaustif) | ✅ |
| Siège = agence nommée « AGENCE PRINCIPALE SIEGE » | `main` : `DemandeController::store`, ligne 313 | ✅ ; dépend du nom exact |
| Supérieur = indicateur `users.superieur` ; menu = rôle `Superieur` | `main` : `DemandeController::store` (`where('superieur', 1)->first()`), `sidebar.blade.php:137` (`@role('Superieur')`) | ✅ |
| Création par l'interface : Demandeur d'office, Supérieur si l'indicateur est coché | `main` : `UserController.php`, lignes 128 à 139 | ✅ contexte cité en B et 5.6 |

Les noms de rôles utilisés (`Demandeur`, `SuperAdmin`, `Admin`, `Superieur`) sont ceux de `RolesAndPermissionsSeeder` sur `main`.

**Fait annexe, constaté pendant l'audit :** sur une base vide, les migrations de `main` échouent. `create_articles_table` référence `type_articles`, qui est créée par une migration postérieure. La production a donc été construite autrement, ou à une autre étape : raison de plus pour lire 0.2 à 0.5 avant d'interpréter les résultats. Je n'ai rien corrigé ; pour le test, les contrôles de clés étrangères ont été suspendus le temps de la construction de la base jetable.

## 3. Vérification SQL, requête par requête

**Légende :** ✅ conforme ; 🔧 corrigé en v2 ; ➕ nouvelle requête.

**Syntaxe :** toutes les requêtes ont été exécutées sans erreur ni avertissement sur MySQL 8.0.46 et MariaDB 10.11.14, avec `ONLY_FULL_GROUP_BY`.

**Fonctions utilisées :** `CAST`, `COALESCE`, `CONCAT`, `CONCAT_WS`, `COUNT`, `DATABASE`, `DATE`, `GROUP_CONCAT`, `LOCATE`, `LOWER`, `MAX`, `MIN`, `NOW`, `SUBSTRING_INDEX`, `SUM`, `TRIM`, `VERSION`, ainsi que `REGEXP`. Elles existent toutes dans MySQL 5.7 et MariaDB 10.2, ainsi que les tables dérivées, `UNION ALL`, `GROUP BY` par position et `information_schema.columns`. **Ces deux versions anciennes n'ont pas été testées.**

| Req. | Jointures et doublons | Comptage | Statut et `NULL` | `GROUP_CONCAT` | Perf.¹ | Verdict |
|---|---|---|---|---|---|---|
| 0.1 | Aucune | — | — | — | 0,03 s | ✅ |
| 0.2 | Aucune | — | — | — | 0,06 s | ✅ |
| 0.3 | Aucune | — | Colonnes sensibles exclues par leur nom | — | 0,04 s | 🔧 alias en minuscules : MySQL 8 renvoie les en-têtes en majuscules |
| 0.4 | `LEFT JOIN users` | `COUNT(DISTINCT u.id)` | — | — | 0,06 s | 🔧 comptait les affectations orphelines (anomalie 6) |
| 0.5 | Aucune | `SUM(CASE…)` | — | — | 0,03 s | ➕ |
| 1.1 | Rôles agrégés par compte avant jointure | Une ligne par compte | 🔧 `COALESCE(bloque, 0)` | — | 0,04 s | ✅ |
| A.1 | Activité agrégée (une ligne par compte) | Sommes par colonne | `NOT EXISTS` : insensible aux `NULL` | — | 0,15 s | 🔧 sous-requêtes corrélées remplacées |
| A.2 | Aucune | `COUNT(*)` sur `users` | 🔧 | — | 0,06 s | ✅ |
| B.1 | Jointure interne sur les rôles agrégés | Une ligne par compte | 🔧 | Rôles d'un compte (court) | 0,05 s | ✅ |
| B.2 | `NOT EXISTS` | — | 🔧 | — | 0,06 s | ✅ |
| B.3 | Rôles agrégés | Effectifs par combinaison | `COALESCE` | Court | 0,07 s | 🔧 détaillée |
| C.1 | Rôles, jetons et activité agrégés séparément | Une ligne par compte | 🔧 `TRIM`, `LOWER` ; adresse sans « @ » | Court | 0,06 s | 🔧 restructurée (anomalies 2, 4, 9, 10) |
| C.2 | Aucune | `COUNT(*)` | 🔧 adresse sans « @ » masquée | — | 0,04 s | 🔧 |
| D.1 | Jointures sur clé primaire (au plus une ligne) | — | `COALESCE(archiver, 0)` | — | 0,04 s | 🔧 incohérences seules |
| D.2 | Jointure sur clé primaire | — | « Sans agence valide » couvre l'agence absente ou inexistante | — | 0,04 s | ➕ (anomalie 3) |
| D.3 | Aucune | `COUNT(*)` | 🔧 | — | 0,04 s | ✅ |
| 5.1 | `LEFT JOIN users` | — | — | — | 0,05 s | ✅ |
| 5.2 | `UNION ALL` de deux agrégats | `COUNT(*)` | — | — | 0,03 s | ✅ |
| 5.3 | `UNION ALL` | — | — | — | 0,04 s | ✅ |
| 5.4 | Jointure sur clé primaire | — | — | — | 0,03 s | ✅ |
| 5.5 | Aucune | `HAVING COUNT(*) > 1` | — | — | 0,03 s | ✅ ; toujours vide tant que la clé primaire existe (précisé) |
| 5.6 | `SELECT DISTINCT` sur le rôle | Une ligne par compte | 🔧 `COALESCE(superieur, 0)` | — | 0,04 s | 🔧 formulée en cas à examiner |
| 5.7 | Jointure groupée par compte | `HAVING` | 🔧 | Court | 0,04 s | ✅ |
| 5.8 | Activité agrégée par compte | Une colonne par nature d'intervention | `MIN`/`MAX` ignorent `NULL` ; 🔧 `NULL` typés | — | 0,42 s | 🔧 analyse spécifique (anomalie 5) |
| 5.9 | `UNION ALL` de deux agrégats | `HAVING COUNT(*) > 1` | `COALESCE` | Quelques identifiants | 0,04 s | ➕ |
| 6.1 | Rôles et jetons agrégés ; structures sur clé primaire | Effectifs par combinaison | 🔧 | 🔧 retiré (anomalie 7) | 0,09 s | 🔧 |
| 6.2 | Identiques à 6.1 | Une ligne par compte | 🔧 | Aucun | 0,11 s | ➕ |
| 7.1, 7.2 | `SELECT DISTINCT` sur les rôles | Une ligne par statut ou par compte | Priorité désactivé, puis bloqué | — | — | 🔧 testées sur le schéma de la branche |

¹ Durée mesurée sur le volume fictif décrit au § 5.4 (MySQL 8 local). Le script entier a pris 1,2 seconde.

### Points particuliers

- **Doublons.** Aucune jointure ne multiplie les lignes : les rôles, l'activité et les jetons sont agrégés par compte avant d'être joints (`GROUP BY` ou `SELECT DISTINCT`), et les structures sont jointes sur leur clé primaire. Une intervention multiple sur une même demande (par exemple gestionnaire et réceptionnaire) est comptée dans chacune des colonnes concernées, ce que la 5.8 précise. La v1 additionnait ces cas dans une seule colonne.
- **Valeurs `NULL`.**
  - Les absences de rôle et d'activité sont testées par `NOT EXISTS` ou par une jointure externe sur un agrégat : jamais par `NOT IN`, sensible aux `NULL`.
  - Les colonnes de rattachement `NULL` sont traitées explicitement.
  - Les colonnes `NOT NULL` du schéma (`bloque`, `superieur`, `archiver`, `force_password_change`) sont protégées par `COALESCE` au cas où la production différerait.
  - `demandes.user_id` est `NOT NULL` ; les quatre autres références et `mouvement_stocks.user_id` sont filtrées par `IS NOT NULL`.
- **`GROUP_CONCAT`.** Il ne reste que des listes courtes (§ 4.4 de la notice). La seule liste potentiellement longue de la v1 (identifiants en 6.1) est remplacée par la 6.2. Une troncature produirait l'avertissement 1260, visible avec `--show-warnings`.
- **Performance.** Les tests corrélés de la v1, avec un `OR` sur cinq colonnes, ne peuvent utiliser aucun index : `EXPLAIN` montre `type = ALL`, donc un parcours complet de `demandes` pour chaque compte examiné. Sur le volume fictif, ce seul test prenait 2,9 secondes. Son coût croît avec le produit comptes × demandes, et il est le plus élevé pour les comptes sans activité, ceux justement visés. La v2 agrège en un passage par colonne : coût proportionnel au volume des tables (0,42 seconde pour la 5.8, la plus lourde).

## 4. Anomalies trouvées et corrections

| N° | Gravité | Anomalie (v1) | Correction (v2) |
|---|---|---|---|
| 1 | Importante | **Droits trop larges :** la notice recommandait `SELECT` sur toute la base, ce qui rend `password`, `remember_token` et `token` lisibles par erreur | Droits par table et par colonne, limités à celles du script ; testés (§ 5.3) |
| 2 | Importante | **Catégorie C :** le compte d'installation était présenté comme « fait établi » de compte technique ; le rôle administratif, l'absence d'activité, le mot de passe initial et l'absence de rattachement étaient additionnés aux indices, ce qui faisait de presque tout compte inactif un « suspect » | Le fait se limite à « adresse identique à celle du seeder », sans conclusion. Quatre indices techniques comptés ; éléments de contexte affichés mais non comptés. Colonnes d'usage ajoutées : dernière demande, dernier mouvement, dernier usage du jeton. Indice « adresse de démonstration du seeder » ajouté |
| 3 | Importante | **Catégorie D :** les rattachements partiels, souvent normaux, étaient mélangés aux vraies incohérences | D.1 : incohérences de données et structures archivées. D.2 : rattachements incomplets, avec la seule lecture établie dans le code de `main`. D.3 : effectifs |
| 4 | Importante | **Confidentialité :** une valeur d'adresse sans « @ » aurait été affichée en entier comme « domaine » | Affichage `(adresse sans @)` (testé) |
| 5 | Moyenne | **5.8 insuffisante** pour une analyse spécifique : deux comptages globaux seulement | Détail par nature d'intervention, première et dernière dates, date de modification du compte, statut, rattachement ; limite rappelée (aucun historique des rôles sur `main`) |
| 6 | Moyenne | **0.4 incohérente avec 1.1 :** les affectations orphelines étaient comptées (12 au lieu de 11 sur le jeu fictif) | Comptage des seuls comptes existants |
| 7 | Moyenne | **6.1 :** liste d'identifiants par `GROUP_CONCAT`, tronquée au-delà de 1 024 caractères ; la notice proposait une commande `SET`, hors du principe « `SELECT` seulement » | Identifiants retirés de 6.1 ; 6.2 donne une ligne par compte ; recommandation `SET` supprimée |
| 8 | Moyenne | **Performance :** activité calculée par sous-requêtes corrélées non indexables (§ 3) | Agrégation préalable en un passage par colonne |
| 9 | Mineure | **Statuts :** `bloque = 1` au lieu du test réellement appliqué par l'application (valeur non nulle) | `COALESCE(bloque, 0) <> 0` partout ; même principe pour `superieur`, `archiver`, `force_password_change` |
| 10 | Mineure | **Comparaison d'adresses :** sensible aux espaces en début ou en fin | `LOWER(TRIM(...))` ; testé avec « ` Admin@Test.COM ` » |
| 11 | Mineure | **Avertissements MySQL 8** (code 1292, conversion de date) dans les `UNION ALL` d'activité ; résultats justes, mais sortie bruitée | `CAST(NULL AS DATETIME)` ; zéro avertissement |
| 12 | Mineure | **Section 7 :** il fallait modifier à la main plusieurs requêtes principales, source d'erreur ; et sous des droits par colonne, la 0.3 ne montre pas `desactive_le` | 7.2 liste les comptes à retirer de B, D et 6, sans rien modifier ; détection par 0.5 (table `migrations`) ; droit à ajouter indiqué dans la notice |
| 13 | Mineure | **Contexte métier absent :** B ne disait pas qu'une absence de Demandeur n'est pas une anomalie ; 5.6 ne disait pas que la divergence est un cas à examiner | Commentaires et colonne `nature_divergence` ; 5.9 ajoutée |

**Aucune anomalie de nom de table, de colonne ou de relation** n'a été trouvée : tous les noms de la v1 existent dans le schéma de `main`.

## 5. Tests

### 5.1 Environnement

| Base locale jetable | Moteur | Schéma | Usage |
|---|---|---|---|
| `gestok_audit_diag` | MySQL 8.0.46 et MariaDB 10.11.14 | 18 migrations exactes de `main` | Jeu fictif, contrôle des résultats |
| `gestok_audit_diag_v2` | MySQL 8.0.46 | 24 migrations de la branche | Section 7 (`desactive_le`) |
| `gestok_audit_perf` | MySQL 8.0.46 | Tables de `main`, avec leurs index | Volume fictif |

Ces bases, le compte local `gestok_diag` et les fichiers temporaires ont été **supprimés à la fin de l'audit**. Aucune donnée réelle n'a été utilisée.

### 5.2 Jeu fictif et résultats

Le jeu fictif compte 23 comptes. Chacun illustre un cas, avec des valeurs factices de `password`, `remember_token` et `token`, qui ne doivent jamais apparaître dans les résultats.

| Contrôle | Attendu | Obtenu |
|---|---|---|
| 1.1 | 23 comptes, 2 bloqués, 21 actifs, 4 sans rôle, 13 avec Demandeur, 6 avec d'autres rôles seulement | ✅ identique |
| A.1 | Comptes 6, 7 (bloqué), 8, 19 (rôle enregistré sous `App\User`, donc non reconnu) | ✅ ; activités : 7 = 2 demandes ; 8 = 3 interventions et 1 mouvement |
| B.1 | 1, 2, 9, 14, 16 ; le 15, bloqué, est exclu | ✅ |
| C.1 | 1, 2, 23 (adresse d'installation, y compris avec espaces et majuscules) ; 14 (`robot@` et jeton, dernier usage affiché) ; 15 (`noreply@example.com`) ; 16 (adresse de démonstration) ; 17 (`Admin.RH@`, **faux positif type** du critère de nom) | ✅ ; `administration.generale@` n'est **pas** retenu, comme prévu |
| C.2 | Domaines seulement ; la valeur sans « @ » est masquée | ✅ |
| D.1 | 9 (département d'une autre agence), 10 (service d'un autre département), 11 (agence archivée), 18 (agence inexistante) | ✅ |
| D.2 | 5 (siège sans département : supérieur introuvable), 6 et 18 (sans agence valide), 4, 9 et 11 (hors siège) | ✅ |
| 5.1 à 5.4 | Affectation orpheline (id 500) ; types `App\Models\User` et `App\User` ; garde `api` ; permission directe du 20 | ✅ |
| 5.6 | 12 (indicateur sans rôle), 13 (rôle sans indicateur) | ✅ |
| 5.7 | 15 | ✅ |
| 5.8 | 7 et 8, avec le détail par nature d'intervention | ✅ |
| 5.9 | Agence 2 : 4, 12, 20 ; département 2 : 12, 20 | ✅ |
| 6.1 et 6.2 | 17 comptes ; effectifs D = 5, B + C = 4, C = 3, A + B = 2, A = 1, A + B + D = 1, B + D = 1 | ✅ ; total cohérent avec la 6.2 |
| 7.1 et 7.2 (schéma branche, 3 comptes désactivés dont un bloqué) | Actifs 17, bloqués 1, désactivés 3 ; à retirer : 8 et 9 | ✅ |

**Les deux moteurs donnent des résultats strictement identiques**, à la largeur de la colonne de version près. **Zéro erreur, zéro avertissement.**

### 5.3 Sécurité, vérifiée par exécution

| Contrôle | Résultat |
|---|---|
| Contrôle statique, chaînes littérales exclues | 27 instructions, toutes `SELECT` ; aucun mot-clé ni aucune fonction à effet de bord (`INSERT`, `UPDATE`, `DELETE`, `SET`, `INTO`, `OUTFILE`, `FOR UPDATE`, `LOCK`, `SLEEP`, `BENCHMARK`, `LOAD_FILE`, `GET_LOCK`, `CALL`, `PREPARE`…) ; aucune variable ; aucun commentaire exécutable `/*!` |
| Colonnes sensibles | Absentes du code ; leurs noms n'apparaissent que dans la liste d'exclusion de 0.3 |
| Adresse complète | Utilisée seulement dans des comparaisons (`WHERE`, `CASE`) et pour en extraire le domaine |
| Script complet sous les droits recommandés | Exécuté sans erreur, sur les deux moteurs |
| Avec ces droits : `SELECT password`, `SELECT *`, `SELECT token`, `SELECT description` | Refusés (erreurs 1143 et 1142) |
| Avec ces droits : `UPDATE users`, `DELETE FROM model_has_roles`, lecture d'une table non nécessaire | Refusés (erreur 1142) |
| Empreinte (`CHECKSUM TABLE`) des 12 tables avant et après exécution | Identique, sur les deux moteurs |
| Recherche des valeurs factices et des adresses complètes dans les sorties | Aucune occurrence |

### 5.4 Volume fictif

Le volume fictif compte 5 000 comptes, 6 166 affectations de rôles, 150 000 demandes et 150 000 mouvements.

| Mesure | Résultat |
|---|---|
| Script entier | 1,2 seconde |
| Requête la plus lente (5.8) | 0,42 seconde |
| Test d'activité de la v1 | 2,9 secondes (sous-requête en parcours complet de `demandes`) |

### 5.5 Limites des tests

- **Données fictives :** elles vérifient la logique, pas la répartition réelle de la production.
- **MySQL 5.7 et MariaDB 10.2 non disponibles :** compatibilité établie par analyse seulement (§ 3).
- **Schéma de production supposé conforme à `main` :** les requêtes 0.2 à 0.5 permettent de le constater avant toute interprétation.

## 6. Vérification des règles métier

| Règle demandée | Traitement dans la v2 |
|---|---|
| Comptes de `UserSeeder` non considérés d'office comme techniques encore utilisés | `adresse_installation_seeder` est un fait sans conclusion ; le commentaire de C.1 et la notice rappellent que le SuperAdmin du seeder porte le nom d'une personne réelle et peut être le compte d'administration en service ; colonnes d'usage fournies ; **décision humaine** |
| Indices C jamais présentés comme des preuves | En-tête « Rien ici n'est une preuve » ; contexte séparé des indices ; faux positif type visible sur le jeu fictif (`Admin.RH@`) et cité dans le rapport |
| Absence de Demandeur non déclarée en anomalie | En-tête « Situation à examiner, pas une anomalie en soi » ; B.3 donne les combinaisons de rôles pour juger par profil |
| Rattachement partiel non considéré d'office comme incorrect | D.2 séparée de D.1 ; seules deux conséquences établies dans le code de `main` sont nommées, les autres lignes décrivent le comportement sans juger |
| Divergences Supérieur présentées comme des cas à examiner, sans correction | 5.6 (« cas à examiner », `nature_divergence`) et 5.9 ; aucune correction suggérée par le script |
| Comptes sans rôle avec activité : analyse spécifique | 5.8 détaillée, limite de traçabilité rappelée ; ligne dédiée dans le tableau des validations humaines de la notice |

## 7. Points qui restent à valider

| N° | Point | Par qui |
|---|---|---|
| 1 | Le code déployé en production est-il bien celui de `main` ? Les lectures « code de main » (D.2, 5.6, 5.9) en dépendent | Exploitation |
| 2 | Version exacte du serveur de production ; tests en MySQL 5.7 ou MariaDB 10.2 si c'est le cas | Exploitation |
| 3 | Création du compte `gestok_diag` avec les droits du § 4.1 de la notice, puis suppression après usage | Administrateur de la base |
| 4 | Nom exact de l'agence siège en production (« AGENCE PRINCIPALE SIEGE ») | Métier ou administration |
| 5 | Liste des termes de `ind_nom_technique` et des domaines de test : à compléter avec les conventions internes de nommage des boîtes techniques | Métier ou administration |
| 6 | Première exécution sur une copie non productive, recommandée | Exploitation |
| 7 | Toutes les décisions listées au § 6 de la notice, après exécution | Responsables métier |

## 8. Confirmations

- **Aucune connexion à la production ni exécution sur la production.**
- **Aucune modification de données réelles.** Les seules écritures ont visé les bases locales jetables créées pour l'audit (schéma, jeu fictif, préparation de la section 7 sur la base de la branche), qui ont ensuite été supprimées. Il en va de même pour le compte local `gestok_diag`. La base `gestok_test` n'a pas été touchée.
- **Aucune attribution ni suppression de rôle**, aucune migration ajoutée au dépôt, aucune commande de régularisation.
- **Aucun commit, push, merge ou déploiement.** Les trois fichiers restent non suivis dans la branche `feature/consultant-regles-acces` (HEAD `48576b0`).
