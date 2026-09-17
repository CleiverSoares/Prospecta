<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SpatiePermissionInstallTest extends TestCase
{
    use RefreshDatabase;

    public function test_pode_atribuir_role_ao_usuario(): void
    {
        $role = Role::create(['name' => 'vendedor', 'guard_name' => 'web']);
        $usuario = User::factory()->create();

        $usuario->assignRole($role);

        $this->assertTrue($usuario->hasRole('vendedor'));
        $this->assertDatabaseHas('roles', ['name' => 'vendedor']);
        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $role->id,
            'model_id' => $usuario->id,
        ]);
    }
}
