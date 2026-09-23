<?php

namespace App\Repositories;

use App\Models\User;

class UsuarioRepository
{
    public function existePorEmail(string $email): bool
    {
        return User::query()->where('email', $email)->exists();
    }

    /**
     * @param  list<string>  $emails
     */
    public function contarPorEmails(array $emails): int
    {
        if ($emails === []) {
            return 0;
        }

        return User::query()->whereIn('email', $emails)->count();
    }
}
