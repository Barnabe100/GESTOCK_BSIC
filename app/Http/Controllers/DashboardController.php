<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Article;
use App\Models\Commande;
use App\Models\Demande;

class DashboardController extends Controller
{


    public function index()
    {
        $user = auth()->user();

        if (
            $user->hasRole('Demandeur')
            &&
            !$user->hasAnyRole([
                'Gestionnaire',
                'SuperAdmin',
                'Admin',
                'ValidateurCommande',
                'Superieur',
                'ValidateurDemande'
            ])
        ) {
            return view('accueil');
        }



        // 🔹 statistiques
        $totalUsers = User::count();

        //$totalDemandes = Demande::where('archiver', 0)->count();

        $totalDemandes = Demande::where('archiver', 0)
            ->where('statut', 'Livrée - Demande clôturée')
            ->count();

        $totalCommandes = Commande::where('archiver', 0)
            ->where('livraison', 1)
            ->count();

        //$totalCommandes = Commande::where('archiver', 0)->count();

        $stockCritique = Article::whereColumn(
            'stock_disponible',
            '<=',
            'stock_securite'
        )->count();

        // 🔹 dernières demandes
        $dernieresDemandes = Demande::where('archiver', 0)
            ->where('statut', 'Livrée - Demande clôturée')
            ->latest()
            ->take(5)
            ->get();

        // 🔹 dernières commandes
        $dernieresCommandes = Commande::where('archiver', 0)
            ->where('livraison', 1)
            ->latest()
            ->take(5)
            ->get();

        // 🔹 articles critiques
        $articlesCritiques = Article::whereColumn(
            'stock_disponible',
            '<=',
            'stock_securite'
        )
            ->latest()
            ->take(5)
            ->get();

        // 🔹 Montant total commande 
        $totalMontantCommandes = Commande::sum('montant');

        // 🔹 Top articles demandés
        $topArticles = DB::table('article_demande')
            ->join('articles', 'articles.id', '=', 'article_demande.article_id')
            ->select(
                'articles.libelle',
                DB::raw('SUM(article_demande.quantite) as total')
            )
            ->groupBy('articles.libelle')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        // 🔹 Demandes par mois
        $demandesParMois = Demande::select(
            DB::raw('MONTH(date) as mois'),
            DB::raw('COUNT(*) as total')
        )
            ->groupBy('mois')
            ->pluck('total', 'mois');

        // 🔹 Commandes par mois
        $commandesParMois = Commande::select(
            DB::raw('MONTH(date) as mois'),
            DB::raw('COUNT(*) as total')
        )
            ->groupBy('mois')
            ->pluck('total', 'mois');

        return view('dashboard', compact(
            'totalUsers',
            'totalDemandes',
            'totalCommandes',
            'stockCritique',
            'dernieresDemandes',
            'dernieresCommandes',
            'articlesCritiques',
            'totalMontantCommandes',
            'topArticles',
            'demandesParMois',
            'commandesParMois'
        ));
    }
}
