<?php

namespace Tests\Feature;

use App\Models\Unidade;
use App\Models\User;
use App\Services\TerritorioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TerritorioServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cep_da_unidade_do_usuario_e_permitido(): void
    {
        $unidade = Unidade::factory()->create([
            'nome' => 'Filial BH',
            'cep_inicio' => '30000000',
            'cep_fim' => '30999999',
        ]);

        $usuario = User::factory()->create(['unidade_id' => $unidade->id]);

        $resultado = app(TerritorioService::class)->verificarCep($usuario, '30130-010');

        $this->assertTrue($resultado['permitido']);
        $this->assertSame('minha_unidade', $resultado['motivo']);
        $this->assertSame($unidade->id, $resultado['unidade_id']);
    }

    public function test_cep_sem_cobertura_e_area_livre(): void
    {
        Unidade::factory()->create([
            'cep_inicio' => '01000000',
            'cep_fim' => '01999999',
        ]);

        $usuario = User::factory()->create(['unidade_id' => null]);

        $resultado = app(TerritorioService::class)->verificarCep($usuario, '30130010');

        $this->assertTrue($resultado['permitido']);
        $this->assertSame('area_livre', $resultado['motivo']);
        $this->assertNull($resultado['unidade_id']);
    }

    public function test_cep_de_outra_unidade_e_bloqueado(): void
    {
        $minha = Unidade::factory()->create([
            'nome' => 'Minha',
            'cep_inicio' => '30000000',
            'cep_fim' => '30099999',
        ]);
        $outra = Unidade::factory()->create([
            'nome' => 'Concorrente SP',
            'cep_inicio' => '01000000',
            'cep_fim' => '01999999',
        ]);

        $usuario = User::factory()->create(['unidade_id' => $minha->id]);

        $resultado = app(TerritorioService::class)->verificarCep($usuario, '01310-100');

        $this->assertFalse($resultado['permitido']);
        $this->assertSame('bloqueado', $resultado['motivo']);
        $this->assertSame($outra->id, $resultado['unidade_id']);
        $this->assertSame('Concorrente SP', $resultado['unidade_nome']);
    }

    public function test_multiplas_unidades_prioriza_a_do_usuario(): void
    {
        $minha = Unidade::factory()->create([
            'nome' => 'Minha',
            'cep_inicio' => '30000000',
            'cep_fim' => '30999999',
        ]);
        Unidade::factory()->create([
            'nome' => 'Sobreposta',
            'cep_inicio' => '30100000',
            'cep_fim' => '30199999',
        ]);

        $usuario = User::factory()->create(['unidade_id' => $minha->id]);

        $resultado = app(TerritorioService::class)->verificarCep($usuario, '30130010');

        $this->assertTrue($resultado['permitido']);
        $this->assertSame('minha_unidade', $resultado['motivo']);
        $this->assertSame($minha->id, $resultado['unidade_id']);
    }
}
