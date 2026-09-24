<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthApiService
{
    /**
     * @return array{token: string, usuario: array{id: int, name: string, email: string, roles: list<string>}}
     */
    public function login(string $email, string $password, string $deviceName): array
    {
        /** @var User|null $usuario */
        $usuario = User::query()->where('email', $email)->first();

        if (! $usuario || ! Hash::check($password, $usuario->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        if ($usuario->ativo === false) {
            throw ValidationException::withMessages([
                'email' => ['Usuário inativo.'],
            ]);
        }

        $token = $usuario->createToken($deviceName)->plainTextToken;

        return [
            'token' => $token,
            'usuario' => $this->serializarUsuario($usuario),
        ];
    }

    public function logout(User $usuario): void
    {
        $token = $usuario->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    /**
     * @return array{id: int, name: string, email: string, roles: list<string>}
     */
    public function serializarUsuario(User $usuario): array
    {
        return [
            'id' => $usuario->id,
            'name' => $usuario->name,
            'email' => $usuario->email,
            'roles' => $usuario->getRoleNames()->values()->all(),
        ];
    }
}
