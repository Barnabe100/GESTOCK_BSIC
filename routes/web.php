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
});

Route::resource('departements', DepartementController::class);
Route::resource('services', ServiceController::class);
Route::resource('agences', AgenceController::class);


require __DIR__.'/auth.php';
