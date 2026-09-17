<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EstimarCepsPoligonoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
        config(['prospecta.mapbox.token' => 'pk.test']);
    }

    public function test_adm_estima_ceps_do_poligono(): void
    {
        Http::fake([
            'api.mapbox.com/*' => Http::response([
                'features' => [['text' => '01310-100']],
            ]),
        ]);

        $adm = User::factory()->create();
        $adm->assignRole('adm');

        $this->actingAs($adm)
            ->postJson(route('admin.unidades.estimar-ceps'), [
                'poligono_geojson' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [-46.66, -23.56],
                        [-46.64, -23.56],
                        [-46.64, -23.54],
                        [-46.66, -23.54],
                        [-46.66, -23.56],
                    ]],
                ],
            ])
            ->assertOk()
            ->assertJsonStructure(['cep_inicio', 'cep_fim']);
    }

    public function test_vendedor_nao_estima_ceps(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');

        $this->actingAs($vendedor)
            ->postJson(route('admin.unidades.estimar-ceps'), [
                'poligono_geojson' => ['type' => 'Polygon', 'coordinates' => []],
            ])
            ->assertForbidden();
    }
}
