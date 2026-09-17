<?php

namespace App\Repositories;

use App\Models\Visita;
use Illuminate\Database\Eloquent\Collection;

class VisitaRepository
{
    public function criar(array $dados): Visita
    {
        return Visita::query()->create($dados);
    }

    public function listarPorUsuario(int $userId): Collection
    {
        return Visita::query()
            ->where('user_id', $userId)
            ->with(['prospecto'])
            ->latest()
            ->get();
    }

    public function buscarPorId(int $id): ?Visita
    {
        return Visita::query()->with(['prospecto', 'usuario'])->find($id);
    }
}
