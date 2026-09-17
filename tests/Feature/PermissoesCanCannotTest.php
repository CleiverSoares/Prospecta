<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissoesCanCannotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
    }

    public function test_adm_pode_gerenciar_papeis_e_acessar_admin(): void
    {
        $adm = User::factory()->create();
        $adm->assignRole('adm');

        $this->assertTrue($adm->can('papeis.gerenciar'));
        $this->assertTrue($adm->can('admin.acessar'));
        $this->assertTrue($adm->can('app.acessar'));
    }

    public function test_vendedor_nao_pode_admin_nem_papeis(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');

        $this->assertFalse($vendedor->can('admin.acessar'));
        $this->assertFalse($vendedor->can('papeis.gerenciar'));
        $this->assertTrue($vendedor->can('app.acessar'));
        $this->assertTrue($vendedor->can('visitas.criar'));
    }

    public function test_gestor_acessa_admin_mas_nao_gerencia_papeis(): void
    {
        $gestor = User::factory()->create();
        $gestor->assignRole('gestor');

        $this->assertTrue($gestor->can('admin.acessar'));
        $this->assertFalse($gestor->can('papeis.gerenciar'));
        $this->assertTrue($gestor->can('usuarios.editar'));
    }
}
