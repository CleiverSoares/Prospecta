<?php

namespace App\Policies;

use App\Models\Unidade;
use App\Models\User;

class UnidadePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('unidades.ver');
    }

    public function view(User $user, Unidade $unidade): bool
    {
        return $user->can('unidades.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('unidades.criar');
    }

    public function update(User $user, Unidade $unidade): bool
    {
        return $user->can('unidades.editar');
    }

    public function delete(User $user, Unidade $unidade): bool
    {
        return $user->can('unidades.excluir');
    }
}
