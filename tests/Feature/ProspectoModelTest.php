<?php

namespace Tests\Feature;

use App\Enums\StatusReceita;
use App\Models\Prospecto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProspectoModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_persiste_prospecto_por_cnpj(): void
    {
        $prospecto = Prospecto::query()->create([
            'cnpj' => '12345678000199',
            'razao_social' => 'Acme Comércio LTDA',
            'cep' => '01310100',
            'lat' => -23.561414,
            'lng' => -46.655881,
            'status_receita' => StatusReceita::Ativa,
            'is_cliente' => false,
        ]);

        $this->assertDatabaseHas('prospectos', [
            'id' => $prospecto->id,
            'cnpj' => '12345678000199',
            'razao_social' => 'Acme Comércio LTDA',
            'status_receita' => 'ATIVA',
            'is_cliente' => 0,
        ]);

        $this->assertSame(StatusReceita::Ativa, $prospecto->fresh()->status_receita);
    }
}
