<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Receita\MockConsultaReceita;
use Tests\TestCase;

class MockConsultaReceitaTest extends TestCase
{
    public function test_mock_retorna_fixture_conhecida(): void
    {
        $dados = app(MockConsultaReceita::class)->consultar('11222333000181');

        $this->assertSame('11222333000181', $dados['cnpj']);
        $this->assertSame('Padaria Central LTDA', $dados['razao_social']);
        $this->assertSame('30130010', $dados['cep']);
    }
}
