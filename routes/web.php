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
use App\Http\Controllers\RegisterController;
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

Route::get('/dashbord', function () {
    return view('dashboard');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', [RegisterController::class, 'create'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');



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

    Route::resource('categories', CategorieController::class);

    Route::resource('articles', ArticleController::class);

    Route::get('/mouvements-stock', [MouvementStockController::class, 'index'])
        ->name(
            'mouvements.index'
        );


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

    Route::resource('commandes', CommandeController::class);

    Route::put(
        '/article-commandes/{id}',
        [ArticleCommandeController::class, 'update']
    )->name('article-commandes.update');


    Route::resource('fournisseurs', FournisseurController::class);
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
        ->name('demandes.superieur');
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
        ->name('demandes.gestionnaire');

    //Validation demandeur
    Route::put('/demandes/{id}/valider-demandeur', [DemandeController::class, 'validerDemandeur'])
        ->name(
            'demandes.valider.demandeur'
        );
    //Validation finale
    Route::get('/demandes-validation-finale', [DemandeController::class, 'validationFinaleIndex'])
        ->name(
            'demandes.validation.finale.index'
        );

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


    Route::resource('departements', DepartementController::class);

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


    Route::resource('type-articles', TypeArticleController::class);

    Route::get(
        'type-articles/export/excel',
        [TypeArticleController::class, 'exportExcel']
    )->name('type-articles.export.excel');

    Route::get(
        'type-articles/export/csv',
        [TypeArticleController::class, 'exportCsv']
    )->name('type-articles.export.csv');

    Route::resource('services', ServiceController::class);
    Route::resource('agences', AgenceController::class);
    Route::resource('users', UserController::class);

    Route::get(
        '/users/{id}/bloquer',
        [UserController::class, 'bloquer']
    )->name('users.bloquer');

    Route::get(
        '/users/{id}/debloquer',
        [UserController::class, 'debloquer']
    )->name('users.debloquer');

    //Artcile demande
    Route::post('article-demande', [ArticleDemandeController::class, 'store'])
        ->name('article_demande.store');

    Route::delete('article-demande/{id}', [ArticleDemandeController::class, 'destroy'])
        ->name('article_demande.destroy');

    Route::post('commandes/{id}/valider', [CommandeController::class, 'valider'])
        ->name('commandes.valider');

    Route::put('commandes/{id}/livrer', [CommandeController::class, 'livrer'])
        ->name('commandes.livrer');

    Route::post('article-commande', [ArticleCommandeController::class, 'store'])
        ->name('article_commande.store');

    Route::delete('article-commande/{id}', [ArticleCommandeController::class, 'destroy'])
        ->name('article_commande.destroy');

    Route::put('/articles/{id}/rebut', [ArticleController::class, 'miseRebut'])
        ->name(
            'articles.rebut'
        );


    //Export Categories
    Route::get('categories-export-excel', [CategorieController::class, 'exportExcel'])
        ->name('categories.export.excel');
    Route::get('categories-export-csv', [CategorieController::class, 'exportCsv'])
        ->name('categories.export.csv');
    //Export Agences
    Route::get('agences-export-excel', [AgenceController::class, 'exportExcel'])
        ->name('agences.export.excel');
    Route::get('agences-export-csv', [AgenceController::class, 'exportCsv'])
        ->name('agences.export.csv');
    //Export Articles
    Route::get('articles-export-excel', [ArticleController::class, 'exportExcel'])
        ->name('articles.export.excel');
    Route::get('articles-export-csv', [ArticleController::class, 'exportCsv'])
        ->name('articles.export.csv');
    //Export demandes
    Route::get('demandes-export-excel', [DemandeController::class, 'exportExcel'])
        ->name('demandes.export.excel');
    Route::get('demandes-export-csv', [DemandeController::class, 'exportCsv'])
        ->name('demandes.export.csv');
    //Export commandes
    Route::get('commandes-export-excel', [CommandeController::class, 'exportExcel'])
        ->name('commandes.export.excel');
    Route::get('commandes-export-csv', [CommandeController::class, 'exportCsv'])
        ->name('commandes.export.csv');

    Route::put('/commandes/{id}/soumettre', [CommandeController::class, 'soumettre'])
        ->name('commandes.soumettre');
    //Export fournisseurs
    Route::get('fournisseurs-export-excel', [FournisseurController::class, 'exportExcel'])
        ->name('fournisseurs.export.excel');
    Route::get('fournisseurs-export-csv', [FournisseurController::class, 'exportCsv'])
        ->name('fournisseurs.export.csv');
    //Export departements
    Route::get('departements-export-excel', [DepartementController::class, 'exportExcel'])
        ->name('departements.export.excel');
    Route::get('departements-export-csv', [DepartementController::class, 'exportCsv'])
        ->name('departements.export.csv');
    //Export services
    Route::get('services-export-excel', [ServiceController::class, 'exportExcel'])
        ->name('services.export.excel');
    Route::get('services-export-csv', [ServiceController::class, 'exportCsv'])
        ->name('services.export.csv');
    //Export users
    Route::get('users-export-excel', [UserController::class, 'exportExcel'])
        ->name('users.export.excel');
    Route::get('users-export-csv', [UserController::class, 'exportCsv'])
        ->name('users.export.csv');

    //Export Articles demandes
    Route::get('articles-demandes-export-excel', [ArticleDemandeController::class, 'exportExcel'])
        ->name('articles-demandes.export.excel');
    Route::get('articles-demandes-export-csv', [ArticleDemandeController::class, 'exportCsv'])
        ->name('articles-demandes.export.csv');

    //Export Articles Commandes
    Route::get('articles-commandes-export-excel', [ArticleCommandeController::class, 'exportExcel'])
        ->name('articles-commandes.export.excel');
    Route::get('articles-commandes-export-csv', [ArticleCommandeController::class, 'exportCsv'])
        ->name('articles-commandes.export.csv');

    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    )->middleware(['auth'])->name('dashboard');


    //Etats
    Route::get(
        '/etats',
        [EtatDemandeController::class, 'index']
    )->name('etats.index');

    Route::get(
        'etats/demandes/par-agence/excel',
        [EtatDemandeController::class, 'demandesParAgenceExcel']
    )->name('etats.demandes.par_agence.excel');

    // PAR AGENCE PDF
    Route::get(
        'etats/demandes/par-agence/pdf',
        [EtatDemandeController::class, 'demandesParAgencePdf']
    )->name('etats.demandes.par_agence.pdf');

    // VALIDÉES EXCEL
    Route::get(
        'etats/demandes/validees/excel',
        [EtatDemandeController::class, 'demandesValideesExcel']
    )->name('etats.demandes.validees.excel');

    // VALIDÉES PDF
    Route::get(
        'etats/demandes/validees/pdf',
        [EtatDemandeController::class, 'demandesValideesPdf']
    )->name('etats.demandes.validees.pdf');

    // EXCEL
    Route::get(
        'etats/demandes/par-service/excel',
        [EtatDemandeController::class, 'demandesParServiceExcel']
    )->name('etats.demandes.par_service.excel');

    //  PDF
    Route::get(
        'etats/demandes/par-service/pdf',
        [EtatDemandeController::class, 'demandesParServicePdf']
    )->name('etats.demandes.par_service.pdf');


    /*
|--------------------------------------------------------------------------
| PAR DEPARTEMENT
|--------------------------------------------------------------------------
*/

    // 🔥 EXCEL
    Route::get(
        'etats/demandes/par-departement/excel',
        [EtatDemandeController::class, 'demandesParDepartementExcel']
    )->name('etats.demandes.par_departement.excel');

    // 🔥 PDF
    // Route::get(
    //     'etats/demandes/par-departement/pdf',
    //     [EtatDemandeController::class, 'demandesParDepartementPdf']
    // )->name('etats.demandes.par_departement.pdf');

    Route::match(
        ['get', 'post'],
        'etats/demandes/par-departement/pdf',
        [EtatDemandeController::class, 'demandesParDepartementPdf']
    )->name('etats.demandes.par_departement.pdf');

    // 🔥 EXCEL
    Route::get(
        'etats/articles/stock-par-article/excel',
        [EtatArticleController::class, 'stockParArticleExcel']
    )->name('etats.articles.stock_par_article.excel');

    // 🔥 PDF
    Route::get(
        'etats/articles/stock-par-article/pdf',
        [EtatArticleController::class, 'stockParArticlePdf']
    )->name('etats.articles.stock_par_article.pdf');


    //Etats stock Controller
    Route::get('/etats/stocks', [EtatStockController::class, 'index'])
        ->name(
            'etats.stocks.index'
        );
    Route::post('/etats/stocks/excel', [EtatStockController::class, 'exportExcel'])
        ->name(
            'etats.stocks.excel'
        );
    Route::get('/etats/stocks/pdf', [EtatStockController::class, 'exportPdf'])
        ->name(
            'etats.stocks.pdf'
        );

    Route::post('/etats/stocks-valorises/excel', [EtatStockController::class, 'exportExcelValorise'])
        ->name(
            'etats.stocks_valorises.excel'
        );
    Route::get('/etats/stocks-valorises/pdf', [EtatStockController::class, 'exportPdfValorise'])
        ->name(
            'etats.stocks_valorises.pdf'
        );



    Route::post('/etats/entrees-stock/excel', [EtatMouvementStockController::class, 'exportExcelEntrees'])
        ->name(
            'etats.entrees_stock.excel'
        );

    Route::get('/etats/entrees-stock/pdf', [EtatMouvementStockController::class, 'exportPdfEntrees'])
        ->name(
            'etats.entrees_stock.pdf'
        );

    Route::post('/etats/sorties-stock/excel', [EtatMouvementStockController::class, 'exportExcelSorties'])
        ->name(
            'etats.sorties_stock.excel'
        );

    Route::get('/etats/sorties-stock/pdf', [EtatMouvementStockController::class, 'exportPdfSorties'])
        ->name(
            'etats.sorties_stock.pdf'
        );

    Route::post('/etats/stock-final/excel', [EtatMouvementStockController::class, 'exportExcelStockFinal'])
        ->name(
            'etats.stock_final.excel'
        );

    Route::get('/etats/stock-final/pdf', [EtatMouvementStockController::class, 'exportPdfStockFinal'])
        ->name(
            'etats.stock_final.pdf'
        );
});







require __DIR__ . '/auth.php';
