<?php

namespace Tests\Feature;

use App\Models\Localizacao;
use App\Models\User;
use App\Models\Visita;
use App\Services\DemoMassaService;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoMassaGpsRefreshTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
        config(['prospecta.demo_seed_enabled' => true]);
    }

    public function test_regenerar_campo_cria_gps_recente_sem_seed_pesado(): void
    {
        $camila = User::factory()->create(['email' => 'vendedor@prospecta.test']);
        $camila->assignRole('vendedor');
        $diego = User::factory()->create(['email' => 'vendedor.livre@prospecta.test']);
        $diego->assignRole('vendedor');
        User::factory()->create(['email' => 'adm@prospecta.test']);
        User::factory()->create(['email' => 'gestor@prospecta.test']);

        Visita::factory()->create([
            'user_id' => $camila->id,
            'checkin_lat' => -22.97,
            'checkin_lng' => -43.18,
            'created_at' => now()->subHours(2),
        ]);
        Visita::factory()->create([
            'user_id' => $camila->id,
            'checkin_lat' => -22.98,
            'checkin_lng' => -43.20,
            'created_at' => now()->subHour(),
        ]);

        $resumo = app(DemoMassaService::class)->regenerarCampo();

        $this->assertGreaterThanOrEqual(2, $resumo['vendedores_ao_vivo']);
        $this->assertGreaterThan(0, $resumo['localizacoes_recentes']);
        $this->assertTrue(
            Localizacao::query()
                ->where('user_id', $camila->id)
                ->where('capturado_em', '>=', now()->subMinutes(15))
                ->exists(),
        );
        $this->assertTrue(
            Localizacao::query()
                ->where('user_id', $diego->id)
                ->where('capturado_em', '>=', now()->subMinutes(15))
                ->exists(),
        );
    }
}
