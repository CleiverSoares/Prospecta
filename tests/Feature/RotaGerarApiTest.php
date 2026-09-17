<?php

namespace Tests\Feature;

use App\Models\Prospecto;
use App\Models\User;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\ComSetupDiario;
use Tests\TestCase;

class RotaGerarApiTest extends TestCase
{
    use ComSetupDiario;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
        config([
            'prospecta.rota.faixa_lng' => 0.004,
            'prospecta.rota.limite_paradas' => 12,
            'prospecta.google.directions_base_url' => 'https://www.google.com/maps/dir/',
        ]);
    }

    public function test_vendedor_gera_rota_via_api(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');

        $a = Prospecto::factory()->create(['lat' => -19.90, 'lng' => -43.96]);
        $b = Prospecto::factory()->create(['lat' => -19.92, 'lng' => -43.96]);

        $resposta = $this->actingAs($vendedor)
            ->withSession($this->sessionSetup())
            ->postJson(route('app.rota.gerar'), [
                'prospecto_ids' => [$b->id, $a->id],
                'local' => 'Belo Horizonte',
                'segmento' => 'MISTO',
                'horas' => '08:00-17:00',
                'mix_prospeccao' => 80,
            ])
            ->assertOk()
            ->assertJsonStructure(['itens', 'url_maps', 'url_waze', 'avisos', 'blocos']);

        $ids = collect($resposta->json('itens'))->pluck('id')->all();
        $this->assertContains($a->id, $ids);
        $this->assertContains($b->id, $ids);
    }

    public function test_exige_setup_completo_antes_da_rota(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');

        $p = Prospecto::factory()->create(['lat' => -19.9, 'lng' => -43.9]);

        $this->actingAs($vendedor)
            ->postJson(route('app.rota.gerar'), [
                'prospecto_ids' => [$p->id],
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Complete o Setup do dia antes de continuar.');
    }

    public function test_exige_campos_setup_no_payload_mesmo_com_sessao(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');
        $p = Prospecto::factory()->create(['lat' => -19.9, 'lng' => -43.9]);

        $this->actingAs($vendedor)
            ->withSession($this->sessionSetup())
            ->postJson(route('app.rota.gerar'), [
                'prospecto_ids' => [$p->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['local', 'segmento', 'horas', 'mix_prospeccao']);
    }

    public function test_exige_pelo_menos_um_prospecto(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');

        $this->actingAs($vendedor)
            ->withSession($this->sessionSetup())
            ->postJson(route('app.rota.gerar'), [
                'prospecto_ids' => [],
                'local' => 'BH',
                'segmento' => 'MISTO',
                'horas' => '08:00-17:00',
                'mix_prospeccao' => 50,
            ])
            ->assertStatus(422);
    }
}
