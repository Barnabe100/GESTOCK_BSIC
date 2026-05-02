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

Route::get('/', function () {
    return view('includes.master');
})->name('home');

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', [RegisterController::class, 'create'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');



//Route::get('/', [AuthenticatedSessionController::class, 'store'])->name('home');

//Route::get('/dashboard', function () {
//    return view('dashboard');
//})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::middleware(['auth'])->group(function () {
    Route::resource('categories', CategorieController::class);
    
    Route::resource('articles', ArticleController::class);
    Route::resource('users', UserController::class); 
    Route::resource('commandes', CommandeController::class);
    Route::resource('fournisseurs', FournisseurController::class);
    Route::resource('demandes', DemandeController::class);
    Route::post('demandes/{id}/valider', [DemandeController::class, 'valider'])->name('demandes.valider');
    Route::post('demandes/{id}/livrer', [DemandeController::class, 'livrer'])->name('demandes.livrer');
    Route::resource('users', UserController::class);

    //Artcile demande
    Route::post('article-demande', [ArticleDemandeController::class, 'store'])
        ->name('article_demande.store');

    Route::delete('article-demande/{id}', [ArticleDemandeController::class, 'destroy'])
        ->name('article_demande.destroy');

    Route::post('commandes/{id}/valider', [CommandeController::class, 'valider'])
        ->name('commandes.valider');

    Route::post('commandes/{id}/livrer', [CommandeController::class, 'livrer'])
        ->name('commandes.livrer');

    Route::post('article-commande', [ArticleCommandeController::class, 'store'])
        ->name('article_commande.store');

    Route::delete('article-commande/{id}', [ArticleCommandeController::class, 'destroy'])
        ->name('article_commande.destroy');


    //Export Categories
    Route::get('categories-export-excel',[CategorieController::class, 'exportExcel'])
    ->name('categories.export.excel');
    Route::get('categories-export-csv',[CategorieController::class, 'exportCsv'])
        ->name('categories.export.csv');
    //Export Agences
    Route::get('agences-export-excel',[AgenceController::class, 'exportExcel'])
        ->name('agences.export.excel');
    Route::get('agences-export-csv',[AgenceController::class, 'exportCsv'])
        ->name('agences.export.csv');
    //Export Articles
    Route::get('articles-export-excel',[ArticleController::class, 'exportExcel'])
        ->name('articles.export.excel');
    Route::get('articles-export-csv',[ArticleController::class, 'exportCsv'])
        ->name('articles.export.csv');
    //Export demandes
    Route::get('demandes-export-excel',[DemandeController::class, 'exportExcel'])
        ->name('demandes.export.excel');
    Route::get('demandes-export-csv',[DemandeController::class, 'exportCsv'])
        ->name('demandes.export.csv');
    //Export commandes
    Route::get('commandes-export-excel',[CommandeController::class, 'exportExcel'])
        ->name('commandes.export.excel');
    Route::get('commandes-export-csv',[CommandeController::class, 'exportCsv'])
        ->name('commandes.export.csv');
    //Export fournisseurs
    Route::get('fournisseurs-export-excel',[FournisseurController::class, 'exportExcel'])
        ->name('fournisseurs.export.excel');
    Route::get('fournisseurs-export-csv',[FournisseurController::class, 'exportCsv'])
        ->name('fournisseurs.export.csv');
    //Export departements
    Route::get('departements-export-excel',[DepartementController::class, 'exportExcel'])
        ->name('departements.export.excel');
    Route::get('departements-export-csv',[DepartementController::class, 'exportCsv'])
        ->name('departements.export.csv');
    //Export services
    Route::get('services-export-excel',[ServiceController::class, 'exportExcel'])
        ->name('services.export.excel');
    Route::get('services-export-csv',[ServiceController::class, 'exportCsv'])
        ->name('services.export.csv');
    //Export users
    Route::get('users-export-excel',[UserController::class, 'exportExcel'])
        ->name('users.export.excel');
    Route::get('users-export-csv',[UserController::class, 'exportCsv'])
        ->name('users.export.csv');

    //Export Articles demandes
    Route::get('articles-demandes-export-excel',[ArticleDemandeController::class, 'exportExcel'])
        ->name('articles-demandes.export.excel');
    Route::get('articles-demandes-export-csv',[ArticleDemandeController::class, 'exportCsv'])
        ->name('articles-demandes.export.csv');

    //Export Articles Commandes
    Route::get('articles-commandes-export-excel',[ArticleCommandeController::class, 'exportExcel'])
        ->name('articles-commandes.export.excel');
    Route::get('articles-commandes-export-csv',[ArticleCommandeController::class, 'exportCsv'])
        ->name('articles-commandes.export.csv');
});

Route::resource('departements', DepartementController::class);
Route::resource('services', ServiceController::class);
Route::resource('agences', AgenceController::class);


require __DIR__.'/auth.php';
