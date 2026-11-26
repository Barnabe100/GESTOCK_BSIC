<?php

namespace Tests\Feature\Finalisation;

use App\Models\Agence;
use App\Models\User;
use App\Organisation\StructureOrganisationnelle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\Support\RequeteDansUnAutreProcessus;
use Tests\TestCase;

/**
 * N03 : unicité du siège actif face à des opérations réellement concurrentes
 * (deux connexions, deux processus).
 */
class StructureOrganisationnelleConcurrenceTest extends TestCase
{
    use DatabaseMigrations;
    use RequeteDansUnAutreProcessus;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');
    }

    public function test_deux_creations_simultanees_d_un_siege_un_seul_reussit(): void
    {
        // Première création, transaction encore ouverte (non validée).
        DB::beginTransaction();
        app(StructureOrganisationnelle::class)->creer(['code' => 'S1', 'nom' => 'Siège A', 'type' => 'SIEGE'], $this->admin);

        // Seconde création, dans un autre processus : elle doit attendre.
        $autre = $this->requeteDansUnAutreProcessus($this->admin->id, 'POST', '/agences', ['code' => 'S2', 'nom' => 'Siège B', 'type' => 'SIEGE']);
        usleep(800_000);
        $this->assertTrue($autre->isRunning(), 'La seconde création doit être bloquée tant que la première n\'est pas validée.');

        DB::commit();
        $resultat = $this->resultatProcessus($autre);

        $this->assertSame(302, $resultat['code'] ?? null, $resultat['brut']);
        $this->assertSame(StructureOrganisationnelle::MESSAGE_SIEGE_EXISTANT, $resultat['error'] ?? null, $resultat['brut']);
        $this->assertSame(1, Agence::where('type', 'SIEGE')->where('archiver', 0)->count());
        $this->assertSame(['S1'], Agence::where('type', 'SIEGE')->pluck('code')->all());
    }

    public function test_deux_changements_de_siege_simultanes_laissent_un_seul_siege(): void
    {
        $siege = Agence::factory()->siege()->create();
        $a = Agence::factory()->create();
        $b = Agence::factory()->create();

        DB::beginTransaction();
        app(StructureOrganisationnelle::class)->designerSiege($a, $this->admin);

        $autre = $this->requeteDansUnAutreProcessus($this->admin->id, 'PATCH', '/agences/'.$b->id.'/designer-siege');
        usleep(800_000);
        $this->assertTrue($autre->isRunning(), 'Le second changement de siège doit attendre le premier.');

        DB::commit();
        $resultat = $this->resultatProcessus($autre);

        $this->assertSame(302, $resultat['code'] ?? null, $resultat['brut']);
        $this->assertSame(1, Agence::where('type', 'SIEGE')->where('archiver', 0)->count());
        $this->assertSame('SIEGE', $b->fresh()->type);
        $this->assertSame('AGENCE', $a->fresh()->type);
        $this->assertSame('AGENCE', $siege->fresh()->type);
    }
}
