<?php

namespace App\Services;

use App\Models\User;

class RedirecionamentoAuthService
{
    public function destinoAposLogin(User $usuario): ?string
    {
        if ($usuario->can('admin.acessar')) {
            return route('admin.painel', absolute: false);
        }

        if ($usuario->can('app.acessar')) {
            return route('app.inicio', absolute: false);
        }

        return null;
    }
}
