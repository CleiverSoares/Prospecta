<?php

namespace App\Services;

use App\Repositories\PapelRepository;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PapelService
{
    public function __construct(
        private readonly PapelRepository $papelRepository,
    ) {}

    public function listarPapeis(): Collection
    {
        return $this->papelRepository->listarPapeis();
    }

    public function listarPermissoes(): Collection
    {
        return $this->papelRepository->listarPermissoes();
    }

    public function buscar(int $id): ?Role
    {
        return $this->papelRepository->buscarPapelPorId($id);
    }

    public function criar(string $nome): Role
    {
        return $this->papelRepository->criarPapel($nome);
    }

    public function sincronizarPermissoes(Role $papel, array $permissoes): Role
    {
        $papel = $this->papelRepository->sincronizarPermissoes($papel, $permissoes);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return $papel;
    }
}
