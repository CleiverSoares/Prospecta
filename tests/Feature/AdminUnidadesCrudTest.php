<?php

namespace Tests\Feature;

use App\Enums\TipoUnidade;
use App\Models\User;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUnidadesCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
    }

    public function test_adm_cria_e_lista_unidade(): void
    {
        $adm = User::factory()->create();
        $adm->assignRole('adm');

        $this->actingAs($adm)
            ->post(route('admin.unidades.store'), [
                'nome' => 'Filial BH',
                'tipo' => TipoUnidade::Filial->value,
                'cep_inicio' => '30000-000',
                'cep_fim' => '30999-999',
            ])
            ->assertRedirect();

        $this->actingAs($adm)
            ->get(route('admin.unidades.index'))
            ->assertOk()
            ->assertSee('Filial BH');

        $this->assertDatabaseHas('unidades', [
            'nome' => 'Filial BH',
            'cep_inicio' => '30000000',
            'cep_fim' => '30999999',
        ]);
    }

    public function test_vendedor_nao_acessa_unidades_admin(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');

        $this->actingAs($vendedor)
            ->get(route('admin.unidades.index'))
            ->assertForbidden();
    }

    public function test_validacao_rejeita_unidade_sem_nome(): void
    {
        $adm = User::factory()->create();
        $adm->assignRole('adm');

        $this->actingAs($adm)
            ->from(route('admin.unidades.create'))
            ->post(route('admin.unidades.store'), [
                'nome' => '',
                'tipo' => TipoUnidade::Filial->value,
            ])
            ->assertRedirect(route('admin.unidades.create'))
            ->assertSessionHasErrors(['nome']);
    }

    public function test_layout_admin_aparece_na_lista(): void
    {
        $adm = User::factory()->create();
        $adm->assignRole('adm');

        $this->actingAs($adm)
            ->get(route('admin.unidades.index'))
            ->assertOk()
            ->assertSee('Prospecta', false)
            ->assertSee('Nova unidade', false);
    }
}
