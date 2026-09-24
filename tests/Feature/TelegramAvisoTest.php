<?php

namespace Tests\Feature;

use App\Enums\StatusVisita;
use App\Events\VisitaConcluida;
use App\Models\Prospecto;
use App\Models\Unidade;
use App\Models\User;
use App\Models\Visita;
use App\Services\TelegramAvisoService;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\ComSetupDiario;
use Tests\TestCase;

class TelegramAvisoTest extends TestCase
{
    use ComSetupDiario;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);

        config([
            'prospecta.telegram.enabled' => true,
            'prospecta.telegram.bot_token' => '123:ABC',
            'prospecta.telegram.chat_adm' => '1001',
            'prospecta.telegram.chat_gestor' => '1002',
            'prospecta.telegram.api_base' => 'https://api.telegram.org',
            'prospecta.telegram.debounce_minutos' => 30,
            'prospecta.telegram.avisos.fora_territorio' => true,
            'prospecta.telegram.avisos.visita_concluida' => true,
        ]);
    }

    public function test_fora_territorio_dispara_telegram_para_adm_e_gestor(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        // Quadrado pequeno em Copacabana; ponto longe = fora.
        $unidade = Unidade::factory()->create([
            'nome' => 'Filial Copa',
            'poligono_geojson' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [-43.185, -22.975],
                    [-43.180, -22.975],
                    [-43.180, -22.970],
                    [-43.185, -22.970],
                    [-43.185, -22.975],
                ]],
            ],
        ]);

        $vendedor = User::factory()->create([
            'name' => 'Diego Fora',
            'unidade_id' => $unidade->id,
        ]);
        $vendedor->assignRole('vendedor');

        $this->actingAs($vendedor)
            ->withSession($this->sessionSetup())
            ->postJson(route('app.localizacao.store'), [
                'lat' => -22.50,
                'lng' => -44.10,
            ])
            ->assertCreated();

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['chat_id'] === '1001'
            && str_contains($request['text'], 'fora do território')
            && str_contains($request['text'], 'Diego Fora'));
    }

    public function test_debounce_nao_reenvia_mesmo_aviso(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $svc = app(TelegramAvisoService::class);
        $this->assertTrue($svc->avisar('teste:1', 'ola', ['adm']));
        $this->assertFalse($svc->avisar('teste:1', 'ola de novo', ['adm']));
        Http::assertSentCount(1);
    }

    public function test_visita_feita_dispara_telegram(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $vendedor = User::factory()->create(['name' => 'Camila Checkin']);
        $vendedor->assignRole('vendedor');
        $prospecto = Prospecto::factory()->create(['razao_social' => 'Padaria Teste']);

        $visita = Visita::factory()->create([
            'user_id' => $vendedor->id,
            'prospecto_id' => $prospecto->id,
            'status' => StatusVisita::Feita,
        ]);

        VisitaConcluida::dispatch($visita);

        Http::assertSent(fn ($request) => str_contains($request['text'], 'visita feita')
            && str_contains($request['text'], 'Camila Checkin')
            && str_contains($request['text'], 'Padaria Teste'));
    }

    public function test_desligado_nao_chama_api(): void
    {
        config(['prospecta.telegram.enabled' => false]);
        Http::fake();

        app(TelegramAvisoService::class)->avisar('x', ' Ignorado', ['adm']);

        Http::assertNothingSent();
        $this->assertDatabaseCount('localizacoes', 0);
    }
}
