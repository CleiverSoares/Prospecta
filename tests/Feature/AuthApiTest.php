<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_retorna_token_e_usuario(): void
    {
        Role::findOrCreate('vendedor');

        $user = User::factory()->create([
            'email' => 'vendedor@prospecta.test',
            'password' => 'senha-secreta',
            'ativo' => true,
        ]);
        $user->assignRole('vendedor');

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
        Role::findOrCreate('vendedor');
        $user = User::factory()->create(['ativo' => true]);
        $user->assignRole('vendedor');

        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('usuario.id', $user->id);
    }

    public function test_resumo_vendedor(): void
    {
        Role::findOrCreate('vendedor');
        $user = User::factory()->create(['ativo' => true]);
        $user->assignRole('vendedor');

        Sanctum::actingAs($user);

        $this->getJson('/api/vendedor/resumo')
            ->assertOk()
            ->assertJsonStructure(['visitas_hoje', 'visitas_semana', 'proxima_rota']);
    }
}
