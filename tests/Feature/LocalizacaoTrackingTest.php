<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\ComSetupDiario;
use Tests\TestCase;

class LocalizacaoTrackingTest extends TestCase
{
    use ComSetupDiario;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
    }

    public function test_vendedor_registra_localizacao(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');

        $this->actingAs($vendedor)
            ->withSession($this->sessionSetup())
            ->postJson(route('app.localizacao.store'), [
                'lat' => -22.9711,
                'lng' => -43.1822,
                'precisao' => 12.5,
            ])
            ->assertCreated()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('localizacoes', [
            'user_id' => $vendedor->id,
        ]);
    }

    public function test_admin_ve_ao_vivo(): void
    {
        $adm = User::factory()->create();
        $adm->assignRole('adm');

        $unidade = \App\Models\Unidade::factory()->create(['nome' => 'Filial Copa']);
        $vendedor = User::factory()->create([
            'name' => 'Campo Live',
            'unidade_id' => $unidade->id,
        ]);
        $vendedor->assignRole('vendedor');

        $this->actingAs($vendedor)
            ->withSession($this->sessionSetup())
            ->postJson(route('app.localizacao.store'), [
                'lat' => -22.9,
                'lng' => -43.2,
            ])
            ->assertCreated();

        $this->actingAs($adm)
            ->getJson(route('admin.localizacoes.ao-vivo'))
            ->assertOk()
            ->assertJsonPath('vendedores.0.nome', 'Campo Live')
            ->assertJsonPath('vendedores.0.unidade_nome', 'Filial Copa')
            ->assertJsonStructure(['vendedores' => [['foto_url', 'unidade_tipo', 'idade_segundos', 'alertas', 'status']]]);
    }

    public function test_admin_ve_trajeto_do_vendedor(): void
    {
        $adm = User::factory()->create();
        $adm->assignRole('adm');

        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');

        $this->actingAs($vendedor)
            ->withSession($this->sessionSetup())
            ->postJson(route('app.localizacao.store'), ['lat' => -22.97, 'lng' => -43.18])
            ->assertCreated();

        $this->actingAs($vendedor)
            ->withSession($this->sessionSetup())
            ->postJson(route('app.localizacao.store'), ['lat' => -22.971, 'lng' => -43.181])
            ->assertCreated();

        $this->actingAs($adm)
            ->getJson(route('admin.localizacoes.trajeto', $vendedor))
            ->assertOk()
            ->assertJsonPath('user_id', $vendedor->id)
            ->assertJsonStructure(['pontos' => [['lat', 'lng']]]);
    }
}
