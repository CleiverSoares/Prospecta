<?php

namespace Tests\Feature;

use App\Models\Prospecto;
use App\Services\RotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RotaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ordena_por_regra_vertical_e_aplica_janela_de_ouro(): void
    {
        config([
            'prospecta.rota.faixa_lng' => 0.004,
            'prospecta.rota.janela_ouro' => 2,
            'prospecta.google.directions_base_url' => 'https://www.google.com/maps/dir/',
        ]);

        // Mesma faixa lng (~-43.96): A mais ao norte que B → A depois B.
        // Faixa leste (~-43.92): C.
        $a = Prospecto::factory()->create([
            'razao_social' => 'A Norte',
            'lat' => -19.90,
            'lng' => -43.96,
        ]);
        $b = Prospecto::factory()->create([
            'razao_social' => 'B Sul',
            'lat' => -19.92,
            'lng' => -43.96,
        ]);
        $c = Prospecto::factory()->create([
            'razao_social' => 'C Leste',
            'lat' => -19.91,
            'lng' => -43.92,
        ]);

        $rota = app(RotaService::class)->gerar([$c, $b, $a]);

        $this->assertSame([$a->id, $b->id], array_column($rota['itens'], 'id'));
        $this->assertSame(1, $rota['itens'][0]['ordem']);
        $this->assertSame(2, $rota['itens'][1]['ordem']);
        $this->assertStringStartsWith('https://www.google.com/maps/dir/', $rota['url_maps']);
        $this->assertStringContainsString('-19.9,-43.96', $rota['url_maps']);
        $this->assertStringContainsString('-19.92,-43.96', $rota['url_maps']);
        $this->assertStringNotContainsString((string) $c->lat, $rota['url_maps']);
    }

    public function test_ignora_prospectos_sem_coordenadas(): void
    {
        config(['prospecta.rota.janela_ouro' => 12]);

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
