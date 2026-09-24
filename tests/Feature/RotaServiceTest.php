<?php

namespace Tests\Feature;

use App\Models\Prospecto;
use App\Services\RotaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RotaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'prospecta.rota.faixa_lng' => 0.004,
            'prospecta.rota.limite_paradas' => 12,
            'prospecta.rota.predio_metros' => 50,
            'prospecta.rota.raio_prospeccao_km' => 2,
            'prospecta.rota.raio_pos_venda_km' => 50,
            'prospecta.rota.duracao_visita_min' => 30,
            'prospecta.rota.velocidade_kmh' => 20,
            'prospecta.rota.almoco_inicio' => '12:00',
            'prospecta.rota.almoco_duracao_min' => 60,
            'prospecta.google.directions_base_url' => 'https://www.google.com/maps/dir/',
        ]);
    }

    public function test_agrupa_predio_por_proximidade_e_mantem_sequencia(): void
    {
        // Três no mesmo prédio (~10 m) + um longe
        $p1 = Prospecto::factory()->create(['razao_social' => 'Sala 1', 'endereco' => 'Rua A, 100', 'lat' => -19.9200, 'lng' => -43.9400]);
        $p2 = Prospecto::factory()->create(['razao_social' => 'Sala 2', 'endereco' => 'Rua A, 100', 'lat' => -19.92005, 'lng' => -43.94002]);
        $p3 = Prospecto::factory()->create(['razao_social' => 'Sala 3', 'endereco' => 'Rua A, 102', 'lat' => -19.9201, 'lng' => -43.94005]);
        $longe = Prospecto::factory()->create(['razao_social' => 'Longe', 'endereco' => 'Outra', 'lat' => -19.95, 'lng' => -43.90]);

        $rota = app(RotaService::class)->gerar(
            [$longe, $p2, $p1, $p3],
            null,
            12,
            ['segmento' => 'MISTO', 'horas' => '08:00-17:00', 'mix_prospeccao' => 50],
        );

        $ids = array_column($rota['itens'], 'id');
        $posPredio = [array_search($p1->id, $ids), array_search($p2->id, $ids), array_search($p3->id, $ids)];
        sort($posPredio);
        $this->assertSame([$posPredio[0], $posPredio[0] + 1, $posPredio[0] + 2], $posPredio, 'as 3 salas devem ser consecutivas');
        $this->assertGreaterThanOrEqual(1, collect($rota['avisos'])->filter(fn ($a) => str_contains($a, 'Prédio'))->count());
    }

    public function test_contabilidade_dias_01_a_05_nao_agenda(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 3, 9, 0, 0, 'America/Sao_Paulo'));

        $p = Prospecto::factory()->create(['lat' => -19.9, 'lng' => -43.9]);

        $rota = app(RotaService::class)->gerar(
            [$p],
            null,
            12,
            ['segmento' => 'CONTABIL', 'horas' => '08:00-17:00', 'mix_prospeccao' => 50],
        );

        $this->assertSame([], $rota['itens']);
        $this->assertTrue(collect($rota['avisos'])->contains(fn ($a) => str_contains($a, '01–05') || str_contains($a, '01-05')));

        Carbon::setTestNow();
    }

    public function test_restaurante_nao_agenda_no_almoco(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 17, 8, 0, 0, 'America/Sao_Paulo'));

        $prospectos = [];
        for ($i = 0; $i < 6; $i++) {
            $prospectos[] = Prospecto::factory()->create([
                'lat' => -19.92 + ($i * 0.001),
                'lng' => -43.94,
            ]);
        }

        $rota = app(RotaService::class)->gerar(
            $prospectos,
            ['lat' => -19.92, 'lng' => -43.94],
            12,
            ['segmento' => 'RESTAURANTE', 'horas' => '08:00-17:00', 'mix_prospeccao' => 50],
        );

        foreach ($rota['itens'] as $item) {
            $inicio = Carbon::parse($item['horario_estimado']);
            $minutos = $inicio->hour * 60 + $inicio->minute;
            $this->assertTrue(
                $minutos < (11 * 60 + 30) || $minutos >= (14 * 60),
                "visita em {$item['horario_estimado']} caiu na janela 11:30–14:00",
            );
            $this->assertNotSame('almoco', $item['bloco']);
        }

        Carbon::setTestNow();
    }

    public function test_combustivel_filtra_raio_quando_mix_alto(): void
    {
        $origem = ['lat' => -19.92, 'lng' => -43.94];
        $perto = Prospecto::factory()->create(['lat' => -19.921, 'lng' => -43.941]); // ~150m
        $longe = Prospecto::factory()->create(['lat' => -19.98, 'lng' => -43.88]); // >2km

        $rota = app(RotaService::class)->gerar(
            [$perto, $longe],
            $origem,
            12,
            ['segmento' => 'MISTO', 'horas' => '08:00-17:00', 'mix_prospeccao' => 85],
        );

        $ids = array_column($rota['itens'], 'id');
        $this->assertContains($perto->id, $ids);
        $this->assertNotContains($longe->id, $ids);
    }

    public function test_parada_mais_proxima_do_gps_vem_primeiro(): void
    {
        $origem = ['lat' => -22.4120, 'lng' => -42.9660]; // Meudon / Tupinins
        $vizinho = Prospecto::factory()->create([
            'razao_social' => 'Vizinho Tupinins',
            'lat' => -22.4121,
            'lng' => -42.9661, // ~15 m
        ]);
        // Prédio com 3 salas bem mais longe — no algoritmo antigo ia primeiro
        $s1 = Prospecto::factory()->create(['razao_social' => 'Sala longe 1', 'lat' => -22.4200, 'lng' => -42.9750]);
        $s2 = Prospecto::factory()->create(['razao_social' => 'Sala longe 2', 'lat' => -22.42005, 'lng' => -42.97502]);
        $s3 = Prospecto::factory()->create(['razao_social' => 'Sala longe 3', 'lat' => -22.4201, 'lng' => -42.97505]);

        $rota = app(RotaService::class)->gerar(
            [$s1, $s2, $s3, $vizinho],
            $origem,
            12,
            ['segmento' => 'CONTABIL', 'horas' => '08:00-17:00', 'mix_prospeccao' => 70],
        );

        $this->assertSame($vizinho->id, $rota['itens'][0]['id']);
    }

    public function test_blocos_manha_e_tarde_com_almoco_no_meio(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 17, 8, 0, 0, 'America/Sao_Paulo'));

        $prospectos = [];
        for ($i = 0; $i < 8; $i++) {
            $prospectos[] = Prospecto::factory()->create([
                'lat' => -19.92 + ($i * 0.002),
                'lng' => -43.94 + ($i * 0.001),
            ]);
        }

        $rota = app(RotaService::class)->gerar(
            $prospectos,
            ['lat' => -19.92, 'lng' => -43.94],
            12,
            ['segmento' => 'MISTO', 'horas' => '08:00-17:00', 'mix_prospeccao' => 50],
        );

        $this->assertNotEmpty($rota['itens']);
        $this->assertArrayHasKey('blocos', $rota);
        $this->assertArrayHasKey('almoco', $rota['blocos']);

        $viuManha = false;
        $viuTarde = false;
        foreach ($rota['itens'] as $item) {
            if ($item['bloco'] === 'manha') {
                $viuManha = true;
                $this->assertLessThan('12:00', substr($item['horario_estimado'], 11, 5));
            }
            if ($item['bloco'] === 'tarde') {
                $viuTarde = true;
                $this->assertGreaterThanOrEqual('13:00', substr($item['horario_estimado'], 11, 5));
            }
        }
        $this->assertTrue($viuManha || $viuTarde);

        Carbon::setTestNow();
    }

    public function test_limite_paradas_respeitado(): void
    {
        config(['prospecta.rota.limite_paradas' => 2]);

        $a = Prospecto::factory()->create(['lat' => -19.90, 'lng' => -43.96]);
        $b = Prospecto::factory()->create(['lat' => -19.92, 'lng' => -43.96]);
        $c = Prospecto::factory()->create(['lat' => -19.91, 'lng' => -43.92]);

        $rota = app(RotaService::class)->gerar([$c, $b, $a]);

        $this->assertCount(2, $rota['itens']);
    }

    public function test_ignora_prospectos_sem_coordenadas(): void
    {
        $com = Prospecto::factory()->create(['lat' => -19.9, 'lng' => -43.9]);
        $sem = Prospecto::factory()->create(['lat' => null, 'lng' => null]);

        $rota = app(RotaService::class)->gerar([$sem, $com]);

        $this->assertCount(1, $rota['itens']);
        $this->assertSame($com->id, $rota['itens'][0]['id']);
    }

    public function test_rota_vazia_nao_gera_url(): void
    {
        $rota = app(RotaService::class)->gerar([]);

        $this->assertSame([], $rota['itens']);
        $this->assertNull($rota['url_maps']);
    }
}
