<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\PapeisEPermissoesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PapeisEPermissoesSeeder::class);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertOk()->assertSee('Prospecta');
    }

    public function test_adm_autentica_e_vai_para_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adm');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.painel', absolute: false));
    }

    public function test_vendedor_autentica_e_vai_para_app(): void
    {
        $user = User::factory()->create();
        $user->assignRole('vendedor');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('app.inicio', absolute: false));
    }

    public function test_senha_invalida_nao_autentica(): void
    {
        $user = User::factory()->create();
        $user->assignRole('vendedor');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_logout_volta_ao_login(): void
    {
        $user = User::factory()->create();
        $user->assignRole('adm');

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
    }
}
