<?php

namespace Tests\Feature;

use App\Models\Prospecto;
use App\Models\User;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\ComSetupDiario;
use Tests\TestCase;

class ReceitaConsultarTest extends TestCase
{
    use ComSetupDiario;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
        config(['prospecta.receita_ws.driver' => 'mock']);
    }

    public function test_consulta_mock_e_atualiza_prospecto(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');

        $prospecto = Prospecto::factory()->create([
            'cnpj' => 'Gabcdef1234567',
            'razao_social' => 'Temporario Places',
        ]);

        // Mock tem fixture para 11222333000181
        $this->actingAs($vendedor)
            ->withSession($this->sessionSetup())
            ->postJson(route('app.receita.consultar'), [
                'cnpj' => '11.222.333/0001-81',
                'prospecto_id' => $prospecto->id,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('driver', 'mock');

        $prospecto->refresh();
        $this->assertSame('11222333000181', $prospecto->cnpj);
    }
}
