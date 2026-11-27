<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DepartementController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\AgenceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\DemandeController;
use App\Http\Controllers\ArticleDemandeController;
use App\Http\Controllers\ArticleCommandeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Etats\EtatDemandeController;
use App\Http\Controllers\Etats\EtatArticleController;
use App\Http\Controllers\MouvementStockController;
use App\Http\Controllers\EtatStockController;
use App\Http\Controllers\Etats\EtatMouvementStockController;
use App\Http\Controllers\TypeArticleController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

//Route::get('/', function () {
//    return view('welcome');
//});

// Lot 1, L1-3 : suppression de la route « /dashbord » (accessible sans
// authentification) et du doublon « /login » (la route de connexion est
// déclarée dans routes/auth.php et rend la même vue auth.login).

// Inscription publique fermée (lot 1, L1-2).



//Route::get('/', [AuthenticatedSessionController::class, 'store'])->name('home');

//Route::get('/dashboard', function () {
//    return view('dashboard');
//})->middleware(['auth', 'verified'])->name('dashboard');

// Route::middleware('auth')->group(function () {});


Route::middleware('auth')->group(function () {

    Route::get(
        '/force-password-change',
        [App\Http\Controllers\ForcePasswordController::class, 'show']
    )->name('force.password.change');

    Route::post(
        '/force-password-change',
        [App\Http\Controllers\ForcePasswordController::class, 'update']
    )->name('force.password.update');
});

