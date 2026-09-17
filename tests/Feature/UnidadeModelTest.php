<?php

namespace Tests\Feature;

use App\Enums\TipoUnidade;
use App\Models\Unidade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnidadeModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_persiste_unidade_com_tipo_enum(): void
    {
        $unidade = Unidade::query()->create([
            'nome' => 'Matriz SP',
            'tipo' => TipoUnidade::Matriz,
            'cep_inicio' => '01000000',
            'cep_fim' => '05999999',
            'poligono_geojson' => ['type' => 'Polygon', 'coordinates' => []],
        ]);

        $this->assertDatabaseHas('unidades', [
            'id' => $unidade->id,
            'nome' => 'Matriz SP',
            'tipo' => 'MATRIZ',
            'cep_inicio' => '01000000',
            'cep_fim' => '05999999',
        ]);

        $this->assertSame(TipoUnidade::Matriz, $unidade->fresh()->tipo);
        $this->assertIsArray($unidade->fresh()->poligono_geojson);
    }
}
