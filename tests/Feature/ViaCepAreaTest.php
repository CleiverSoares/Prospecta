<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ViaCepService;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ViaCepAreaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
    }

    public function test_via_cep_service_mapeia_endereco(): void
    {
        Http::fake([
            'viacep.com.br/*' => Http::response([
                'cep' => '30130-010',
                'logradouro' => 'Avenida Afonso Pena',
                'bairro' => 'Centro',
                'localidade' => 'Belo Horizonte',
                'uf' => 'MG',
            ]),
        ]);

        $endereco = app(ViaCepService::class)->buscar('30130010');

        $this->assertSame('30130010', $endereco['cep']);
        $this->assertSame('Centro', $endereco['bairro']);
        $this->assertSame('MG', $endereco['uf']);
    }

    public function test_via_cep_sugere_bairros_na_cidade(): void
    {
        Http::fake([
            'viacep.com.br/*' => Http::response([
                [
                    'cep' => '25953-000',
                    'logradouro' => 'Rua Exemplo',
                    'bairro' => 'Várzea',
                    'localidade' => 'Teresópolis',
                    'uf' => 'RJ',
                ],
                [
                    'cep' => '25953-001',
                    'logradouro' => 'Rua Outra',
                    'bairro' => 'Várzea',
                    'localidade' => 'Teresópolis',
                    'uf' => 'RJ',
                ],
                [
                    'cep' => '28000-000',
                    'logradouro' => 'Av. Teresópolis',
                    'bairro' => 'Parque Guarus',
                    'localidade' => 'Campos dos Goytacazes',
                    'uf' => 'RJ',
                ],
            ]),
        ]);

        $bairros = app(ViaCepService::class)->sugerirBairros('RJ', 'Teresópolis', 'var');

        $this->assertSame(['Várzea'], $bairros);
    }

    public function test_ibge_lista_municipios_por_uf(): void
    {
        Http::fake([
            'servicodados.ibge.gov.br/*' => Http::response([
                ['id' => 3301850, 'nome' => 'Guapimirim'],
                ['id' => 3305802, 'nome' => 'Teresópolis'],
            ]),
        ]);

        $lista = app(\App\Services\IbgeService::class)->municipiosPorUf('RJ');

        $this->assertCount(2, $lista);
        $this->assertSame('Guapimirim', $lista[0]['nome']);
    }
}
