<?php

namespace Tests\Feature;

use App\Models\Unidade;
use App\Models\User;
use App\Services\TerritorioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TerritorioBordasTest extends TestCase
{
    use RefreshDatabase;

    public function test_cep_limitrofe_inicio_pertence_a_unidade(): void
    {
        $unidade = Unidade::factory()->create([
            'cep_inicio' => '30000000',
            'cep_fim' => '30999999',
        ]);
        $usuario = User::factory()->create(['unidade_id' => $unidade->id]);

        $resultado = app(TerritorioService::class)->verificarCep($usuario, '30000-000');

        $this->assertTrue($resultado['permitido']);
        $this->assertSame('minha_unidade', $resultado['motivo']);
    }

    public function test_cep_limitrofe_fim_pertence_a_unidade(): void
    {
        $unidade = Unidade::factory()->create([
            'cep_inicio' => '30000000',
            'cep_fim' => '30999999',
        ]);
        $usuario = User::factory()->create(['unidade_id' => $unidade->id]);

        $resultado = app(TerritorioService::class)->verificarCep($usuario, '30999-999');

        $this->assertTrue($resultado['permitido']);
        $this->assertSame('minha_unidade', $resultado['motivo']);
    }

    public function test_usuario_sem_unidade_em_territorio_alheio_e_bloqueado(): void
    {
        $outra = Unidade::factory()->create([
            'nome' => 'Filial RJ',
            'cep_inicio' => '20000000',
            'cep_fim' => '20999999',
        ]);
        $usuario = User::factory()->create(['unidade_id' => null]);

        $resultado = app(TerritorioService::class)->verificarCep($usuario, '20040-020');

        $this->assertFalse($resultado['permitido']);
        $this->assertSame('bloqueado', $resultado['motivo']);
        $this->assertSame($outra->id, $resultado['unidade_id']);
    }
}
