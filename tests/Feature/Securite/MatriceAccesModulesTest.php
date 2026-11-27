<?php

namespace Tests\Feature\Securite;

use App\Models\Article;
use App\Models\Commande;
use App\Models\Fournisseur;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Socle des droits : matrice d'accès des modules, par appel HTTP direct.
 *
 * Les rôles attendus sont ceux de MATRICE_ROLES_PERMISSIONS_GESTOK.md
 * (conditions déjà présentes dans l'interface et le seeder). Un rôle autorisé
 * ne doit jamais recevoir 403 ; un rôle non autorisé reçoit toujours 403.
 */
class MatriceAccesModulesTest extends TestCase
{
    use RefreshDatabase;

    private const ROLES = ['Demandeur', 'Superieur', 'Gestionnaire', 'ValidateurDemande', 'ValidateurCommande', 'Admin', 'SuperAdmin', 'Consultant'];

    /** URL (GET) => rôles autorisés (Consultant : lecture seule, décisions A1, A2, A3) */
    private const PAGES = [
        '/users' => ['Admin', 'SuperAdmin'],
        '/users/create' => ['Admin', 'SuperAdmin'],
        '/users-import' => ['Admin', 'SuperAdmin'],
        '/users-export-csv' => ['Admin', 'SuperAdmin'],
        '/agences' => ['Admin', 'SuperAdmin'],
        '/departements' => ['Admin', 'SuperAdmin'],
        '/services' => ['Admin', 'SuperAdmin'],
        '/agences-export-csv' => ['Admin', 'SuperAdmin'],
        '/articles' => ['Gestionnaire', 'SuperAdmin'],
        '/articles/create' => ['Gestionnaire', 'SuperAdmin'],
        '/categories' => ['Gestionnaire', 'SuperAdmin'],
        '/type-articles' => ['Gestionnaire', 'SuperAdmin'],
        '/articles-export-csv' => ['Gestionnaire', 'SuperAdmin'],
        '/mouvements-stock' => ['Gestionnaire', 'SuperAdmin', 'Consultant'],
        '/fournisseurs' => ['Gestionnaire', 'SuperAdmin'],
        '/fournisseurs-export-csv' => ['Gestionnaire', 'SuperAdmin'],
        '/commandes' => ['ValidateurCommande', 'Gestionnaire', 'SuperAdmin', 'Consultant'],
        '/commandes/create' => ['Gestionnaire', 'SuperAdmin'],
        '/commandes-export-csv' => ['ValidateurCommande', 'Gestionnaire', 'SuperAdmin'],
        '/articles-commandes-export-csv' => ['ValidateurCommande', 'Gestionnaire', 'SuperAdmin'],
        '/etats' => ['Gestionnaire', 'ValidateurDemande', 'SuperAdmin', 'Consultant'],
        '/etats/stocks' => ['Gestionnaire', 'ValidateurDemande', 'SuperAdmin', 'Consultant'],
        '/demandes-export-csv' => ['Gestionnaire', 'Admin', 'SuperAdmin'],
        '/articles-demandes-export-csv' => ['Gestionnaire', 'Admin', 'SuperAdmin'],
        '/demandes-a-valider' => ['Superieur'],
        '/demandes-gestionnaire' => ['Gestionnaire'],
        '/demandes-validation-finale' => ['ValidateurDemande', 'SuperAdmin'],
        // Pages ouvertes à tout utilisateur connecté (inchangées)
        '/dashboard' => self::ROLES,
        '/mes-demandes' => self::ROLES,
        '/demandes/create' => self::ROLES,
        '/mon-profil' => self::ROLES,
    ];

    private function compte(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    public static function rolesEtPages(): iterable
    {
        foreach (self::PAGES as $url => $autorises) {
            foreach (self::ROLES as $role) {
                yield "$role $url" => [$role, $url, in_array($role, $autorises, true)];
            }
        }
    }

    /** @dataProvider rolesEtPages */
    public function test_acces_aux_pages_et_exports(string $role, string $url, bool $autorise): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $reponse = $this->actingAs($this->compte($role))->get($url);

        if ($autorise) {
            $this->assertNotSame(403, $reponse->getStatusCode(), "$role doit accéder à $url");
        } else {
            $reponse->assertForbidden();
        }
    }

    public function test_actions_sensibles_des_commandes_et_du_stock(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $article = Article::factory()->create(['stock_disponible' => 10]);
        $nouvelleCommande = fn () => Commande::forceCreate(['numero_bon_commande' => 'BC-'.uniqid(), 'date' => now()->toDateString(), 'montant' => 100,
            'fournisseur_id' => Fournisseur::factory()->create()->id, 'validation' => 0, 'livraison' => 0, 'statut' => 'Prête pour validation', 'archiver' => 0]);

        $attendus = [
            // [méthode, url fabriquée, rôles autorisés]
            ['post', fn () => '/commandes/'.$nouvelleCommande()->id.'/valider', ['ValidateurCommande', 'SuperAdmin']],
            ['delete', fn () => '/commandes/'.$nouvelleCommande()->id, ['SuperAdmin']],
            // A1 (décision validée) : réception par le Gestionnaire seulement.
            ['put', fn () => '/commandes/'.$nouvelleCommande()->id.'/livrer', ['Gestionnaire']],
            ['post', fn () => '/article-commande', ['Gestionnaire', 'SuperAdmin']],
            ['put', fn () => '/articles/'.$article->id.'/rebut', ['Gestionnaire', 'SuperAdmin']],
        ];

        foreach ($attendus as [$methode, $url, $autorises]) {
            foreach (self::ROLES as $role) {
                $chemin = $url();
                $code = $this->actingAs($this->compte($role))->call(strtoupper($methode), $chemin, ['quantite' => 1, 'motif' => 'x', 'date_rebut' => now()->toDateString()])->getStatusCode();
                if (in_array($role, $autorises, true)) {
                    $this->assertNotSame(403, $code, "$role doit pouvoir $methode $chemin");
                } else {
                    $this->assertSame(403, $code, "$role ne doit pas pouvoir $methode $chemin");
                }
            }
        }

    }

    public function test_validation_d_une_commande_non_soumise_refusee(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $commande = Commande::forceCreate(['numero_bon_commande' => 'BC-1', 'date' => now()->toDateString(), 'montant' => 100,
            'fournisseur_id' => Fournisseur::factory()->create()->id, 'validation' => 0, 'livraison' => 0, 'statut' => 'En préparation', 'archiver' => 0]);

        $this->actingAs($this->compte('ValidateurCommande'))->post(route('commandes.valider', $commande->id))->assertForbidden();
        $this->assertFalse((bool) $commande->fresh()->validation);
    }

    public function test_aucune_ligne_ajoutee_a_une_commande_validee(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $commande = Commande::forceCreate(['numero_bon_commande' => 'BC-2', 'date' => now()->toDateString(), 'montant' => 1000,
            'fournisseur_id' => Fournisseur::factory()->create()->id, 'validation' => 1, 'livraison' => 0, 'statut' => 'Prête pour validation', 'archiver' => 0]);
        $article = Article::factory()->create();

        $this->actingAs($this->compte('Gestionnaire'))->from('/commandes/'.$commande->id)->post(route('article_commande.store'), [
            'commande_id' => $commande->id, 'article_id' => $article->id, 'quantite' => 1, 'prix_unitaire' => 10,
        ])->assertSessionHas('error', 'Commande déjà validée');
        $this->assertSame(0, $commande->articles()->count());
    }
}
