<?php

namespace Tests\Feature;

use App\Contracts\ConsultaReceitaInterface;
use App\Models\Unidade;
use App\Models\User;
use App\Services\ProspectoService;
use App\Services\Receita\MockConsultaReceita;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProspectoServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
        config(['prospecta.receita_ws.driver' => 'mock']);
        $this->app->bind(ConsultaReceitaInterface::class, MockConsultaReceita::class);
    }

    public function test_busca_upsert_prospecto_em_territorio_livre(): void
    {
        $usuario = User::factory()->create(['unidade_id' => null]);

        $resultado = app(ProspectoService::class)->buscar($usuario, '11.222.333/0001-81');

        $this->assertTrue($resultado['territorio']['permitido']);
        $this->assertSame('Padaria Central LTDA', $resultado['prospecto']->razao_social);
        $this->assertDatabaseHas('prospectos', [
            'cnpj' => '11222333000181',
            'cep' => '30130010',
        ]);
    }

    public function test_busca_bloqueia_cep_de_outra_unidade(): void
    {
        $minha = Unidade::factory()->create([
            'cep_inicio' => '30000000',
            'cep_fim' => '30999999',
        ]);
        Unidade::factory()->create([
            'nome' => 'SP Centro',
            'cep_inicio' => '01000000',
            'cep_fim' => '01999999',
        ]);

        $usuario = User::factory()->create(['unidade_id' => $minha->id]);

        $this->expectException(ValidationException::class);

        app(ProspectoService::class)->buscar($usuario, '99888777000166');
    }

    public function test_busca_permite_cep_da_propria_unidade(): void
    {
        $unidade = Unidade::factory()->create([
            'cep_inicio' => '30000000',
            'cep_fim' => '30999999',
        ]);
        $usuario = User::factory()->create(['unidade_id' => $unidade->id]);

        $resultado = app(ProspectoService::class)->buscar($usuario, '11222333000181');

        $this->assertTrue($resultado['territorio']['permitido']);
        $this->assertSame('minha_unidade', $resultado['territorio']['motivo']);
    }
}
