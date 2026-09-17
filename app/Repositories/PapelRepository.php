<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PapelRepository
{
    public function listarPapeis(): Collection
    {
        return Role::query()->with('permissions')->orderBy('name')->get();
    }

    public function listarPermissoes(): Collection
    {
        return Permission::query()->orderBy('name')->get();
    }

    public function buscarPapelPorId(int $id): ?Role
    {
        return Role::query()->with('permissions')->find($id);
    }

    public function criarPapel(string $nome): Role
    {
        return Role::create(['name' => $nome, 'guard_name' => 'web']);
    }

    public function sincronizarPermissoes(Role $papel, array $permissoes): Role
    {
        $papel->syncPermissions($permissoes);

        return $papel->fresh('permissions');
    }
}