Route::middleware([
    'auth',
    'force.password.change'
])->group(function () {

    Route::resource('categories', CategorieController::class)
        ->middleware('can:gerer-stock');

    Route::resource('articles', ArticleController::class)
        ->middleware('can:gerer-stock');

    Route::get('/mouvements-stock', [MouvementStockController::class, 'index'])
        ->name(
            'mouvements.index'
        )
        ->middleware('can:voir-mouvements');


    Route::resource('users', UserController::class);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get(
        '/mon-profil',
        function () {

            return view(
                'users.show',
                [
                    'user' => auth()->user()
                ]
            );
        }
    )->middleware('auth')
        ->name('mon.profil');

    Route::get(
        '/users-import',
        [UserController::class, 'importForm']
    )->name('users.import.form');

    Route::post(
        '/users-import',
        [UserController::class, 'import']
    )->name('users.import');

    // Lecture (liste, fiche) : voir-commandes (rôles du module ou Consultant) ;
    // création, modification et archivage restent sous les capacités
    // d'opération du contrôleur (preparer-commandes, supprimer-commandes).
    Route::resource('commandes', CommandeController::class)
        ->middleware('can:voir-commandes');

    Route::put(
        '/article-commandes/{id}',
        [ArticleCommandeController::class, 'update']
    )->name('article-commandes.update')
        ->middleware('can:preparer-commandes');


    Route::resource('fournisseurs', FournisseurController::class)
        ->middleware('can:gerer-fournisseurs');
    Route::resource('demandes', DemandeController::class);

    Route::put(
        '/article-demandes/{id}',
        [ArticleDemandeController::class, 'update']
    )->name('article-demandes.update');

    Route::get('/mes-demandes', [DemandeController::class, 'mesDemandes'])->name('mes.demandes');
    Route::put('demandes/{id}/valider', [DemandeController::class, 'valider'])->name('demandes.valider');
    Route::post('demandes/{id}/livrer', [DemandeController::class, 'livrer'])->name('demandes.livrer');

    // DEMANDES SUPERIEUR
    Route::get('/demandes-a-valider/', [DemandeController::class, 'demandesSuperieur'])
        ->name('demandes.superieur')
        ->middleware('can:lister-demandes-superieur');
    // VALIDER
    Route::put('/demandes/{id}/valider-superieur', [DemandeController::class, 'validerSuperieur'])
        ->name('demandes.valider.superieur');
    // REJETER
    Route::put('/demandes/{id}/rejeter-superieur', [DemandeController::class, 'rejeterSuperieur'])
        ->name('demandes.rejeter.superieur');
    //Rejet Gestionnaire
    Route::put('/demandes/{id}/rejeter-gestionnaire', [DemandeController::class, 'rejeterGestionnaire'])
        ->name('demandes.rejeter.gestionnaire');

    Route::get('/demandes-gestionnaire', [DemandeController::class, 'indexGestionnaire'])
        ->name('demandes.gestionnaire')
        ->middleware('can:lister-demandes-gestionnaire');

    //Validation demandeur
    Route::put('/demandes/{id}/valider-demandeur', [DemandeController::class, 'validerDemandeur'])
        ->name(
            'demandes.valider.demandeur'
        );
    //Validation finale
    Route::get('/demandes-validation-finale', [DemandeController::class, 'validationFinaleIndex'])
        ->name(
            'demandes.validation.finale.index'
        )
        ->middleware('can:lister-validation-finale');

    Route::put('/demandes/{id}/rejet-finale', [DemandeController::class, 'rejeterFinale'])
        ->name(
            'demandes.rejet.finale'
        );
    // Action validation
    Route::put('/demandes/{id}/validation-finale', [DemandeController::class, 'validationFinale'])
        ->name(
            'demandes.validation.finale'
        );
    //Confirmation de la reception
    Route::put('/demandes/{id}/confirmer-reception', [DemandeController::class, 'confirmerReception'])
        ->name(
            'demandes.confirmer.reception'
        );
    //Soumettre demande
    Route::put('/demandes/{id}/soumettre', [DemandeController::class, 'soumettre'])
        ->name('demandes.soumettre');
    //Demandes collegues
    Route::get(
        '/demandes-entite',
        [DemandeController::class, 'demandesOrganisation']
    )->name('demandes.organisation');


    Route::resource('departements', DepartementController::class)
        ->middleware('can:gerer-departement');

    Route::get(
        '/departements/{id}/services',
        [DepartementController::class, 'services']
    );

    Route::get(
        '/departements/par-agence/{agence}',
        [UserController::class, 'getDepartementsParAgence']
    );

    Route::get(
        '/services/par-departement/{departement}',
        [UserController::class, 'getServicesParDepartement']
    );


    Route::resource('type-articles', TypeArticleController::class)
        ->middleware('can:gerer-stock');

    Route::get(
        'type-articles/export/excel',
        [TypeArticleController::class, 'exportExcel']
    )->name('type-articles.export.excel')
        ->middleware('can:gerer-stock');

    Route::get(
        'type-articles/export/csv',
        [TypeArticleController::class, 'exportCsv']
    )->name('type-articles.export.csv')
        ->middleware('can:gerer-stock');

    Route::resource('services', ServiceController::class)
        ->middleware('can:gerer-service');
    Route::resource('agences', AgenceController::class)
        ->middleware('can:gerer-agence');
    // N03 : changement de siège (transaction unique, historique)
    Route::patch('/agences/{id}/designer-siege', [AgenceController::class, 'designerSiege'])
        ->name('agences.designer.siege')
        ->middleware('can:gerer-agence');
    Route::resource('users', UserController::class);

    // Blocage, déblocage, réactivation : formulaires PATCH protégés par le
    // jeton CSRF (les anciens liens GET n'existent plus).
    Route::patch(
        '/users/{id}/bloquer',
        [UserController::class, 'bloquer']
    )->name('users.bloquer');

    Route::patch(
        '/users/{id}/debloquer',
        [UserController::class, 'debloquer']
    )->name('users.debloquer');

    Route::patch(
        '/users/{id}/reactiver',
        [UserController::class, 'reactiver']
    )->name('users.reactiver');

    // Q3 : remplaçants temporaires de validation supérieure (autre entité)
    Route::get('/interims-validation', [\App\Http\Controllers\InterimValidationController::class, 'index'])
        ->name('interims.index')
        ->middleware('can:gerer-utilisateurs');
    Route::post('/interims-validation', [\App\Http\Controllers\InterimValidationController::class, 'store'])
        ->name('interims.store')
        ->middleware('can:gerer-utilisateurs');
    Route::patch('/interims-validation/{id}/revoquer', [\App\Http\Controllers\InterimValidationController::class, 'revoquer'])
        ->name('interims.revoquer')
        ->middleware('can:gerer-utilisateurs');

    //Artcile demande
    Route::post('article-demande', [ArticleDemandeController::class, 'store'])
        ->name('article_demande.store');

    Route::delete('article-demande/{id}', [ArticleDemandeController::class, 'destroy'])
        ->name('article_demande.destroy');

    Route::post('commandes/{id}/valider', [CommandeController::class, 'valider'])
        ->name('commandes.valider')
        ->middleware('can:valider-commandes');

    Route::put('commandes/{id}/livrer', [CommandeController::class, 'livrer'])
        ->name('commandes.livrer')
        ->middleware('can:receptionner-commandes');

    Route::post('article-commande', [ArticleCommandeController::class, 'store'])
        ->name('article_commande.store')
        ->middleware('can:preparer-commandes');

    Route::delete('article-commande/{id}', [ArticleCommandeController::class, 'destroy'])
        ->name('article_commande.destroy')
        ->middleware('can:preparer-commandes');

    Route::put('/articles/{id}/rebut', [ArticleController::class, 'miseRebut'])
        ->name(
            'articles.rebut'
        )
        ->middleware('can:gerer-stock');


    //Export Categories
    Route::get('categories-export-excel', [CategorieController::class, 'exportExcel'])
        ->name('categories.export.excel')
        ->middleware('can:gerer-stock');
    Route::get('categories-export-csv', [CategorieController::class, 'exportCsv'])
        ->name('categories.export.csv')
        ->middleware('can:gerer-stock');
    //Export Agences
    Route::get('agences-export-excel', [AgenceController::class, 'exportExcel'])
        ->name('agences.export.excel')
        ->middleware('can:gerer-agence');
    Route::get('agences-export-csv', [AgenceController::class, 'exportCsv'])
        ->name('agences.export.csv')
        ->middleware('can:gerer-agence');
    //Export Articles
    Route::get('articles-export-excel', [ArticleController::class, 'exportExcel'])
        ->name('articles.export.excel')
        ->middleware('can:gerer-stock');
    Route::get('articles-export-csv', [ArticleController::class, 'exportCsv'])
        ->name('articles.export.csv')
        ->middleware('can:gerer-stock');
    //Export demandes
    Route::get('demandes-export-excel', [DemandeController::class, 'exportExcel'])
        ->name('demandes.export.excel')
        ->middleware('can:exporter-demandes');
    Route::get('demandes-export-csv', [DemandeController::class, 'exportCsv'])
        ->name('demandes.export.csv')
        ->middleware('can:exporter-demandes');
    //Export commandes
    Route::get('commandes-export-excel', [CommandeController::class, 'exportExcel'])
        ->name('commandes.export.excel')
        ->middleware('can:acceder-commandes');
    Route::get('commandes-export-csv', [CommandeController::class, 'exportCsv'])
        ->name('commandes.export.csv')
        ->middleware('can:acceder-commandes');

    // Décision 3.6 : soumission réservée au Gestionnaire.
    Route::put('/commandes/{id}/soumettre', [CommandeController::class, 'soumettre'])
        ->name('commandes.soumettre')
        ->middleware('can:soumettre-commandes');
    //Export fournisseurs
    Route::get('fournisseurs-export-excel', [FournisseurController::class, 'exportExcel'])
        ->name('fournisseurs.export.excel')
        ->middleware('can:gerer-fournisseurs');
    Route::get('fournisseurs-export-csv', [FournisseurController::class, 'exportCsv'])
        ->name('fournisseurs.export.csv')
        ->middleware('can:gerer-fournisseurs');
    //Export departements
    Route::get('departements-export-excel', [DepartementController::class, 'exportExcel'])
        ->name('departements.export.excel')
        ->middleware('can:gerer-departement');
    Route::get('departements-export-csv', [DepartementController::class, 'exportCsv'])
        ->name('departements.export.csv')
        ->middleware('can:gerer-departement');
    //Export services
    Route::get('services-export-excel', [ServiceController::class, 'exportExcel'])
        ->name('services.export.excel')
        ->middleware('can:gerer-service');
    Route::get('services-export-csv', [ServiceController::class, 'exportCsv'])
        ->name('services.export.csv')
        ->middleware('can:gerer-service');
    //Export users
    Route::get('users-export-excel', [UserController::class, 'exportExcel'])
        ->name('users.export.excel');
    Route::get('users-export-csv', [UserController::class, 'exportCsv'])
        ->name('users.export.csv');

    //Export Articles demandes
    Route::get('articles-demandes-export-excel', [ArticleDemandeController::class, 'exportExcel'])
        ->name('articles-demandes.export.excel')
        ->middleware('can:exporter-demandes');
    Route::get('articles-demandes-export-csv', [ArticleDemandeController::class, 'exportCsv'])
        ->name('articles-demandes.export.csv')
        ->middleware('can:exporter-demandes');

    //Export Articles Commandes
    Route::get('articles-commandes-export-excel', [ArticleCommandeController::class, 'exportExcel'])
        ->name('articles-commandes.export.excel')
        ->middleware('can:acceder-commandes');
    Route::get('articles-commandes-export-csv', [ArticleCommandeController::class, 'exportCsv'])
        ->name('articles-commandes.export.csv')
        ->middleware('can:acceder-commandes');

    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    )->middleware(['auth'])->name('dashboard');


    //Etats
    Route::get(
        '/etats',
        [EtatDemandeController::class, 'index']
    )->name('etats.index')
        ->middleware('can:voir-etats');

    Route::get(
        'etats/demandes/par-agence/excel',
        [EtatDemandeController::class, 'demandesParAgenceExcel']
    )->name('etats.demandes.par_agence.excel')
        ->middleware('can:voir-etats');

    // PAR AGENCE PDF
    Route::get(
        'etats/demandes/par-agence/pdf',
        [EtatDemandeController::class, 'demandesParAgencePdf']
    )->name('etats.demandes.par_agence.pdf')
        ->middleware('can:voir-etats');

    // VALIDÉES EXCEL
    Route::get(
        'etats/demandes/validees/excel',
        [EtatDemandeController::class, 'demandesValideesExcel']
    )->name('etats.demandes.validees.excel')
        ->middleware('can:voir-etats');

    // VALIDÉES PDF
    Route::get(
        'etats/demandes/validees/pdf',
        [EtatDemandeController::class, 'demandesValideesPdf']
    )->name('etats.demandes.validees.pdf')
        ->middleware('can:voir-etats');

    // EXCEL
    Route::get(
        'etats/demandes/par-service/excel',
        [EtatDemandeController::class, 'demandesParServiceExcel']
    )->name('etats.demandes.par_service.excel')
        ->middleware('can:voir-etats');

    //  PDF
    Route::get(
        'etats/demandes/par-service/pdf',
        [EtatDemandeController::class, 'demandesParServicePdf']
    )->name('etats.demandes.par_service.pdf')
        ->middleware('can:voir-etats');


    /*
|--------------------------------------------------------------------------
| PAR DEPARTEMENT
|--------------------------------------------------------------------------
*/

    // 🔥 EXCEL
    Route::get(
        'etats/demandes/par-departement/excel',
        [EtatDemandeController::class, 'demandesParDepartementExcel']
    )->name('etats.demandes.par_departement.excel')
        ->middleware('can:voir-etats');

    // 🔥 PDF
    // Route::get(
    //     'etats/demandes/par-departement/pdf',
    //     [EtatDemandeController::class, 'demandesParDepartementPdf']
    // )->name('etats.demandes.par_departement.pdf');

    Route::match(
        ['get', 'post'],
        'etats/demandes/par-departement/pdf',
        [EtatDemandeController::class, 'demandesParDepartementPdf']
    )->name('etats.demandes.par_departement.pdf')
        ->middleware('can:voir-etats');

    // 🔥 EXCEL
    Route::get(
        'etats/articles/stock-par-article/excel',
        [EtatArticleController::class, 'stockParArticleExcel']
    )->name('etats.articles.stock_par_article.excel')
        ->middleware('can:voir-etats');

    // 🔥 PDF
    Route::get(
        'etats/articles/stock-par-article/pdf',
        [EtatArticleController::class, 'stockParArticlePdf']
    )->name('etats.articles.stock_par_article.pdf')
        ->middleware('can:voir-etats');


    //Etats stock Controller
    Route::get('/etats/stocks', [EtatStockController::class, 'index'])
        ->name(
            'etats.stocks.index'
        )
        ->middleware('can:voir-etats');
    Route::post('/etats/stocks/excel', [EtatStockController::class, 'exportExcel'])
        ->name(
            'etats.stocks.excel'
        )
        ->middleware('can:voir-etats');
    Route::get('/etats/stocks/pdf', [EtatStockController::class, 'exportPdf'])
        ->name(
            'etats.stocks.pdf'
        )
        ->middleware('can:voir-etats');

    Route::post('/etats/stocks-valorises/excel', [EtatStockController::class, 'exportExcelValorise'])
        ->name(
            'etats.stocks_valorises.excel'
        )
        ->middleware('can:voir-etats');
    Route::get('/etats/stocks-valorises/pdf', [EtatStockController::class, 'exportPdfValorise'])
        ->name(
            'etats.stocks_valorises.pdf'
        )
        ->middleware('can:voir-etats');



    Route::post('/etats/entrees-stock/excel', [EtatMouvementStockController::class, 'exportExcelEntrees'])
        ->name(
            'etats.entrees_stock.excel'
        )
        ->middleware('can:voir-etats');

    Route::get('/etats/entrees-stock/pdf', [EtatMouvementStockController::class, 'exportPdfEntrees'])
        ->name(
            'etats.entrees_stock.pdf'
        )
        ->middleware('can:voir-etats');

    Route::post('/etats/sorties-stock/excel', [EtatMouvementStockController::class, 'exportExcelSorties'])
        ->name(
            'etats.sorties_stock.excel'
        )
        ->middleware('can:voir-etats');

    Route::get('/etats/sorties-stock/pdf', [EtatMouvementStockController::class, 'exportPdfSorties'])
        ->name(
            'etats.sorties_stock.pdf'
        )
        ->middleware('can:voir-etats');

    Route::post('/etats/stock-final/excel', [EtatMouvementStockController::class, 'exportExcelStockFinal'])
        ->name(
            'etats.stock_final.excel'
        )
        ->middleware('can:voir-etats');

    Route::get('/etats/stock-final/pdf', [EtatMouvementStockController::class, 'exportPdfStockFinal'])
        ->name(
            'etats.stock_final.pdf'
        )
        ->middleware('can:voir-etats');
});







require __DIR__ . '/auth.php';
