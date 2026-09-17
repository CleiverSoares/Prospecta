<?php

namespace Tests\Feature;

use App\Services\PoligonoCepService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PoligonoCepServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_estima_faixa_de_cep_pelos_cantos_do_poligono(): void
    {
        config(['prospecta.mapbox.token' => 'pk.test']);

        Http::fake([
            'api.mapbox.com/*' => Http::sequence()
                ->push(['features' => [['text' => '30130-010']]])
                ->push(['features' => [['text' => '30130-010']]])
                ->push(['features' => [['text' => '30411-185']]])
                ->push(['features' => [['text' => '30411-185']]])
                ->push(['features' => [['text' => '30260-080']]]),
        ]);

        $geojson = [
            'type' => 'Polygon',
            'coordinates' => [[
                [-43.95, -19.95],
                [-43.90, -19.95],
                [-43.90, -19.90],
                [-43.95, -19.90],
                [-43.95, -19.95],
            ]],
        ];

        $faixa = app(PoligonoCepService::class)->estimarFaixa($geojson);

        $this->assertSame('30130010', $faixa['cep_inicio']);
        $this->assertSame('30411185', $faixa['cep_fim']);
    }

    public function test_poligono_vazio_falha_validacao(): void
    {
        config(['prospecta.mapbox.token' => 'pk.test']);

        $this->expectException(ValidationException::class);

        app(PoligonoCepService::class)->estimarFaixa([
            'type' => 'Polygon',
            'coordinates' => [],
        ]);
    }
}
