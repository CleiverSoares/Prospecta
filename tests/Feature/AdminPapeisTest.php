<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPapeisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
    }

    public function test_adm_lista_e_cria_papel(): void
    {
        $adm = User::factory()->create();
        $adm->assignRole('adm');

        $this->actingAs($adm)
            ->get(route('admin.papeis.index'))
            ->assertOk()
            ->assertSee('vendedor');

        $this->actingAs($adm)
            ->post(route('admin.papeis.store'), ['nome' => 'coordenador'])
            ->assertRedirect();

        $this->assertDatabaseHas('roles', ['name' => 'coordenador']);
    }

    public function test_adm_sincroniza_permissoes_do_papel(): void
    {
        $adm = User::factory()->create();
        $adm->assignRole('adm');
        $papel = Role::findByName('vendedor', 'web');

        $this->actingAs($adm)
            ->put(route('admin.papeis.update', $papel), [
                'permissoes' => ['app.acessar', 'visitas.ver'],
            ])
            ->assertRedirect(route('admin.papeis.edit', $papel));

        $papel->refresh();
        $this->assertTrue($papel->hasPermissionTo('app.acessar'));
        $this->assertTrue($papel->hasPermissionTo('visitas.ver'));
        $this->assertFalse($papel->hasPermissionTo('visitas.criar'));
    }

    public function test_vendedor_nao_gerencia_papeis(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');

        $this->actingAs($vendedor)
            ->get(route('admin.papeis.index'))
            ->assertForbidden();
    }
}
