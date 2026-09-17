<?php

namespace Tests\Feature;

use App\Models\Prospecto;
use App\Models\Unidade;
use App\Models\Visita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoriesDominioTest extends TestCase
{
    use RefreshDatabase;

    public function test_factories_do_dominio_criam_registros(): void
    {
        $unidade = Unidade::factory()->create();
        $prospecto = Prospecto::factory()->create();
        $visita = Visita::factory()->create([
            'prospecto_id' => $prospecto->id,
        ]);

        $this->assertDatabaseCount('unidades', 1);
        $this->assertDatabaseCount('prospectos', 1);
        $this->assertDatabaseCount('visitas', 1);
        $this->assertNotNull($unidade->nome);
        $this->assertSame($prospecto->id, $visita->prospecto_id);
    }
}
