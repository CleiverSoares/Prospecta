<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private function prepararVendedor(array $attrs = []): User
    {
        Permission::findOrCreate('app.acessar');
        $role = Role::findOrCreate('vendedor');
        $role->givePermissionTo('app.acessar');

        $user = User::factory()->create(array_merge(['ativo' => true], $attrs));
        $user->assignRole('vendedor');

        return $user;
    }

    public function test_login_retorna_token_e_usuario(): void
    {
        $this->prepararVendedor([
            'email' => 'vendedor@prospecta.test',
            'password' => 'senha-secreta',
        ]);

        $resposta = $this->postJson('/api/auth/login', [
            'email' => 'vendedor@prospecta.test',
            'password' => 'senha-secreta',
            'device_name' => 'prospecta-vendedor',
        ]);

        $resposta->assertOk()
            ->assertJsonPath('usuario.email', 'vendedor@prospecta.test')
            ->assertJsonStructure(['token', 'usuario' => ['id', 'name', 'email', 'roles']]);

        $this->assertNotEmpty($resposta->json('token'));
    }

    public function test_me_exige_token(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_me_com_token(): void
    {
        $user = $this->prepararVendedor();
        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('usuario.id', $user->id);
    }

    public function test_resumo_vendedor(): void
    {
        $user = $this->prepararVendedor();
        Sanctum::actingAs($user);

        $this->getJson('/api/vendedor/resumo')
            ->assertOk()
            ->assertJsonStructure(['visitas_hoje', 'visitas_semana', 'proxima_rota']);
    }
}
