<?php

namespace Tests\Feature;

use App\Enums\TipoUnidade;
use App\Models\Unidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioCamposTerritorioTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_vincula_unidade_e_gestor(): void
    {
        $unidade = Unidade::query()->create([
            'nome' => 'Filial Centro',
            'tipo' => TipoUnidade::Filial,
            'cep_inicio' => '01000000',
            'cep_fim' => '01099999',
        ]);

        $gestor = User::factory()->create([
            'unidade_id' => $unidade->id,
            'cep_base_inicio' => '01000000',
            'cep_base_fim' => '01099999',
        ]);

        $vendedor = User::factory()->create([
            'unidade_id' => $unidade->id,
            'gestor_id' => $gestor->id,
            'cep_base_inicio' => '01010000',
            'cep_base_fim' => '01019999',
        ]);

        $vendedor = $vendedor->fresh(['unidade', 'gestor']);

        $this->assertTrue($vendedor->unidade->is($unidade));
        $this->assertTrue($vendedor->gestor->is($gestor));
        $this->assertSame('01010000', $vendedor->cep_base_inicio);
        $this->assertTrue($gestor->fresh()->vendedores->contains($vendedor));
        $this->assertTrue($unidade->fresh()->usuarios->contains($vendedor));
    }
}
