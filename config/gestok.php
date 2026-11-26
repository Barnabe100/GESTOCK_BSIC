<?php

/*
|--------------------------------------------------------------------------
| Paramètres propres à GESTOK
|--------------------------------------------------------------------------
|
| Les valeurs sensibles sont lues dans le fichier .env du serveur et ne
| doivent jamais être écrites dans le code source (lot 1, L1-5).
|
*/

return [

    /*
    | Mot de passe initial attribué aux comptes importés (UsersImport) et par
    | la commande de rotation gestok:mots-de-passe-exposes. L'utilisateur doit
    | le changer à sa première connexion (force_password_change).
    */
    'mot_de_passe_initial' => env('GESTOK_MOT_DE_PASSE_INITIAL'),

    /*
    | Mot de passe des comptes créés par database/seeders/UserSeeder
    | (installation uniquement). Changement obligatoire à la première connexion.
    */
    'seed_mot_de_passe' => env('GESTOK_SEED_MOT_DE_PASSE'),

];
