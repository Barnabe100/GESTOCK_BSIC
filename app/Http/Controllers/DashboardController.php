<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Article;
use App\Models\Commande;
use App\Models\Demande;
use App\Autorisation\Capacites;

class DashboardController extends Controller
{


    public const MESSAGE_SANS_ROLE = 'Aucun rôle n’est attribué à votre compte : le tableau de bord n’est pas accessible. Contactez votre administrateur.';

    /**
     * E10 (décision validée) : le tableau de bord dépend des droits, côté
     * serveur ; les données non autorisées ne sont ni calculées ni transmises.
     * - compte sans rôle : aucun tableau de bord métier (renvoi vers son profil) ;
     * - rôles du tableau global (inchangés) : statistiques de l'organisation ;
     * - Consultant : tableau de consultation, limité à ses droits ;
     * - Demandeur seul : page d'accueil (inchangé).
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->getRoleNames()->isEmpty()) {
            return redirect()->route('mon.profil')->with('error', self::MESSAGE_SANS_ROLE);
        }

        if (Capacites::autorise($user, 'voir-tableau-de-bord-global')) {
            return $this->tableauGlobal();
        }

        if (Capacites::autorise($user, 'voir-tableau-de-bord-consultation')) {
            return $this->tableauConsultation($user);
        }

        return view('accueil');
    }

    /**
     * Consultant : demandes de SON entité (N01) ; commandes réceptionnées,
     * stock critique (organisation, A1 et A2). Ni montant des commandes, ni
     * nombre d'utilisateurs.
     */
    private function tableauConsultation(User $user)
    {
        $demandesEntite = fn () => Demande::where('archiver', 0)
            ->whereHas('user', fn ($q) => User::filtrerMemeEntite($q, $user));

        $totalDemandes = $demandesEntite()->where('statut', 'Livrée - Demande clôturée')->count();
        $dernieresDemandes = $demandesEntite()->where('statut', 'Livrée - Demande clôturée')->latest()->take(5)->get();

        $totalCommandes = Commande::where('archiver', 0)->where('livraison', 1)->count();
        $dernieresCommandes = Commande::where('archiver', 0)->where('livraison', 1)->latest()->take(5)->get();

        $stockCritique = Article::whereColumn('stock_disponible', '<=', 'stock_securite')->count();
        $articlesCritiques = Article::whereColumn('stock_disponible', '<=', 'stock_securite')->latest()->take(5)->get();

        $topArticles = DB::table('article_demande')
            ->join('articles', 'articles.id', '=', 'article_demande.article_id')
            ->whereIn('article_demande.demande_id', $demandesEntite()->select('id'))
            ->select('articles.libelle', DB::raw('SUM(article_demande.quantite) as total'))
            ->groupBy('articles.libelle')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        $demandesParMois = $demandesEntite()
            ->select(DB::raw('MONTH(date) as mois'), DB::raw('COUNT(*) as total'))
            ->groupBy('mois')
            ->pluck('total', 'mois');

        $commandesParMois = Commande::where('archiver', 0)->where('livraison', 1)
            ->select(DB::raw('MONTH(date) as mois'), DB::raw('COUNT(*) as total'))
            ->groupBy('mois')
            ->pluck('total', 'mois');

        return view('dashboard', compact(
            'totalDemandes',
            'totalCommandes',
            'stockCritique',
            'dernieresDemandes',
            'dernieresCommandes',
            'articlesCritiques',
            'topArticles',
            'demandesParMois',
            'commandesParMois'
        ));
    }

    /** Tableau de bord global : contenu inchangé. */
    private function tableauGlobal()
    {
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
