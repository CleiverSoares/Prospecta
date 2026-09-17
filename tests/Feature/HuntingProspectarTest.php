<?php

namespace Tests\Feature;

use App\Models\Unidade;
use App\Models\User;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\ComSetupDiario;
use Tests\TestCase;

class HuntingProspectarTest extends TestCase
{
    use ComSetupDiario;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
        config([
            'prospecta.google.maps_api_key' => 'test-key',
            'prospecta.google.places_api_key' => 'test-key',
        ]);
    }

    public function test_vendedor_prospecta_com_places_em_area_livre(): void
    {
        Http::fake([
            'maps.googleapis.com/maps/api/geocode/*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'geometry' => ['location' => ['lat' => -22.9711, 'lng' => -43.1822]],
                    'address_components' => [[
                        'long_name' => '22041-080',
                        'types' => ['postal_code'],
                    ]],
                ]],
            ]),
            'maps.googleapis.com/maps/api/place/nearbysearch/*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'place_id' => 'ChIJteste123',
                    'name' => 'Contábil Copacabana',
                    'vicinity' => 'Av. Atlântica, 100',
                    'geometry' => ['location' => ['lat' => -22.9712, 'lng' => -43.1823]],
                ]],
            ]),
            'maps.googleapis.com/maps/api/place/details/*' => Http::response([
                'status' => 'OK',
                'result' => [
                    'name' => 'Contábil Copacabana',
                    'formatted_address' => 'Av. Atlântica, 100',
                    'formatted_phone_number' => '(21) 9999-0000',
                    'rating' => 4.5,
                    'types' => ['accounting'],
                    'photos' => [[
                        'photo_reference' => 'foto-teste-ref',
                        'html_attributions' => [],
                    ]],
                ],
            ]),
        ]);

        $vendedor = User::factory()->create(['unidade_id' => null]);
        $vendedor->assignRole('vendedor');

        $resposta = $this->actingAs($vendedor)
            ->withSession($this->sessionSetup())
            ->postJson(route('app.area.prospectar'), [
                'bairro' => 'Copacabana',
                'cidade' => 'Rio de Janeiro',
                'uf' => 'RJ',
                'segmento' => 'CONTABIL',
            ]);

        $resposta
            ->assertOk()
            ->assertJsonPath('territorio.motivo', 'area_livre')
            ->assertJsonPath('prospectos.0.razao_social', 'Contábil Copacabana')
            ->assertJsonPath('prospectos.0.origem', 'GOOGLE');

        $foto = $resposta->json('prospectos.0.foto');
        $this->assertIsString($foto);
        $this->assertStringContainsString('photo_reference=foto-teste-ref', $foto);
    }

    public function test_bloqueia_area_de_outra_unidade(): void
    {
        Unidade::factory()->create([
            'nome' => 'Rep Sul',
            'cep_inicio' => '27000000',
            'cep_fim' => '27999999',
        ]);

        $vendedor = User::factory()->create(['unidade_id' => null]);
        $vendedor->assignRole('vendedor');

        Http::fake([
            'maps.googleapis.com/maps/api/geocode/*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'geometry' => ['location' => ['lat' => -22.5, 'lng' => -44.1]],
                    'address_components' => [[
                        'long_name' => '27200-000',
                        'types' => ['postal_code'],
                    ]],
                ]],
            ]),
        ]);

        $this->actingAs($vendedor)
            ->withSession($this->sessionSetup())
            ->postJson(route('app.area.prospectar'), [
                'cep' => '27200-000',
                'bairro' => 'Centro',
                'cidade' => 'Volta Redonda',
                'uf' => 'RJ',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['area']);
    }
}
