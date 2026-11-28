# GESTOK : notice d'exécution du diagnostic des comptes

**Script :** `database/diagnostics/diagnostic_comptes_production.sql`, version 2, revue après l'audit `docs/AUDIT_SCRIPT_DIAGNOSTIC_COMPTES.md`.

**Nature :** diagnostic **en lecture seule**. Le script contient 27 requêtes `SELECT` et rien d'autre : ni `INTO`, ni `FOR UPDATE`, ni variable, ni procédure, ni fonction à effet de bord.

**État :** **jamais exécuté en production.** Testé uniquement sur des bases locales jetables, avec un jeu de données fictif (voir le rapport d'audit, § 5).

---

## 1. Prérequis

| Élément | Exigence |
|---|---|
| Serveur | MySQL 5.7 ou plus, ou MariaDB 10.2 ou plus. Testé sur MySQL 8.0.46 et MariaDB 10.11.14 ; MySQL 5.7 et MariaDB 10.2 n'ont pas été testés. |
| Client | Client en ligne de commande `mysql` (ou `mariadb`). |
| Compte SQL | Compte **dédié**, limité à `SELECT` sur les **seules colonnes nécessaires** (§ 4.1). |
| Accès | Poste autorisé par l'exploitation à joindre la base de production. |
| Validation préalable | Recommandée : une première exécution sur une copie non productive (préproduction ou sauvegarde restaurée). |

Le script s'appuie sur le schéma des branches `main` et `develop`, qui est identique sur les deux. Il n'exige aucune migration récente. La colonne `users.desactive_le` n'est utilisée que dans la section 7, en commentaire, si la requête 0.5 en confirme la présence.

## 2. Tables et colonnes consultées

| Table | Colonnes lues | Usage |
|---|---|---|
| `users` | `id`, `email`, `agence_id`, `departement_id`, `service_id`, `superieur`, `bloque`, `force_password_change`, `created_at`, `updated_at` | `email` : seul le **domaine** est affiché ; l'adresse complète sert uniquement de comparaison interne |
| `roles` | `id`, `name`, `guard_name` (et horodatages, via 0.3) | |
| `permissions` | `id`, `name`, `guard_name` | |
| `model_has_roles` | `role_id`, `model_type`, `model_id` | |
| `model_has_permissions` | `permission_id`, `model_type`, `model_id` | |
| `agences` | `id`, `nom`, `archiver` | `nom` : reconnaissance du siège (D.2) |
| `departements` | `id`, `agence_id`, `archiver` | |
| `services` | `id`, `departement_id`, `archiver` | |
| `demandes` | `user_id`, `superieur_id`, `gestionnaire_id`, `validateur_final_id`, `reception_par`, `created_at` | Comptages et dates uniquement |
| `mouvement_stocks` | `user_id`, `created_at` | Comptages et dates uniquement |
| `personal_access_tokens` | `tokenable_type`, `tokenable_id`, `last_used_at` | Existence d'un jeton et date de dernier usage |
| `migrations` | `id`, `migration`, `batch` | |
| `information_schema.columns` | Noms et types des colonnes **accordées** | |

**Jamais lues :**
- `users` : `password`, `remember_token`, `name`, `prenom`, `email_verified_at` ;
- `personal_access_tokens` : `token`, `abilities`, `name` ;
- le texte des demandes : objet, description, commentaires.

## 3. Ce que donne chaque section

| Section | Requêtes | Contenu | Nature |
|---|---|---|---|
| 0. Contexte | 0.1 à 0.5 | Base et version, migrations, colonnes visibles, rôles et nombre de comptes existants, migrations récentes (`desactivation`, `type_entite_agences`, `role_consultant`) | Information |
| 1. Vue d'ensemble | 1.1 | Totaux : comptes, bloqués, actifs, sans rôle, avec ou sans Demandeur | Faits |
| **A.** Sans aucun rôle | A.1, A.2 | Liste avec statut, rattachement et activité ; effectifs | **Fait établi** |
| **B.** Actifs sans Demandeur | B.1 à B.3 | Comptes ayant d'autres rôles ; comptes sans rôle ; effectifs par combinaison de rôles | **Situation à examiner, pas une anomalie en soi** |
| **C.** Comptes techniques | C.1, C.2 | Adresse d'installation, quatre indices techniques, éléments de contexte ; répartition des domaines | **Indices, jamais des preuves** |
| **D.** Rattachement des comptes actifs | D.1 à D.3 | D.1 : incohérences de données (fait) ou structure archivée ; D.2 : rattachement incomplet, avec la lecture du code de `main` ; D.3 : effectifs | **D.1 : faits ; D.2 : à interpréter** |
| 5. Cohérence | 5.1 à 5.9 | Affectations orphelines, `model_type`, gardes, permissions directes, doublons, divergences Supérieur, bloqués administrateurs, **sans rôle avec activité**, plusieurs supérieurs dans une entité | **Cas à examiner** |
| 6. Chevauchements | 6.1, 6.2 | Effectifs par combinaison A, B, C, D ; liste d'une ligne par compte | Synthèse |
| 7. Désactivation | 7.1, 7.2 (en commentaire) | Statut à trois états ; comptes désactivés à retirer de B, D et 6 | Variante |

**Règles d'interprétation (rappelées en tête de chaque section du script) :**

- **Catégorie C.** Une adresse d'installation (`superadmin@test.com`, `admin@test.com`) est un **fait**, mais ne prouve **ni** que le compte est technique, **ni** qu'il est inutilisé. Le seeder donne au SuperAdmin le nom d'une personne réelle : ce compte peut être le compte d'administration effectivement utilisé. Les colonnes d'activité et de jeton aident à le savoir. Les indices ne sont jamais des preuves. Le rôle administratif, l'absence d'activité, le mot de passe initial et l'absence de rattachement sont affichés comme **contexte** et ne sont **pas comptés** comme indices.
- **Catégorie B.** L'absence de Demandeur n'est **pas** une anomalie en soi : un compte d'administration, de validation ou de consultation peut légitimement s'en passer.
- **Catégorie D.** Un rattachement partiel n'est **pas** automatiquement incorrect. La colonne `lecture_code_main` ne rapporte que deux conséquences établies dans le code de `main` (`DemandeController::store`) :
  - **sans agence valide :** le dépôt d'une demande est impossible ;
  - **agence « AGENCE PRINCIPALE SIEGE » sans département :** le supérieur est introuvable.

  Les autres lignes disent seulement comment `main` cherche le supérieur.
- **Rôle Supérieur (5.6, 5.9).** Les divergences sont des **cas à examiner** ; aucune correction n'est déduite.
- **Comptes sans rôle avec activité (5.8).** Ils font l'objet d'une **analyse spécifique** : détail par nature d'intervention et dates. Le schéma de `main` ne conserve aucun historique des rôles, donc la cause ne peut pas être établie par SQL.

## 4. Procédure d'exécution

### 4.1 Compte SQL en lecture seule, limité aux colonnes nécessaires

Le compte est à créer par l'administrateur de la base, **pas par l'équipe applicative**. Ces droits ont été **testés localement** : le script complet s'exécute sans erreur avec eux, et toute lecture de `password` ou de `token` comme toute écriture sont refusées.

Remplacer `<base>` et `<hote>`.

```sql
CREATE USER 'gestok_diag'@'<hote>' IDENTIFIED BY '<mot_de_passe_fort>';
GRANT SELECT ON <base>.migrations TO 'gestok_diag'@'<hote>';
GRANT SELECT ON <base>.roles TO 'gestok_diag'@'<hote>';
GRANT SELECT ON <base>.permissions TO 'gestok_diag'@'<hote>';
GRANT SELECT ON <base>.model_has_roles TO 'gestok_diag'@'<hote>';
GRANT SELECT ON <base>.model_has_permissions TO 'gestok_diag'@'<hote>';
GRANT SELECT (id, nom, archiver) ON <base>.agences TO 'gestok_diag'@'<hote>';
GRANT SELECT (id, agence_id, archiver) ON <base>.departements TO 'gestok_diag'@'<hote>';
GRANT SELECT (id, departement_id, archiver) ON <base>.services TO 'gestok_diag'@'<hote>';
GRANT SELECT (id, email, force_password_change, agence_id, departement_id, service_id, superieur, bloque, created_at, updated_at) ON <base>.users TO 'gestok_diag'@'<hote>';
GRANT SELECT (user_id, superieur_id, gestionnaire_id, validateur_final_id, reception_par, created_at) ON <base>.demandes TO 'gestok_diag'@'<hote>';
GRANT SELECT (user_id, created_at) ON <base>.mouvement_stocks TO 'gestok_diag'@'<hote>';
GRANT SELECT (tokenable_type, tokenable_id, last_used_at) ON <base>.personal_access_tokens TO 'gestok_diag'@'<hote>';
```

Règles :
- **aucun autre droit** : pas de droit global, pas de `SELECT` sur toute la base, pas de `INSERT`, `UPDATE`, `DELETE`, `CREATE`, `DROP`, `ALTER`, `FILE`, `PROCESS` ni `SUPER` ;
- avant l'exécution, `SHOW GRANTS FOR CURRENT_USER();` ne doit montrer que `USAGE` et les `SELECT` ci-dessus ;
- avec ces droits, `information_schema` (requête 0.3) ne montre que les colonnes accordées : **c'est normal** ;
- si la requête 0.5 indique `desactivation = 1`, et seulement dans ce cas, ajouter le droit sur la colonne `desactive_le`, puis décommenter la section 7 :
  ```sql
  GRANT SELECT (desactive_le) ON <base>.users TO 'gestok_diag'@'<hote>';
  ```
- **supprimer le compte** après le diagnostic : `DROP USER 'gestok_diag'@'<hote>';`, à exécuter par l'administrateur.

### 4.2 Identifiants de connexion

Les identifiants vont dans un **fichier d'options protégé**, jamais sur la ligne de commande, qui les exposerait dans l'historique et la liste des processus.

```ini
# fichier diag.cnf, droits 600, lisible par l'opérateur seul
[client]
user=gestok_diag
password=<mot_de_passe>
host=<hote>
```

```bash
chmod 600 diag.cnf
```

### 4.3 Exécution

1. **Commencer par les requêtes 0.1 à 0.5**, par exemple dans un outil d'administration : elles disent si la section 7 s'applique.
2. Exécuter le script complet, avec une limite de durée par requête. Sous MySQL (60 secondes) :

```bash
mysql --defaults-extra-file=diag.cnf --table --show-warnings --init-command="SET SESSION max_execution_time=60000" <base> < diagnostic_comptes_production.sql > resultats_diagnostic.txt 2> erreurs_diagnostic.txt
```

Sous MariaDB (60 secondes) :

```bash
mysql --defaults-extra-file=diag.cnf --table --show-warnings --init-command="SET SESSION max_statement_time=60" <base> < diagnostic_comptes_production.sql > resultats_diagnostic.txt 2> erreurs_diagnostic.txt
```

La limite de durée ne concerne que la session de l'opérateur : elle ne modifie aucune donnée. Pour obtenir des colonnes séparées par des tabulations, à ouvrir dans un tableur, remplacer `--table` par `--batch`.

3. Contrôler que `erreurs_diagnostic.txt` est vide et que `resultats_diagnostic.txt` ne contient aucune ligne `Warning`. Un avertissement 1260 signalerait une liste tronquée (§ 4.4).
4. Supprimer `diag.cnf`.

**Durée attendue.** Sur un volume fictif de 5 000 comptes, 150 000 demandes et 150 000 mouvements, la requête la plus lente (5.8) a pris 0,42 seconde et le script entier 1,2 seconde (MySQL 8, local). L'activité est agrégée en un seul passage par colonne : la durée croît avec le volume des tables, sans multiplication par le nombre de comptes. Exécuter tout de même en heures creuses.

**Verrous.** Des `SELECT` simples sur InnoDB, dans le niveau d'isolation par défaut (`REPEATABLE READ`), ne posent aucun verrou sur les lignes. Ne pas passer la session en `SERIALIZABLE`.

### 4.4 Listes concaténées (`GROUP_CONCAT`)

La 6.2 produit une ligne par compte, sans aucune concaténation. `GROUP_CONCAT`, limité par défaut à 1 024 caractères, ne sert qu'à de courtes listes :

| Requête | Liste concaténée | Taille attendue |
|---|---|---|
| B.1, B.3, C.1, 5.7 | Rôles d'un compte | Au plus quelques dizaines de caractères |
| 5.9 | Supérieurs d'une même entité | Quelques identifiants |

Une troncature y est très improbable. Elle serait signalée par l'avertissement 1260, et les comptages resteraient exacts.

## 5. Précautions de sécurité

- **Lecture seule garantie deux fois** : par le contenu du script (27 `SELECT`, contrôle statique dans le rapport d'audit) et par les droits du compte SQL (§ 4.1).
- **Aucun secret produit ni lisible** : les colonnes `password`, `remember_token`, `token` et `abilities` ne sont même pas accordées au compte.
- **Données personnelles minimales** :
  - comptes désignés par `users.id` ;
  - ni nom, ni prénom, ni adresse complète ;
  - domaine affiché en section C seulement ; une valeur sans « @ » s'affiche `(adresse sans @)`, jamais en clair ;
  - aucun texte de demande lu.
- **Pas de copie de la production** vers un poste de développement ni vers une base locale.
- **Conservation des résultats** : dans un emplacement à accès restreint, puis suppression une fois l'analyse terminée. Un domaine porté par un seul compte (C.2) peut être identifiant : ne pas diffuser C.2 hors du cercle de l'analyse.
- **Aucune régularisation** à la suite du diagnostic sans décision explicite : ni attribution ou retrait de rôle, ni déblocage, ni rattachement, ni `gestok:comptes:attribuer-demandeur --appliquer`.
- **Ne pas modifier le script** pour y ajouter des colonnes nominatives. Si un compte doit être identifié, une personne habilitée le fait dans l'interface d'administration.

## 6. Récupération et transmission des résultats

À transmettre pour l'analyse :
- `resultats_diagnostic.txt` (ou `.tsv`) et `erreurs_diagnostic.txt` ;
- la sortie de `SHOW GRANTS FOR CURRENT_USER();`, pour preuve des droits utilisés ;
- en cas d'usage de la section 7, ses résultats séparément.

Les résultats ne contiennent que des identifiants internes, des nombres, des indicateurs `0`/`1`, des dates, des noms de rôles, de permissions et de migrations, et des domaines d'adresse. Ils passent par le canal interne habituel. **Ne jamais y joindre** le fichier d'options ni aucun identifiant de connexion.

### Cas qui demandent une validation humaine

| Requête | Cas | Décision attendue |
|---|---|---|
| C.1 | Adresse d'installation | Compte encore utilisé ? par qui ? à conserver, à renommer ou à bloquer ? |
| C.1 | Indices techniques | Technique ou non ; si technique, déclaration dans `GESTOK_COMPTES_TECHNIQUES` |
| B.1, B.3 | Comptes actifs sans Demandeur | Le rôle est-il utile à ces comptes ? |
| A.1 | Comptes sans rôle sans activité | À conserver, à bloquer ou à compléter ? |
| 5.8 | Comptes sans rôle avec activité | Rôle perdu, import, départ ? Analyse dans l'interface et auprès des responsables |
| D.1 | Incohérences de rattachement | Correction à décider par l'administration |
| D.2 | Rattachements incomplets | Normal pour le poste, ou à compléter ? Priorité aux deux cas où le dépôt de demande est bloqué |
| 5.6, 5.9 | Divergences Supérieur, plusieurs supérieurs | Lequel est le bon valideur ? |
| 5.7 | Bloqués avec un rôle administratif | Retrait du rôle ? |
| 5.1 à 5.5 | Contrôles techniques | Analyse par l'équipe technique |

## 7. Limites connues

1. **Aucune exécution sur la production.** Le script n'a été exécuté que localement (§ 1). La production peut différer : 0.2 à 0.5 permettent de le vérifier. Une colonne absente ferait échouer la seule requête concernée, sans effet sur les données.
2. **Aucun marqueur de compte technique dans le schéma.** La catégorie C repose sur des indices.
3. **« Actif » signifie « non bloqué »**, tant que `desactive_le` n'existe pas. Sinon, appliquer la section 7.
4. **Aucune date de dernière connexion** dans le schéma (sessions en fichiers, pas de colonne dédiée). L'usage d'un compte n'est approché que par son activité métier et ses jetons d'API.
5. **Rattachement attendu non modélisé.** La reconnaissance du siège repose sur le nom exact de l'agence (« AGENCE PRINCIPALE SIEGE »), comme dans `main`. Une agence renommée ne serait pas reconnue.
6. **Aucun historique des rôles** sur `main` : la cause d'une absence de rôle reste à établir humainement.
7. **MySQL 5.7 et MariaDB 10.2 non testés** : compatibilité établie par analyse des fonctions utilisées (rapport d'audit, § 3).
