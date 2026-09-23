<?php

namespace Tests\Feature;

use App\Enums\StatusVisita;
use App\Models\Prospecto;
use App\Models\User;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\ComSetupDiario;
use Tests\TestCase;

class VisitaStoreTest extends TestCase
{
    use ComSetupDiario;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        ]);
    }

    public function test_vendedor_salva_visita_com_foto_e_audio(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');

        $prospecto = Prospecto::factory()->create([
            'lat' => -22.9,
            'lng' => -43.2,
        ]);

        $this->actingAs($vendedor)
            ->withSession($this->sessionSetup())
            ->postJson(route('app.visitas.store'), [
                'prospecto_id' => $prospecto->id,
                'status' => StatusVisita::Feita->value,
                'checkin_lat' => -22.9001,
                'checkin_lng' => -43.2001,
                'foto' => UploadedFile::fake()->image('fachada.jpg'),
                'audio' => UploadedFile::fake()->create('resumo.webm', 20, 'audio/webm'),
            ])
            ->assertCreated()
            ->assertJsonPath('visita.status', StatusVisita::Feita->value);

        $this->assertDatabaseHas('visitas', [
            'prospecto_id' => $prospecto->id,
            'user_id' => $vendedor->id,
            'status' => StatusVisita::Feita->value,
        ]);
    }

    public function test_checkin_longe_do_prospecto_retorna_422(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');

        $prospecto = Prospecto::factory()->create([
            'lat' => -22.9,
            'lng' => -43.2,
        ]);

        $this->actingAs($vendedor)
            ->withSession($this->sessionSetup())
            ->postJson(route('app.visitas.store'), [
                'prospecto_id' => $prospecto->id,
                'status' => StatusVisita::Feita->value,
                'checkin_lat' => -22.95,
                'checkin_lng' => -43.25,
                'foto' => UploadedFile::fake()->image('fachada.jpg'),
                'audio' => UploadedFile::fake()->create('resumo.webm', 20, 'audio/webm'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['checkin_lat']);
    }
}
