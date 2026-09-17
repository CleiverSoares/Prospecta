<?php

namespace Tests\Feature;

use App\Enums\TipoUnidade;
use App\Models\Unidade;
use App\Services\UnidadeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UnidadeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_atualiza_e_exclui_unidade(): void
    {
        $service = app(UnidadeService::class);

        $unidade = $service->criar([
            'nome' => 'Matriz RJ',
            'tipo' => TipoUnidade::Matriz,
            'cep_inicio' => '20000-000',
            'cep_fim' => '20099-999',
        ]);

        $this->assertSame('20000000', $unidade->cep_inicio);
        $this->assertSame('20099999', $unidade->cep_fim);

        $atualizada = $service->atualizar($unidade, [
            'nome' => 'Matriz Rio',
            'tipo' => TipoUnidade::Matriz,
            'cep_inicio' => '20000000',
            'cep_fim' => '20100000',
        ]);

        $this->assertSame('Matriz Rio', $atualizada->nome);
        $this->assertSame('20100000', $atualizada->cep_fim);

        $service->excluir($atualizada);
        $this->assertDatabaseMissing('unidades', ['id' => $atualizada->id]);
    }

    public function test_rejeita_faixa_de_cep_invertida(): void
    {
        $this->expectException(ValidationException::class);

        app(UnidadeService::class)->criar([
            'nome' => 'Filial inválida',
            'tipo' => TipoUnidade::Filial,
            'cep_inicio' => '30000000',
            'cep_fim' => '20000000',
        ]);
    }

    public function test_lista_unidades_ordenadas_por_nome(): void
    {
        Unidade::factory()->create(['nome' => 'Zeta']);
        Unidade::factory()->create(['nome' => 'Alpha']);

        $lista = app(UnidadeService::class)->listar();

        $this->assertSame(['Alpha', 'Zeta'], $lista->pluck('nome')->all());
    }
}
