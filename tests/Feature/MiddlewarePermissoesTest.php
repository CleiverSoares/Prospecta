<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiddlewarePermissoesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
    }

    public function test_vendedor_nao_acessa_admin(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');

        $this->actingAs($vendedor)
            ->get(route('admin.painel'))
            ->assertForbidden();
    }

    public function test_vendedor_acessa_app(): void
    {
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');

        $this->actingAs($vendedor)
            ->get(route('app.inicio'))
            ->assertOk();
    }

    public function test_adm_acessa_admin_e_app(): void
    {
        $adm = User::factory()->create();
        $adm->assignRole('adm');

        $this->actingAs($adm)
            ->get(route('admin.painel'))
            ->assertOk();

        $this->actingAs($adm)
            ->get(route('app.inicio'))
            ->assertOk();
    }
}
