<?php

namespace Tests\Feature;

use App\Models\Unidade;
use App\Models\User;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\ComSetupDiario;
use Tests\TestCase;

class TerritorioApiTest extends TestCase
{
    use RefreshDatabase, ComSetupDiario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
    }

    public function test_vendedor_verifica_territorio_via_api(): void
    {
        $unidade = Unidade::factory()->create([
            'cep_inicio' => '30000000',
            'cep_fim' => '30999999',
        ]);
        $vendedor = User::factory()->create(['unidade_id' => $unidade->id]);
        $vendedor->assignRole('vendedor');

        $this->actingAs($vendedor)
            ->withSession($this->sessionSetup())
            ->postJson(route('app.territorio.verificar'), ['cep' => '30130-010'])
            ->assertOk()
            ->assertJson([
                'permitido' => true,
                'motivo' => 'minha_unidade',
            ]);
    }

    public function test_sem_permission_nao_verifica(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('app.territorio.verificar'), ['cep' => '30130-010'])
            ->assertForbidden();
    }
}
