<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DemoMassaService;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoMassaRotaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
    }

    public function test_rota_demo_retorna_404_quando_desabilitada(): void
    {
        config(['prospecta.demo_seed_enabled' => false]);

        $adm = User::factory()->create();
        $adm->assignRole('adm');

        $this->actingAs($adm)
            ->get(route('admin.demo.index'))
            ->assertNotFound();

        $this->actingAs($adm)
            ->post(route('admin.demo.regenerar'))
            ->assertNotFound();
    }

    public function test_adm_ve_pagina_e_regenera_via_servico(): void
    {
        config(['prospecta.demo_seed_enabled' => true]);

        $adm = User::factory()->create();
        $adm->assignRole('adm');

        $this->actingAs($adm)
            ->get(route('admin.demo.index'))
            ->assertOk()
            ->assertSee('Regenerar mapa ao vivo');

        $this->mock(DemoMassaService::class, function ($mock) {
            $mock->shouldReceive('habilitado')->andReturn(true);
            $mock->shouldReceive('regenerarCampo')->once()->andReturn([
                'usuarios_demo' => 4,
                'localizacoes_recentes' => 20,
                'vendedores_ao_vivo' => 2,
            ]);
        });

        $this->actingAs($adm)
            ->post(route('admin.demo.regenerar'))
            ->assertRedirect(route('admin.demo.index'))
            ->assertSessionHas('sucesso');
    }

    public function test_convidado_nao_acessa_demo(): void
    {
        config(['prospecta.demo_seed_enabled' => true]);

        $this->get(route('admin.demo.index'))->assertRedirect();
        $this->post(route('admin.demo.regenerar'))->assertRedirect();
        $this->post(route('admin.demo.clientes-mock'))->assertRedirect();
    }

    public function test_adm_gera_clientes_mock(): void
    {
        config(['prospecta.demo_seed_enabled' => true]);

        $adm = User::factory()->create();
        $adm->assignRole('adm');

        $this->mock(DemoMassaService::class, function ($mock) {
            $mock->shouldReceive('habilitado')->andReturn(true);
            $mock->shouldReceive('gerarClientesMock')->once()->andReturn([
                'criados' => 16,
                'clientes_no_mapa' => 16,
            ]);
        });

        $this->actingAs($adm)
            ->post(route('admin.demo.clientes-mock'))
            ->assertRedirect(route('admin.demo.index'))
            ->assertSessionHas('sucesso');
    }
}
