# Tests GESTOK

## Avertissement

Les tests utilisent `RefreshDatabase` : **ils suppriment et recréent toutes les tables
de la base ciblée**. Ne jamais lancer les tests sur un serveur de production.

## Protections en place

1. `phpunit.xml` force `DB_CONNECTION=mysql` et `DB_DATABASE=gestok_test`.
2. `tests/CreatesApplication.php` appelle `Tests\Support\GardeBaseDeTest` avant toute
   migration : les tests s'arrêtent si `APP_ENV` ne vaut pas `testing` ou si le nom de
   la base ne se termine pas par `_test`.

## Mise en place (poste de développement ou serveur de recette)

1. Créer une base dédiée et un compte limité à cette base :

   ```sql
   CREATE DATABASE gestok_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'gestok_test'@'localhost' IDENTIFIED BY '<mot de passe local>';
   GRANT ALL ON gestok_test.* TO 'gestok_test'@'localhost';
   ```

2. Créer un fichier `.env.testing` (non versionné) contenant au minimum :

   ```dotenv
   APP_ENV=testing
   APP_KEY=base64:...        # php artisan key:generate --show
   DB_HOST=127.0.0.1
   DB_USERNAME=gestok_test
   DB_PASSWORD=<mot de passe local>
   ```

3. Lancer les tests :

   ```bash
   php artisan test
   ```

## Migrations (lot 0)

La clé étrangère `articles.type_article_id -> type_articles.id` était déclarée dans la
migration `2026_04_14_173341_create_articles_table`, exécutée **avant** la création de
`type_articles` (`2026_07_28_235701`). `migrate` échouait donc sur une base vierge.

La contrainte est désormais posée par `2026_07_28_235701_create_type_articles_table`,
uniquement si elle n'existe pas déjà. Le schéma final est identique (même colonne,
même nom de contrainte `articles_type_article_id_foreign`, même `ON DELETE CASCADE`).

Ces deux migrations figurent déjà dans la table `migrations` des bases existantes :
elles ne sont **pas** rejouées par `php artisan migrate`. Aucun effet sur la production.
