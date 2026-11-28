<?php

namespace Tests\Feature\Consultant;

use App\Models\Article;
use App\Models\ArticleCommande;
use App\Models\Commande;
use App\Models\Fournisseur;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Arbitrage A (F1) : le Consultant exporte uniquement les commandes
 * entièrement réceptionnées (livraison = 1), sur toutes les voies d'export
 * (commandes et lignes de commandes, Excel et CSV). Filtre côté serveur.
 */
class ExportCommandesConsultantTest extends TestCase
{
    use RefreshDatabase;

    private array $numeros = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $f = Fournisseur::factory()->create();
        foreach (['RECUE' => [1, 4, 4], 'PARTIELLE' => [0, 4, 2], 'NON-RECUE' => [0, 4, 0]] as $nom => [$livraison, $q, $livree]) {
            $c = Commande::forceCreate(['numero_bon_commande' => 'BC-'.$nom, 'date' => '2026-10-01', 'montant' => 400, 'fournisseur_id' => $f->id,
                'statut' => 'Prête pour validation', 'validation' => 1, 'livraison' => $livraison, 'archiver' => 0]);
            ArticleCommande::create(['commande_id' => $c->id, 'article_id' => Article::factory()->create(['libelle' => 'Article '.$nom])->id,
                'quantite' => $q, 'prix_unitaire' => 100, 'quantite_livree' => $livree, 'quantite_restante' => $q - $livree]);
        }
    }

    private function compte(array $roles): User
    {
        $u = User::factory()->create();
        $u->assignRole($roles);

        return $u;
    }

    /** Contenu exporté, lu dans le fichier réellement produit (CSV ou XLSX). */
    private function contenu(User $u, string $route): string
    {
        $reponse = $this->actingAs($u)->get(route($route));
        $reponse->assertOk();
        $fichier = $reponse->baseResponse->getFile()->getPathname();

        if (str_ends_with($route, 'excel')) {
            return json_encode(IOFactory::load($fichier)->getActiveSheet()->toArray(), JSON_UNESCAPED_UNICODE);
        }

        return file_get_contents($fichier);
    }

    public static function routesExport(): array
    {
        return [
            'commandes excel' => ['commandes.export.excel'],
            'commandes csv' => ['commandes.export.csv'],
            'lignes excel' => ['articles-commandes.export.excel'],
            'lignes csv' => ['articles-commandes.export.csv'],
        ];
    }

    /** @dataProvider routesExport */
    public function test_consultant_n_exporte_que_les_commandes_entierement_receptionnees(string $route): void
    {
        foreach ([['Consultant'], ['Demandeur', 'Consultant']] as $roles) {
            $contenu = $this->contenu($this->compte($roles), $route);

            $this->assertStringContainsString('BC-RECUE', $contenu, "$route : commande réceptionnée exportée");
            $this->assertStringNotContainsString('BC-PARTIELLE', $contenu, "$route : commande partielle exclue");
            $this->assertStringNotContainsString('BC-NON-RECUE', $contenu, "$route : commande non réceptionnée exclue");
        }
    }

    /** @dataProvider routesExport */
    public function test_les_autres_roles_gardent_l_export_complet(string $route): void
    {
        foreach ([['Gestionnaire'], ['ValidateurCommande'], ['SuperAdmin'], ['Gestionnaire', 'Consultant']] as $roles) {
            $contenu = $this->contenu($this->compte($roles), $route);
            foreach (['BC-RECUE', 'BC-PARTIELLE', 'BC-NON-RECUE'] as $numero) {
                $this->assertStringContainsString($numero, $contenu, implode('+', $roles)." : $route complet");
            }
        }

        foreach ([['Demandeur'], ['Admin'], ['ValidateurDemande'], ['Superieur']] as $roles) {
            $this->actingAs($this->compte($roles))->get(route($route))->assertForbidden();
        }
    }

    public function test_appel_direct_avec_parametres_ne_contourne_pas_le_filtre(): void
    {
        $consultant = $this->compte(['Consultant']);

        foreach (['commandes.export.csv', 'articles-commandes.export.csv'] as $route) {
            $reponse = $this->actingAs($consultant)->get(route($route, ['livraison' => 0, 'tout' => 1, 'commande_id' => 2]))->assertOk();
            $contenu = file_get_contents($reponse->baseResponse->getFile()->getPathname());
            $this->assertStringNotContainsString('BC-PARTIELLE', $contenu);
            $this->assertStringNotContainsString('BC-NON-RECUE', $contenu);
        }
    }

    public function test_export_sans_nouveau_droit_operationnel(): void
    {
        $consultant = $this->compte(['Consultant']);
        $partielle = Commande::where('numero_bon_commande', 'BC-PARTIELLE')->firstOrFail();

        $this->actingAs($consultant)->get(route('commandes.show', $partielle->id))->assertForbidden();
        $this->actingAs($consultant)->from('/commandes')->put(route('commandes.livrer', $partielle->id), ['date_livraison' => '2026-11-10',
            'quantites' => [$partielle->articles()->first()->id => 1], 'jeton_reception' => (string) Str::uuid()])->assertForbidden();
        $this->actingAs($consultant)->post(route('commandes.valider', $partielle->id))->assertForbidden();

        // Boutons d'export affichés au Consultant dans la liste des commandes.
        $this->actingAs($consultant)->get(route('commandes.index'))->assertOk()
            ->assertSee(route('commandes.export.excel'), false)->assertSee(route('commandes.export.csv'), false);
    }
}
