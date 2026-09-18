<?php

namespace Tests\Feature;

use App\Models\Unidade;
use App\Models\User;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\ComSetupDiario;
use Tests\TestCase;

class ProspectoBuscaApiTest extends TestCase
{
    use ComSetupDiario;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
        config(['prospecta.receita_ws.driver' => 'mock']);
    }

    public function test_vendedor_busca_prospecto_via_api(): void
    {
        $unidade = Unidade::factory()->create([
            'cep_inicio' => '30000000',
            'cep_fim' => '30999999',
        ]);
        $vendedor = User::factory()->create(['unidade_id' => $unidade->id]);
        $vendedor->assignRole('vendedor');

        $this->actingAs($vendedor)
            ->withSession($this->sessionSetup())
            ->postJson(route('app.prospectos.buscar'), ['cnpj' => '11222333000181'])
            ->assertOk()
            ->assertJsonPath('prospecto.razao_social', 'Padaria Central LTDA')
            ->assertJsonPath('territorio.permitido', true);
    }
}
