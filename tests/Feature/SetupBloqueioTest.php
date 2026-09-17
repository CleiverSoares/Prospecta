<?php

namespace Tests\Feature;

use App\Models\Prospecto;
use App\Models\User;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupBloqueioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
    }

    public function test_sem_setup_redireciona_area_para_setup(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');

        $this->actingAs($vendedor)
            ->get(route('app.area'))
            ->assertRedirect(route('app.setup'));
    }

    public function test_com_setup_acessa_area(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');

        $this->actingAs($vendedor)
            ->withSession([
                'prospecta.setup' => [
                    'local' => 'Copacabana',
                    'segmento' => 'MISTO',
                    'horas' => '08:00-17:00',
                    'mix_prospeccao' => 80,
                ],
            ])
            ->get(route('app.area'))
            ->assertOk();
    }

    public function test_salvar_setup_grava_sessao(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');

        $this->actingAs($vendedor)
            ->postJson(route('app.setup.store'), [
                'local' => 'Ipanema',
                'segmento' => 'RESTAURANTE',
                'horas' => '09:00-18:00',
                'mix_prospeccao' => 70,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertEquals('Ipanema', session('prospecta.setup.local'));
        $this->assertEquals(70.0, session('prospecta.setup.mix_prospeccao'));
    }

    public function test_gerar_rota_sem_setup_sessao_retorna_422(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');
        $p = Prospecto::factory()->create(['lat' => -19.9, 'lng' => -43.9]);

        $this->actingAs($vendedor)
            ->postJson(route('app.rota.gerar'), [
                'prospecto_ids' => [$p->id],
                'local' => 'BH',
                'segmento' => 'MISTO',
                'horas' => '08:00-17:00',
                'mix_prospeccao' => 50,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Complete o Setup do dia antes de continuar.');
    }
}
