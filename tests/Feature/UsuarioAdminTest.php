<?php

namespace Tests\Feature;

use App\Models\Unidade;
use App\Models\User;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
    }

    public function test_adm_cria_usuario_com_papel_vendedor(): void
    {
        $adm = User::factory()->create();
        $adm->assignRole('adm');

        $unidade = Unidade::factory()->create(['nome' => 'Filial Teste']);
        $gestor = User::factory()->create(['unidade_id' => $unidade->id]);
        $gestor->assignRole('gestor');

        $this->actingAs($adm)
            ->post(route('admin.usuarios.store'), [
                'name' => 'Vendedor Novo',
                'email' => 'vendedor.novo@prospecta.test',
                'password' => 'password',
                'unidade_id' => $unidade->id,
                'gestor_id' => $gestor->id,
                'cep_base_inicio' => '22010-000',
                'cep_base_fim' => '22080-000',
                'role' => 'vendedor',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'vendedor.novo@prospecta.test',
            'name' => 'Vendedor Novo',
            'unidade_id' => $unidade->id,
            'gestor_id' => $gestor->id,
            'cep_base_inicio' => '22010000',
            'cep_base_fim' => '22080000',
        ]);

        $criado = User::query()->where('email', 'vendedor.novo@prospecta.test')->first();
        $this->assertNotNull($criado);
        $this->assertTrue($criado->hasRole('vendedor'));
    }

    public function test_adm_desativa_usuario(): void
    {
        $adm = User::factory()->create();
        $adm->assignRole('adm');
        $alvo = User::factory()->create(['ativo' => true]);
        $alvo->assignRole('vendedor');

        $this->actingAs($adm)
            ->post(route('admin.usuarios.desativar', $alvo))
            ->assertRedirect(route('admin.usuarios.index'));

        $this->assertFalse($alvo->fresh()->ativo);
    }

    public function test_adm_filtra_so_vendedores(): void
    {
        $adm = User::factory()->create();
        $adm->assignRole('adm');

        $vendedor = User::factory()->create(['name' => 'Só Campo']);
        $vendedor->assignRole('vendedor');
        $gestor = User::factory()->create(['name' => 'Só Gestor']);
        $gestor->assignRole('gestor');

        $this->actingAs($adm)
            ->get(route('admin.usuarios.index', ['papel' => 'vendedor']))
            ->assertOk()
            ->assertSee('Só Campo')
            ->assertDontSee('Só Gestor');
    }

    public function test_usuario_inativo_nao_loga(): void
    {
        $user = User::factory()->create([
            'email' => 'off@prospecta.test',
            'password' => 'password',
            'ativo' => false,
        ]);
        $user->assignRole('vendedor');

        $this->post(route('login'), [
            'email' => 'off@prospecta.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
