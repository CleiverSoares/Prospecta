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
}
