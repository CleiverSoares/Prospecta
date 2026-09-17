<?php

namespace Tests\Feature;

use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PapeisEPermissoesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_cria_roles_e_permissions_iniciais(): void
    {
        $this->seed(PapeisEPermissoesSeeder::class);

        $this->assertTrue(Role::where('name', 'adm')->exists());
        $this->assertTrue(Role::where('name', 'gestor')->exists());
        $this->assertTrue(Role::where('name', 'vendedor')->exists());
        $this->assertTrue(Permission::where('name', 'admin.acessar')->exists());
        $this->assertTrue(Permission::where('name', 'app.acessar')->exists());

        $adm = Role::findByName('adm', 'web');
        $vendedor = Role::findByName('vendedor', 'web');

        $this->assertTrue($adm->hasPermissionTo('papeis.gerenciar'));
        $this->assertTrue($adm->hasPermissionTo('admin.acessar'));
        $this->assertFalse($vendedor->hasPermissionTo('admin.acessar'));
        $this->assertTrue($vendedor->hasPermissionTo('app.acessar'));
        $this->assertTrue($vendedor->hasPermissionTo('visitas.criar'));
    }
}
