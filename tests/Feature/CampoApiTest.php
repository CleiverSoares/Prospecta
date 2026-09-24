<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CampoApiTest extends TestCase
{
    use RefreshDatabase;

    private function vendedorComPermissao(): User
    {
        Permission::findOrCreate('app.acessar');
        Permission::findOrCreate('prospectos.ver');
        Permission::findOrCreate('prospectos.buscar');
        Permission::findOrCreate('territorio.verificar');
        Permission::findOrCreate('visitas.criar');

        $role = Role::findOrCreate('vendedor');
        $role->syncPermissions([
            'app.acessar',
            'prospectos.ver',
            'prospectos.buscar',
            'territorio.verificar',
            'visitas.criar',
        ]);

        $user = User::factory()->create(['ativo' => true]);
        $user->assignRole('vendedor');

        return $user;
    }

    public function test_setup_obrigatorio_antes_da_rota(): void
    {
        Sanctum::actingAs($this->vendedorComPermissao());

        $this->getJson('/api/vendedor/rota/hoje')
            ->assertStatus(422)
            ->assertJsonPath('codigo', 'setup_obrigatorio');
    }

    public function test_salvar_e_ler_setup(): void
    {
        Sanctum::actingAs($this->vendedorComPermissao());

        $this->postJson('/api/vendedor/setup', [
            'local' => 'Centro SP',
            'segmento' => 'MISTO',
            'horas' => '08:00-17:00',
            'mix_prospeccao' => 70,
        ])->assertOk()->assertJsonPath('setup.local', 'Centro SP');

        $this->getJson('/api/vendedor/setup')
            ->assertOk()
            ->assertJsonPath('setup.segmento', 'MISTO');

        $this->getJson('/api/vendedor/rota/hoje')
            ->assertOk()
            ->assertJsonPath('itens', []);
    }
}
