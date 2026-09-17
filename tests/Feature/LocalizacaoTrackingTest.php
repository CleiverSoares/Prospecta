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

        $vendedor = User::factory()->create(['name' => 'Campo Live']);
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
            ->assertJsonPath('vendedores.0.nome', 'Campo Live');
    }
}
