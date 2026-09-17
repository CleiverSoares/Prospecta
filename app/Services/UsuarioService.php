<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsuarioService
{
    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     password?: string|null,
     *     unidade_id?: int|null,
     *     gestor_id?: int|null,
     *     cep_base_inicio?: string|null,
     *     cep_base_fim?: string|null,
     *     role: string
     * }  $dados
     */
    public function criar(array $dados): User
    {
        return DB::transaction(function () use ($dados) {
            $usuario = User::query()->create([
                'name' => $dados['name'],
                'email' => $dados['email'],
                'password' => Hash::make($dados['password'] ?? 'password'),
                'unidade_id' => $dados['unidade_id'] ?? null,
                'gestor_id' => $dados['gestor_id'] ?? null,
                'cep_base_inicio' => $dados['cep_base_inicio'] ?? null,
                'cep_base_fim' => $dados['cep_base_fim'] ?? null,
                'email_verified_at' => now(),
            ]);

            $this->sincronizarPapel($usuario, $dados['role']);

            return $usuario->fresh(['unidade', 'gestor', 'roles']);
        });
    }

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     password?: string|null,
     *     unidade_id?: int|null,
     *     gestor_id?: int|null,
     *     cep_base_inicio?: string|null,
     *     cep_base_fim?: string|null,
     *     role: string
     * }  $dados
     */
    public function atualizar(User $usuario, array $dados): User
    {
        return DB::transaction(function () use ($usuario, $dados) {
            $payload = [
                'name' => $dados['name'],
                'email' => $dados['email'],
                'unidade_id' => $dados['unidade_id'] ?? null,
                'gestor_id' => $dados['gestor_id'] ?? null,
                'cep_base_inicio' => $dados['cep_base_inicio'] ?? null,
                'cep_base_fim' => $dados['cep_base_fim'] ?? null,
            ];

            if (! empty($dados['password'])) {
                $payload['password'] = Hash::make($dados['password']);
            }

            $usuario->update($payload);
            $this->sincronizarPapel($usuario, $dados['role']);

            return $usuario->fresh(['unidade', 'gestor', 'roles']);
        });
    }

    public function sincronizarPapel(User $usuario, string $role): void
    {
        Role::findOrCreate($role, 'web');
        $usuario->syncRoles([$role]);
    }
}
