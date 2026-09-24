<?php

namespace Tests\Feature;

use App\Enums\StatusParadaPlanejada;
use App\Enums\StatusVisita;
use App\Models\ParadaPlanejada;
use App\Models\Prospecto;
use App\Models\User;
use App\Models\Visita;
use App\Services\AgendaDiaService;
use App\Services\RotaDiaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RotaDiaAgendaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('adm');
        Role::findOrCreate('gestor');
        Role::findOrCreate('vendedor');
    }

    public function test_publicar_rota_cria_plano_do_dia(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');
        $p1 = Prospecto::factory()->create(['lat' => -22.9, 'lng' => -43.1]);
        $p2 = Prospecto::factory()->create(['lat' => -22.91, 'lng' => -43.11]);

        $rota = app(RotaDiaService::class)->publicar($vendedor, [
            ['id' => $p1->id, 'ordem' => 1, 'lat' => $p1->lat, 'lng' => $p1->lng],
            ['id' => $p2->id, 'ordem' => 2, 'lat' => $p2->lat, 'lng' => $p2->lng],
        ]);

        $this->assertNotNull($rota);
        $this->assertSame(2, $rota->total_paradas);
        $this->assertSame(2, ParadaPlanejada::query()->where('rota_dia_id', $rota->id)->count());
    }

    public function test_checkin_marca_parada_do_plano(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');
        $prospecto = Prospecto::factory()->create(['lat' => -22.9, 'lng' => -43.1]);

        app(RotaDiaService::class)->publicar($vendedor, [
            ['id' => $prospecto->id, 'ordem' => 1, 'lat' => -22.9, 'lng' => -43.1],
        ]);

        $visita = Visita::query()->create([
            'prospecto_id' => $prospecto->id,
            'user_id' => $vendedor->id,
            'status' => StatusVisita::SemNinguem,
            'checkin_lat' => -22.9,
            'checkin_lng' => -43.1,
        ]);

        $parada = app(RotaDiaService::class)->marcarCheckin($visita);

        $this->assertNotNull($parada);
        $this->assertSame(StatusParadaPlanejada::Feita, $parada->status);
        $this->assertSame($visita->id, $parada->visita_id);
    }

    public function test_agenda_mostra_progresso_do_plano(): void
    {
        $vendedor = User::factory()->create(['ativo' => true]);
        $vendedor->assignRole('vendedor');
        $p1 = Prospecto::factory()->create(['lat' => -22.9, 'lng' => -43.1, 'razao_social' => 'A']);
        $p2 = Prospecto::factory()->create(['lat' => -22.91, 'lng' => -43.11, 'razao_social' => 'B']);

        app(RotaDiaService::class)->publicar($vendedor, [
            ['id' => $p1->id, 'ordem' => 1, 'lat' => $p1->lat, 'lng' => $p1->lng],
            ['id' => $p2->id, 'ordem' => 2, 'lat' => $p2->lat, 'lng' => $p2->lng],
        ]);

        $visita = Visita::query()->create([
            'prospecto_id' => $p1->id,
            'user_id' => $vendedor->id,
            'status' => StatusVisita::Feita,
            'checkin_lat' => -22.9,
            'checkin_lng' => -43.1,
            'caminho_foto' => 'x.jpg',
            'caminho_audio' => 'x.wav',
        ]);
        app(RotaDiaService::class)->marcarCheckin($visita);

        $cards = app(AgendaDiaService::class)->montar(null)['cards'];
        $card = collect($cards)->first(fn ($c) => $c['vendedor']->id === $vendedor->id);

        $this->assertTrue($card['tem_plano']);
        $this->assertSame(1, $card['feitas_plano']);
        $this->assertSame(2, $card['total_plano']);
    }

    public function test_cancelar_plano_remove_da_agenda(): void
    {
        $vendedor = User::factory()->create(['ativo' => true]);
        $vendedor->assignRole('vendedor');
        $p1 = Prospecto::factory()->create(['lat' => -22.9, 'lng' => -43.1]);

        $rota = app(RotaDiaService::class)->publicar($vendedor, [
            ['id' => $p1->id, 'ordem' => 1, 'lat' => $p1->lat, 'lng' => $p1->lng],
        ]);
        $this->assertNotNull($rota);

        $ok = app(RotaDiaService::class)->cancelarDoDia($vendedor->id);
        $this->assertTrue($ok);
        $this->assertNull(app(RotaDiaService::class)->buscarDoDia($vendedor->id));
        $this->assertSame(0, ParadaPlanejada::query()->count());

        $cards = app(AgendaDiaService::class)->montar(null)['cards'];
        $card = collect($cards)->first(fn ($c) => $c['vendedor']->id === $vendedor->id);
        $this->assertFalse($card['tem_plano'] ?? false);
    }
}
