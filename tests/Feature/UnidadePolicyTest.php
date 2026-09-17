<?php

namespace Tests\Feature;

use App\Models\Unidade;
use App\Models\User;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnidadePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
    }

    public function test_policy_respeita_permissions_spatie(): void
    {
        $adm = User::factory()->create();
        $adm->assignRole('adm');
        $vendedor = User::factory()->create();
        $vendedor->assignRole('vendedor');
        $unidade = Unidade::factory()->create();

        $this->assertTrue($adm->can('viewAny', Unidade::class));
        $this->assertTrue($adm->can('create', Unidade::class));
        $this->assertTrue($adm->can('update', $unidade));
        $this->assertFalse($vendedor->can('viewAny', Unidade::class));
        $this->assertFalse($vendedor->can('update', $unidade));
    }
}
