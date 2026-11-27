<?php

namespace Tests\Feature\Consultant;

use App\Autorisation\Capacites;
use App\Models\User;
use App\Policies\DemandePolicy;
use App\Policies\UserPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use ReflectionClass;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * LC1 : socle du rôle Consultant (consultation sans opération), garde
 * contre les collisions de noms Spatie, cumul des rôles, rôle Demandeur par
 * défaut (E11).
 */
class ConsultantSocleTest extends TestCase
{
    use RefreshDatabase;

    private const ROLES_DE_BASE = ['Demandeur', 'Gestionnaire', 'ValidateurDemande', 'ValidateurCommande', 'Superieur', 'Admin'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function compte(array $roles): User
    {
        $u = User::factory()->create();
        $u->assignRole($roles);

        return $u;
    }

    /** Capacités d'opération : toutes sauf les capacités de lecture. */
    private function capacitesOperation(): array
    {
        return array_values(array_diff(Capacites::noms(), Capacites::CAPACITES_LECTURE));
    }

    /** Noms de toutes les méthodes publiques des Policies (abilities). */
    private function methodesDePolicy(): array
    {
        $noms = [];
        foreach ([DemandePolicy::class, UserPolicy::class] as $policy) {
            foreach ((new ReflectionClass($policy))->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
                if (! $m->isStatic() && ! str_starts_with($m->name, '__')) {
                    $noms[] = $m->name;
                }
            }
        }

        return array_values(array_unique($noms));
    }

    public function test_role_et_permissions_de_consultation_crees(): void
    {
        $consultant = Role::findByName('Consultant');

        $this->assertEqualsCanonicalizing(Capacites::PERMISSIONS_CONSULTATION, $consultant->permissions->pluck('name')->all());
        $this->assertTrue(Role::findByName('SuperAdmin')->hasAllPermissions(Capacites::PERMISSIONS_CONSULTATION));
    }

    public function test_aucune_collision_spatie_entre_permissions_et_abilities(): void
    {
        $operation = $this->capacitesOperation();
        $policy = $this->methodesDePolicy();

        // Aucune permission de consultation ne porte le nom d'une capacité
        // d'opération ni d'une méthode de Policy.
        foreach (Capacites::PERMISSIONS_CONSULTATION as $p) {
            $this->assertNotContains($p, $operation, "Collision : $p est une capacité d'opération");
            $this->assertNotContains($p, $policy, "Collision : $p est une méthode de Policy");
        }

        // Aucune permission de la base ne porte le nom d'une méthode de Policy.
        foreach (Permission::pluck('name') as $p) {
            $this->assertNotContains($p, $policy, "Collision : la permission $p ouvrirait la règle du même nom");
        }

        // Les permissions du rôle Consultant sont exactement les permissions de consultation.
        $this->assertEqualsCanonicalizing(Capacites::PERMISSIONS_CONSULTATION,
            Role::findByName('Consultant')->permissions->pluck('name')->all());
    }

    public function test_consultant_seul_n_a_aucune_capacite_d_operation(): void
    {
        $consultant = $this->compte(['Consultant']);

        foreach ($this->capacitesOperation() as $capacite) {
            $this->assertFalse(Gate::forUser($consultant)->allows($capacite), "Le Consultant ne doit pas avoir « $capacite »");
        }
    }

    public function test_cumul_le_consultant_n_ajoute_aucune_operation(): void
    {
        foreach (self::ROLES_DE_BASE as $role) {
            $seul = $this->compte([$role]);
            $cumul = $this->compte([$role, 'Consultant']);

            foreach ($this->capacitesOperation() as $capacite) {
                $this->assertSame(
                    Gate::forUser($seul)->allows($capacite),
                    Gate::forUser($cumul)->allows($capacite),
                    "$role + Consultant : « $capacite » doit être identique à $role seul"
                );
            }
        }
    }

    public function test_affectation_par_le_formulaire_existant_et_historique(): void
    {
        $admin = $this->compte(['Admin']);
        $cible = $this->compte(['Demandeur']);

        // Le rôle apparaît dans le formulaire existant (Role::all()), sans nouvelle interface.
        $this->actingAs($admin)->get(route('users.edit', $cible->id))->assertOk()->assertSee('value="Consultant"', false);

        $this->actingAs($admin)->put(route('users.update', $cible->id), ['name' => 'C', 'prenom' => 'P', 'email' => $cible->email,
            'roles' => ['Demandeur', 'Consultant']])->assertRedirect(route('users.index'));

        $this->assertTrue($cible->fresh()->hasAllRoles(['Demandeur', 'Consultant']));
        $this->assertDatabaseHas('historique_roles', ['user_id' => $cible->id, 'role' => 'Consultant', 'action' => 'attribue', 'effectue_par' => $admin->id]);
    }

    public function test_migration_de_donnees_reversible_et_superadmin_existant(): void
    {
        $migration = require database_path('migrations/2026_11_28_000001_creer_role_consultant.php');
        $sa = Role::findByName('SuperAdmin');

        $migration->down();
        $this->assertNull(Role::where('name', 'Consultant')->first());
        $this->assertSame(0, Permission::whereIn('name', Capacites::PERMISSIONS_CONSULTATION)->count());
        $this->assertTrue(Role::findByName('Gestionnaire')->hasPermissionTo('gerer-stock'), 'Les autres permissions restent intactes');

        // Installation existante : le SuperAdmin reçoit explicitement les permissions.
        $migration->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->assertTrue($sa->fresh()->hasAllPermissions(Capacites::PERMISSIONS_CONSULTATION));
        $this->assertEqualsCanonicalizing(Capacites::PERMISSIONS_CONSULTATION, Role::findByName('Consultant')->permissions->pluck('name')->all());

        // Idempotente.
        $migration->up();
        $this->assertSame(1, Role::where('name', 'Consultant')->count());
    }

    // ---------------- E11 : Demandeur par défaut ----------------

    public function test_demandeur_par_defaut_a_la_creation_et_a_l_import(): void
    {
        config(['gestok.mot_de_passe_initial' => 'Initial#Consultant2026']);
        $admin = $this->compte(['Admin']);

        $this->actingAs($admin)->post(route('users.store'), ['name' => 'N', 'prenom' => 'P', 'email' => 'nouveau@ex.test',
            'password' => 'Provisoire1', 'password_confirmation' => 'Provisoire1'])->assertRedirect(route('users.index'));
        $this->assertSame(['Demandeur'], User::where('email', 'nouveau@ex.test')->firstOrFail()->getRoleNames()->all());

        $entete = "name,prenom,email,agence_id,departement_id,service_id,superieur,roles\n";
        $this->actingAs($admin)->post(route('users.import'), ['file' => UploadedFile::fake()->createWithContent('u.csv',
            $entete."A,A,consult@ex.test,,,,0,Consultant\nB,B,sansrole@ex.test,,,,0,\n")])->assertRedirect(route('users.index'));

        // Les rôles du fichier s'ajoutent au rôle Demandeur, sans rien retirer.
        $this->assertEqualsCanonicalizing(['Demandeur', 'Consultant'], User::where('email', 'consult@ex.test')->firstOrFail()->getRoleNames()->all());
        $this->assertSame(['Demandeur'], User::where('email', 'sansrole@ex.test')->firstOrFail()->getRoleNames()->all());
    }

    public function test_comptes_d_installation_avec_demandeur_sans_ecraser_leur_role(): void
    {
        config(['gestok.seed_mot_de_passe' => 'Installation#2026']);
        // Le seeder rattache les comptes d'installation aux entités n° 1.
        \App\Models\Agence::forceCreate(['id' => 1, 'code' => 'AG-1', 'nom' => 'Agence 1', 'type' => 'AGENCE', 'archiver' => 0]);
        \App\Models\Departement::forceCreate(['id' => 1, 'code' => 'D-1', 'nom' => 'Département 1', 'agence_id' => 1, 'archiver' => 0]);
        \App\Models\Service::forceCreate(['id' => 1, 'code' => 'S-1', 'nom' => 'Service 1', 'departement_id' => 1, 'archiver' => 0]);
        $this->seed(UserSeeder::class);

        $this->assertEqualsCanonicalizing(['SuperAdmin', 'Demandeur'], User::where('email', 'superadmin@test.com')->firstOrFail()->getRoleNames()->all());
        $this->assertEqualsCanonicalizing(['Admin', 'Demandeur'], User::where('email', 'admin@test.com')->firstOrFail()->getRoleNames()->all());
    }
}
