<?php

namespace Tests\Feature;

use App\Enums\StatusReceita;
use App\Enums\StatusVisita;
use App\Models\Prospecto;
use App\Models\User;
use App\Models\Visita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitaModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_persiste_visita_ligada_a_prospecto_e_usuario(): void
    {
        $usuario = User::factory()->create();
        $prospecto = Prospecto::query()->create([
            'cnpj' => '99888777000166',
            'razao_social' => 'Beta Serviços ME',
            'cep' => '20040020',
            'status_receita' => StatusReceita::Ativa,
        ]);

        $visita = Visita::query()->create([
            'prospecto_id' => $prospecto->id,
            'user_id' => $usuario->id,
            'status' => StatusVisita::Feita,
            'checkin_lat' => -22.906847,
            'checkin_lng' => -43.172897,
            'caminho_foto' => 'visitas/foto.jpg',
            'caminho_audio' => 'visitas/audio.m4a',
        ]);

        $this->assertDatabaseHas('visitas', [
            'id' => $visita->id,
            'prospecto_id' => $prospecto->id,
            'user_id' => $usuario->id,
            'status' => 'FEITA',
        ]);

        $visita = $visita->fresh(['prospecto', 'usuario']);

        $this->assertSame(StatusVisita::Feita, $visita->status);
        $this->assertTrue($visita->prospecto->is($prospecto));
        $this->assertTrue($visita->usuario->is($usuario));
        $this->assertTrue($prospecto->fresh()->visitas->contains($visita));
        $this->assertTrue($usuario->fresh()->visitas->contains($visita));
    }
}
